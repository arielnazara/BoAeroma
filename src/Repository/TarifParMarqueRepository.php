<?php

namespace App\Repository;

use App\Entity\TarifParMarque;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TarifParMarque|null find($id, $lockMode = null, $lockVersion = null)
 * @method TarifParMarque|null findOneBy(array $criteria, array $orderBy = null)
 * @method TarifParMarque[]    findAll()
 * @method TarifParMarque[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TarifParMarqueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TarifParMarque::class);
    }

    // /**
    //  * @return TarifParMarque[] Returns an array of TarifParMarque objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?TarifParMarque
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
