<?php

namespace App\Repository;

use App\Entity\Training;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Training>
 */
class TrainingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Training::class);
    }

    public function findByAdmin(int $adminId): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.admin = :adminId')
            ->setParameter('adminId', $adminId)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.category = :category')
            ->setParameter('category', $category)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPopularTrainings(): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.likes', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(Training $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Training $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function searchTrainings(string $search = '', string $category = ''): array
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.admin', 'a')
            ->addSelect('a');

        if (!empty($search)) {
            $qb->andWhere('t.title LIKE :search OR t.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if (!empty($category)) {
            $qb->andWhere('t.category = :category')
               ->setParameter('category', $category);
        }

        return $qb->orderBy('t.createdAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    public function hasUserLiked(int $trainingId, int $userId): bool
    {
        // This would need a UserTrainingLike entity or similar relationship
        // For now, return false as placeholder
        return false;
    }

    public function likeTraining(int $trainingId, int $userId): void
    {
        // This would need a UserTrainingLike entity or similar relationship
        // For now, just increment the likes count
        $training = $this->find($trainingId);
        if ($training) {
            $training->setLikes($training->getLikes() + 1);
            $this->getEntityManager()->flush();
        }
    }

    public function unlikeTraining(int $trainingId, int $userId): void
    {
        // This would need a UserTrainingLike entity or similar relationship
        // For now, just decrement the likes count
        $training = $this->find($trainingId);
        if ($training && $training->getLikes() > 0) {
            $training->setLikes($training->getLikes() - 1);
            $this->getEntityManager()->flush();
        }
    }

    public function findUserLikedTrainings(int $userId): array
    {
        // This would need a UserTrainingLike entity or similar relationship
        // For now, return empty array as placeholder
        return [];
    }

    /**
     * Find available trainings for candidate.
     */
    public function findAvailableTrainingsForCandidate($candidate, int $limit = 3): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.admin', 'a')
            ->addSelect('a')
            ->orderBy('t.likes', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
