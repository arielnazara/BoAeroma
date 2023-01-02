<?php

namespace App\Repository;

use App\Entity\BonLivraison;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method BonLivraison|null find($id, $lockMode = null, $lockVersion = null)
 * @method BonLivraison|null findOneBy(array $criteria, array $orderBy = null)
 * @method BonLivraison[]    findAll()
 * @method BonLivraison[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BonLivraisonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BonLivraison::class);
    }

    // /**
    //  * @return BonLivraison[] Returns an array of BonLivraison objects
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
    public function findOneBySomeField($value): ?BonLivraison
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function lastBL()
    {
        return $this->createQueryBuilder('b')
            ->orderBy('b.increment', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
