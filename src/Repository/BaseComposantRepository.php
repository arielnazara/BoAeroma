<?php

namespace App\Repository;

use App\Entity\BaseComposant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method BaseComposant|null find($id, $lockMode = null, $lockVersion = null)
 * @method BaseComposant|null findOneBy(array $criteria, array $orderBy = null)
 * @method BaseComposant[]    findAll()
 * @method BaseComposant[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BaseComposantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BaseComposant::class);
    }

    // /**
    //  * @return BaseComposant[] Returns an array of BaseComposant objects
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
    public function findOneBySomeField($value): ?BaseComposant
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
