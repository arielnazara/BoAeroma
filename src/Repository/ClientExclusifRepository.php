<?php

namespace App\Repository;

use App\Entity\ClientExclusif;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ClientExclusif|null find($id, $lockMode = null, $lockVersion = null)
 * @method ClientExclusif|null findOneBy(array $criteria, array $orderBy = null)
 * @method ClientExclusif[]    findAll()
 * @method ClientExclusif[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClientExclusifRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClientExclusif::class);
    }

    // /**
    //  * @return ClientExclusif[] Returns an array of ClientExclusif objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ClientExclusif
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
