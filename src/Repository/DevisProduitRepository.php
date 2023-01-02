<?php

namespace App\Repository;

use App\Entity\Devis;
use App\Entity\DevisProduit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DevisProduit|null find($id, $lockMode = null, $lockVersion = null)
 * @method DevisProduit|null findOneBy(array $criteria, array $orderBy = null)
 * @method DevisProduit[]    findAll()
 * @method DevisProduit[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DevisProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DevisProduit::class);
    }

    // /**
    //  * @return DevisProduit[] Returns an array of DevisProduit objects
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
    public function findOneBySomeField($value): ?DevisProduit
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function exist($value1, $value2)
    {
        return $this->createQueryBuilder('d')
            ->where('d.produit = :val')
            ->andWhere('d.devis = :val2')
            ->setParameter('val', $value1)
            ->setParameter('val2', $value2)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    
    public function sumQuantity(Devis $devis)
    {
        return $this->createQueryBuilder('d')
            ->select("SUM(d.quantite)")
            ->andWhere('d.devis = :devis')
            ->setParameter('devis', $devis)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
    
}
