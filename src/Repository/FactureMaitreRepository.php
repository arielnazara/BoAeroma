<?php

namespace App\Repository;

use App\Entity\FactureMaitre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method FactureMaitre|null find($id, $lockMode = null, $lockVersion = null)
 * @method FactureMaitre|null findOneBy(array $criteria, array $orderBy = null)
 * @method FactureMaitre[]    findAll()
 * @method FactureMaitre[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FactureMaitreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FactureMaitre::class);
    }

    // /**
    //  * @return FactureMaitre[] Returns an array of FactureMaitre objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?FactureMaitre
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function getMasterNotPaid()
    {
        return $this->createQueryBuilder('f')
            ->where('f.estPayer IS NULL OR f.estPayer = 0')
            ->getQuery()
            ->getResult();
    }

    public function lastMasterInvoice()
    {
        return $this->createQueryBuilder('f')
            ->orderBy('f.increment', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function masterturnover($paid = null) {

        $qb = $this->createQueryBuilder('f')
            ->select('SUM(f.MontantAPayer)')
            ->where('f.estPayer = :paid')
            ->setParameter('paid', false)
        ;
        
        return $qb->getQuery()->getSingleScalarResult();
    }
}
