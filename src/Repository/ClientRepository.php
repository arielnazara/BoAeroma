<?php

namespace App\Repository;

use App\Entity\Client;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Client|null find($id, $lockMode = null, $lockVersion = null)
 * @method Client|null findOneBy(array $criteria, array $orderBy = null)
 * @method Client[]    findAll()
 * @method Client[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    /**
     * @return int|mixed|string
     */
    public function findBpco() {
        return $this->createQueryBuilder('c')
            ->where('c.clientType is not null')
            ->getQuery()->getResult()
        ;
    }

    public function tclients() {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()->getSingleScalarResult()
            ;
    }

    public function ifExist($val){
        return $this->createQueryBuilder('c')
        ->where('c.email = :val')
        ->setParameter('val', $val)
        ->getQuery()->getOneOrNullResult()
    ;
    }
    
}
