<?php

namespace App\Repository;

use App\Entity\FactureCommande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method FactureCommande|null find($id, $lockMode = null, $lockVersion = null)
 * @method FactureCommande|null findOneBy(array $criteria, array $orderBy = null)
 * @method FactureCommande[]    findAll()
 * @method FactureCommande[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FactureCommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FactureCommande::class);
    }

    // /**
    //  * @return FactureCommande[] Returns an array of FactureCommande objects
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
    public function findOneBySomeField($value): ?FactureCommande
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function getAll(string $filter = null) {
        $qb = $this->getQuertBuilder();
        switch ($filter) {
            case 'paid':
                $qb->andWhere('i.isPaid = 1');
                break;

            case 'notpaid':
                $qb->andWhere('i.isPaid = 0');
                break;

            case 'asc':
                $qb->orderBy('i.id', 'asc');
                break;

            case 'desc':
                $qb->orderBy('i.id', 'desc');
                break;
        }

        return $qb->getQuery()->getResult();
    }

    public function turnover($paid = null) {

        $qb = $this->createQueryBuilder('f')
            ->select('SUM(f.montantAPayer)')
            ->where('f.estPayer = :paid')
            ->setParameter('paid', $paid)
        ;

        return $qb->getQuery()->getSingleScalarResult();
    }


    public function lastInvoice()
    {
        return $this->createQueryBuilder('f')
            ->orderBy('f.increment', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getNotPaid()
    {
        return $this->createQueryBuilder('f')
            ->where('f.estPayer IS NULL OR f.estPayer = 0')
            ->getQuery()
            ->getResult();
    }

    public function getLastInvoiceByClient(int $clientId){
        return $this->createQueryBuilder('f')
                   ->where('f.client = :clientId')
                   ->setParameter('clientId', $clientId)
                   ->orderBy('f.id', 'DESC')
                   ->setMaxResults(2)
                   ->getQuery()
                   ->getResult();
                   //->getOneOrNullResult();        
    }

    
}
