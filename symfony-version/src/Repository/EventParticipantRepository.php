<?php

namespace App\Repository;

use App\Entity\EventParticipant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EventParticipant>
 */
class EventParticipantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventParticipant::class);
    }

    /**
     * Save an event participant entity.
     */
    public function save(EventParticipant $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove an event participant entity.
     */
    public function remove(EventParticipant $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find participants by event.
     *
     * @return EventParticipant[]
     */
    public function findByEvent($eventId): array
    {
        return $this->createQueryBuilder('ep')
            ->leftJoin('ep.user', 'u')
            ->addSelect('u')
            ->where('ep.event = :eventId')
            ->setParameter('eventId', $eventId)
            ->orderBy('ep.joinedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find participants by user.
     *
     * @return EventParticipant[]
     */
    public function findByUser($userId): array
    {
        return $this->createQueryBuilder('ep')
            ->leftJoin('ep.event', 'e')
            ->addSelect('e')
            ->where('ep.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('ep.joinedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count participants by event.
     */
    public function countByEvent($eventId): int
    {
        return (int) $this->createQueryBuilder('ep')
            ->select('COUNT(ep.id)')
            ->where('ep.event = :eventId')
            ->setParameter('eventId', $eventId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find participants by status.
     *
     * @return EventParticipant[]
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('ep')
            ->leftJoin('ep.event', 'e')
            ->leftJoin('ep.user', 'u')
            ->addSelect('e', 'u')
            ->where('ep.status = :status')
            ->setParameter('status', $status)
            ->orderBy('ep.joinedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if user is already participating in event.
     */
    public function isUserParticipating($eventId, $userId): bool
    {
        $count = $this->createQueryBuilder('ep')
            ->select('COUNT(ep.id)')
            ->where('ep.event = :eventId')
            ->andWhere('ep.user = :userId')
            ->setParameter('eventId', $eventId)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Find participant by event and user.
     */
    public function findByEventAndUser($eventId, $userId): ?EventParticipant
    {
        return $this->createQueryBuilder('ep')
            ->where('ep.event = :eventId')
            ->andWhere('ep.user = :userId')
            ->setParameter('eventId', $eventId)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find upcoming events for candidate.
     */
    public function findUpcomingEventsForCandidate($candidate): array
    {
        return $this->createQueryBuilder('ep')
            ->leftJoin('ep.event', 'e')
            ->addSelect('e')
            ->where('ep.user = :candidate')
            ->andWhere('e.eventDate >= :now')
            ->setParameter('candidate', $candidate)
            ->setParameter('now', new \DateTime())
            ->orderBy('e.eventDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
