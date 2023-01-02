<?php

namespace App\Repository;

use App\Entity\LotVegetolPdo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LotVegetolPdo|null find($id, $lockMode = null, $lockVersion = null)
 * @method LotVegetolPdo|null findOneBy(array $criteria, array $orderBy = null)
 * @method LotVegetolPdo[]    findAll()
 * @method LotVegetolPdo[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LotVegetolPdoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LotVegetolPdo::class);
    }

    // /**
    //  * @return LotVegetolPdo[] Returns an array of LotVegetolPdo objects
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
    public function findOneBySomeField($value): ?LotVegetolPdo
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
