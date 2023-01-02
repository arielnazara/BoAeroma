<?php

namespace App\Repository;

use App\Entity\LotNicotine;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LotNicotine|null find($id, $lockMode = null, $lockVersion = null)
 * @method LotNicotine|null findOneBy(array $criteria, array $orderBy = null)
 * @method LotNicotine[]    findAll()
 * @method LotNicotine[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LotNicotineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LotNicotine::class);
    }

    // /**
    //  * @return LotNicotine[] Returns an array of LotNicotine objects
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
    public function findOneBySomeField($value): ?LotNicotine
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
