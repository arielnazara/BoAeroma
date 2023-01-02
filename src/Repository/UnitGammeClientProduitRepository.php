<?php

namespace App\Repository;

use App\Entity\UnitGammeClientProduit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UnitGammeClientProduit|null find($id, $lockMode = null, $lockVersion = null)
 * @method UnitGammeClientProduit|null findOneBy(array $criteria, array $orderBy = null)
 * @method UnitGammeClientProduit[]    findAll()
 * @method UnitGammeClientProduit[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UnitGammeClientProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UnitGammeClientProduit::class);
    }

    // /**
    //  * @return UnitGammeClientProduit[] Returns an array of UnitGammeClientProduit objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?UnitGammeClientProduit
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
