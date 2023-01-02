<?php

namespace App\Repository;

use App\Entity\OrdreExtravape;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method OrdreExtravape|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrdreExtravape|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrdreExtravape[]    findAll()
 * @method OrdreExtravape[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrdreExtravapeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrdreExtravape::class);
    }

    // /**
    //  * @return OrdreExtravape[] Returns an array of OrdreExtravape objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('o.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?OrdreExtravape
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
