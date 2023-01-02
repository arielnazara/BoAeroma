<?php

namespace App\Repository;

use App\Entity\UnitProduitArome;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UnitProduitArome|null find($id, $lockMode = null, $lockVersion = null)
 * @method UnitProduitArome|null findOneBy(array $criteria, array $orderBy = null)
 * @method UnitProduitArome[]    findAll()
 * @method UnitProduitArome[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UnitProduitAromeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UnitProduitArome::class);
    }

    // /**
    //  * @return UnitProduitArome[] Returns an array of UnitProduitArome objects
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
    public function findOneBySomeField($value): ?UnitProduitArome
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
