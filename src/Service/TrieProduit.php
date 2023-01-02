<?php


namespace App\Service;

use App\Entity\Devis;
use App\Entity\Produit;

class TrieProduit {
   
    protected $commandes;
    protected $gammesClient;
    protected $clientArray;

    public function setGammesClient(array $gammesClient)
    {
        $this->gammesClient = $gammesClient;

        return $this;
    }

    public function getGammeCLient()
    {
        return $this->gammesClient;
    }


    public function setCommandes($commandes)
    {
        if(is_array($commandes))
        {
            $this->commandes = $commandes;
        }
        else
        {
            $this->commandes = $commandes->getValues();
        }
        
        return $this;
    }

    public function getGreendot() : bool
    {
        if($this->getDevis())
        {
            return $this->getDevis()->getShop() == "grossiste_greendot" ? true : false;
        }

        return false;
    }

    public function getCommandes()
    {
        return $this->commandes;
    }

    public function getDevis()
    {
        $devis = null;

        foreach($this->getCommandes() as $commande)
        {
            if(method_exists($commande, 'getDevis'))
            {
                $devis = $commande->getDevis();
                break;
            }
        }

        return $devis;
    }

    public function trieCommande()
    {
        $commandes = $this->getCommandes();
        $gammesClient = $this->getGammeCLient();
        $greendot = $this->getGreendot();
        $commandesTrier = []; 

        foreach($gammesClient as $gamme)
        {
            if($greendot && $gamme->getGreendot() || ! $greendot && !$gamme->getGreendot())
            {
                foreach($gamme->getMarqueBlanches() as $mb)
                {
                    foreach($commandes as $commande)
                    {
                        if( $commande instanceof Produit )
                        {
                            $produit = $commande;
                        }
                        else
                        {
                            $produit = $commande->getProduit();
                        }
                        if($produit == $mb->getProduit() && !in_array($commande, $commandesTrier))
                        {   
                            array_push($commandesTrier, $commande);
                            $delete = array_search($commande, $commandes);
    
                            unset($commandes[$delete]);
                        }
                    }
                }
            }
        }

        $commandeFinal = array_merge($commandesTrier, $commandes);
    
        return $commandeFinal;
        
    }

    public function unitOrderArray()
    {
        $commandes = $this->getCommandes();
        $gammesClient = $this->getGammeCLient();
        $greendot = $this->getGreendot();
        $commandesTrier = []; 
        $clientArray = $this->clientGammeArray();

        foreach($gammesClient as $gamme)
        {
            if($greendot && $gamme->getGreendot() || ! $greendot && !$gamme->getGreendot())
            {
                foreach($gamme->getMarqueBlanches() as $mb)
                {
                    foreach($commandes as $commande)
                    {
                        if( $commande instanceof Produit )
                        {
                            $produit = $commande;
                        }
                        else
                        {
                            $produit = $commande->getProduit();
                        }
                        if($produit == $mb->getProduit() && !in_array($commande, $commandesTrier))
                        {   
                            array_push($commandesTrier, $commande);
                            $delete = array_search($commande, $commandes);
    
                            unset($commandes[$delete]);

                            if( ! empty($clientArray) )
                            {
                                $mbGamme = $gamme->getGammeParDefaut() ? $gamme->getGammePrincipale()->getNom() : $gamme->getCustomGamme()->getNom();
                               
                                if( array_key_exists($mbGamme, $clientArray) )
                                {
                                    $clientArray[$mbGamme][] = $commande;
                                }
                                
                            }
                        }
                    }
                }
            }
        }

        $commandes = $this->getDefaultG($commandes);
        
        foreach($commandes as $index => $commande)
        {
            foreach($commande as $c)
            {
                $clientArray[$index][] =  $c;
            }
        }
        
        return $clientArray;
        
    }

    public function clientGammeArray()
    {
        $clientArray = [];
        $greendot = $this->getGreendot();

        foreach($this->getGammeCLient() as $gamme)
        {
            if($greendot && $gamme->getGreendot() || ! $greendot && !$gamme->getGreendot())
            {
                $gammeFinal = null;

                if($gamme->getGammeParDefaut())
                {
                    $gammeFinal = $gamme->getGammePrincipale()->getNom();
                }
                else
                {
                    $gammeFinal = $gamme->getCustomGamme()->getNom();
                }

                if( ! in_array($gammeFinal, $clientArray))
                {
                    $clientArray[$gammeFinal] = [];
                }
            }
        }
        
        return $clientArray;
    }

    public function getDefaultG(array $commandes)
    {
        $array = [];
        
        foreach($commandes as $commande)
        {
            $produit = $commande->getProduit();
              
            $array[$produit->getGamme()->getNom()][] = $commande;
        }

        return $array;
    }
}