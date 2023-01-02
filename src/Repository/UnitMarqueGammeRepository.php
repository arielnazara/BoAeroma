<?php

namespace App\Repository;

use App\Entity\UnitMarqueGamme;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UnitMarqueGamme|null find($id, $lockMode = null, $lockVersion = null)
 * @method UnitMarqueGamme|null findOneBy(array $criteria, array $orderBy = null)
 * @method UnitMarqueGamme[]    findAll()
 * @method UnitMarqueGamme[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UnitMarqueGammeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UnitMarqueGamme::class);
    }

    // /**
    //  * @return UnitMarqueGamme[] Returns an array of UnitMarqueGamme objects
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
    public function findOneBySomeField($value): ?UnitMarqueGamme
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
