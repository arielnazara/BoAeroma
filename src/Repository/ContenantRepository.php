<?php

namespace App\Repository;

use App\Entity\Contenant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Contenant|null find($id, $lockMode = null, $lockVersion = null)
 * @method Contenant|null findOneBy(array $criteria, array $orderBy = null)
 * @method Contenant[]    findAll()
 * @method Contenant[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ContenantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contenant::class);
    }

    // /**
    //  * @return Contenant[] Returns an array of Contenant objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Contenant
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function findContainingLike($value): ?Contenant
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.nom LIKE :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
