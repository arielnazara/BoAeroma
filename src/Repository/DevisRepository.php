<?php

namespace App\Repository;

use App\Entity\Devis;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Devis|null find($id, $lockMode = null, $lockVersion = null)
 * @method Devis|null findOneBy(array $criteria, array $orderBy = null)
 * @method Devis[]    findAll()
 * @method Devis[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DevisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Devis::class);
    }

    // /**
    //  * @return Devis[] Returns an array of Devis objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Devis
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function devisSigner()
    {

        return $this->createQueryBuilder('d')
            ->andWhere('d.signeParClient = :val')
            ->setParameter('val', true)
            ->getQuery()
            ->getResult();
    }

    public function lastProstore($value = 'aeroma_prostore')
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.shop = :val')
            ->setParameter('val', $value)
            ->orderBy('d.shopOrderId', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function lastGreendot($value = 'grossiste_greendot')
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.shop = :val')
            ->setParameter('val', $value)
            ->orderBy('d.shopOrderId', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function lastYzy($value = 'yzy_vape')
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.shop = :val')
            ->setParameter('val', $value)
            ->orderBy('d.shopOrderId', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function standBy()
    {

        return $this->createQueryBuilder('d')
            ->andWhere('d.valider = :val')
            ->setParameter('val', false)
            ->getQuery()
            ->getResult();
    }

    public function fetchPrestaOrder(){
        return $this->createQueryBuilder('d')
        ->andWhere('d.shopOrderId IS NOT NULL ')
        ->getQuery()
        ->getResult();
    }
}
