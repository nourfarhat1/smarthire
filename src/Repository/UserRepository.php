<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function authenticate(string $email, string $password): ?User
    {
        $user = $this->findOneBy(['email' => $email]);
        
        if ($user && password_verify($password, $user->getPassword())) {
            return $user;
        }
        
        return null;
    }

    public function save(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function searchUsers(string $search = '', string $role = '', string $status = ''): array
    {
        $qb = $this->createQueryBuilder('u');
        
        // Apply search filter
        if (!empty($search)) {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like('u.firstName', ':search'),
                $qb->expr()->like('u.lastName', ':search'),
                $qb->expr()->like('u.email', ':search')
            ))
            ->setParameter('search', '%' . $search . '%');
        }
        
        // Apply role filter
        if (!empty($role)) {
            $qb->andWhere('u.roleId = :role')
               ->setParameter('role', $role);
        }
        
        // Apply status filter
        if ($status === 'banned') {
            $qb->andWhere('u.isBanned = :banned')
               ->setParameter('banned', true);
        } elseif ($status === 'unbanned') {
            $qb->andWhere('u.isBanned = :banned')
               ->setParameter('banned', false);
        } elseif ($status === 'verified') {
            $qb->andWhere('u.isVerified = :verified')
               ->setParameter('verified', true);
        } elseif ($status === 'unverified') {
            $qb->andWhere('u.isVerified = :verified')
               ->setParameter('verified', false);
        }
        
        return $qb->getQuery()->getResult();
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
