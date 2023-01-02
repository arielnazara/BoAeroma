<?php

namespace App\Repository;

use App\Entity\LotGlycerine;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LotGlycerine|null find($id, $lockMode = null, $lockVersion = null)
 * @method LotGlycerine|null findOneBy(array $criteria, array $orderBy = null)
 * @method LotGlycerine[]    findAll()
 * @method LotGlycerine[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LotGlycerineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LotGlycerine::class);
    }

    // /**
    //  * @return LotGlycerine[] Returns an array of LotGlycerine objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?LotGlycerine
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
