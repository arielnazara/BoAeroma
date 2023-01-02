<?php

namespace App\Repository;

use App\Entity\ProduitGammeClientMarque;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ProduitGammeClientMarque|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProduitGammeClientMarque|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProduitGammeClientMarque[]    findAll()
 * @method ProduitGammeClientMarque[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProduitGammeClientMarqueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProduitGammeClientMarque::class);
    }

    // /**
    //  * @return ProduitGammeClientMarque[] Returns an array of ProduitGammeClientMarque objects
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
    public function findOneBySomeField($value): ?ProduitGammeClientMarque
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
