<?php

namespace App\Repository;

use App\Entity\AppEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AppEvent>
 */
class AppEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AppEvent::class);
    }

    public function findByOrganizer(int $organizerId): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.organizer = :organizerId')
            ->setParameter('organizerId', $organizerId)
            ->orderBy('e.eventDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findUpcomingEvents(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.eventDate >= :today')
            ->setParameter('today', new \DateTime())
            ->orderBy('e.eventDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findPastEvents(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.eventDate < :today')
            ->setParameter('today', new \DateTime())
            ->orderBy('e.eventDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(AppEvent $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AppEvent $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function searchEvents(string $search = '', string $month = '', string $status = ''): array
    {
        $qb = $this->createQueryBuilder('e');

        if (!empty($search)) {
            $qb->andWhere('e.name LIKE :search OR e.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if (!empty($month)) {
            // Extract month and year from the month parameter (format: '2024-01' or '01')
            if (strlen($month) === 7) {
                // Format: '2024-01'
                $startDate = new \DateTime($month . '-01');
                $endDate = new \DateTime($month . '-31');
            } else {
                // Format: '01' (month number)
                $year = date('Y');
                $startDate = new \DateTime($year . '-' . $month . '-01');
                $endDate = new \DateTime($year . '-' . $month . '-31');
            }
            
            $qb->andWhere('e.eventDate >= :monthStart')
               ->andWhere('e.eventDate <= :monthEnd')
               ->setParameter('monthStart', $startDate)
               ->setParameter('monthEnd', $endDate);
        }

        if ($status === 'upcoming') {
            $qb->andWhere('e.eventDate >= :today')
               ->setParameter('today', new \DateTime('today'));
        } elseif ($status === 'past') {
            $qb->andWhere('e.eventDate < :today')
               ->setParameter('today', new \DateTime('today'));
        } elseif ($status === 'today') {
            $qb->andWhere('DATE(e.eventDate) = :today')
               ->setParameter('today', new \DateTime('today'));
        }

        return $qb->orderBy('e.eventDate', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    public function isUserRegistered(int $eventId, int $userId): bool
    {
        return $this->getEntityManager()->createQuery(
            'SELECT COUNT(ep.id) FROM App\Entity\EventParticipant ep 
             WHERE ep.event = :eventId AND ep.user = :userId'
        )
        ->setParameter('eventId', $eventId)
        ->setParameter('userId', $userId)
        ->getSingleScalarResult() > 0;
    }

    public function registerUser(int $eventId, int $userId): void
    {
        $event = $this->find($eventId);
        $user = $this->getEntityManager()->find('App\Entity\User', $userId);
        
        if ($event && $user) {
            $participant = new \App\Entity\EventParticipant();
            $participant->setEvent($event);
            $participant->setUser($user);
            $participant->setJoinedAt(new \DateTime());
            $participant->setStatus('CONFIRMED');
            
            $this->getEntityManager()->persist($participant);
            $this->getEntityManager()->flush();
        }
    }

    public function unregisterUser(int $eventId, int $userId): void
    {
        $participant = $this->getEntityManager()->getRepository('App\Entity\EventParticipant')
            ->findOneBy(['event' => $eventId, 'user' => $userId]);
            
        if ($participant) {
            $this->getEntityManager()->remove($participant);
            $this->getEntityManager()->flush();
        }
    }

    public function findUserEvents(int $userId): array
    {
        return $this->createQueryBuilder('e')
            ->innerJoin('e.participants', 'p')
            ->where('p.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('e.eventDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getEventParticipants(int $eventId): array
    {
        return $this->getEntityManager()->createQuery(
            'SELECT ep, u FROM App\Entity\EventParticipant ep
             JOIN ep.user u
             WHERE ep.event = :eventId
             ORDER BY ep.joinedAt ASC'
        )
        ->setParameter('eventId', $eventId)
        ->getResult();
    }
}
