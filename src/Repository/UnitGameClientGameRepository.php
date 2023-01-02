<?php

namespace App\Repository;

use App\Entity\UnitGameClientGame;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UnitGameClientGame|null find($id, $lockMode = null, $lockVersion = null)
 * @method UnitGameClientGame|null findOneBy(array $criteria, array $orderBy = null)
 * @method UnitGameClientGame[]    findAll()
 * @method UnitGameClientGame[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UnitGameClientGameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UnitGameClientGame::class);
    }

    // /**
    //  * @return UnitGameClientGame[] Returns an array of UnitGameClientGame objects
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
    public function findOneBySomeField($value): ?UnitGameClientGame
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
