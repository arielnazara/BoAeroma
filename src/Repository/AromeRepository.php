<?php

namespace App\Repository;

use App\Entity\Arome;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Arome|null find($id, $lockMode = null, $lockVersion = null)
 * @method Arome|null findOneBy(array $criteria, array $orderBy = null)
 * @method Arome[]    findAll()
 * @method Arome[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AromeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Arome::class);
    }

    // /**
    //  * @return Arome[] Returns an array of Arome objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Arome
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function findLike($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.nom LIKE :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
