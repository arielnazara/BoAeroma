<?php

namespace App\Repository;

use App\Entity\ProduitMarqueBlanche;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ProduitMarqueBlanche|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProduitMarqueBlanche|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProduitMarqueBlanche[]    findAll()
 * @method ProduitMarqueBlanche[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProduitMarqueBlancheRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProduitMarqueBlanche::class);
    }

    // /**
    //  * @return ProduitMarqueBlanche[] Returns an array of ProduitMarqueBlanche objects
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
    public function findOneBySomeField($value): ?ProduitMarqueBlanche
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
