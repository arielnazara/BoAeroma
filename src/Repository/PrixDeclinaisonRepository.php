<?php

namespace App\Repository;

use App\Entity\PrixDeclinaison;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PrixDeclinaison|null find($id, $lockMode = null, $lockVersion = null)
 * @method PrixDeclinaison|null findOneBy(array $criteria, array $orderBy = null)
 * @method PrixDeclinaison[]    findAll()
 * @method PrixDeclinaison[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PrixDeclinaisonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrixDeclinaison::class);
    }

    // /**
    //  * @return PrixDeclinaison[] Returns an array of PrixDeclinaison objects
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
    public function findOneBySomeField($value): ?PrixDeclinaison
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
