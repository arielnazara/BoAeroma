<?php

namespace App\Repository;

use App\Entity\Client;
use App\Entity\PositionGammeClient;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method PositionGammeClient|null find($id, $lockMode = null, $lockVersion = null)
 * @method PositionGammeClient|null findOneBy(array $criteria, array $orderBy = null)
 * @method PositionGammeClient[]    findAll()
 * @method PositionGammeClient[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PositionGammeClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PositionGammeClient::class);
    }

    // /**
    //  * @return PositionGammeClient[] Returns an array of PositionGammeClient objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?PositionGammeClient
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function getRange(Client $client)
    {
        return $this->createQueryBuilder('p')
            ->where('p.client = :val')
            ->andWhere('p.greendot = 0 OR p.greendot IS NULL')
            ->setParameter('val', $client->getId())
            ->getQuery()
            ->getResult()
        ;
    }

    public function findWhereCLientExist()
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.client IS NOT null')
            ->groupBy('p.client')
            ->orderBy('p.id', 'desc')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findGammeClient(Client $client)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.client = :client')
            ->setParameter('client', $client)
            ->orderBy('p.position', 'asc')
            ->getQuery()
            ->getResult()
        ;
    }
}
