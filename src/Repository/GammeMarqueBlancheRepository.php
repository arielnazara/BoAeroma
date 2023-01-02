<?php

namespace App\Repository;

use App\Entity\Client;
use App\Entity\GammeMarqueBlanche;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method GammeMarqueBlanche|null find($id, $lockMode = null, $lockVersion = null)
 * @method GammeMarqueBlanche|null findOneBy(array $criteria, array $orderBy = null)
 * @method GammeMarqueBlanche[]    findAll()
 * @method GammeMarqueBlanche[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GammeMarqueBlancheRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GammeMarqueBlanche::class);
    }

    // /**
    //  * @return GammeMarqueBlanche[] Returns an array of GammeMarqueBlanche objects
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
    public function findOneBySomeField($value): ?GammeMarqueBlanche
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function findLike($val)
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.nom LIKE :val')
            ->setParameter('val', '%' . $val . '%')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findWhereCLientExist()
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.client IS NOT null')
            ->groupBy('g.client')
            ->orderBy('g.id', 'desc')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findGammeClient(Client $client)
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.client = :client')
            ->setParameter('client', $client)
            ->orderBy('g.position', 'asc')
            ->getQuery()
            ->getResult()
        ;
    }
}
