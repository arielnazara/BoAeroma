<?php

namespace App\Repository;

use App\Entity\GroupeBondeCommande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method GroupeBondeCommande|null find($id, $lockMode = null, $lockVersion = null)
 * @method GroupeBondeCommande|null findOneBy(array $criteria, array $orderBy = null)
 * @method GroupeBondeCommande[]    findAll()
 * @method GroupeBondeCommande[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GroupeBondeCommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GroupeBondeCommande::class);
    }

    // /**
    //  * @return GroupeBondeCommande[] Returns an array of GroupeBondeCommande objects
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
    public function findOneBySomeField($value): ?GroupeBondeCommande
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
