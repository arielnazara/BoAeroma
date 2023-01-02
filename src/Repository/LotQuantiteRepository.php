<?php

namespace App\Repository;

use App\Entity\LotQuantite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LotQuantite|null find($id, $lockMode = null, $lockVersion = null)
 * @method LotQuantite|null findOneBy(array $criteria, array $orderBy = null)
 * @method LotQuantite[]    findAll()
 * @method LotQuantite[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LotQuantiteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LotQuantite::class);
    }

    // /**
    //  * @return LotQuantite[] Returns an array of LotQuantite objects
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
    public function findOneBySomeField($value): ?LotQuantite
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
