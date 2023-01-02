<?php


namespace App\Service;

use App\Entity\Arome;
use App\Entity\Base;
use App\Entity\Produit;
use App\Entity\Categorie;
use App\Entity\Gamme;
use App\Entity\Marque;
use App\Entity\ProduitArome;
use App\Repository\AromeRepository;
use App\Repository\BaseRepository;
use App\Repository\ProduitRepository;
use App\Repository\CategorieRepository;
use App\Repository\ContenantRepository;
use App\Repository\GammeRepository;
use App\Repository\MarqueRepository;
use App\Repository\PrincipeActifRepository;
use Doctrine\ORM\EntityManagerInterface;


class CsvAddProducts
{

    protected $em;
    protected $produitRepository;
    protected $categorieRepository;
    protected $contenantRepository;
    protected $gammeRepository;
    protected $supportRepository;
    protected $aromeRepository;
    protected $principeActifRepository;
    protected $marqueRepository;

    

    public function __construct(ProduitRepository $produitRepository, MarqueRepository $marqueRepository, AromeRepository $aromeRepository, PrincipeActifRepository $principeActifRepository, GammeRepository $gammeRepository, BaseRepository $supportRepository, ContenantRepository $contenantRepository, EntityManagerInterface $em, CategorieRepository $categorieRepository)
    {
        $this->em = $em;
        $this->produitRepository = $produitRepository;
        $this->categorieRepository = $categorieRepository;
        $this->contenantRepository = $contenantRepository;
        $this->gammeRepository = $gammeRepository;
        $this->supportRepository = $supportRepository;
        $this->aromeRepository = $aromeRepository;
        $this->principeActifRepository = $principeActifRepository;
        $this->marqueRepository = $marqueRepository;

    }

    public function productExist(array $data, Produit $produit, $new = false)
    {

        $id = $data[0];
        $reference = $data[1];
        $Nom = $data[2];
        $Type = $data[3];
        $Categorie = $data[4];
        $Contenant = $data[5];
        $Gamme = $data[6];
        $Support = $data[7];
        $Aromes = $data[8];
        $Pricipe = $data[9];
        $Declinaisons = $data[10];
        $refDecl = $data[11];
        $Marque = $data[12];
        
        if( strtolower($produit->getReference()) != strtolower($reference))
        {
            $produit->setReference($reference);
        }

        if( strtolower($produit->getNom()) != strtolower($Nom))
        {
            $produit->setNom($Nom);
        }

        if($produit->getType() != $Type)
        {
            $produit->setType($Type);
        }

        
        if( ($produit->getCategorie() and strtolower($produit->getCategorie()->getNom()) != strtolower($Categorie)) || (!$produit->getCategorie() and !empty($Categorie)) )
        {
            $newCategory = $this->categorieRepository->findCategoryLike($Categorie);

            if(!$newCategory)
            {
                $newCategory = new Categorie;
                $newCategory->setNom($Categorie);
                $this->em->persist($newCategory);
                $this->em->flush();

            }

            $produit->setCategorie($newCategory);
            
        }

        if( ($produit->getContenant() && strtolower($produit->getContenant()->getNom()) != strtolower($Contenant)) || (!$produit->getContenant() and !empty($Contenant)))
        {
            $newContainting = $this->contenantRepository->findContainingLike($Contenant);

            if(!$newContainting)
            {
                
                dd('aucun contenant trouvé');

            }

            $produit->setContenant($newContainting);
        }

        if( ($produit->getGamme() and strtolower($produit->getGamme()->getNom()) != strtolower($Gamme)) || (!$produit->getGamme() and !empty($Gamme)) )
        {
            
            $newGamme = $this->gammeRepository->findGammeLike($Gamme);
            if(!$newGamme)
            {
                $newGamme = new Gamme;
                $newGamme->setNom($Gamme);
                $newGamme->setParDefaut(true);
                
                $this->em->persist($newGamme);
                $this->em->flush();
                
            }
            
            $produit->setGamme($newGamme);
            
        }

        if( ($produit->getBase() and strtolower($produit->getBase()->getNom()) != strtolower($Support)) || (!$produit->getBase() and !empty($Support)) )
        {
            $newSupport = $this->supportRepository->findSupportLike($Support);

            if(!$newSupport)
            {
                $newSupport = new Base;
                $newSupport->setNom($Support);
                
                $this->em->persist($newSupport);
                $this->em->flush();

            }

            $produit->setBase($newSupport);
            
        }
      

        $aromes = explode(',', $Aromes);

        
        $this->checkArome($aromes, $produit);

        if(($produit->getPrincipeActif() and strtolower($produit->getPrincipeActif()->getPrincipeActif()) != strtolower($Pricipe)) || (!$produit->getPrincipeActif() && !empty($Pricipe)))
        {
            $newPrincipe = $this->principeActifRepository->findPrincipeLike($Pricipe);

            $produit->setPrincipeActif($newPrincipe);
        }

        
        if(!empty($Declinaisons))
        {
            $Declinaisons = explode(', ', ltrim($Declinaisons));
            $declinaisonProduit = $produit->getDeclinaison();

            $diffA = array_diff($Declinaisons, $declinaisonProduit);

            $diffB = array_diff($declinaisonProduit, $Declinaisons);

            if(!empty($diffA) || !empty($diffB))
            {
                $produit->setDeclinaison($Declinaisons);
            }
        }
        else
        {
            $produit->setDeclinaison([]);
        }

        
        if(!empty($refDecl))
        {   
            $ref = explode(', ', ltrim($refDecl));
            $refDeclProduit = $produit->getReferenceDeclinaison();

            $diffA = array_diff($ref, $refDeclProduit);

            $diffB = array_diff($refDeclProduit, $ref);

            if(!empty($diffA) || !empty($diffB) || $new)
            {
                $declRefFinal = [];

                foreach($ref as $declRef)
                {
                    $intDecl = intval(substr($declRef, -2));

                    foreach($produit->getDeclinaison() as $declinaison)
                    {
                        if(intval($declinaison) == $intDecl)
                        {
                            $declRefFinal[$declinaison] = $declRef;
                        }
                    }
                    
                }

                $produit->setReferenceDeclinaison($declRefFinal);
            }
        }
        else
        {
            $produit->setReferenceDeclinaison([]);
        }


       
        if( ($produit->getMarque() and strtolower($produit->getMarque()->getNom()) != strtolower($Marque)) || (!$produit->getMarque() and !empty($Marque)) )
        {
            $newMarque = $this->marqueRepository->findBrandLike($Marque);

            if(!$newMarque)
            {
                $newMarque = new Marque;
                $newMarque->setNom($Marque);
                
                $this->em->persist($newMarque);
                $this->em->flush();

            }

            $produit->setMarque($newMarque);
            
        }

        $this->em->flush();
      
        
    }

    public function checkArome(array $aromes, Produit $produit)
    {

        $lengthA = count($aromes);
        if( $lengthA > 0)
        {
           
            $countA = 0;
            
            foreach($aromes as $arome)
            {
                
                $countA++;

                if(strlen($arome) > 0 && !empty($arome))
                {
                    $arome = ltrim($arome);
                    $aromeExist = $this->aromeRepository->findLike($arome);

                    $aromeExist = array_unique($aromeExist);
                   
                    if($aromeExist)
                    {
                        $countB = 0;
                        $lengthB = count($produit->getProduitAromes());

                        if($lengthA < $lengthB)
                        {
                            foreach($produit->getProduitAromes() as $prodArome)
                            {
                                $countB++;
                                
                                if($countA == $countB)
                                {
                                    $prodArome->setArome($aromeExist[0]);
                                }
                                
                                if($countA == $lengthA && $countB > $lengthA)
                                {
                                    $this->em->remove($prodArome);
                                }
                                
                            }
                        }
                        elseif($lengthA > $lengthB)
                        {
                        
                            foreach($produit->getProduitAromes() as $prodArome)
                            {
                                $countB++;
                                
                                if($countA == $countB)
                                {
                                    $prodArome->setArome($aromeExist[0]);
                                }

                            }

                            if($countA > $countB)
                            {
                                
                                $produitArome = new ProduitArome;
                                $produitArome->setProduit($produit);
                                $produitArome->setArome($aromeExist[0]);

                                $this->em->persist($produitArome);
                                
                            }
                        }
                        else
                        {
                            foreach($produit->getProduitAromes() as $prodArome)
                            {
                                
                                if($countA == $countB)
                                {
                                    $prodArome->setArome($aromeExist[0]);
                                }

                                $countB++;
                            }
                        }
                    }
                    else
                    {
                        $newArome = new Arome;
                        $newArome->setNom($arome);
                        $this->em->persist($newArome);

                        $produitArome = new ProduitArome;
                        $produitArome->setProduit($produit);
                        $produitArome->setArome($newArome);

                        $this->em->persist($produitArome);

                    }
                }
            }

        }
        
       
    }

}