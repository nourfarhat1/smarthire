<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\AppEvent;
use App\Entity\User;

class EventService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function add(AppEvent $event): void
    {
        $this->entityManager->persist($event);
        $this->entityManager->flush();
    }

    public function update(AppEvent $event): void
    {
        $this->entityManager->flush();
    }

    public function delete(int $id): void
    {
        $event = $this->entityManager->find(AppEvent::class, $id);
        if ($event) {
            $this->entityManager->remove($event);
            $this->entityManager->flush();
        }
    }

    /**
     * @return AppEvent[]
     */
    public function getAll(): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('e', 'u')
            ->from(AppEvent::class, 'e')
            ->leftJoin('e.organizer', 'u')
            ->getQuery()
            ->getResult();
    }

    public function getOne(int $id): ?AppEvent
    {
        return $this->entityManager->createQueryBuilder()
            ->select('e', 'u')
            ->from(AppEvent::class, 'e')
            ->leftJoin('e.organizer', 'u')
            ->where('e.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function joinEvent(int $eventId, int $userId): void
    {
        $this->entityManager->getConnection()->beginTransaction();
        
        try {
            $event = $this->entityManager->find(AppEvent::class, $eventId);
            $user = $this->entityManager->find(User::class, $userId);
            
            if ($event && $user) {
                // Create ticket and attendance logic would go here
                // This depends on your entity structure for tickets and attendance
                $this->entityManager->flush();
            }
            
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            throw $e;
        }
    }

    public function leaveEvent(int $eventId, int $userId): void
    {
        // Delete ticket logic would go here
        // This depends on your entity structure for tickets
    }

    /**
     * @return AppEvent[]
     */
    public function getJoinedEvents(int $userId): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('e', 'u')
            ->from(AppEvent::class, 'e')
            ->innerJoin('e.participants', 'p')
            ->leftJoin('e.organizer', 'u')
            ->where('p.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();
    }
}
