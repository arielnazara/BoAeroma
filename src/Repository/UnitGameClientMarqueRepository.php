<?php

namespace App\Repository;

use App\Entity\UnitGameClientMarque;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UnitGameClientMarque|null find($id, $lockMode = null, $lockVersion = null)
 * @method UnitGameClientMarque|null findOneBy(array $criteria, array $orderBy = null)
 * @method UnitGameClientMarque[]    findAll()
 * @method UnitGameClientMarque[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UnitGameClientMarqueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UnitGameClientMarque::class);
    }

    // /**
    //  * @return UnitGameClientMarque[] Returns an array of UnitGameClientMarque objects
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
    public function findOneBySomeField($value): ?UnitGameClientMarque
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
