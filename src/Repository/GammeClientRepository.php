<?php

namespace App\Repository;

use App\Entity\GammeClient;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method GammeClient|null find($id, $lockMode = null, $lockVersion = null)
 * @method GammeClient|null findOneBy(array $criteria, array $orderBy = null)
 * @method GammeClient[]    findAll()
 * @method GammeClient[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GammeClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GammeClient::class);
    }

    // /**
    //  * @return GammeClient[] Returns an array of GammeClient objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('g.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?GammeClient
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
