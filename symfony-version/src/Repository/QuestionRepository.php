<?php

namespace App\Repository;

use App\Entity\Question;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Question>
 *
 * @method Question|null find($id, $lockMode = null, $lockVersion = null)
 * @method Question|null findOneBy(array $criteria, array $orderBy = null)
 * @method Question[]    findAll()
 * @method Question[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Question::class);
    }

    public function save(Question $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Question $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByQuiz(int $quizId): array
    {
        return $this->createQueryBuilder('q')
            ->innerJoin('q.quiz', 'quiz')
            ->where('quiz.id = :quizId')
            ->setParameter('quizId', $quizId)
            ->orderBy('q.questionNumber', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByDifficulty(string $difficulty): array
    {
        return $this->createQueryBuilder('q')
            ->where('q.difficulty = :difficulty')
            ->setParameter('difficulty', $difficulty)
            ->orderBy('q.questionText', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findRandomByCategory(string $category, int $limit = 10): array
    {
        return $this->createQueryBuilder('q')
            ->where('q.category = :category')
            ->setParameter('category', $category)
            ->orderBy('RANDOM()')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
