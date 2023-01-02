<?php

namespace App\Repository;

use App\Entity\LotIsolat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LotIsolat|null find($id, $lockMode = null, $lockVersion = null)
 * @method LotIsolat|null findOneBy(array $criteria, array $orderBy = null)
 * @method LotIsolat[]    findAll()
 * @method LotIsolat[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LotIsolatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LotIsolat::class);
    }

    // /**
    //  * @return LotIsolat[] Returns an array of LotIsolat objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?LotIsolat
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
