<?php

namespace App\Repository;

use App\Entity\ProduitArome;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ProduitArome|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProduitArome|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProduitArome[]    findAll()
 * @method ProduitArome[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProduitAromeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProduitArome::class);
    }

    // /**
    //  * @return ProduitArome[] Returns an array of ProduitArome objects
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
    public function findOneBySomeField($value): ?ProduitArome
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
