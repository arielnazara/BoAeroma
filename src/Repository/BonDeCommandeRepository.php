<?php

namespace App\Repository;

use App\Entity\BonDeCommande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method BonDeCommande|null find($id, $lockMode = null, $lockVersion = null)
 * @method BonDeCommande|null findOneBy(array $criteria, array $orderBy = null)
 * @method BonDeCommande[]    findAll()
 * @method BonDeCommande[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BonDeCommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BonDeCommande::class);
    }

    // /**
    //  * @return BonDeCommande[] Returns an array of BonDeCommande objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('b.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?BonDeCommande
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function tOrder() {
        return $this->createQueryBuilder('b')
           ->select('SUM(b.nombreProduit)')
           ->getQuery()->getSingleScalarResult()
        ;
   }

   public function trestToDelivred() {
       return $this->createQueryBuilder('b')
           ->select('SUM(b.resteAlivrer)')
           ->getQuery()->getSingleScalarResult()
           ;
   }

    public function fetchPrestaOrder(){
        return $this->createQueryBuilder('b')
        ->leftJoin('b.devis', 'devis')
        ->addSelect('devis')
        ->where('b.toutEstPayer = 0 OR b.toutEstPayer IS NULL ')
        ->andWhere('devis.shopOrderId IS NOT NULL')
        ->getQuery()
        ->getResult();
    }

    public function fetchExtravapOrder()
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.client', 'client')
            ->addSelect('client')
            ->where('b.envoyerAuLogistique = true')
            ->andWhere('client.extravape = true')
            ->andWhere('b.toutEstPayer IS NULL OR b.toutEstPayer = false')
            ->orderBy('b.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
