<?php

namespace App\Service;

use App\Service\Useful;
use App\Entity\Commandes;
use App\Entity\BonDeCommande;
use App\Entity\Client;
use App\Entity\Gamme;
use App\Entity\GammeMarqueBlanche;
use App\Entity\Produit;
use App\Repository\BonLivraisonRepository;
use App\Repository\ClientRepository;
use App\Repository\ContenantRepository;
use App\Repository\GammeMarqueBlancheRepository;
use App\Repository\GammeRepository;
use App\Repository\MarqueBlancheRepository;
use App\Repository\OrdreCeresRepository;
use App\Repository\PositionGammeClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use PhpParser\Node\Expr\Cast\Array_;

class LogistiqueServices
{

    private $gammeRepo;
    private $clientRepo;
    private $marqueBlancheRepository;
    private $ordreCeresRepository;
    private $gammeMarqueBlancheRepository;
    private $em;
    private $contenantRepository;
    private $positionGammeClientRepository;
    private $blRepo;



    
    public function __construct(GammeRepository $gammeRepository, BonLivraisonRepository $blRepo, PositionGammeClientRepository $positionGammeClientRepository, ContenantRepository $contenantRepository, GammeMarqueBlancheRepository $gammeMarqueBlancheRepository, EntityManagerInterface $em, ClientRepository $clientRepo, OrdreCeresRepository $ordreCeresRepository, MarqueBlancheRepository $marqueBlancheRepository)
    {
        $this->gammeRepo = $gammeRepository;
        $this->clientRepo = $clientRepo;
        $this->marqueBlancheRepository = $marqueBlancheRepository;
        $this->ordreCeresRepository = $ordreCeresRepository;
        $this->gammeMarqueBlancheRepository = $gammeMarqueBlancheRepository;
        $this->em = $em;
        $this->contenantRepository = $contenantRepository;
        $this->positionGammeClientRepository = $positionGammeClientRepository;
        $this->blRepo = $blRepo;

        
    }

    public function checkEmptyPrice(BonDeCommande $bonDeCommande): void
    {
        $client = $bonDeCommande->getClient();

        $typeDeClient = $client->getTypeDeClient()->getNom();
        $typeDeClient = strtolower($typeDeClient);
        
        foreach($bonDeCommande->getCommandes() as $commande)
        {
            if( ! $commande->getPrix() && ! $commande->getPrixSpecial() )
            {
                $produit = $commande->getProduit();

                $mb = $produit->siClientMarqueBlanche($client);
                
                if($mb)
                {
                    $tarif = $mb->getTarif();

                    $prix = null;

                    if($tarif->getMemeTarif())
                    {
                        if($typeDeClient == "grossiste")
                        {
                            $prix = $tarif->getPrixDeReferenceGrossiste();
                        }
                        else
                        {
                            $prix = $tarif->getPrixDeReferenceDetaillant();
                        }
                        
                    }
                    else
                    {
                        $prixActif = $tarif->getActivePrice();
                        if( isset($prixActif[$typeDeClient]) )
                        {
                            
                            if(isset($prixActif[$typeDeClient][$commande->getDeclinaison()]))
                            {
                                $prix = $prixActif[$typeDeClient][$commande->getDeclinaison()];
                            }
                        }
                    }

                    $commande->setPrix($prix);
                    
                    $this->em->flush();
                }
            }
        }

    }

    public function trieCeres(array $table, $commande = false, ?Client $client = null)
    {

        for($I = count($table) - 2;$I >= 0; $I--) {
            for($J = 0; $J <= $I; $J++) {

                $chiffre1 = null;
                $chiffre2 = null;

                if($commande)
                {
                    $chiffre1 = $table[$J + 1]->getProduit()->getOrdreCeres()->getPosition();
                    
                    $chiffre2 = $table[$J]->getProduit()->getOrdreCeres()->getPosition();

                    
                }
                else
                {

                    $chiffre1 = $table[$J + 1]->getOrdreCeres()->getPosition();
                    
                    $chiffre2 = $table[$J]->getOrdreCeres()->getPosition();
                }
   

                if( $chiffre1 < $chiffre2) {
                    $t = $table[$J + 1];
                    $table[$J + 1] = $table[$J];
                    $table[$J] = $t;
                }
            }
        }
        return $table;
    }


    public function trieCommandeCeres( $commandeListe)
    {
        
        $gamme1 = [];
        $gamme2 = [];
        $echantillon = [];
        $exclu = [];
        foreach($commandeListe as $commande)
        {

            
            if( method_exists($commande, "getEchantillon") &&  $commande->getEchantillon())
            {
                array_push($echantillon, $commande);
            }
            else
            {
                if($commande->getProduit()->getOrdreCeres())
                {

                    if( strtolower($commande->getProduit()->getOrdreCeres()->getCustomGamme()) == "gamme cbd")
                    {
                        
                        array_push($gamme2, $commande);
                    }
                    else
                    {
                        array_push($gamme1, $commande);
                    }
                }
                else
                {
                    array_push($exclu, $commande);
                }
            }
        }

       
        $gamme1 = $this->trieCeres($gamme1, true);
        $gamme2 = $this->trieCeres($gamme2, true);

        $commandes = [];

        foreach($gamme1 as $gamme)
        {
            array_push($commandes, $gamme);
        }

        foreach($gamme2 as $gamme)
        {
            array_push($commandes, $gamme);
        }

        if(!empty($exclu))
        {
            foreach($exclu as $gamme)
            {
                array_push($commandes, $gamme);
            }
        }

        foreach($echantillon as $echantillon)
        {
            array_push($commandes, $echantillon);
        }

        return $commandes;

    }

    public function trieTable(array $table, $greendot = false, Client $client)
    {
    
        for($I = count($table) - 2;$I >= 0; $I--) {
            for($J = 0; $J <= $I; $J++) {

                $position1 = null;
                $position2 = null;
                
                $produit1 = $table[$J + 1];

                if($produit1 instanceof Commandes)
                {
                    $produit1 = $produit1->getProduit();
                }

                if($greendot)
                {
                    $mbs1 = $this->marqueBlancheRepository->findOneBy(['client' => $client, 'produit' => $produit1, 'greendot' => true]);
                }
                else
                {
                    $mbs1 = $this->marqueBlancheRepository->findOneBy(['client' => $client, 'produit' => $produit1]);
                }

                if(!$mbs1)
                {
                    if($client->getCeres())
                    {
                        $position1 = $produit1->getOrdreCeres()->getPosition();
                    }
                }
                else
                {
                    $position1 =  $mbs1->getPosition();
                }
                //$mbs1 = $client->getMarqueBlanches();

                $produit2 = $table[$J];

                if($produit2 instanceof Commandes)
                {
                    $produit2 = $produit2->getProduit();
                }

                if($greendot)
                {
                    $mbs2 = $this->marqueBlancheRepository->findOneBy(['client' => $client, 'produit' => $produit2, 'greendot' => true]);
                }
                else
                {
                    $mbs2 = $this->marqueBlancheRepository->findOneBy(['client' => $client, 'produit' => $produit2]);
                }
                
                //$mbs2 = $client->getMarqueBlanches();

                if(!$mbs2)
                {
                    if($client->getCeres())
                    {
                        $position2 = $produit2->getOrdreCeres()->getPosition();
                    }
                }
                else
                {
                    $position2 =  $mbs2->getPosition();
                }

                if( ! $position1 )
                {
                    $position1 = 100;
                }

                if( ! $position2 )
                {
                    $position1 = 101;
                }

                if( $position1 < $position2) {
                    $t = $table[$J + 1];
                    $table[$J + 1] = $table[$J];
                    $table[$J] = $t;
                }
            }
        }
        return $table;
    }

    public function getPosition($mbs, $produit, $client ): int
    {
        $position = 0;

        foreach($mbs as $mb)
        {
            $mbClient = $mb->getClient();
            $produitMb = $mb->getProduit();
            
            if($client == $mbClient && $produit == $produitMb)
            {
                $position = $mb->getPosition();
                
                if(is_null($position))
                {
                    $defaultCli = $this->clientRepo->findOneBy(['raisonSocial' => 'hpfi']);

                    $defaultProduct = $this->marqueBlancheRepository->findOneBy(['client' => $defaultCli, 'produit' => $produit ]);
                    $position = $defaultProduct->getPosition();
                }
            }
        }

        if(is_null($position))
        {
            $position = 0;
        }

        return $position;
    }

    public function trieListeCommande(array $table, $greendot = false, client $client)
    {
       
        for($I = count($table) - 2;$I >= 0; $I--) {
            for($J = 0; $J <= $I; $J++) {

                $position1 = null;
                $position2 = null;
                
                $produit1 = $table[$J + 1]->getProduit();
                
                if($greendot)
                {
                    $mbs1 = $this->marqueBlancheRepository->findOneBy(['client' => $client, 'produit' => $produit1, 'greendot' => true]);
                }
                else
                {
                    $mbs1 = $this->marqueBlancheRepository->findOneBy(['client' => $client, 'produit' => $produit1]);
                }

                $position1 = $mbs1->getPosition();

                $produit2 = $table[$J]->getProduit();

                if($greendot)
                {
                    $mbs2 = $this->marqueBlancheRepository->findOneBy(['client' => $client, 'produit' => $produit2, 'greendot' => true]);
                }
                else
                {
                    $mbs2 = $this->marqueBlancheRepository->findOneBy(['client' => $client, 'produit' => $produit2]);
                }


                $position2 = $mbs2->getPosition();

                if( $position1 < $position2) {
                    $t = $table[$J + 1];
                    $table[$J + 1] = $table[$J];
                    $table[$J] = $t;
                }
            }
        }
        return $table;
        

    }

    public function trieCommande($commandes)
    {
        $listeGamme = [];
        $devis = null;
        $client = null;

        foreach($commandes as $commande)
        {
            $produit = $commande->getProduit();

            if(method_exists($commande, "getBonDeCommande"))
            {
                $devis = $commande->getBonDeCommande()->getDevis();
            }
            elseif(method_exists($commande, "getLotCommande"))
            {
                $devis = $commande->getLotCommande()->getBonDeCommande()->getDevis();
            }
            elseif(method_exists($commande, "getDevis"))
            {
                $devis = $commande->getDevis();
            }

            $client = $devis->getClient();

            if(!in_array($produit->getGamme()->getId(), $listeGamme))
            {
                array_push($listeGamme, $produit->getGamme()->getId());
            }

        }
        $gammeOrdonner = [];
        
        foreach($this->gammeRepo->findGamme($listeGamme) as $gamme){
            
            if(!is_null($gamme->getPosition())){
                array_push($gammeOrdonner, $gamme);
            }
        }

        foreach($this->gammeRepo->findGamme($listeGamme) as $gamme){
            if(is_null($gamme->getPosition())){
                array_push($gammeOrdonner, $gamme);
            }
        }
        

        $greendot = false;

        $devisShop = $devis->getShop();

        $client = $devis->getClient();

        $ceres = $client->getCeres();

        if($devisShop && $devisShop === "grossiste_greendot")
        {
            $greendot = true;
            $client = $this->clientRepo->find(1);

        }

        $marqueBlanches = $client->getMarqueBlanches();

        $pasDeMb = false;

        if(count($marqueBlanches) == 0)
        {
            $client = $this->clientRepo->findOneBy(["raisonSocial" => "hpfi"]);

            $marqueBlanches = $client->getMarqueBlanches();
            
            $pasDeMb = true;
        }


        $offert = [];

        $echantillon = [];
        $sopurePdo = [];
        $summerExtra = [];
        $ceresExtravape = [];

        $nonMb = [];

        $trieListeCommande = $this->positonGammes($client, $greendot);

        $positionCeres = false;

       
        foreach($commandes as $commande)
        {
            
            $produit = $commande->getProduit();

           
            if(method_exists($commande, "getEchantillon") && $commande->getEchantillon())
            {
                array_push($echantillon, $commande);
                continue;
            }

            $mbExist = $this->marqueBlancheRepository->findOneBy(['client' => $client, 'produit' => $produit]);

            if($mbExist or $ceres)
            {

                if($client->getExtravape())
                {
                    foreach($marqueBlanches as $mb)
                    {
                        $produitMb = $mb->getProduit();

                        $gammeMb = $mb->getGammeMarqueBlanche();

                        if($produit == $produitMb)
                        {
                            
                            if(( $gammeMb && strpos( " ".strtolower($gammeMb->getNom()), "sopure") != false && !in_array($commande, $sopurePdo)) || (strpos( " ".strtolower($produit->getGamme()->getNom()), "sopure") != false && !in_array($commande, $sopurePdo)) )
                            {
                                array_push($sopurePdo, $commande);
                            }

                            if(( $gammeMb && strpos( " ".strtolower($gammeMb->getNom()), "summer") != false  && !in_array($commande, $summerExtra)) || (strpos( " ".strtolower($produit->getGamme()->getNom()), "summer") != false  && !in_array($commande, $summerExtra)) )
                            {
                                array_push($summerExtra, $commande);
                            }

                            if(( $gammeMb && strpos( " ".strtolower($gammeMb->getNom()), "ceres") != false && !in_array($commande, $ceresExtravape)) || (strpos( " ".strtolower($produit->getGamme()->getNom()), "ceres") != false && !in_array($commande, $ceresExtravape)) )
                            {
                                array_push($ceresExtravape, $commande);
                            }
                        }
                    }
                }

                

                foreach($marqueBlanches as $mb)
                {
                    
                    $produitMb = $mb->getProduit();
                    
                    $gammeMb = $mb->getGammeMarqueBlanche();

                    if($produit == $produitMb)
                    {
                        
                        if(!$mb->getGammeMarqueBlanche() || !$mb->getPosition() )
                        {
                            $hpfi = $this->clientRepo->findOneBy(['raisonSocial' => 'hpfi']);
                            $defaultMB = $this->marqueBlancheRepository->findOneBy(['produit' => $produit, 'client' => $hpfi]);

                            if($defaultMB)
                            {
                                $gammeMb = $defaultMB->getGammeMarqueBlanche();
                            }
                            //dd($mb, $gammeMb);
                        }

                        if(!$gammeMb)
                        {
                            $gammeMb = $produit->getGamme();
                        }
                        
                        $nomGamme = $gammeMb->getNom();

                        if(array_key_exists($nomGamme, $trieListeCommande))
                        {

                            if( ! in_array($commande, $trieListeCommande[$nomGamme]) )
                            {
                                $trieListeCommande[$nomGamme][] = $commande ;
                            }

                        }
                        
                        if(strtolower($gammeMb->getNom()) == 'booster' && strtolower($gammeMb->getNom()) != 'base' && ! in_array($commande, $offert))
                        {
                            array_push($offert, $commande);
                        }
                    }

                }

                if($ceres)
                {

                    $positionCeres = true;

                    $produitsCeres = $this->ordreCeresRepository->findBy([], ['position' => 'asc']);

                    foreach($produitsCeres as $ceres)
                    {
                        $produit = $ceres->getProduit();

                        if( $produit == $commande->getProduit())
                        {
                            $nomGamme = $ceres->getCustomGamme();
                            
                            if(array_key_exists($nomGamme, $trieListeCommande))
                            {
                               
                                if( ! in_array($commande, $trieListeCommande[$nomGamme]) )
                                {
                                    $trieListeCommande[$nomGamme][] = $commande ;
                                }
                            }
                        }

                        
                       
                    }
                }
            }
            else
            {
                $gamme = $produit->getGamme()->getNom();

                if(array_key_exists($gamme, $trieListeCommande))
                {
                    if( ! in_array($commande, $trieListeCommande[$gamme]) )
                    {
                        $trieListeCommande[$gamme][] = $commande ;
                    }

                }
                else
                {
                    $nonMb[$gamme][]= $commande;
                }
            }
    
        }

        if($positionCeres)
        {
            foreach($trieListeCommande as $gamme => $produits)
            {
                $trieListeCommande[$gamme] = $this->trieCeres($produits, true);
            }
        }
        else
        {
            foreach($trieListeCommande as $gamme => $produits)
            {
                $trieListeCommande[$gamme] = $this->trieTable($produits,$greendot, $client);
            }
        }

        if(count($nonMb) > 0 && $pasDeMb)
        {
            $trieListeCommande = array_merge($trieListeCommande, $nonMb);
        }

        $trieListeCommande['echantillon'] = $echantillon;

        if(count($offert) > 0)
        {
    
            $gamme = $offert[0]->getProduit()->getGamme()->getNom();
            
            $trieListeCommande[$gamme] = $offert;
        }
        //dd($trieListeCommande);
        return $trieListeCommande;
        
       
    }

    public function groupeByGamme($listeGamme, $marqueBlanches, Client $client)
    {

        $unitGammeProduit = $this->positonGammes($client);

        foreach($marqueBlanches as $mb)
        {

            $produit = $mb->getProduit();
            
            $gammeMb = $mb->getGammeMarqueBlanche();

            if(!$gammeMb)
            {
                $gammeMb = $produit->getGamme();
            }
            
            $nomGamme = $gammeMb->getNom();

            if(array_key_exists($nomGamme, $unitGammeProduit))
            {

                if( ! in_array($produit, $unitGammeProduit[$nomGamme]) )
                {
                    $unitGammeProduit[$nomGamme][] = $produit ;
                }

            }

        }

        if($client->getCeres())
        {

            $produitsCeres = $this->ordreCeresRepository->findAll();

            foreach($produitsCeres as $ceres)
            {
                $produit = $ceres->getProduit();

                $nomGamme = $ceres->getCustomGamme();

                if(array_key_exists($nomGamme, $unitGammeProduit))
                {

                    if( ! in_array($produit, $unitGammeProduit[$nomGamme]) )
                    {
                        $unitGammeProduit[$nomGamme][] = $produit ;
                    }
                }
            }
        }

        foreach($unitGammeProduit as $gamme => $produits)
        {
            $unitGammeProduit[$gamme] = $this->trieTable($produits,false, $client);
        }

        return $unitGammeProduit;

    }


    public function positonGammes($client, bool $greendot = false): Array
    {
        $unitGammeProduit = [];

        if($greendot)
        {
            $positionGammes = $this->positionGammeClientRepository->findBy(['client' => $client, 'greendot' => true], ['position' => 'asc'] );
        }
        else
        {
            $positionGammes = $this->positionGammeClientRepository->getRange($client);
        }
        
        foreach( $positionGammes as $gammePosition )
        {
            if($gammePosition->getPosition())
            {     
                $gamme = null;
    
                if( $gammePosition->getGammePrincipale() )
                {
                    $gamme = $gammePosition->getGammePrincipale();
                }
                else
                {
                    $gamme = $gammePosition->getCustomGamme();
                }
    
                $unitGammeProduit[$gamme->getNom()] = [];
            }
        }

        return $unitGammeProduit;
    }


    public function getGammeMb(array $data, Client $client): ?object 
    {
        
        $gammeFinal = null;

        $gamme = rtrim(ltrim(substr($data[0], strpos($data[0], ": ") + 1)));

        $gammeMarqueBlanches = null;

        $gammeMB = $this->gammeMarqueBlancheRepository->findByNom($gamme);

        $gammePricinpale = $this->gammeRepo->findByNom($gamme);
        
        if($gammePricinpale)
        {

            $gammeFinal = $gammePricinpale;

        }
        elseif(!empty($gammeMB))
        {
            $gammeMarqueBlanches = $gammeMB;
        }
        else
        {
        
            $newGamme = new GammeMarqueBlanche;

            $newGamme->setNom($gamme);

            $this->em->persist($newGamme);
            $this->em->flush();

            $gammeFinal = $newGamme;

        }

        if(!empty($gammeMarqueBlanches))
        {

            if(count($gammeMarqueBlanches) > 1)
            {
    
                foreach($gammeMarqueBlanches as $gamemb)
                {
                    if(strtolower($gamemb->getNom()) == strtolower($gamme))
                    {
                        $gammeFinal = $gamemb;
                    }
                }
    
                if(!$gammeFinal)
                {
                    $newGamme = new GammeMarqueBlanche;
    
                    $newGamme->setNom($gamme);
    
                    $this->em->persist($newGamme);
                    $this->em->flush();
    
                    $gammeFinal = $newGamme;
    
                }
                
            }
            else
            {
                $gammeFinal = $gammeMarqueBlanches[0];
            }
        }

        if(!$gammeFinal)
        {
            dd($gamme, $client);
        }

        if(is_array($gammeFinal))
        {
            $result = $gammeFinal[0];
        }
        else
        {
            $result = $gammeFinal;
        }

        return $result ;
        
    }


    public function createProduct(Array $data, $gammeFinal, array $decl = []): Produit
    {
        
        $reference = $data[0];
        $nom = $data[3];
        $contenants = explode(';', $data[4]);
        $type = null;

        if(count($contenants) > 1)
        {
            //dd($data);
        }

        $gamme = $gammeFinal;

        if($gammeFinal instanceof GammeMarqueBlanche)
        {
            $gamme = $this->gammeRepo->findOneBy(['nom' => $gammeFinal->getNom()]);

            if(!$gamme)
            {
                
                $gamme = new Gamme;
                $gamme->setNom($gammeFinal->getNom());
    
                $this->em->persist($gamme);
                $this->em->flush();
                
            }
        }

        $produit = new Produit;

        $produit->setNom($nom);
        $produit->setReference($reference);
        $produit->setGamme($gamme);
        $produit->setMarqueBlanche(true);
        $produit->setDeclinaison($decl);

        $contenant = $contenants[0];
        if($contenant = 10)
        {
            $contenant = $this->contenantRepository->findOneBy(['nom' => '10ml PET']);
        }
        $produit->setContenant($contenant);

        if(substr( $reference, 0, 2) == "SV")
        {
            $type = 'Chubby';
        }
        
        if(substr( $reference, 0, 2) == "AC")
        {
            $type = 'Concentré';
        }

        $produit->setType($type);

        $this->em->persist($produit);

        $this->em->flush();

        return $produit;
        
    }

    public function importExcelCreateDecl(array $data): array
    {
        $declTab = explode(";", $data[5]);

        $declFinal = [];

        if( count($declTab) > 1 )
        {
            foreach($declTab as $decl)
            {
                $decl = explode("=", $decl);
                $decl = intval($decl[0]);
                
                if($decl == 0)
                {
                    $decl = "00";
                }
    
                $decl = $decl."mg";
    
                array_push($declFinal, $decl);
            }
        }

        return $declFinal;
    }

    public function incrementBL(): int
    {

        $last = null;
        $lastBl = $this->blRepo->lastBL();

        if($lastBl)
        {
            $last = $lastBl->getIncrement();
        }

        if($last && $last > 4606)
        {
            $lastBl = $last;
        }
        else
        {
            $lastBl = 4606;
        }

        return $lastBl + 1;
        
    }
}