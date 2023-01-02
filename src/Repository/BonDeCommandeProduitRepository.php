<?php

namespace App\Repository;

use App\Entity\BonDeCommandeProduit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method BonDeCommandeProduit|null find($id, $lockMode = null, $lockVersion = null)
 * @method BonDeCommandeProduit|null findOneBy(array $criteria, array $orderBy = null)
 * @method BonDeCommandeProduit[]    findAll()
 * @method BonDeCommandeProduit[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BonDeCommandeProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BonDeCommandeProduit::class);
    }

    // /**
    //  * @return BonDeCommandeProduit[] Returns an array of BonDeCommandeProduit objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('b.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?BonDeCommandeProduit
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

   
}
