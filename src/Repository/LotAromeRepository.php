<?php

namespace App\Repository;

use App\Entity\LotArome;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LotArome|null find($id, $lockMode = null, $lockVersion = null)
 * @method LotArome|null findOneBy(array $criteria, array $orderBy = null)
 * @method LotArome[]    findAll()
 * @method LotArome[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LotAromeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LotArome::class);
    }

    // /**
    //  * @return LotArome[] Returns an array of LotArome objects
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
    public function findOneBySomeField($value): ?LotArome
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
