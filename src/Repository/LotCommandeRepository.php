<?php

namespace App\Repository;

use App\Entity\LotCommande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LotCommande|null find($id, $lockMode = null, $lockVersion = null)
 * @method LotCommande|null findOneBy(array $criteria, array $orderBy = null)
 * @method LotCommande[]    findAll()
 * @method LotCommande[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LotCommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LotCommande::class);
    }

    // /**
    //  * @return LotCommande[] Returns an array of LotCommande objects
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
    public function findOneBySomeField($value): ?LotCommande
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function findLots($val)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.id IN (' . implode(',', $val) . ')')
            ->getQuery()
            ->getResult();
    }
}
