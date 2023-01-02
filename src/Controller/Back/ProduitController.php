<?php


namespace App\Controller\Back;

use App\Entity\Base;
use App\Entity\Arome;
use App\Entity\Gamme;
use App\Entity\Client;
use App\Entity\Marque;
use App\Form\BaseType;
use App\Entity\Produit;
use App\Form\AromeType;
use App\Form\GammeType;
use App\Form\MarqueType;
use App\Entity\Categorie;
use App\Entity\Composant;
use App\Entity\Contenant;
use App\Entity\Reference;
use App\Form\ProduitType;
use App\Service\AeromaApi;
use App\Entity\Declinaison;
use App\Entity\Fournisseur;
use App\Entity\ProduitBase;
use App\Form\CategorieType;
use App\Form\ComposantType;
use App\Form\ContenantType;
use App\Form\ReferenceType;
use App\Entity\ProduitArome;
use App\Entity\BaseComposant;
use App\Entity\MarqueBlanche;
use App\Entity\PrincipeActif;
use App\Form\FournisseurType;
use App\Entity\ClientExclusif;
use App\Entity\GammeMarqueBlanche;
use App\Entity\PositionGammeClient;
use App\Entity\UnitMarqueGamme;
use App\Service\CsvAddProducts;
use App\Repository\BaseRepository;
use App\Repository\AromeRepository;
use App\Repository\GammeRepository;
use App\Repository\TarifRepository;
use App\Entity\ProduitMarqueBlanche;
use App\Form\Etape2ProduitType;
use App\Repository\ClientRepository;
use App\Repository\MarqueRepository;
use App\Repository\ProduitRepository;
use App\Repository\CategorieRepository;
use App\Repository\ComposantRepository;
use App\Repository\ContenantRepository;
use App\Repository\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\DeclinaisonRepository;
use App\Repository\FournisseurRepository;
use App\Repository\ProduitBaseRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Repository\ProduitAromeRepository;
use App\Repository\TypeDeClientRepository;
use App\Repository\BaseComposantRepository;
use App\Repository\MarqueBlancheRepository;
use App\Repository\PrincipeActifRepository;
use App\Repository\ClientExclusifRepository;
use Symfony\Component\Filesystem\Filesystem;
use App\Repository\UnitMarqueGammeRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\GammeMarqueBlancheRepository;
use App\Repository\PositionGammeClientRepository;
use Symfony\Component\HttpKernel\KernelInterface;
use App\Repository\ProduitMarqueBlancheRepository;
use App\Service\LogistiqueServices;
use App\Service\Useful;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as ReaderXlsx;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\TokenNotFoundException;

class ProduitController extends AbstractController
{


    public function modifListeProduits(KernelInterface $kernel, EntityManagerInterface $em, DeclinaisonRepository $declinaisonRepo, ProduitRepository $produitRepo, CategorieRepository $categorieRepo, BaseRepository $supportRepo, AromeRepository $aromeRepo, PrincipeActifRepository $principeRepo, MarqueRepository $marqueRepo)
    {
        $fileName = $kernel->getProjectDir()."/public/info.xlsx";

        $reader = new ReaderXlsx;

        $spreadsheet = $reader->load($fileName);
        $sheet = $spreadsheet->getActiveSheet();

        $datas = $sheet->toArray();

        unset($datas[0]);

        $dont = [];

        foreach($datas as $data)
        {
            $reference = $data[0];
            $nom = $data[1];
            $categorie = $data[2];
            $support = $data[3];
            $arome = $data[4];
            $principe = $data[5];
            $declinaison = $data[6];
            $marque = $data[7];

            $produit = $produitRepo->findOneBy(['reference'=> $reference]);

            $produit->setReference($reference);
            $produit->setNom($nom);

            $produit->setCategorie($categorieRepo->findCategoryLike($categorie));

            $aromeArray = explode("+", $arome);

           
          

            if( !empty($aromeArray) )
            {
                $i = 1;

                foreach($aromeArray as $arom)
                {
                    $aro = $aromeRepo->findLike($arom);

                    if(! $aro)
                    {
                        $aro = new Arome;

                        $aro->setNom($arom);
                        $em->persist($aro);
                    }

                    if($aro)
                    {
                        $prodAro = new ProduitArome;
                        $prodAro->setProduit( $produit);
                        $prodAro->setArome($aro);
                        $prodAro->setNumArome("arome-$i");
                        $em->persist($prodAro);
                        $i++;
                    }
                    else
                    {
                        if(!in_array($arom, $dont))
                        {
                            array_push($dont, $arom);
                        }
                    }
                }

            }
            
            $support = $supportRepo->findSupportLike($support);

            if($support)
            {
                $produit->setBase($support);
            }
           
            $principe = $principeRepo->findPrincipeLike($principe);

            if($principe)
            {
                $produit->setPrincipeActif($principe);

                $decl = intval(substr("declinaison+", -2));

                if($decl == 0)
                {
                    $decl = "00";
                }

                $produit->setDeclinaison([$decl.'mg']);
            }
            else
            {
                if( $produit->getDeclinaison() && count($produit->getDeclinaison()) > 0)
                {
                    $produit->setDeclinaison([]);
                }
            }

            $marque = $marqueRepo->findBrandLike($marque);

            $produit->setMarque($marque);

            $em->flush();

        }
        dd($dont,"stop");
    }

    public function testPrix(KernelInterface $kernel, ClientRepository $clientRepository, MarqueBlancheRepository $mbRepo, ProduitRepository $produitRepo)
    {
        $fileName = $kernel->getProjectDir()."/public/testMb.xlsx";

        $reader = new ReaderXlsx;

        $spreadsheet = $reader->load($fileName);

        $nbSheets = $spreadsheet->getSheetCount();

        $change = [];

        $i = 0;
        while($i <=  1)
        {
            $sheet = $spreadsheet->getSheet($i);
            $title = $sheet->getTitle();

            $datas = $sheet->toArray();
            unset($datas[0]);
            unset($datas[1]);
            unset($datas[2]);

            $client = $clientRepository->findOneBy(['raisonSocial' => rtrim(ltrim($title)) ]);

            $typeClient = $client->getTypeDeClient();
            
            foreach($datas as $data)
            {
                if( isset($data[5]) && $data[5] != "Prix" )
                {
                    $prixExcel = $data[5];
                    $produit = $produitRepo->findOneBy(['reference' => $data[0]]);

                    $marqueBlanche = $mbRepo->findOneBy(['client' => $client, 'produit' => $produit]);

                    if(!$marqueBlanche)
                    {
                        dd($data);
                    }
                    $tarifMb = $marqueBlanche->getTarif();

                    if( $tarifMb && $tarifMb->getClient() == $client)
                    {

                        if($tarifMb->getMemeTarif())
                        {
                            if($typeClient->getNom() == "Grossiste")
                            {
                                $prix = $tarifMb->getPrixDeReferenceGrossiste();
                            }
                            else
                            {
                                $prix = $tarifMb->getPrixDeReferenceDetaillant();
                            }

                            if($prix != $prixExcel)
                            {
                                array_push($change, $data);
                            }
                        }
                        else
                        {
                            if(strpos($data[0], "GAMME") !== false && $data[0] != "NEW CBD SATIVAP"  && $data[0] != "YZY CBD")
                            {
                                dd($data, 'par declinaison', $prixExcel, $change);
                            }
                        }
                    }
                    else
                    {
                        dd('tsy tarif', $data, $change);
                    }
                    
                }
            }

        }
        dd('stop', $change);

    }

    public function importExcel(KernelInterface $kernel, GammeMarqueBlancheRepository $gammeMarqueBlancheRepo, ProduitRepository $produitRepository, PositionGammeClientRepository $positionGammeClientRepository, GammeRepository $gammeRepository, EntityManagerInterface $em, LogistiqueServices $logistiqueServices, ClientRepository $clientRepository, GammeMarqueBlancheRepository $gammeMarqueBlancheRepository, MarqueBlancheRepository $marqueBlancheRepository)
    {
        $fileName = $kernel->getProjectDir()."/public/testMb.xlsx";

        $reader = new ReaderXlsx;

        $spreadsheet = $reader->load($fileName);

        $nbSheets = $spreadsheet->getSheetCount();


        $i = 0;
        while($i <=  $nbSheets)
        {

            $sheet = $spreadsheet->getSheet($i);
            $title = $sheet->getTitle();

            $datas = $sheet->toArray();
            unset($datas[0]);
            //unset($datas[1]);

            $client = $clientRepository->findOneBy(['raisonSocial' => rtrim(ltrim($title)) ]);
            $positionG = 1;

            $gammeFinal = null;
            $gammePrecedent = null;

            $produitMarqueBlanches = [];

            $listeMb = [];

            $position = 1;
           
            $double = false;
            foreach($datas as $data)
            {
                if(!empty($data))
                {
                    
                    if(strpos($data[0], "GAMME") !== false)
                    {   
                        if( isset($data[4]) && $data[4] == 1)
                        {
                            $double = true;
                        }
                        else
                        {
                            $double = false;
                        }


                        if(!$client)
                        {
                            dd($data, $title,);
                        }
                        
                        $gammeFinal = $logistiqueServices->getGammeMb($data, $client);
        
                        
                        if($gammeFinal instanceof Gamme)
                        {
                            $gammeCus = $gammeMarqueBlancheRepo->findOneBy(['nom' => $gammeFinal->getNom()]);

                            if($gammeCus)
                            {
                                $gammeFinal = $gammeCus;
                            }
                        }

                        $pg = $positionGammeClientRepository->findOneBy(['client' => $client, 'position' => $positionG]);

                        if($pg)
                        {
                            if($gammeFinal instanceof Gamme)
                            {
                                if($gammeFinal != $pg->getGammePrincipale())
                                {
                                    $pg->setPosition(null);
                                }
                            }
                            else
                            {
                                if($gammeFinal != $pg->getCustomGamme())
                                {
                                    $pg->setPosition(null);
                                }
                            }
                        }

                        
                        $position = 1;
                        
                        if(count($produitMarqueBlanches) > 0)
                        {
                            foreach($produitMarqueBlanches as $produit)
                            {
                                $mbExist = $marqueBlancheRepository->getMbByGamme($client, $gammePrecedent, $produit);
                                //dd($mbExist, $produitMarqueBlanches);
                                if($mbExist)
                                {
                                    $em->remove($mbExist[0]);
                                }
                               
                            }
                        }
    
                        $listeMb = $marqueBlancheRepository->getMBProducts($client, $gammeFinal);
        
                        foreach($listeMb as $mb)
                        {
                            array_push($produitMarqueBlanches, $mb->getProduit());
                        }

                        $principale = false;
    
                        if($gammeFinal instanceof Gamme)
                        {
                            $principale = true;
                            $positionGC = $positionGammeClientRepository->findOneBy(['client' => $client, "GammePrincipale" => $gammeFinal ]);
                        }
                        else
                        {
                            $positionGC = $positionGammeClientRepository->findOneBy(['client' => $client, "CustomGamme" => $gammeFinal ]);
                        }

                        if(!$positionGC)
                        {
                            $positionGC = new PositionGammeClient;
        
                            $positionGC->setClient($client);
        
                          

                            $positionGC->setPosition($positionG);
                            
                            $positionG++;
        
                            $em->persist($positionGC);
                            $em->flush();
        
                        }
                        else
                        {
                           
                            $positionGC->setPosition($positionG);
                            
                            $positionG++;
                            
                        }

                        if($principale)
                        {
                            $positionGC->setGammePrincipale($gammeFinal);
                        }
                        else
                        {
                            $positionGC->setCustomGamme($gammeFinal);
                        }

                        if($double)
                        {
                            $positionGC->setGreendot(true);
                        }

                       

                    }
                    
                    if($data[0] && $data[0] != "Reference produit" && $data[1] && $data[2]  && $data[3])
                    {

                        $declinaisons = $logistiqueServices->importExcelCreateDecl($data);
                        
                        $produit = $produitRepository->findOneBy(['reference' => $data[0]]);
    
                        $marqueB = $marqueBlancheRepository->getMbByGamme($client, $gammeFinal, $produit);
    
                        $mb = null;

                        if( !empty($marqueB) )
                        {
                            $mb = $marqueB[0];
                        }

                        if(count($marqueB) > 1)
                        {
                            $mb = $marqueB[0];

                            for($i = 1; $i < count($marqueB); $i++)
                            {
                                dd($marqueB[$i], $marqueB);
                                $em->remove($marqueB[$i]);
                            }
                        }


                        if( ! $double )
                        {

                            $mbExist = $marqueBlancheRepository->findBy(['client' => $client, 'produit' => $produit]);
                            
                            if(count($mbExist) >= 1)
                            {
                                $mb = $mbExist[0];
                            }

                        }

                        if(!$mb)
                        {
                            if(!$produit)
                            {
                                $produit = $logistiqueServices->createProduct($data, $gammeFinal, $declinaisons);
                            }
    
                            if( strtolower($produit->getMarque()) != "ceres")
                            {
                    
                                $mb = new MarqueBlanche;
                                
                                if($data[1])
                                {
                                    $mb->setReference($data[1]);
                                }
                                else
                                {
                                    $mb->setReference($data[2]);
                                }
        
                                if($double)
                                {
                                    $mb->setGreendot(true);
                                }

                                $mb->setProduit($produit);
                                $mb->setClient($client);
                                $mb->setNom($data[2]);
                                $mb->setMarque($client->getRaisonSocial());
                                $mb->setMemeEan13(true);
        
                                if($gammeFinal instanceof GammeMarqueBlanche )
                                {
                                    $mb->setGammeMarqueBlanche($gammeFinal);
                                }
        
                                $mb->setPosition($position);
    
                                $em->persist($mb);
                                $em->flush();
  
                            }
                        }
                        else
                        {

                            if($gammeFinal instanceof Gamme and $gammeFinal->getNom() != $mb->getProduit()->getGamme()->getNom())
                            {
                                $exist = $gammeMarqueBlancheRepo->findOneByNom($gammeFinal->getNom());

                                if(!$exist)
                                {
                                    $newGamme = new GammeMarqueBlanche;
        
                                    $newGamme->setNom($gammeFinal->getNom());
                    
                                    $em->persist($newGamme);
                                    $em->flush();
                                    
                                    $gammeFinal = $newGamme;
                                }
                                else
                                {
                                    $gammeFinal = $exist;
                                }
                            }

                            $index = array_search($produit, $produitMarqueBlanches);
                            unset($produitMarqueBlanches[$index]);
    
                            if($data[1])
                            {
                                $mb->setReference($data[1]);
                            }
                            else
                            {
                                $mb->setReference($data[2]);
                            }

                            if($double)
                            {
                                $mb->setGreendot(true);
                            }
    
                            $mb->setProduit($produit);
                            $mb->setClient($client);
                            $mb->setNom($data[2]);
                            $mb->setMarque($client->getRaisonSocial());
                            $mb->setMemeEan13(true);
    
                            if($gammeFinal instanceof GammeMarqueBlanche )
                            {
                                $mb->setGammeMarqueBlanche($gammeFinal);
                            }
                            else
                            {
                                $mb->setGammeMarqueBlanche(null);
                            }
    
                            $mb->setPosition($position);
    
                        }
                        
                        $gammePrecedent = $gammeFinal;
                        
                        $position++;
                    }

                }

                $em->flush();
            }
            

            $i++;
        }

        dd('ok');
    }


    public function exportMb(MarqueBlancheRepository $marqueBlancheRepository, ClientRepository $clientRepository, GammeRepository $gammeRepository, KernelInterface $kernel, LogistiqueServices $sercice)
    {
        $mbClients = $marqueBlancheRepository->getClients();

        $clients = [];

        foreach($mbClients as $mb)
        {
            array_push($clients, $mb->getClient());
        }
       
        $spreadsheet = new Spreadsheet();

        $count = 0;
        foreach($clients as $client)
        {

            $cellule = 1;

            $spreadsheet->createSheet();
            // Zero based, so set the second tab as active sheet
            $spreadsheet->setActiveSheetIndex($count);
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle($client->getRaisonSocial());
            
            $sheet->getColumnDimension('A')->setWidth(40);
            $sheet->getColumnDimension('B')->setWidth(40);
            $sheet->getColumnDimension('C')->setWidth(70);
            $sheet->getColumnDimension('D')->setWidth(70);
          
            $sheet->mergeCells("A$cellule:D$cellule");
            $sheet->setCellValue("A$cellule", "Client : {$client->getRaisonSocial()}");

            $sheet->getStyle("A$cellule")->getFont()->setSize(15)->setBold(true);
            $sheet->getStyle("A$cellule:D$cellule")->getAlignment()->setHorizontal('center');
            

            $marqueBlanches = $client->getMarqueBlanches();

            $listeGamme = [];
            foreach($marqueBlanches as $mb)
            {

                if (!in_array($mb->getProduit()->getGamme(), $listeGamme)) 
                {
                    array_push($listeGamme, $mb->getProduit()->getGamme());
                }

            }

            $results = $sercice->groupeByGamme($listeGamme, $marqueBlanches, $client);

           
            $gammeExist = [];

            if($client->getCeres())
            {

                foreach($results as $index => $produits)
                {
                    $gamme = $index;

                    if(!in_array($gamme, $gammeExist))
                    {
    
                        $cellule += 2;
                        
                        $sheet->mergeCells("A$cellule:D$cellule");
                        
                        $sheet->setCellValue("A$cellule", "GAMME : $gamme");
                        
                        $sheet->getStyle("A$cellule")->getFont()->setSize(15)->setBold(true);
                        $sheet->getStyle("A$cellule:D$cellule")->getAlignment()->setHorizontal('center');
        
                        $cellule++;

                        $sheet->setCellValue("A$cellule", "Reference produit");
                        $sheet->getStyle("A$cellule")->getFont()->setSize(13)->setBold(true);

                        $sheet->setCellValue("B$cellule", "Reference produit client");
                        $sheet->getStyle("B$cellule")->getFont()->setSize(13)->setBold(true);

                        $sheet->setCellValue("C$cellule", "Désignation Client");
                        $sheet->getStyle("C$cellule")->getFont()->setSize(13)->setBold(true);

                        $sheet->setCellValue("D$cellule", "Désignation Aéroma");
                        $sheet->getStyle("D$cellule")->getFont()->setSize(13)->setBold(true);


                        $cellule++;
                    }
                    // manambotra ceres
                    foreach($produits as $produit)
                    {
                        $mb = $marqueBlancheRepository->findOneBy(['produit' => $produit, 'client' => $client]);

                        /*$position = $mb->getPosition();
                        
                        if(is_null($position))
                        {
                            $mb = $marqueBlancheRepository->findOneBy(['produit' => $produit, 'client' => $clientRepository->find(1)]);

                           
                            $position = $mb->getPosition();

                        }
                        
                        if(is_null($position) || $position == 0)
                        {
                            $position = "aucune";
                        }*/

                        $sheet->setCellValue("A$cellule", $produit->getReference());

                        if($mb)
                        {
                            $sheet->setCellValue("B$cellule", $mb->getReference());
                            $sheet->setCellValue("C$cellule", $mb->getNom());

                        }
                        else
                        {
                            $sheet->setCellValue("B$cellule", $produit->getReference());

                            $sheet->setCellValue("C$cellule", $produit->getNom());
                        }

                        $sheet->setCellValue("D$cellule", $produit->getNom());
        
                        $cellule++;
                    }
                }
            }
            else
            {

                foreach($results as $gamme => $produits)
                {
       
                    if(count($produits) > 0)
                    {
        
                        if(!in_array($gamme, $gammeExist))
                        {
        
                            $cellule += 2;
                            
                            $sheet->mergeCells("A$cellule:D$cellule");
                            
                            $sheet->setCellValue("A$cellule", "GAMME : $gamme");
                            
                            $sheet->getStyle("A$cellule")->getFont()->setSize(15)->setBold(true);
                            $sheet->getStyle("A$cellule:C$cellule")->getAlignment()->setHorizontal('center');
            
                            $cellule++;
    
                            $sheet->setCellValue("A$cellule", "Reference produit");
                            $sheet->getStyle("A$cellule")->getFont()->setSize(13)->setBold(true);

                            $sheet->setCellValue("B$cellule", "Reference produit client");
                            $sheet->getStyle("B$cellule")->getFont()->setSize(13)->setBold(true);

                            $sheet->setCellValue("C$cellule", "Désignation Client");
                            $sheet->getStyle("C$cellule")->getFont()->setSize(13)->setBold(true);

                            $sheet->setCellValue("D$cellule", "Désignation Aéroma");
                            $sheet->getStyle("D$cellule")->getFont()->setSize(13)->setBold(true);
    
                            $cellule++;
                        }
        
    
                        foreach($produits as $produit)
                        {
                            $mb = $marqueBlancheRepository->findOneBy(['produit' => $produit, 'client' => $client]);

                            $sheet->setCellValue("A$cellule", $produit->getReference());
                            $sheet->setCellValue("B$cellule", $mb->getReference());
                            $sheet->setCellValue("C$cellule", $mb->getNom());
                            $sheet->setCellValue("D$cellule", $produit->getNom());
            
                            $cellule++;
                        }
                    }
    
                    
    
                }
            }
            
        
            $count++;
        }




        $fileName = "testMb.xlsx";

        $writer = new Xlsx($spreadsheet);
        
        $temp_file = "excel/bonPreparation/" . $fileName;

        $filesystem = new Filesystem();


        $publicRoot = "{$kernel->getProjectDir()}/public";

        if ($filesystem->exists($publicRoot . '/excel/bonPreparation/' . $fileName)) {
            $filesystem->remove($publicRoot . '/excel/bonPreparation/' . $fileName);
        }

        $writer->save($temp_file);

        $response = ['temp_file' => $temp_file, 'fileName' => $fileName];

        return $this->file($response['temp_file'], $response['fileName'], ResponseHeaderBag::DISPOSITION_INLINE);
        
    }


    public function getProducts(ProduitRepository $productRepo, KernelInterface $kernel, EntityManagerInterface $em, GammeMarqueBlancheRepository $gammeMarqueBlancheRepository)
    {
        $products = $productRepo->findAll();
/*
        $cellule = 1;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->setCellValue("A1", "Reference" );
        $sheet->getStyle("A$cellule")->getFont()->setSize(15)->setBold(true);


        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->setCellValue("B1", "Nom" );
        $sheet->getStyle("B$cellule")->getFont()->setSize(15)->setBold(true);


        $sheet->getColumnDimension('C')->setWidth(40);
        $sheet->setCellValue("C1", "Catégorie" );
        $sheet->getStyle("C$cellule")->getFont()->setSize(15)->setBold(true);

        $sheet->getColumnDimension('D')->setWidth(40);
        $sheet->setCellValue("D1", "Gamme" );
        $sheet->getStyle("D$cellule")->getFont()->setSize(15)->setBold(true);

        $sheet->getColumnDimension('E')->setWidth(40);
        $sheet->setCellValue("E1", "Support" );
        $sheet->getStyle("E$cellule")->getFont()->setSize(15)->setBold(true);


        $sheet->getColumnDimension('F')->setWidth(70);
        $sheet->setCellValue("F1", "Aromes" );
        $sheet->getStyle("F$cellule")->getFont()->setSize(15)->setBold(true);


        $sheet->getColumnDimension('G')->setWidth(30);
        $sheet->setCellValue("G1", "Principe actif" );
        $sheet->getStyle("G$cellule")->getFont()->setSize(15)->setBold(true);


        $sheet->getColumnDimension('H')->setWidth(40);
        $sheet->setCellValue("H1", "liste des déclinaisons" );
        $sheet->getStyle("H$cellule")->getFont()->setSize(15)->setBold(true);

        $sheet->getColumnDimension('I')->setWidth(80);
        $sheet->setCellValue("I1", "Référence déclinaison" );
        $sheet->getStyle("I$cellule")->getFont()->setSize(15)->setBold(true);

        $sheet->getColumnDimension('J')->setWidth(30);
        $sheet->setCellValue("J1", "Marque" );
        $sheet->getStyle("J$cellule")->getFont()->setSize(15)->setBold(true);


        //$header = ["ID", "Reference", "Nom", "Type", "Categorie", "Contenant", "Gamme", "Support", "Aromes", "Pricipe actif", "Déclinaisons", "Référence déclinaison", "Marque"];


        foreach($products as $product)
        {
            $cellule++;

            $decl = $product->getDeclinaison();
            $declinaisons = null;

            if($decl && count($decl) > 1)
            {
                $declinaisons = implode(', ', $decl );
            }

            $referencesDecl = $product->getReferenceDeclinaison();

            $ref = null;
            if($referencesDecl && count($referencesDecl) > 1)
            {
                $ref = implode(", ", $referencesDecl);
            }

            $sheet->setCellValue("A$cellule", $product->getReference());
            $sheet->setCellValue("B$cellule", $product->getNom());
            $sheet->setCellValue("C$cellule", $product->getCategorie());
            $sheet->setCellValue("D$cellule", $product->getGamme());
            $sheet->setCellValue("E$cellule", $product->getBase());
            $sheet->setCellValue("F$cellule", $product->getAromes());

            if($product->getId() > 248)
            {
                $sheet->setCellValue("G$cellule", "");
                $sheet->setCellValue("H$cellule", "");
                $sheet->setCellValue("I$cellule", "");
                $sheet->setCellValue("J$cellule", "");

            }
            else
            {
                $sheet->setCellValue("G$cellule", ($product->getPrincipeActif())? $product->getPrincipeActif()->getPrincipeActif() : "");
                $sheet->setCellValue("H$cellule", $declinaisons);
                $sheet->setCellValue("I$cellule", $ref);
                $sheet->setCellValue("J$cellule", $product->getMarque());
            }
        }

        $fileName = "produitsManquants.xlsx";

        $writer = new Xlsx($spreadsheet);
        
        $temp_file = "excel/bonPreparation/" . $fileName;

        $filesystem = new Filesystem();


        $publicRoot = "{$kernel->getProjectDir()}/public";

        if ($filesystem->exists($publicRoot . '/excel/bonPreparation/' . $fileName)) {
            $filesystem->remove($publicRoot . '/excel/bonPreparation/' . $fileName);
        }

        $writer->save($temp_file);

        $response = ['temp_file' => $temp_file, 'fileName' => $fileName];

        return $this->file($response['temp_file'], $response['fileName'], ResponseHeaderBag::DISPOSITION_INLINE);
 */    
/*        //etape 1
        foreach($products as $prod)
        {
            $mbs = $prod->getMarqueBlanches();

            foreach($mbs as $mb)
            {
                if($mb->getClient()->getId() == 1 && $mb->getProduit() == $prod)
                {
                    $mb->setPosition($prod->getPosition());
                    $em->flush();
                }
            }
        }
*/
        //etape 2
/*
        foreach($products as $prod)
        {
            $mbs = $prod->getMarqueBlanches();

            foreach($mbs as $mb)
            {
                if($mb->getClient()->getId() == 1 && $mb->getProduit() == $prod)
                {

                    if($prod->getPlaisir())
                    {
                        $mb->setGammeMarqueBlanche($gammeMarqueBlancheRepository->find(1));
                    }


                    if($prod->getConstellation())
                    {
                        $mb->setGammeMarqueBlanche($gammeMarqueBlancheRepository->find(2));
                    }

                    if($prod->getGreenLeaf())
                    {
                        $mb->setGammeMarqueBlanche($gammeMarqueBlancheRepository->find(3));
                    }

                    if($prod->getSaltyDog())
                    {
                        $mb->setGammeMarqueBlanche($gammeMarqueBlancheRepository->find(4));
                    }


                    if($prod->getYzyCities() && $prod->getSpring())
                    {
                        $mb->setGammeMarqueBlanche($gammeMarqueBlancheRepository->find(5));
                    }

                    if($prod->getYzyCities() && $prod->getSummer())
                    {
                        $mb->setGammeMarqueBlanche($gammeMarqueBlancheRepository->find(6));
                    }

                    if($prod->getSativap())
                    {
                        $mb->setGammeMarqueBlanche($gammeMarqueBlancheRepository->find(7));
                    }

                    if($prod->getCbdYzy())
                    {
                        $mb->setGammeMarqueBlanche($gammeMarqueBlancheRepository->find(8));
                    }

                    if($prod->getAbsolu())
                    {
                        $mb->setGammeMarqueBlanche($gammeMarqueBlancheRepository->find(9));
                    }

                  


                    $em->flush();
                }
            }
        }

        //etape 3 mameno table position gamme
*/
        dd('ok');
        $rows = [];

        $header = ["ID", "Reference", "Nom", "Type", "Categorie", "Contenant", "Gamme", "Support", "Aromes", "Pricipe actif", "Déclinaisons", "Référence déclinaison", "Marque"];

        $rows[] = implode(';', $header);
        foreach ($products as $product) {

            $decl = $product->getDeclinaison();
            $declinaisons = null;

            if($decl && count($decl) > 1)
            {
                $declinaisons = implode(', ', $decl );
            }

            $referencesDecl = $product->getReferenceDeclinaison();

            $ref = null;
            if($referencesDecl && count($referencesDecl) > 1)
            {
                $ref = implode(", ", $referencesDecl);
            }

            $data = array($product->getId(), $product->getReference(), $product->getNom(), $product->getType(), $product->getCategorie(), $product->getContenant(), $product->getGamme(), $product->getBase(), $product->getAromes(), $product->getPrincipeActif() , $declinaisons, $ref, $product->getMarque());
    
            $rows[] = implode(';', $data);
        }
    
        $content = implode("\n", $rows);
       
    
        $csv = mb_convert_encoding($content, 'UTF-16LE', 'UTF-8');

        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv');
        $filename = "products" . '.csv';
        $response->headers->set('Content-disposition', 'attachment; filename="'.$filename.'"');


        return $response;

    }

    public function testCsv(KernelInterface $kernel, CsvAddProducts $csvAddProducts, ProduitRepository $produitRepository, EntityManagerInterface $em)
    {
        $lignes = file($kernel->getProjectDir()."/public/products.csv");

        $datas = [];
        foreach($lignes as $k => $ligne)
        {
            if($k > 0)
            {
               
                $ligne = utf8_encode($ligne);
                
                array_push($datas, str_getcsv(  trim($ligne), ";") );
            }
        }

        foreach($datas as $data)
        {
            if(count($data) == 13)
            {
                $id = $data[0];

                $product = null;

                if($id)
                {
                    $product = $produitRepository->find($id);
                }
                
                if(!$product)
                {
                    $product = new Produit;
                    $product->setNom("temp");
                    $em->persist($product);
                    $em->flush();

                    $csvAddProducts->productExist($data, $product, true);
                }
                else
                {
                    $csvAddProducts->productExist($data, $product);
                }
                
                
                
            }
        }
        
        return $this->redirectToRoute('back_produit_list');
    }

    //produits
    public function listeProduit(ProduitRepository $produitRepo, AeromaApi $aeromaApi, EntityManagerInterface $em)
    {

        return $this->render('back/produit/index.html.twig', [
            'produits' => $produitRepo->findBy([], ['id' => 'desc']),
            'produit' => true,
            'product' => true
        ]);
    }

    public function ficheProduit(Produit $produit, ProduitBaseRepository $proBase, TypeDeClientRepository $typeRepo, MarqueBlancheRepository $mbRepo, ProduitAromeRepository $proAro)
    {

        return $this->render('back/produit/ficheProduit.html.twig', [
            'product' => true,
            'produits' => $produit,
            'produit' => true,
            'proAro' => $proAro->findBy(['produit' => $produit->getId()]),
            'proBase' => $proBase->findBy(['produit' => $produit->getId()]),
            'marqueBlanche' => $mbRepo->findBy(['produit' => $produit->getId()]),
            'types' => $typeRepo->findAll()
        ]);
    }

    public function creationProduit(Request $request, TarifRepository $tarifRepo, PrincipeActifRepository $principeRepo, BaseRepository $base, ClientRepository $clientRepo, MarqueBlancheRepository $mbRepo, FournisseurRepository $fournisseur, EntityManagerInterface $em, GammeRepository $gamme, MarqueRepository $marque, ContenantRepository $contenant, CategorieRepository $categorie, AromeRepository $arome, ProduitRepository $produitRepo)
    {
        $produit = new Produit;
        
        $form = $this->createForm(ProduitType::class, $produit);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if($request->request->get('declinaison'))
            {
                $decs = $request->request->get('declinaison');
                
                $produit->setDeclinaison(array_unique($decs));
            }

            $em->persist($produit);

            $em->flush();
            return $this->redirectToRoute('back_produit_creation_etape_2', ['id' => $produit->getId(), 'create' => true]);
        }

        return $this->render('back/produit/creationProduit.html.twig', [
            'product' => true,
            'produits' => true,
            'produit' => true,
            'form' => $form->createView(),
            'arome' => $arome->findAll(),
            'base' => $base->findAll(),
            'clientListe' => $clientRepo->findAll(),
            'principeActif' => $principeRepo->findAll(),
            'tarifListe' => $tarifRepo->findBy(['client' => !null])
        ]);
    }

    public function modificationProduit(Produit $produit, ClientRepository $clientRepo, TarifRepository $tarifRepo, MarqueBlancheRepository $mbRepo, PrincipeActifRepository $principeRepo, ProduitBaseRepository $produitBaseRepo, BaseRepository $baseRepo, FournisseurRepository $fournisseurRepo, Request $request, GammeRepository $gamme, CategorieRepository $categorie, ContenantRepository $contenant, MarqueRepository $marque, EntityManagerInterface $em, ProduitRepository $produitRepo, AromeRepository $arome, ProduitAromeRepository $produitArome)
    {
        $produits = $produitRepo->find($produit->getId());
        $form = $this->createForm(ProduitType::class, $produits);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $listeDeclinaison = [];

            if($request->request->get('declinaison'))
            {
                $decs = $request->request->get('declinaison');
                
                $produit->setDeclinaison(array_unique($decs));

                $ean = $produits->getCodeEAN13();

                if ($ean == null) {
                    $ean = [];
                }

                $countEan = count($ean);
                $countNew = count($listeDeclinaison);

                if ($countNew > $countEan) {

                    foreach ($listeDeclinaison as $decl) {

                        if (!in_array($decl, array_flip($ean))) {
                            $ean[$decl] = '';
                        }
                    }
                } elseif ($countNew < $countEan) {

                    foreach ($ean as $index => $val) {

                        if (!in_array($index, $listeDeclinaison)) {
                            unset($ean[$index]);
                        }
                    }
                } else {

                    foreach ($ean as $index => $val) {

                        if (!in_array($index, $listeDeclinaison)) {
                            unset($ean[$index]);
                            foreach ($listeDeclinaison as $decl) {
                                if (!in_array($decl, array_flip($ean))) {
                                    $ean[$decl] = '';
                                }
                            }
                        }
                    }
                }

                $produits->setCodeEAN13($ean);
                $mb = $mbRepo->findBy(['produit' => $produits]);


                foreach ($mb as $m) {

                    $eanMb = $m->getCodeEAN13();
                    $countEanMB = count($eanMb);

                    if ($countNew > $countEanMB) {
                        foreach ($listeDeclinaison as $decl) {

                            if (!in_array($decl, array_flip($eanMb))) {
                                $eanMb[$decl] = '';
                            }
                        }
                    } elseif ($countNew < $countEanMB) {

                        foreach ($eanMb as $index => $val) {

                            if (!in_array($index, $listeDeclinaison)) {
                                unset($eanMb[$index]);
                            }
                        }
                    } else {

                        foreach ($eanMb as $index => $val) {

                            if (!in_array($index, $listeDeclinaison)) {
                                unset($eanMb[$index]);
                                foreach ($listeDeclinaison as $decl) {
                                    if (!in_array($decl, array_flip($eanMb))) {
                                        $eanMb[$decl] = '';
                                    }
                                }
                            }
                        }
                    }

                    $m->setCodeEAN13($eanMb);
                }

                $em->flush();
            }
            
            $em->flush();

            return $this->redirectToRoute('back_produit_modificatio_etape_2', ['id' => $produit->getId()]);
        }

        return $this->render('back/produit/modificationProduit.html.twig', [
            'product' => true,
            'produits' => true,
            'produit' => $produit,
            'form' => $form->createView(),
            'arome' => $arome->findAll(),
            'produitArome' => $produitArome->findBy(['produit' => $produit->getId()]),
            'base' => $baseRepo->findAll(),
            'produitBase' => $produitBaseRepo->findBy(['produit' => $produit->getId()]),
            'produitMB' => $mbRepo->findBy(['produit' => $produit->getId()]),
            'clientListe' => $clientRepo->findAll(),
            'principeActif' => $principeRepo->findAll(),
            'tarifListe' => $tarifRepo->findBy(['client' => !null])
        ]);
    }

    public function secondEtapeModification(Produit $produit, MarqueBlancheRepository $mbRepo, TarifRepository $tarifRepo, FournisseurRepository $fournisseur, MarqueRepository $marque, ClientRepository $clientRepo, Request $request, EntityManagerInterface $em)
    {

        $form = $this->createForm(Etape2ProduitType::class, $produit);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {

            $tarif = $tarifRepo->find($_POST['etape2_produit']['tarif']);
            $egal = false;
            if ($tarif) {
                if ($tarif->getCategorie() == $produit->getCategorie() && $tarif->getContenance() == $produit->getContenant() && $tarif->getDeclineAvec() == $produit->getPrincipeActif()) {

                    if ($tarif->getMarque() && $tarif->getMarque() == $produit->getMarque()) {

                        $egal = true;
                    } else {

                        $egal = true;
                    }
                }
            }
            
            if ($egal or !$tarif) {

                $ean = [];
                $refDecl = [];
                
                foreach ($produit->getDeclinaison() as $decli) {
                    if (isset($_POST[$decli])) {
                        $ean[$decli] = $_POST[$decli];
                    }

                    if (isset($_POST["reference_$decli"])) {
                        $refDecl[$decli] = $_POST["reference_$decli"];
                    }
                }

                $produit->setReferenceDeclinaison($refDecl);
                $produit->setCodeEAN13($ean);

               
                $em->flush();

                if ($_POST['etape2_produit']['marqueBlanche']) {

                    return $this->redirectToRoute('back_produit_reference_marque_blanche', ['id' => $produit->getId()]);
                } else {

                    $this->addFlash('successProduit', 'Modification produit effectuée avec succès');
                    return $this->redirectToRoute('back_produit_affichage', ['id' => $produit->getId()]);
                }
            } else {

                $this->addFlash('errorModifProduit', 'Le tarif que vous avez choisi ne correspond pas au configuration du produit ' . $produit->getNom());
                return $this->redirectToRoute('back_produit_creation_etape_2', ['id' => $produit->getId()]);
            }
        }

        if ($produit->getMarque() && !$produit->getTarif()) {
            $tarifProposer = $tarifRepo->findOneBy([
                'contenance' => $produit->getContenant(),
                'categorie' => $produit->getCategorie(),
                'base' => $produit->getBase(),
                'declineAvec' => $produit->getPrincipeActif(),
                'marque' => $produit->getMarque()
            ]);

            if (empty($tarifProposer)) {
                $tarifProposer = $tarifRepo->findOneBy([
                    'contenance' => $produit->getContenant(),
                    'categorie' => $produit->getCategorie(),
                    'base' => $produit->getBase(),
                    'declineAvec' => $produit->getPrincipeActif(),
                ]);
            }
        } else {
            $tarifProposer = null;
        }

        return $this->render('back/produit/etape2modificationProduit.html.twig', [
            'product' => true,
            'produits' => true,
            'produit' => $produit,
            'form' => $form->createView(),
            'clientListe' => $clientRepo->findAll(),
            'produitMB' => $mbRepo->findBy(['produit' => $produit->getId()]),
            'clientListe' => $clientRepo->findAll(),
            'declineAvec' => ($produit->getPrincipeActif()) ? $produit->getPrincipeActif()->getPrincipeActif() : null,
            'tarifListe' => $tarifRepo->findByClient(),
            'tarifProposer' => $tarifProposer
        ]);
    }

    public function suggestionTarif(TarifRepository $tarifRepo, Request $request, ProduitRepository $produitRepo)
    {
        if ($request->isXmlHttpRequest()) {

            $produit = $produitRepo->find($request->request->get('idProduit'));

            if ($request->request->get('idMarque')) {
                $marque = $request->request->get('idMarque');
            } else {
                $marque = null;
            }

            $suggestionTarif = $tarifRepo->findOneBy(
                [
                    'categorie' => $produit->getCategorie(),
                    'contenance' => $produit->getContenant(),
                    'base' => $produit->getBase(),
                    'declineAvec' => $produit->getPrincipeActif(),
                    'client' => null,
                    'marque' => $marque
                ],

                ['id' => 'desc']
            );

            $client = false;
            $marque = false;
            if ($suggestionTarif == null) {
                $suggestionTarif = $tarifRepo->findOneBy(
                    [
                        'categorie' => $produit->getCategorie(),
                        'contenance' => $produit->getContenant(),
                        'base' => $produit->getBase(),
                        'declineAvec' => $produit->getPrincipeActif(),
                        'client' => null,
                    ],

                    ['id' => 'desc']
                );
            } else {

                if ($suggestionTarif->getMarque()) {

                    $marque = $suggestionTarif->getMarque()->getNom();
                } elseif ($suggestionTarif->getClient()) {

                    if ($suggestionTarif->getClient()->getRaisonSocial()) {
                        $client = $suggestionTarif->getClient()->getRaisonSocial();
                    } else {
                        $client = $suggestionTarif->getClient()->getFirstName() . ' ' . $suggestionTarif->getClient()->getLastName();
                    }
                }
            }


            if ($suggestionTarif) {
                $nomTarif = $suggestionTarif->getNom();
                $dateTarif = $suggestionTarif->getDateAjout()->format('d-m-Y');
            } else {
                $nomTarif = null;
                $dateTarif = null;
            }

            return $this->json(
                [
                    'code' => 200,
                    'nomTarif' => $nomTarif,
                    'dateTarif'     => $dateTarif,
                    'marque' => $marque,
                    'client' => $client
                ],
                200
            );
        }
    }

    public function codeEan13MarqueBlanche(Produit $produit, MarqueBlancheRepository $mbRepo)
    {
        return $this->render('back/produit/ean13marqueBlanche.html.twig', [
            'product' => true,
            'produits' => true,
            'produit' => $produit,
            'listeMB' => $mbRepo->findBy(['produit' => $produit])
        ]);
    }

    public function ajoutCodeMB($id = null, Request $request, EntityManagerInterface $em, MarqueBlancheRepository $mbRepo)
    {

        if ($request->isXMLHttpRequest()) {

            $mbListe = $mbRepo->find($id);
            $final = [];
            $array = $request->request->all();

            foreach ($array as $a => $value) {
                if ($a != "ean") {
                    $final[$a] = $value;
                }
            }

            $mbListe->setMemeEan13(false);

            $mbListe->setCodeEAN13($final);

            $em->flush();
            return $this->json(
                [
                    'code' => 200,
                    'supprimer' => 'ok'
                ],
                200
            );
        }
    }

    public function configMBterminer(Produit $produit, Request $request, MarqueBlancheRepository $mbRepo, EntityManagerInterface $em)
    {
        if ($request->isXMLHttpRequest()) {
            $array = $request->request->all();

            foreach ($array as $value1) {
                foreach ($value1 as $value2) {
                    foreach ($value2 as $key => $val) {

                        if ($val == "Oui") {

                            $mb = $mbRepo->find($key);
                            $mb->setMemeEan13(true);
                            $mb->setCodeEAN13([]);
                            $em->flush();
                        }
                    }
                }
            }

            $em->flush();
            return $this->json(
                [
                    'code' => 200,
                    'supprimer' => 'succès'
                ],
                200
            );
        }
    }


    public function supprimeAromeSupplementaire(Request $request, ProduitAromeRepository $repo, EntityManagerInterface $em)
    {

        if ($request->isXMLHttpRequest()) {
            //dd($request->request->all());
            $aromeSup = $repo->findOneBy(['produit' => $request->request->get('id'), 'NumArome' => $request->request->get('numArome')]);

            $em->remove($aromeSup);
            $em->flush();
            return $this->json(
                [
                    'code' => 200,
                    'supprimer' => $request->request->get('numArome')
                ],
                200
            );
        }
    }

    public function supprimeMarqueBlanche(Request $request, MarqueBlancheRepository $mbRepo, EntityManagerInterface $em)
    {

        if ($request->isXMLHttpRequest()) {
            //dd($request->request->all());
            $marqueBlanche = $mbRepo->find($request->request->get('id'));

            $em->remove($marqueBlanche);
            $em->flush();
            return $this->json(
                [
                    'code' => 200,
                    'supprimer' => $request->request->get('id')
                ],
                200
            );
        }
    }


    public function supprimeBaseSupplementaire(Request $request, ProduitBaseRepository $repo, EntityManagerInterface $em)
    {

        if ($request->isXMLHttpRequest()) {

            $baseSup = $repo->findOneBy(['produit' => $request->request->get('id'), 'numBase' => $request->request->get('numBase')]);

            $em->remove($baseSup);
            $em->flush();
            return $this->json(
                [
                    'code' => 200,
                    'supprimer' => $request->request->get('numBase')
                ],
                200
            );
        }
    }


    public function supprimerProduit(Produit $arome, Request $request, EntityManagerInterface $em)
    {
        if ($this->isCsrfTokenValid('delete' . $arome->getId(), $request->request->get('_token'))) {
            $em->remove($arome);
            $em->flush();
            $this->addFlash('successProduit', 'Produit supprimé avec succès');
            return $this->redirectToRoute('back_produit_list');
        } else {
            throw new TokenNotFoundException('Token invalide');
        }
    }

    //aromes
    public function listeArome(AromeRepository $aromeRepo)
    {
        return $this->render('back/produit/arome/listeArome.html.twig', [
            'produits' => true,
            'configuration' => true,
            'aromes' => $aromeRepo->findAll()
        ]);
    }


    public function creationArome(Request $request, EntityManagerInterface $em)
    {
        $arome = new Arome;

        $form = $this->createForm(AromeType::class, $arome);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->persist($arome);
            $em->flush();
            $this->addFlash('successArome', 'Création arome éffectuée avec succès');

            return $this->redirectToRoute('back_produit_list_arome');
        }

        return $this->render('back/produit/arome/creationArome.html.twig', [
            'produits' => true,
            'configuration' => true,
            'aromes' => true,
            'form' => $form->createView()
        ]);
    }

    public function modificationArome(Arome $arome, Request $request, EntityManagerInterface $em)
    {

        $form = $this->createForm(AromeType::class, $arome);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('successArome', 'Modification arôme effectuée avec succès');

            return $this->redirectToRoute('back_produit_list_arome');
        }

        return $this->render('back/produit/arome/modificationArome.html.twig', [
            'produits' => true,
            'configuration' => true,
            'aromes' => true,
            'form' => $form->createView()
        ]);
    }

    public function supprimerArome(Arome $arome, Request $request, EntityManagerInterface $em)
    {
        if ($this->isCsrfTokenValid('delete' . $arome->getId(), $request->request->get('_token'))) {
            $em->remove($arome);
            $em->flush();
            $this->addFlash('successArome', 'Arome supprimée avec succès');
            return $this->redirectToRoute('back_produit_list_arome');
        } else {
            throw new TokenNotFoundException('Token invalide');
        }
    }

    //contenant
    public function creationContenant(Request $request, EntityManagerInterface $em)
    {
        $contenant = new Contenant;

        $form = $this->createForm(ContenantType::class, $contenant);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($contenant);
            $em->flush();
            $this->addFlash('successContenant', 'Création contenant éffectué avec succès');

            return $this->redirectToRoute('back_produit_list_contenant');
        }

        return $this->render('back/produit/contenant/creationContenant.html.twig', [
            'produits' => true,
            'configuration' => true,
            'contenants' => true,
            'form' => $form->createView()
        ]);
    }

    public function listeContenant(ContenantRepository $contenantRepo)
    {
        return $this->render('back/produit/contenant/listeContenant.html.twig', [
            'produits' => true,
            'configuration' => true,
            'contenants' => $contenantRepo->findAll()
        ]);
    }

    public function modificationContenant(Contenant $contenant, Request $request, EntityManagerInterface $em)
    {

        $form = $this->createForm(ContenantType::class, $contenant);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->flush();
            $this->addFlash('successContenant', 'Modification du contenant effectué avec succès');

            return $this->redirectToRoute('back_produit_list_contenant');
        }

        return $this->render('back/produit/contenant/modificationContenant.html.twig', [
            'produits' => true,
            'configuration' => true,
            'contenants' => true,
            'form' => $form->createView()
        ]);
    }

    public function supprimerContenant(Contenant $contenant, Request $request, EntityManagerInterface $em)
    {
        if ($this->isCsrfTokenValid('delete' . $contenant->getId(), $request->request->get('_token'))) {
            $em->remove($contenant);
            $em->flush();
            $this->addFlash('successContenant', 'Contenant supprimé avec succès');
            return $this->redirectToRoute('back_produit_list_contenant');
        } else {
            throw new TokenNotFoundException('Token invalide');
        }
    }

    //fournisseur
    public function creationFournisseur(Request $request, EntityManagerInterface $em)
    {
        $fournisseur = new Fournisseur;

        $form = $this->createForm(FournisseurType::class, $fournisseur);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($fournisseur);
            $em->flush();
            $this->addFlash('successFournisseur', 'Création fournisseur effectué avec succès');

            return $this->redirectToRoute('back_produit_list_fournisseur');
        }

        return $this->render('back/produit/fournisseur/creationFournisseur.html.twig', [
            'produits' => true,
            'configuration' => true,
            'fournisseurs' => true,
            'form' => $form->createView()
        ]);
    }


    public function listeFournisseur(FournisseurRepository $fournisseur)
    {

        return $this->render('back/produit/fournisseur/listeFournisseur.html.twig', [
            'produits' => true,
            'configuration' => true,
            'fournisseurs' => $fournisseur->findAll()
        ]);
    }

    public function modificationFournisseur(Fournisseur $fournisseur, Request $request, EntityManagerInterface $em)
    {

        $form = $this->createForm(FournisseurType::class, $fournisseur);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($fournisseur);
            $em->flush();
            $this->addFlash('successFournisseur', 'Modification du fournisseur effectué avec succès');

            return $this->redirectToRoute('back_produit_list_fournisseur');
        }

        return $this->render('back/produit/fournisseur/modificationFournisseur.html.twig', [
            'produits' => true,
            'configuration' => true,
            'fournisseurs' => true,
            'form' => $form->createView()
        ]);
    }

    public function supprimerFournisseur(Fournisseur $fournisseur, Request $request, EntityManagerInterface $em)
    {
        if ($this->isCsrfTokenValid('delete' . $fournisseur->getId(), $request->request->get('_token'))) {
            $em->remove($fournisseur);
            $em->flush();
            $this->addFlash('successFournisseur', 'Fournisseur supprimée avec succès');
            return $this->redirectToRoute('back_produit_list_fournisseur');
        } else {
            throw new TokenNotFoundException('Token invalide');
        }
    }

    //marque
    public function listeMarque(MarqueRepository $marque)
    {

        return $this->render('back/produit/marque/listeMarque.html.twig', [
            'produit' => true,
            'produits' => true,
            'marques' => $marque->findBy([], ['id' => 'desc'])
        ]);
    }

    public function creationMarque(EntityManagerInterface $em, Request $request, ClientRepository $clientRepo, GammeRepository $gammeRepo)
    {
        $marque = new Marque;

        $form = $this->createForm(MarqueType::class, $marque);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //dd($_POST);
            $PostCount = count($_POST);
            $i = 1;
            while ($i <= $PostCount) {
                if (isset($_POST["client-$i"])) {
                    $clientExclusif = new ClientExclusif;

                    $clientExclusif->setMarque($marque);
                    $clientExclusif->setClient($clientRepo->find($_POST["client-$i"]));
                    $clientExclusif->setNumClient("client-$i");
                    $em->persist($clientExclusif);
                }
                $i++;
            }

            if ($_POST['marque']['avecGamme']) {
                $j = 1;
                while ($j <= $PostCount) {
                    if (isset($_POST["gamme-$j"])) {
                        $gamme = new UnitMarqueGamme;
                        $gamme->setMarque($marque);
                        $gamme->setGamme($gammeRepo->find($_POST["gamme-$j"]));
                        $gamme->setNumGamme("gamme-$j");
                        $em->persist($gamme);
                    }
                    $j++;
                }
            }

            $em->persist($marque);
            $em->flush();

            $this->addFlash('successMarque', 'Marque créée avec succès');
            return $this->redirectToRoute('back_produit_list_marque');
        }

        return $this->render('back/produit/marque/creationMarque.html.twig', [
            'produit' => true,
            'marques' => true,
            'produits' => true,
            'form' => $form->createView(),
            'listeClient' => $clientRepo->findAll(),
            'listeGamme' => $gammeRepo->findAll()
        ]);
    }

    public function modificationMarque(Marque $marque, $pr = null, Request $request, UnitMarqueGammeRepository $gammeMarqueRepo, GammeRepository $gammeRepo, EntityManagerInterface $em, ClientRepository $clientRepo, ClientExclusifRepository $clientExcluRepo)
    {

        $form = $this->createForm(MarqueType::class, $marque);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if ($_POST['marque']['proprietaire'] == "Fournisseur") {

                $marque->setClient(null);
            } elseif ($_POST['marque']['proprietaire'] == "Client") {

                $marque->setFournisseur(null);
            } else {

                $marque->setClient(null);
                $marque->setFournisseur(null);
            }

            if ($_POST['marque']['exclusivite']) {
                $j = 1;
                $countPost = count($_POST);
                while ($j <= $countPost) {
                    if (isset($_POST["client-$j"])) {
                        $clientExclu = $clientExcluRepo->findOneBy(['marque' => $marque->getId(), 'numClient' => "client-$j"]);

                        if ($clientExclu) {

                            $clientExclu->setClient($clientRepo->find($request->request->get("client-$j")));

                            $em->flush();
                        } else {
                            $newClientExclusif = new ClientExclusif;
                            $newClientExclusif->setMarque($marque);
                            $newClientExclusif->setClient($clientRepo->find($request->request->get("client-$j")));
                            $newClientExclusif->setNumClient("client-$j");
                            $em->persist($newClientExclusif);
                            $em->flush();
                        }
                    }

                    $j++;
                }
            } else {
                $clientExclu = $clientExcluRepo->findBy(['marque' => $marque->getId()]);

                foreach ($clientExclu as $exclu) {
                    $em->remove($exclu);
                    $em->flush();
                }
            }

            if ($_POST['marque']['avecGamme']) {
                $k = 1;
                $countPost = count($_POST);
                while ($k <= $countPost) {
                    if (isset($_POST["gamme-$k"])) {
                        $gammeExist = $gammeMarqueRepo->findOneBy(['marque' => $marque->getId(), 'num_gamme' => "gamme-$k"]);

                        if ($gammeExist) {

                            $gammeExist->setGamme($gammeRepo->find($_POST["gamme-$k"]));

                            $em->flush();
                        } else {
                            $newMG = new UnitMarqueGamme;
                            $newMG->setMarque($marque);
                            $newMG->setGamme($gammeRepo->find($_POST["gamme-$k"]));
                            $newMG->setNumGamme("gamme-$k");
                            $em->persist($newMG);
                            $em->flush();
                        }
                    }

                    $k++;
                }
            } else {
                $gammeExist = $gammeMarqueRepo->findBy(['marque' => $marque->getId()]);

                foreach ($gammeExist as $exist) {
                    $em->remove($exist);
                    $em->flush();
                }
            }


            $em->persist($marque);
            $em->flush();
            $this->addFlash('successMarque', 'Marque modifiée avec succès');

            if ($pr) {
                return $this->redirectToRoute('back_produit_affichage', ['id' => $pr]);
            } else {
                return $this->redirectToRoute('back_produit_list_marque');
            }
        }

        return $this->render('back/produit/marque/modificationMarque.html.twig', [
            'produit' => true,
            'produits' => true,
            'marques' => true,
            'form' => $form->createView(),
            'listeClient' => $clientRepo->findAll(),
            'clientExclusif' => $clientExcluRepo->findBy(['marque' => $marque->getId()]),
            'listeGammeMarque' => $gammeMarqueRepo->findBy(['marque' => $marque->getId()]),
            'listeGamme' => $gammeRepo->findAll(),
            'pr' => $pr
        ]);
    }

    public function produitMarque(Marque $marque, ProduitRepository $produitRepo)
    {

        return $this->render('back/produit/marque/produitMarque.html.twig', [
            'listeProduit' => $produitRepo->findBy(['marque' => $marque->getId()]),
            'marques' => $marque,
            'produit' => true,
            'produits' => true,
        ]);
    }

    public function supprimerMarque(Marque $marque, Request $request, EntityManagerInterface $em)
    {
        if ($this->isCsrfTokenValid('delete' . $marque->getId(), $request->request->get('_token'))) {
            $em->remove($marque);
            $em->flush();
            $this->addFlash('successMarque', 'Marque supprimée avec succès');
            return $this->redirectToRoute('back_produit_list_marque');
        } else {
            throw new TokenNotFoundException('Token invalide');
        }
    }

    public function supprimeClientExclusif(Request $request, ClientExclusifRepository $clientExcluRepo, EntityManagerInterface $em)
    {

        if ($request->isXMLHttpRequest()) {

            $clientExclusif = $clientExcluRepo->findOneBy(['marque' => $request->request->get('idMarque'), 'client' => $request->request->get('id'), 'numClient' => $request->request->get('numClient')]);

            $em->remove($clientExclusif);
            $em->flush();
            return $this->json(
                [
                    'code' => 200,
                    'supprimer' => $request->request->get('numClient')
                ],
                200
            );
        }
    }

    public function supprimeGammeMarque(Request $request, UnitMarqueGammeRepository $UGMRepo, EntityManagerInterface $em)
    {

        if ($request->isXMLHttpRequest()) {

            $gammeMarque = $UGMRepo->findOneBy(['marque' => $request->request->get('idMarque'), 'Gamme' => $request->request->get('idGamme'), 'num_gamme' => $request->request->get('numGamme')]);

            $em->remove($gammeMarque);
            $em->flush();
            return $this->json(
                [
                    'code' => 200,
                    'supprimer' => $request->request->get('numGamme')
                ],
                200
            );
        }
    }


    //Gamme
    public function listeGamme(GammeRepository $gamme)
    {

        return $this->render('back/produit/gamme/listeGamme.html.twig', [
            'produits' => true,
            'produit' => true,
            'gammes' => $gamme->findAll()
        ]);
    }

    public function creationGamme(EntityManagerInterface $em, Request $request)
    {
        $gamme = new Gamme;

        $form = $this->createForm(GammeType::class, $gamme);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($gamme);
            $em->flush();

            $this->addFlash('successGamme', 'Gamme créée avec succès');
            return $this->redirectToRoute('back_produit_list_gamme');
        }

        return $this->render('back/produit/gamme/creationGamme.html.twig', [
            'produit' => true,
            'produits' => true,
            'gammes' => true,
            'form' => $form->createView()
        ]);
    }

    public function modificationGamme(Gamme $gamme, Request $request, EntityManagerInterface $em)
    {

        $form = $this->createForm(GammeType::class, $gamme);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($gamme);
            $em->flush();
            $this->addFlash('successGamme', 'Gamme modifiée avec succès');

            return $this->redirectToRoute('back_produit_list_gamme');
        }

        return $this->render('back/produit/gamme/modificationGamme.html.twig', [
            'produit' => true,
            'produits' => true,
            'gammes' => true,
            'form' => $form->createView()
        ]);
    }

    public function supprimerGamme(Gamme $gamme, Request $request, EntityManagerInterface $em)
    {
        if ($this->isCsrfTokenValid('delete' . $gamme->getId(), $request->request->get('_token'))) {
            $em->remove($gamme);
            $em->flush();
            $this->addFlash('successGamme', 'Gamme supprimée avec succès');
            return $this->redirectToRoute('back_produit_list_gamme');
        } else {
            throw new TokenNotFoundException('Token invalide');
        }
    }

    //categorie

    public function listeCategorie(CategorieRepository $categorie)
    {

        return $this->render('back/produit/categorie/listeCategorie.html.twig', [
            'produit' => true,
            'produits' => true,
            'categories' => $categorie->findAll()
        ]);
    }

    public function creationCategorie(EntityManagerInterface $em, Request $request)
    {
        $categorie = new Categorie;

        $form = $this->createForm(CategorieType::class, $categorie);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($categorie);
            $em->flush();

            $this->addFlash('successCategorie', 'Categorie créée avec succès');
            return $this->redirectToRoute('back_produit_list_categorie');
        }

        return $this->render('back/produit/categorie/creationCategorie.html.twig', [
            'produit' => true,
            'produits' => true,
            'categories' => true,
            'form' => $form->createView()
        ]);
    }

    public function modificationCategorie(Categorie $categorie, Request $request, EntityManagerInterface $em)
    {

        $form = $this->createForm(CategorieType::class, $categorie);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($categorie);
            $em->flush();
            $this->addFlash('successCategorie', 'Categorie modifiée avec succès');

            return $this->redirectToRoute('back_produit_list_categorie');
        }

        return $this->render('back/produit/categorie/modificationCategorie.html.twig', [
            'produit' => true,
            'produits' => true,
            'categories' => true,
            'form' => $form->createView()
        ]);
    }

    public function supprimerCategorie(Categorie $categorie, Request $request, EntityManagerInterface $em)
    {
        if ($this->isCsrfTokenValid('delete' . $categorie->getId(), $request->request->get('_token'))) {
            $em->remove($categorie);
            $em->flush();
            $this->addFlash('successCategorie', 'Categorie supprimée avec succès');
            return $this->redirectToRoute('back_produit_list_categorie');
        } else {
            throw new TokenNotFoundException('Token invalide');
        }
    }

    // base

    public function listeBase(BaseRepository $bases)
    {

        return $this->render('back/produit/base/listeBase.html.twig', [
            'produits' => true,
            'configuration' => true,
            'bases' => $bases->findAll()
        ]);
    }

    public function creationBase(EntityManagerInterface $em, Request $request, ComposantRepository $compoRepo, BaseRepository $baseRepo)
    {
        $base = new Base;

        $form = $this->createForm(BaseType::class, $base);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if ($_POST['somme'] == 100) {

                $base->setNom($_POST['base']['nom']);
                $base->setReference($_POST['base']['reference']);

                $em->persist($base);

                $PostCount = count($_POST);
                $i = 1;
                while ($i <= $PostCount) {
                    if (isset($_POST["composant-$i"])) {
                        $baseCompo = new BaseComposant;
                        $id = $request->request->get("composant-$i");
                        $baseCompo->setComposant($compoRepo->find($id));
                        $baseCompo->setNumComposant('composant-' . $i);
                        $baseCompo->setRatio($_POST["ratio-$i"]);
                        $baseCompo->setBase($base);
                        $em->persist($baseCompo);
                    }
                    $i++;
                }

                $em->flush();

                $this->addFlash('successBase', 'Support créé avec succès');
                return $this->redirectToRoute('back_produit_list_base');
            } else {

                $this->addFlash('erreurBase', 'La somme des ratios pour les composants doit être égale à 100% ');
                return $this->redirectToRoute('back_produit_creation_base');
            }
        }

        return $this->render('back/produit/base/creationBase.html.twig', [
            'produits' => true,
            'configuration' => true,
            'bases' => true,
            'form' => $form->createView(),
            'composants' => $compoRepo->findAll()
        ]);
    }

    public function modificationBase(Base $base, Request $request, EntityManagerInterface $em, ComposantRepository $compoRepo, BaseComposantRepository $baseCompoRepo)
    {

        $form = $this->createForm(BaseType::class, $base);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($_POST['somme'] == 100) {
                $base->setNom($_POST['base']['nom']);
                $base->setReference($_POST['base']['reference']);
                $j = 1;
                $countPost = count($_POST);
                while ($j <= $countPost) {
                    if (isset($_POST["composant-$j"])) {
                        $compBase = $baseCompoRepo->findOneBy(['base' => $base->getId(), 'numComposant' => "composant-$j"]);

                        if ($compBase) {
                            $id = $request->request->get("composant-$j");
                            $compBase->setComposant($compoRepo->find($id));
                            $compBase->setRatio($_POST["ratio-$j"]);
                            $em->flush();
                        } else {
                            $newBaseCompo = new BaseComposant;
                            $id = $request->request->get("composant-$j");
                            $newBaseCompo->setBase($base);
                            $newBaseCompo->setComposant($compoRepo->find($id));
                            $newBaseCompo->setRatio($_POST["ratio-$j"]);
                            $newBaseCompo->setNumComposant("composant-$j");
                            $em->persist($newBaseCompo);
                            $em->flush();
                        }
                    }

                    $j++;
                }

                $em->persist($base);
                $em->flush();
                $this->addFlash('successBase', 'Base modifiée avec succès');

                return $this->redirectToRoute('back_produit_list_base');
            } else {
                $this->addFlash('erreurBase', 'La somme des ratios pour les composants doit être égale à 100% ');
                return $this->redirectToRoute('back_produit_modification_base', ['id' => $base->getId()]);
            }
        }

        return $this->render('back/produit/base/modificationBase.html.twig', [
            'produits' => true,
            'configuration' => true,
            'bases' => true,
            'form' => $form->createView(),
            'composants' => $compoRepo->findAll(),
            'baseComposants' => $baseCompoRepo->findBy(['base' => $base->getId()])
        ]);
    }

    public function supprimerBase(Base $base, Request $request, EntityManagerInterface $em)
    {
        if ($this->isCsrfTokenValid('delete' . $base->getId(), $request->request->get('_token'))) {
            $em->remove($base);
            $em->flush();
            $this->addFlash('successBase', 'Base supprimée avec succès');
            return $this->redirectToRoute('back_produit_list_base');
        } else {
            throw new TokenNotFoundException('Token invalide');
        }
    }

    public function supprimeComposantSupplementaire(Request $request, BaseComposantRepository $baseCompoRepo, EntityManagerInterface $em)
    {

        if ($request->isXMLHttpRequest()) {
            //dd($request->request->all());
            $compoSup = $baseCompoRepo->findOneBy(['base' => $request->request->get('id'), 'numComposant' => $request->request->get('numComposant')]);

            $em->remove($compoSup);
            $em->flush();
            return $this->json(
                [
                    'code' => 200,
                    'supprimer' => $request->request->get('numComposant')
                ],
                200
            );
        }
    }

    //composant

    public function listeComposant(ComposantRepository $composantRepo)
    {

        return $this->render('back/produit/base/listeComposant.html.twig', [
            'produits' => true,
            'configuration' => true,
            'bases' => true,
            'composants' => $composantRepo->findAll()
        ]);
    }

    public function creationComposant(EntityManagerInterface $em, Request $request)
    {
        $composant = new Composant;

        $form = $this->createForm(ComposantType::class, $composant);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($composant);
            $em->flush();

            $this->addFlash('successComposant', 'Composant créée avec succès');
            return $this->redirectToRoute('back_produit_list_composant');
        }

        return $this->render('back/produit/base/creationComposant.html.twig', [
            'produits' => true,
            'configuration' => true,
            'bases' => true,
            'form' => $form->createView(),

        ]);
    }

    public function modificationComposant(Composant $composant, Request $request, EntityManagerInterface $em)
    {

        $form = $this->createForm(ComposantType::class, $composant);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($composant);
            $em->flush();
            $this->addFlash('successComposant', 'Composant modifiée avec succès');

            return $this->redirectToRoute('back_produit_list_composant');
        }

        return $this->render('back/produit/base/modificationComposant.html.twig', [
            'produits' => true,
            'configuration' => true,
            'bases' => true,
            'form' => $form->createView()
        ]);
    }

    public function supprimerComposant(Composant $composant, Request $request, EntityManagerInterface $em)
    {
        if ($this->isCsrfTokenValid('delete' . $composant->getId(), $request->request->get('_token'))) {
            $em->remove($composant);
            $em->flush();
            $this->addFlash('successComposant', 'Composant supprimée avec succès');
            return $this->redirectToRoute('back_produit_list_composant');
        } else {
            throw new TokenNotFoundException('Token invalide');
        }
    }
}
