<?php

namespace App\Repository;

use App\Entity\Client;
use App\Entity\Gamme;
use App\Entity\MarqueBlanche;
use App\Entity\PositionGammeClient;
use App\Entity\Produit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MarqueBlanche|null find($id, $lockMode = null, $lockVersion = null)
 * @method MarqueBlanche|null findOneBy(array $criteria, array $orderBy = null)
 * @method MarqueBlanche[]    findAll()
 * @method MarqueBlanche[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MarqueBlancheRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MarqueBlanche::class);
    }

    // /**
    //  * @return MarqueBlanche[] Returns an array of MarqueBlanche objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('m.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?MarqueBlanche
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function findUnique(Client $client)
    {
        return $this->createQueryBuilder('m')
            ->where('m.client = :client')
            ->setParameter('client', $client)
            ->groupBy('m.reference')
            ->getQuery()
            ->getResult();
    }

    public function findAdditionalProducts(array $exist, Client $client)
    {
        return $this->createQueryBuilder('m')
            ->where('m.client = :client')
            ->andWhere('m.produit NOT IN (' . implode(',', $exist) . ')')
            ->setParameter('client', $client)
            ->orderBy('m.produit', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getClients()
    {
        return $this->createQueryBuilder('m')
            ->groupBy('m.client')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getMBProducts($client, $gamme)
    {
        $query = $this->createQueryBuilder('m')
            ->innerJoin('m.produit', 'produit')
            ->addSelect('produit')
            ->where('m.client = :client')
        ;

        if($gamme instanceof Gamme)
        {
            $query->andWhere('m.gammeMarqueBlanche IS NULL')
                ->andWhere('produit.gamme = :gamme')
            ;
        }
        else
        {
            $query->andWhere('m.gammeMarqueBlanche = :gamme');
        }
       
        $query->setParameter('client', $client)
            ->setParameter('gamme', $gamme)
        ;

        return $query->getQuery()->getResult();
    }

    public function getMbByGamme($client, $gamme, $produit)
    {
        $query = $this->createQueryBuilder('m')
            ->innerJoin('m.produit', 'produit')
            ->addSelect('produit')
            ->where('m.client = :client')
            ->andWhere('produit = :produit')
        ;

        if($gamme instanceof Gamme)
        {
            $query->andWhere('m.gammeMarqueBlanche IS NULL')
                ->andWhere('produit.gamme = :gamme')
            ;
        }
        else
        {
            $query->andWhere('m.gammeMarqueBlanche = :gamme');
        }
       
        $query->setParameter('client', $client)
            ->setParameter('gamme', $gamme)
            ->setParameter('produit', $produit)
        ;

        return $query->getQuery()->getResult();
    }

    public function produitsMarqueBlanches( Client $client , $greendot = false)
    {
        $qb = $this->createQueryBuilder('m')
            ->where('m.client = :client')
        ;

        if($greendot)
        {
            $qb->andWhere('m.greendot = true');
        }
        else
        {
            $qb->andWhere('m.greendot = false OR m.greendot IS NULL');
        }

        $qb->setParameter('client', $client)
            ->orderBy('m.position', 'ASC')
        ;

        return $qb->getQuery()->getResult();
    }

    public function mbList( Client $client , Produit $produit, $greendot = false)
    {
        $qb = $this->createQueryBuilder('m')
            ->where('m.client = :client')
            ->andWhere('m.produit = :produit')

        ;

        if($greendot)
        {
            $qb->andWhere('m.greendot = true');
        }
        else
        {
            $qb->andWhere('m.greendot = false OR m.greendot IS NULL');
        }

        $qb->setParameter('client', $client)
            ->setParameter('produit', $produit)
            ->orderBy('m.position', 'ASC')
        ;
        
        return $qb->getQuery()->getResult();
    }

    public function findByIds(array $val, $client)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.produit IN (' . implode(',', $val) . ')')
            ->andWhere('m.client = :client')
            ->groupBy('m.reference')
            ->setParameter('client', $client)
            ->orderBy('m.position', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function idsNotIN(array $val, $client)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.produit NOT IN (' . implode(',', $val) . ')')
            ->andWhere('m.client = :client')
            ->setParameter('client', $client)
            ->orderBy('m.position', 'DESC')
            ->groupBy('m.reference')
            ->getQuery()
            ->getResult();
    }

    public function trier(PositionGammeClient $position)
    {
        return $this->createQueryBuilder('m')
            ->where('m.positionGammeClient = :position')
            ->setParameter('position', $position->getId())
            //->orderBy('m.position', 'asc')
            ->getQuery()
            ->getResult();
    }
}
