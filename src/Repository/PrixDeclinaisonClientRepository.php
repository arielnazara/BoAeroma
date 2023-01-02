<?php

namespace App\Repository;

use App\Entity\PrixDeclinaisonClient;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PrixDeclinaisonClient|null find($id, $lockMode = null, $lockVersion = null)
 * @method PrixDeclinaisonClient|null findOneBy(array $criteria, array $orderBy = null)
 * @method PrixDeclinaisonClient[]    findAll()
 * @method PrixDeclinaisonClient[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PrixDeclinaisonClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrixDeclinaisonClient::class);
    }

    // /**
    //  * @return PrixDeclinaisonClient[] Returns an array of PrixDeclinaisonClient objects
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
    public function findOneBySomeField($value): ?PrixDeclinaisonClient
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
