<?php

namespace App\Repository;

use App\Entity\Client;
use App\Entity\CatalogueClient;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method CatalogueClient|null find($id, $lockMode = null, $lockVersion = null)
 * @method CatalogueClient|null findOneBy(array $criteria, array $orderBy = null)
 * @method CatalogueClient[]    findAll()
 * @method CatalogueClient[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CatalogueClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CatalogueClient::class);
    }

    // /**
    //  * @return CatalogueClient[] Returns an array of CatalogueClient objects
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
    public function findOneBySomeField($value): ?CatalogueClient
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function findWhereCLientExist()
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.client IS NOT null')
            ->groupBy('c.client')
            ->orderBy('c.id', 'desc')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findGammeClient(Client $client)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.client = :client')
            ->setParameter('client', $client)
            ->orderBy('c.position', 'asc')
            ->getQuery()
            ->getResult()
        ;
    }
}
