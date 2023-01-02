<?php

namespace App\Repository;

use App\Entity\TarifParClient;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TarifParClient|null find($id, $lockMode = null, $lockVersion = null)
 * @method TarifParClient|null findOneBy(array $criteria, array $orderBy = null)
 * @method TarifParClient[]    findAll()
 * @method TarifParClient[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TarifParClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TarifParClient::class);
    }

    // /**
    //  * @return TarifParClient[] Returns an array of TarifParClient objects
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
    public function findOneBySomeField($value): ?TarifParClient
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
