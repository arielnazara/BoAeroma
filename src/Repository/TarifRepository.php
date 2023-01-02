<?php

namespace App\Repository;

use App\Entity\Tarif;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Tarif|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tarif|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tarif[]    findAll()
 * @method Tarif[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TarifRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tarif::class);
    }

    // /**
    //  * @return Tarif[] Returns an array of Tarif objects
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
    public function findOneBySomeField($value): ?Tarif
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function findByClient()
    {
        return $this->createQueryBuilder('t')
            ->where('t.client IS NOT NULL')
            ->orderBy('t.client', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function exist($cat, $cont, $sup)
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.categorie', 'cat')
            ->leftJoin('t.contenance', 'cont')
            ->leftJoin('t.base', 'sup')
            ->where('cat.id = :cat')
            ->andWhere('cont.id = :cont')
            ->andWhere('sup.id = :sup')
            ->setParameter('cat', $cat)
            ->setParameter('cont', $cont)
            ->setParameter('sup', $sup)
            ->orderBy('t.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
