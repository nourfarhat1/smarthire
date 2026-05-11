<?php

namespace App\Repository;

use App\Entity\UserTrainingVote;
use App\Entity\Training;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserTrainingVote>
 */
class UserTrainingVoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserTrainingVote::class);
    }

    public function findByUserAndTraining(User $user, Training $training): ?UserTrainingVote
    {
        return $this->createQueryBuilder('v')
            ->where('v.user = :user')
            ->andWhere('v.training = :training')
            ->setParameter('user', $user)
            ->setParameter('training', $training)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('v')
            ->where('v.user = :user')
            ->setParameter('user', $user)
            ->orderBy('v.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByTraining(Training $training): array
    {
        return $this->createQueryBuilder('v')
            ->where('v.training = :training')
            ->setParameter('training', $training)
            ->orderBy('v.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countLikes(Training $training): int
    {
        return $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->where('v.training = :training')
            ->andWhere('v.voteType = :voteType')
            ->setParameter('training', $training)
            ->setParameter('voteType', UserTrainingVote::VOTE_LIKE)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countDislikes(Training $training): int
    {
        return $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->where('v.training = :training')
            ->andWhere('v.voteType = :voteType')
            ->setParameter('training', $training)
            ->setParameter('voteType', UserTrainingVote::VOTE_DISLIKE)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function save(UserTrainingVote $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(UserTrainingVote $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
