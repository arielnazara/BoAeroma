<?php

namespace App\Repository;

use App\Entity\Produit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Produit|null find($id, $lockMode = null, $lockVersion = null)
 * @method Produit|null findOneBy(array $criteria, array $orderBy = null)
 * @method Produit[]    findAll()
 * @method Produit[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produit::class);
    }

    // /**
    //  * @return Produit[] Returns an array of Produit objects
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
    public function findOneBySomeField($value): ?Produit
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function getBooster($val = null, $categorie = null)
    {
        return $this->createQueryBuilder('p')
            //->where('p.reference LIKE :val')
            //->andWhere('p.categorie = :categorie')
            //->andWhere('p.id != 112')
            //->setParameter('val', '%' . $val . '%')
            //->setParameter('categorie', $categorie)
            ->where('p.offert = true')
            ->getQuery()
            ->getoneOrNullResult();
    }

    public function findByThree(array $val)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.id NOT IN (' . implode(',', $val) . ')')
            ->orderBy('p.id', 'DESC')
            ->setMaxResults(6)
            ->getQuery()
            ->getResult();
    }

    public function findByIds(array $val)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.id IN (' . implode(',', $val) . ')')
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAdditionalProducts(array $exist)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.id NOT IN (' . implode(',', $exist) . ')')
            ->orderBy('p.id', 'asc')
            ->getQuery()
            ->getResult();
    }

    public function findSamples(array $val)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.id IN (' . implode(',', $val) . ')')
            ->getQuery()
            ->getResult();
    }

    public function findBySession(array $val)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.id IN (' .  implode(',', $val) . ')')
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function produitExist($val)
    {
        
        if (substr($val, 0, 2) == "SV" or substr($val, 0, 2) == "AC") {
            $val = substr($val, 2);
        }

        return $this->createQueryBuilder('p')
            ->where('p.reference LIKE :val')
            //->andWhere('p.type IS NULL')
            ->setParameter('val', $val)
            ->getQuery()
            ->getoneOrNullResult();
    }

    public function findChubOrConce($val)
    {
        return $this->createQueryBuilder('p')
            ->where('p.reference LIKE :val')
            ->andWhere('p.type IS NOT NULL')
            ->setParameter('val', '%' . $val . '%')
            ->getQuery()
            ->getResult();
    }


    public function findByDecl(array $val)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.principeActif IN (' .  implode(',', $val) . ')')
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByString(string $val)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.nom LIKE :val')
            ->setParameter('val', '%' . $val . '%')
            ->getQuery()
            ->getResult();
    }

    public function findByRef($ref)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.reference LIKE :ref')
            ->setParameter('ref', $ref . '%')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByRefs($ref)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.reference LIKE :ref')
            ->setParameter('ref', $ref . '%')
            ->getQuery()
            ->getResult();
    }

    /*public function findPresByRef($ref)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.reference LIKE :ref')
            ->setParameter('ref', $ref . '%')
            ->getQuery()
            ->getResult();
    }*/

    public function findProductList(array $tab)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.id IN (' .  implode(',', $tab) . ')')
            ->getQuery()
            ->getResult();
    }
}
