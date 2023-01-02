<?php

namespace App\Repository;

use App\Entity\UnitGameClientCategorie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UnitGameClientCategorie|null find($id, $lockMode = null, $lockVersion = null)
 * @method UnitGameClientCategorie|null findOneBy(array $criteria, array $orderBy = null)
 * @method UnitGameClientCategorie[]    findAll()
 * @method UnitGameClientCategorie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UnitGameClientCategorieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UnitGameClientCategorie::class);
    }

    // /**
    //  * @return UnitGameClientCategorie[] Returns an array of UnitGameClientCategorie objects
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
    public function findOneBySomeField($value): ?UnitGameClientCategorie
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
