<?php

namespace App\Repository;

use App\Entity\ProduitBase;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ProduitBase|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProduitBase|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProduitBase[]    findAll()
 * @method ProduitBase[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProduitBaseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProduitBase::class);
    }

    // /**
    //  * @return ProduitBase[] Returns an array of ProduitBase objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ProduitBase
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
