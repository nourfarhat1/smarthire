<?php

namespace App\Repository;

use App\Entity\Notification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.user = :user')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findUnreadByUser(User $user): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.user = :user AND n.isRead = :isRead')
            ->setParameter('user', $user)
            ->setParameter('isRead', false)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findRecentByUser(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.user = :user')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findActiveNotifications(): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.isRead = :isRead AND (n.expiresAt IS NULL OR n.expiresAt >= :now)')
            ->setParameter('isRead', false)
            ->setParameter('now', new \DateTime())
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function markAsRead(int $notificationId): void
    {
        $this->createQueryBuilder('n')
            ->update('App\Entity\Notification', 'n')
            ->set('n.isRead', true)
            ->set('n.readAt', ':readAt')
            ->where('n.id = :id')
            ->setParameter('id', $notificationId)
            ->setParameter('readAt', new \DateTime())
            ->getQuery()
            ->execute();
    }

    public function deleteExpiredNotifications(): int
    {
        return $this->createQueryBuilder('n')
            ->delete('App\Entity\Notification', 'n')
            ->where('n.expiresAt < :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->execute();
    }

    public function getUnreadCount(User $user): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.user = :user AND n.isRead = :isRead')
            ->setParameter('user', $user)
            ->setParameter('isRead', false)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByType(string $type, ?User $user = null): array
    {
        $qb = $this->createQueryBuilder('n')
            ->where('n.type = :type')
            ->setParameter('type', $type);

        if ($user) {
            $qb->andWhere('n.user = :user')
               ->setParameter('user', $user);
        }

        return $qb->orderBy('n.createdAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }
}
