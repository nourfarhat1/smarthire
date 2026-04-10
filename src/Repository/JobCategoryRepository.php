<?php

namespace App\Repository;

use App\Entity\JobCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JobCategory>
 *
 * @method JobCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method JobCategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method JobCategory[]    findAll()
 * @method JobCategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class JobCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobCategory::class);
    }

    public function save(JobCategory $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(JobCategory $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByActiveJobs(): array
    {
        return $this->createQueryBuilder('jc')
            ->innerJoin('jc.jobOffers', 'jo')
            ->where('jo.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('jc.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findWithJobCount(): array
    {
        return $this->createQueryBuilder('jc')
            ->leftJoin('jc.jobOffers', 'jo')
            ->select('jc', 'COUNT(jo.id) as jobCount')
            ->groupBy('jc.id')
            ->orderBy('jc.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
