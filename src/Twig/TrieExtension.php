<?php

namespace App\Twig;

use App\Repository\FactureCommandeRepository;
use App\Repository\PositionGammeClientRepository;
use App\Service\TrieProduit;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TrieExtension extends AbstractExtension
{

    private $trieProduit;
    private $positionGammeClientRepo;
    private $factureCommandeRepo;
    

    public function __construct(TrieProduit $trieProduit, PositionGammeClientRepository $positionGammeClientRepo, FactureCommandeRepository $factureCommandeRepo)
    {
        $this->trieProduit = $trieProduit;
        $this->positionGammeClientRepo = $positionGammeClientRepo;
        $this->positionGammeClientRepo = $positionGammeClientRepo;
        $this->factureCommandeRepo = $factureCommandeRepo;

    }

    public function getFilters()
    {
        return [
            new TwigFilter('trier', [$this, 'trier']),
            new TwigFilter('getLastInvoiceByClient', [$this, 'getLastInvoiceByClient']),
            
        ];
    }

    public function trier($commande, $client)
    {
    
        $gammesClient = $this->positionGammeClientRepo->findBy(['client' => $client ], ['position' => 'asc']);

        $commandeTrier = $this->trieProduit->setGammesClient($gammesClient)
                                    ->setCommandes($commande)
                                    ->trieCommande();

        return $commandeTrier;
    }

    public function getLastInvoiceByClient(int $clientId)
    {
        if( count($this->factureCommandeRepo->getLastInvoiceByClient($clientId)) > 1 ){
             return $this->factureCommandeRepo->getLastInvoiceByClient($clientId)[1];
        }

        return null;
       
    }
    
}