<?php

namespace App\Repository;

use App\Entity\Interview;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Interview>
 *
 * @method Interview|null find($id, $lockMode = null, $lockVersion = null)
 * @method Interview|null findOneBy(array $criteria, array $orderBy = null)
 * @method Interview[]    findAll()
 * @method Interview[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InterviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Interview::class);
    }

    public function save(Interview $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Interview $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByCandidate(int $candidateId): array
    {
        return $this->createQueryBuilder('i')
            ->innerJoin('i.jobRequest', 'jr')
            ->innerJoin('jr.candidate', 'c')
            ->where('c.id = :candidateId')
            ->setParameter('candidateId', $candidateId)
            ->orderBy('i.interviewDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByRecruiter(int $recruiterId): array
    {
        return $this->createQueryBuilder('i')
            ->innerJoin('i.jobRequest', 'jr')
            ->innerJoin('jr.jobOffer', 'jo')
            ->innerJoin('jo.postedBy', 'r')
            ->where('r.id = :recruiterId')
            ->setParameter('recruiterId', $recruiterId)
            ->orderBy('i.interviewDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findUpcomingInterviews(int $candidateId): array
    {
        return $this->createQueryBuilder('i')
            ->innerJoin('i.jobRequest', 'jr')
            ->innerJoin('jr.candidate', 'c')
            ->where('c.id = :candidateId')
            ->andWhere('i.interviewDate >= :today')
            ->setParameter('candidateId', $candidateId)
            ->setParameter('today', new \DateTime('today'))
            ->orderBy('i.interviewDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByDateRange(\DateTime $startDate, \DateTime $endDate): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.interviewDate BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('i.interviewDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findInterviewsForDate(\DateTimeInterface $date): array
    {
        $startOfDay = clone $date;
        $startOfDay->setTime(0, 0, 0);
        $endOfDay = clone $date;
        $endOfDay->setTime(23, 59, 59);

        return $this->createQueryBuilder('i')
            ->where('i.dateTime BETWEEN :startOfDay AND :endOfDay')
            ->setParameter('startOfDay', $startOfDay)
            ->setParameter('endOfDay', $endOfDay)
            ->orderBy('i.dateTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findScheduledInterviews(): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.status = :status')
            ->setParameter('status', 'SCHEDULED')
            ->orderBy('i.dateTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findInterviewsNeedingReminders(): array
    {
        $now = new \DateTime();
        $tomorrow = (clone $now)->add(new \DateInterval('P1D'));
        
        return $this->createQueryBuilder('i')
            ->where('i.dateTime BETWEEN :now AND :tomorrow')
            ->andWhere('i.status = :status')
            ->andWhere('i.reminderSent = :reminderSent')
            ->setParameter('now', $now)
            ->setParameter('tomorrow', $tomorrow)
            ->setParameter('status', 'SCHEDULED')
            ->setParameter('reminderSent', false)
            ->getQuery()
            ->getResult();
    }
}
