<?php

namespace App\Repository;

use App\Entity\QuizResult;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<QuizResult>
 */
class QuizResultRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuizResult::class);
    }

    public function findByCandidate(int $candidateId): array
    {
        return $this->createQueryBuilder('qr')
            ->where('qr.candidate = :candidateId')
            ->setParameter('candidateId', $candidateId)
            ->orderBy('qr.attemptDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByQuiz(int $quizId): array
    {
        return $this->createQueryBuilder('qr')
            ->where('qr.quiz = :quizId')
            ->setParameter('quizId', $quizId)
            ->orderBy('qr.attemptDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPassedResults(): array
    {
        return $this->findBy(['isPassed' => true], ['attemptDate' => 'DESC']);
    }

    public function findFailedResults(): array
    {
        return $this->findBy(['isPassed' => false], ['attemptDate' => 'DESC']);
    }

    public function countPassed(): int
    {
        return $this->createQueryBuilder('qr')
            ->select('COUNT(qr.id)')
            ->where('qr.isPassed = :passed')
            ->setParameter('passed', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countFailed(): int
    {
        return $this->createQueryBuilder('qr')
            ->select('COUNT(qr.id)')
            ->where('qr.isPassed = :passed')
            ->setParameter('passed', false)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
