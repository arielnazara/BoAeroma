<?php

namespace App\Repository;

use App\Entity\PrixDeclinaisonMarque;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PrixDeclinaisonMarque|null find($id, $lockMode = null, $lockVersion = null)
 * @method PrixDeclinaisonMarque|null findOneBy(array $criteria, array $orderBy = null)
 * @method PrixDeclinaisonMarque[]    findAll()
 * @method PrixDeclinaisonMarque[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PrixDeclinaisonMarqueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrixDeclinaisonMarque::class);
    }

    // /**
    //  * @return PrixDeclinaisonMarque[] Returns an array of PrixDeclinaisonMarque objects
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
    public function findOneBySomeField($value): ?PrixDeclinaisonMarque
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
