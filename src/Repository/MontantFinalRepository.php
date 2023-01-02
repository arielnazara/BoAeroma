<?php

namespace App\Repository;

use App\Entity\MontantFinal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MontantFinal|null find($id, $lockMode = null, $lockVersion = null)
 * @method MontantFinal|null findOneBy(array $criteria, array $orderBy = null)
 * @method MontantFinal[]    findAll()
 * @method MontantFinal[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MontantFinalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MontantFinal::class);
    }

    // /**
    //  * @return MontantFinal[] Returns an array of MontantFinal objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('m.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?MontantFinal
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
