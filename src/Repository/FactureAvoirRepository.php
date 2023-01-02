<?php

namespace App\Repository;

use App\Entity\FactureAvoir;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method FactureAvoir|null find($id, $lockMode = null, $lockVersion = null)
 * @method FactureAvoir|null findOneBy(array $criteria, array $orderBy = null)
 * @method FactureAvoir[]    findAll()
 * @method FactureAvoir[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FactureAvoirRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FactureAvoir::class);
    }

    // /**
    //  * @return FactureAvoir[] Returns an array of FactureAvoir objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?FactureAvoir
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
