<?php


namespace App\Service;

use App\Entity\Devis;
use App\Entity\Tarif;
use App\Entity\Client;
use App\Entity\Produit;
use App\Entity\Commandes;
use App\Entity\DevisProduit;
use App\Repository\CommandesRepository;
use App\Repository\DevisProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Exception;

class DevisServices 
{
    private $em;
    private $session;
    private $commandeRepo;
    private $devisProduitRepo;

    public function __construct(EntityManagerInterface $em, SessionInterface $session, CommandesRepository $commandeRepo, DevisProduitRepository $devisProduitRepo)
    {
        $this->em = $em;
        $this->session = $session;
        $this->commandeRepo = $commandeRepo;
        $this->devisProduitRepo = $devisProduitRepo;

    }

    public function newDevisProduct($dataSet, Produit $produit,Client $client, Tarif $tarif, $declinaison = null, string $valueOk)
    {
        
        if($dataSet instanceof Devis)
        {
            $tempDevisP = new DevisProduit();
            $tempDevisP->setDevis($dataSet);
        }
        else
        {
            $tempDevisP = new Commandes();
            $tempDevisP->setBonDeCommande($dataSet);
            $tempDevisP->setDevis($dataSet->getDevis());
        }

        $tempDevisP->setProduit($produit);
        $tempDevisP->setQuantite($valueOk);
        $tempDevisP->setDeclinaison($declinaison);
        $tempDevisP->setDeclineAvec($produit->getPrincipeActif());
        
        if ($tarif->getMemeTarif()) 
        {
            if (strtolower($client->getTypeDeClient()->getNom()) == "grossiste") {
                $tempDevisP->setPrix($tarif->getPrixDeReferenceGrossiste());
            } else {
                $tempDevisP->setPrix($tarif->getPrixDeReferenceDetaillant());
            }
        } 
        else 
        {
            foreach ($tarif->getPrixDeclinaisons() as $prixDecl) 
            {
                if ($prixDecl->getActif()) 
                {
                    if (strtolower($prixDecl->getTypeDeClient()->getNom()) == strtolower($client->getTypeDeClient()->getNom())) 
                    {

                        if ($prixDecl->getDeclinaison() == $tempDevisP->getDeclinaison()) 
                        {
                            $tempDevisP->setPrix($prixDecl->getPrix());
                        }
                    }
                }
                
            }

        }
        
        $this->em->persist($tempDevisP);
        $this->em->flush();

        return $tempDevisP;
    }

    public function productQuote(array $changes ,$devis, Client $client, Produit $produit, Tarif $tarif, $quantite, $ceres, $decl = null, $quantiteOffert = null )
    {
        $loop = true;

        $superieur = false;

        $devisProduit = null;
        $devi = true;
        
        if($devis instanceof Devis)
        {
            $repo = $this->devisProduitRepo;
        }
        else
        {
            $devi = false;
            $repo = $this->commandeRepo;
        }

        if($devi)
        {
            $lots = $repo->findBy(['produit' => $produit ,'devis' => $devis, 'declinaison' => $decl]);
        }
        else
        {
            $lots = $repo->findBy(['produit' => $produit ,'bonDeCommande' => $devis, 'declinaison' => $decl]);
        }
        
        $passed = [];
        $count = 0;
        while($loop)
        {

            $valueOk = $quantite;

            if($changes[0])
            {
                if(($quantite > 2000 && $ceres) or $superieur)
                {
                    $superieur = true;
    
                    if($valueOk > 2000)
                    {
                        $valueOk = 2000;
                    }
    
                    $quantite -= 2000;
    
                }
                else
                {
                    $loop = false;
                }

                if($valueOk > 0)
                {
    
                    if(!$changes[1])
                    {
                        $devisProduit = $this->newDevisProduct($devis, $produit, $client, $tarif,$decl, $valueOk);
                        array_push($passed, $devisProduit->getId());

                    }
                    else
                    {
                        if( isset($lots[$count]))
                        {
                            $lots[$count]->setQuantite($valueOk);
                            array_push($passed, $lots[$count]->getId());
                        }
                    }

                }
                else
                {
                    $loop = false;
                }
            }
            else
            {
                $loop = false;
            }

            if ($produit->getType()  && strtolower($produit->getType()) == "chubby" && $client->getProduitOffert()) {
    
                if($produit->getContenant() and strpos($produit->getContenant()->getTaille(), '50') !== false)
                {

                    //$devis->setNombreProduit($devis->getNombreProduit() + $devisProduit->getQuantite());
                    if($devisProduit)
                    {
                        $quantiteOffert += $devisProduit->getQuantite();
                    }
                }
            }

            $count++;
        }

        

        return $passed;
    }

    public function deleteSpecialChar($value): string
    {
        $value = trim($value);
        $value = str_replace('é', 'e', $value);
        $value = str_replace('è', 'e', $value);
        $value = str_replace('ê', 'e', $value);
        $value = str_replace('ç', 'c', $value);
        $value = str_replace('à', 'a', $value);

        return  preg_replace('/[^A-Za-z0-9\-]/', '', $value);
    }

    public function checkProducts(array $ids, array $datas): array
    {

        foreach($datas as $data )
        {
            $id = $data->getProduit()->getId();

            if( in_array($id, $ids) )
            {
                $index = array_search($id, $ids);
                unset($ids[$index]);
            }

        }

        return ($ids);
    }

    public function produitChoisis(array $data, array $produits) : array
    {
        $id = intval($data["val"]);
        $supprimer = $data["supprimer"];

        if($supprimer)
        {
            $key = array_search($id, $produits);
            
            if( isset($produits[$key]) )
            {
                unset($produits[$key]) ;
            }
            
        }
        else
        {
            array_push($produits, $id);
        }

        $produits = array_unique($produits);

        $this->session->set('produitChoisi', $produits);

        return $produits;
    }

    public function refreshOrder(Devis $devis): void
    {
        foreach($devis->getCommandes() as $c)
        {
            $this->em->remove($c);
            $this->em->flush();
        }

        $bonDeCommande = $devis->getBonDeCommande();

        $dateLivraison = $bonDeCommande->getDate();
        $devisProd = $this->devisProduitRepo->findBy(['devis' => $devis]);
        foreach ($devisProd as $uni) {

            if ($uni->getDeclinaison()) {

                $commande = new Commandes();
                $commande->setDateLivraison($dateLivraison);
                $commande->setBonDeCommande($bonDeCommande);
                $commande->setProduit($uni->getProduit());
                $commande->setQuantite($uni->getQuantite());
                $commande->setDevis($devis);
                $commande->setDeclineAvec($uni->getDeclineAvec());
                $commande->setDeclinaison($uni->getDeclinaison());
                $commande->setOffert($uni->getOffert());
                $commande->setPrix($uni->getPrix());
                $this->em->persist($commande);
            } else {

                $exist = $this->commandeRepo->findOneBy(['devis' => $devis->getId(), 'produit' => $uni->getProduit()]);

                if (!$exist) {
                    $commande = new Commandes();
                    $commande->setDateLivraison($dateLivraison);
                    $commande->setBonDeCommande($bonDeCommande);
                    $commande->setDeclineAvec($uni->getDeclineAvec());
                    $commande->setProduit($uni->getProduit());
                    $commande->setQuantite($uni->getQuantite());
                    $commande->setDevis($devis);
                    $commande->setOffert($uni->getOffert());
                    $commande->setPrix($uni->getPrix());
                    $this->em->persist($commande);
                } else {
                    $exist->setQuantite($exist->getQuantite() + $uni->getQuantite());
                }
            }


            $this->em->flush();
        }

    }


    public function traitementDevis($dataSet, Produit $produit, Client $client, bool $ceres)
    {
        $data = null;
        $repo = null;
        $devis = true;
        if($dataSet instanceof Devis)
        {
        
            $data = $dataSet->getDevisProduits();

            $repo = $this->devisProduitRepo;
        }
        else
        {
            $data = $dataSet->getCommandes();
            $devis = false;
            $repo = $this->commandeRepo;
        }
        $modifier = [];
        foreach ($data as $dev) {
            
            if($dev->getProduit() && $produit)
            {    
                if($produit == $dev->getProduit() && ! $dev->getOffert())
                {
                    
                    $tarif = $dev->getProduit()->getTarifMb($client);
                    
                    $id = $dev->getProduit()->getId();

                    if ($dev->getProduit()->getDeclinaison()) 
                    {

                        $declinaisonCommander = $dev->getDeclinaison();
                       
                        $declinaisonCommander = $this->deleteSpecialChar($declinaisonCommander);
                        
                        foreach($dev->getProduit()->getDeclinaison() as $declinaison)
                        {
                            $tempDecl = $this->deleteSpecialChar($declinaison);

                            if (isset($_POST["quantite-$id-$tempDecl"]) && $_POST["quantite-$id-$tempDecl"] > 0) 
                            {
                                if($declinaisonCommander == $tempDecl)
                                {
                                    
                                    $quantite = $_POST["quantite-$id-$tempDecl"];

                                    $change = false;

                                    if($ceres)
                                    {
                                        if($devis)
                                        {
                                            $lots = $repo->findBy(['produit' => $id ,'devis' => $dataSet, 'declinaison' => $declinaison]);
                                        }
                                        else
                                        {
                                            $lots = $repo->findBy(['produit' => $id ,'bonDeCommande' => $dataSet, 'declinaison' => $declinaison]);
                                        }
                                        
                                        $total = 0;

                                        foreach($lots as $lot)
                                        {
                                            $total += $lot->getQuantite();
                                        }
                            
                                        if($total != $quantite)
                                        {
                                            foreach($lots as $pro)
                                            {
                                                if($pro != $dev)
                                                {
                                                    $this->em->remove($pro);
                                                    $this->em->flush();
                                                }
                                            }

                                            $change = true;
                                        }
                                    }
                                    elseif($quantite != $dev->getQuantite() && !$ceres)
                                    {
                                        $change = true;
                                    }

                                    $new = false;

                                    if( $change && $dev->getQuantite() == 2000 && $quantite > 2000 && $ceres)
                                    {
                                        $quantite -= 2000;
                                        $new = true;
                                    }
                                   
                                    
                                    if($new)
                                    {
                                        $this->productQuote([true, false], $dataSet, $client, $dev->getProduit(), $tarif, $quantite, $ceres, $declinaison);
                                    }
                                    else
                                    {
                                        if($ceres)
                                        {
                                            if( ! in_array($dev->getId(), $modifier) )
                                            {
                                                $result = $this->productQuote([true, true], $dataSet, $client, $dev->getProduit(), $tarif, $quantite, $ceres, $declinaison);

                                                $modifier = array_merge($modifier, $result);
                                            }
                                            
                                        }
                                        else
                                        {
                                            $dev->setQuantite($quantite);
                                        }
                                    }
                                }
                                else
                                {
                                
                                    if($devis)
                                    {
                                        $produitExist = $repo->findOneBy(['devis' => $dataSet, 'produit' => $dev->getProduit(), 'declinaison' => $declinaison]);
                                    }
                                    else
                                    {
                                        $produitExist = $repo->findBy(['bonDeCommande' => $dataSet, 'produit' => $dev->getProduit(), 'declinaison' => $declinaison]);
                                    }
                                    

                                    if(is_null($produitExist) or empty($produitExist))
                                    {

                                        $quantite = $_POST["quantite-$id-$tempDecl"];
                                        
                                        $this->productQuote([true, false], $dataSet, $client, $dev->getProduit(), $tarif, $quantite, $ceres, $declinaison);
                                        
                                    }
                                }

                                
                            }
                            else
                            {
                                try
                                {
                                    if($devis)
                                    {
                                        $devProds = $repo->findBy(['devis' => $dataSet, 'produit' => $dev->getProduit(), 'declinaison' => $declinaison]);
                                    }
                                    else
                                    {
                                        $devProds = $repo->findBy(['bonDeCommande' => $dataSet, 'produit' => $dev->getProduit(), 'declinaison' => $declinaison]);
                                    }
                                    
                                    
                                    if($devProds)
                                    {
                                        
                                        foreach($devProds as $devProd)
                                        {
                                            if( !$devProd->getOffert() )
                                            {
                                                $this->em->remove($devProd);
                                            }
                                        }
                                    }
                                }
                                catch(Exception $e)
                                {

                                }
                            }

                            $this->em->flush();
                            
                        }
                    } else {
                        if (isset($_POST["quantite-$id-$id"])) {
                            $dev->setQuantite($_POST["quantite-$id-$id"]);
                        }
                    }
                    
                }
            }

        }
    }

}