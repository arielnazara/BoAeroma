<?php

namespace App\Controller\Back;

use DateTime;
use Knp\Snappy\Pdf;
use App\Service\Mailer;
use App\Service\Useful;
use App\Entity\Commandes;
use App\Entity\LotCommande;
use App\Entity\LotQuantite;
use App\Entity\BonLivraison;
use App\Entity\MontantFinal;
use App\Entity\BonDeCommande;
use App\Entity\FactureCommande;
use App\Entity\AdresseLivraison;
use App\Entity\FactureMaitre;
use App\Service\InvoiceProcessing;
use App\Service\LogistiqueServices;
use App\Repository\ClientRepository;
use App\Repository\ProduitRepository;
use App\Repository\LotAromeRepository;
use App\Repository\CommandesRepository;
use App\Repository\LotIsolatRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\LotCommandeRepository;
use App\Repository\LotNicotineRepository;
use App\Repository\BonLivraisonRepository;
use App\Repository\LotGlycerineRepository;
use App\Repository\BonDeCommandeRepository;
use App\Repository\LotVegetolPdoRepository;
use App\Repository\MarqueBlancheRepository;
use Symfony\Component\Filesystem\Filesystem;
use App\Repository\FactureCommandeRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\AdresseLivraisonRepository;
use App\Repository\CommandeRepository;
use App\Repository\FactureMaitreRepository;
use App\Repository\LotQuantiteRepository;
use App\Repository\PositionGammeClientRepository;
use App\Service\DevisServices;
use App\Service\TrieProduit;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\UrlHelper;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Exception\TokenNotFoundException;

class LogistiqueController extends AbstractController
{

    public function index(BonDeCommandeRepository $bonDeCommandeRepo, CommandesRepository $commandeRepo, Request $request, EntityManagerInterface $em, UrlHelper $url)
    {
        //dd($request->getSchemeAndHttpHost());

        if ($request->isXmlHttpRequest()) {
            if ($request->request->get('id')) {
                
                $bc = $bonDeCommandeRepo->find((int)$request->request->get('id'));
                $reste = $commandeRepo->getRemainder($bc);

                foreach($reste as $r)
                {
                    $r->setResteAExpedier(0);
                    $r->setQuantite($r->getExpedier());
                }
                
                $em->flush();
                
                $newQuantity = $commandeRepo->countOrders($bc);
                
                if ($bc) {

                    $resteAlivrer = $commandeRepo->sumQuantity($bc);
                    $bc->setNombreProduit($newQuantity);
                    $bc->setResteAlivrer($resteAlivrer);

                    $livrer = $newQuantity - $resteAlivrer;

                    $em->flush();
                    return $this->json([
                        'data' => '<span class="status-pill smaller green"></span><span class="text-success">OUI</span>',
                        'newQuantity' => $newQuantity,
                        'livrer' => $livrer
                    ], 200);
                }
            }
        }

        $bonDeCommande = $bonDeCommandeRepo->findBy(['envoyerAuLogistique' => true],  array('id' => 'DESC'));

        return $this->render('back/logistique/logistique.html.twig', [
            'logistiques' => $bonDeCommande,
            'logistique' => true,
        ]);
    }

    public function modification(BonDeCommande $bonDeCommande, DevisServices $devisServices, LogistiqueServices $logistiqueServices, TrieProduit $trieProduit, PositionGammeClientRepository $positionGammeClientRepo, LotAromeRepository $lotAromeRepository, LotVegetolPdoRepository $lotVegetolPdoRepository, LotIsolatRepository $lotIsolatRepository, LotGlycerineRepository $lotGlycerineRepository, LotNicotineRepository $lotNicotineRepository, MarqueBlancheRepository $mbRepo, LotCommandeRepository $lotRepo, Useful $useful, BonLivraisonRepository $bl, CommandesRepository $commandeRepo, ProduitRepository $produitRepository, LotCommandeRepository $lotCommandeRepo, EntityManagerInterface $em, Request $request)
    {
        
        $client = $bonDeCommande->getClient();
        
        $gammesClient = $positionGammeClientRepo->findBy(['client' => $client ], ['position' => 'asc']);

        $commandesOrdoner = $trieProduit->setGammesClient($gammesClient)
                                    ->setCommandes($bonDeCommande->getCommandes())
                                    ->trieCommande();
        
        $logistiqueServices->checkEmptyPrice($bonDeCommande);
        
        $customOrder = [];
        $offert = 1;

        $lotCommandes = $bonDeCommande->getLotCommandes();
        $client = $bonDeCommande->getClient();


        $commandes = [];

        foreach($commandesOrdoner as $commande)
        {
            foreach($commande as $com)
            {
                array_push($commandes, $com);
            }
        }
        //temporaire
        $commandes = $commandesOrdoner;
        $count = 1;
        foreach ($commandes as $com) {
                
            $produit = $com->getProduit();
            
            $nom = $produit->getNom();
            
            
            $customName = null;
            if($produit->getMarqueBlanches())
            {
                foreach($produit->getMarqueBlanches() as $mb)
                {
                    if($mb->getClient() == $client)
                    {
                        $customName = $mb->getNom();
                    }
                }
            }
            
            $max = $com->getQuantite() - $com->getPreparer();
            
            if (!array_key_exists($nom, $customOrder) or $com->getOffert()) {
                
                
                if ($com->getOffert()) {
                    $nom .=' offert';
                    $offert++;
                } 

                if($com->getDeclinaison())
                {
                    
                    $declinaison = $com->getDeclinaison();
                }
                else
                {
                    $declinaison = 'Sans déclinaison';
                }

                $customOrder[$nom][$declinaison] = [ "quantity" => $max, "order" => $com->getId()];

                
                
            } else {
                

                if (!$com->getOffert()) {

                    
                    if($com->getDeclinaison())
                    {
                        
                        $declinaison = $com->getDeclinaison();
                    }
                    else
                    {
                        $declinaison = 'Sans déclinaison';
                    }
    
                    
                    if( isset($customOrder[$nom][$declinaison]))
                    {
                        $val = $declinaison."_$count";
                        //$customOrder[$nom][$val] = $max;
                        $customOrder[$nom][$val] = [ "quantity" => $max, "order" => $com->getId()];

                        $count++;
                        
                    }
                    else
                    {

                        $customOrder[$nom][$declinaison] = [ "quantity" => $max, "order" => $com->getId()];

                    }

                    
                }
            }

            
            if($produit->getOrdreCeres())
            {
                $ordreCeres = $produit->getOrdreCeres();

                if($ordreCeres->getLotNicotine())
                {
                    $customOrder[$nom]['lotNicotine'] = 'ok';
                }

                if($ordreCeres->getLotGlycerine())
                {
                    $customOrder[$nom]['lotGlycerine'] = 'ok';
                }

                if($ordreCeres->getLotIsolat())
                {
                    $customOrder[$nom]['lotIsolat'] = 'ok';
                }

                if($ordreCeres->getLotVegetol())
                {
                    $customOrder[$nom]['lotVegetol'] = 'ok';
                }

                if($ordreCeres->getLotArome())
                {
                    $customOrder[$nom]['lotArome'] = 'ok';
                }
            }
            
            if($lotCommandes)
            {
                foreach( $lotCommandes as $lq)
                {
                    foreach($lq->getLotQuantites() as $q)
                    {
            
                        if($q->getProduit() == $produit)
                        {
                        
                            if($q->getLotNicotine())
                            {
                                $customOrder[$nom]['lotNicotine'] = $q->getLotNicotine()->getId();
                            }

                            if($q->getLotGlycerine())
                            {
                                $customOrder[$nom]['lotGlycerine'] = $q->getLotGlycerine()->getId();
                            }

                            if($q->getLotIsolat())
                            {
                                $customOrder[$nom]['lotIsolat'] = $q->getLotIsolat()->getId();
                            }

                            if($q->getLotVegetol())
                            {
                                $customOrder[$nom]['lotVegetol'] = $q->getLotVegetol()->getId();
                            }

                            if($q->getLotArome())
                            {
                                $customOrder[$nom]['lotArome'] = $q->getLotArome()->getId();
                            }
                            
                        }

                    }

                }
            }

            $customOrder[$nom]['customName'] = $customName;
        
        }
        
        foreach($customOrder as $index => $order)
        {
            ksort($order, SORT_NUMERIC );
           
            $customOrder[$index] = $order;
        }

        if ($request->isXmlHttpRequest() && $request->query->get('form')) {

            $lotNicotines = $lotNicotineRepository->findBy(['active' => true]);
            $lotGlycerine = $lotGlycerineRepository->findBy(['active' => true]);
            $lotIsolat = $lotIsolatRepository->findBy(['active' => true]);
            $lotVegetolPdo = $lotVegetolPdoRepository->findBy(['active' => true]);
            $lotAromes = $lotAromeRepository->findBy(['active' => true]);

            return new JsonResponse([
                'content' => $this->renderView('back/logistique/_form_preparation _commande.html.twig', [
                    'customer' => $bonDeCommande->getClient(),
                    'orders' => $commandes,
                    'customOrders' => $customOrder,
                    'bonDeCommande' => $bonDeCommande,
                    'products' => $produitRepository->findAll(),
                    'lotNicotines' => $lotNicotines,
                    'lotGlycerine' => $lotGlycerine,
                    'lotIsolat' => $lotIsolat,
                    'lotVegetolPdo' => $lotVegetolPdo,
                    'lotAromes' => $lotAromes,
                    'ceres' => ($request->query->get('ceres'))? true : false
                ])
            ]);
        }

        if ($request->isMethod('post')) {

            if ($request->request->get('modifier')) {

                $lotPreparation = $lotRepo->find($request->request->get('modifier'));
            } else {

                $lotPreparation = new LotCommande();
                $lotPreparation->setDate(new DateTime());
            }
            
            $devisPayerParCarte =  $bonDeCommande->getDevis()->getPayerParCarte();

            if($devisPayerParCarte)
            {
                $facturePayerParCarte = $bonDeCommande->getFactureCommandes()[0];
               
                $lotPreparation->setFactureCommande($facturePayerParCarte);
            }

            $ceres = $request->get('ceres');

            $lotPreparation->setNom($request->request->get('name'));
            $status = ($request->request->get('status')) ? $request->request->get('status') : $request->request->get('name') . ' à expédier';
            $lotPreparation->setStatus($status);
            $lotPreparation->setBonDeCommande($bonDeCommande);
            $lotPreparation->setClient($bonDeCommande->getClient());
            $lotPreparation->setEnPreparation(true);
            $lotPreparation->setDateExpedition($bonDeCommande->getDateLivraison());
            $lotPreparation->setNombreColis($request->request->get('nombreColis'));

            if($request->get("dernier"))
            {
                $lotPreparation->setDernierLot(true);
            }

            if ($request->request->get('expresse')) {

                $lotPreparation->setExpresse($request->request->get('expresse'));
            }

            if ($request->request->get('affretement')) {

                $lotPreparation->setAffretement($request->request->get('affretement'));
            }

            $quantityTotal = 0;
            if ($request->request->get('modifier')) {
                $test = [];
                //dd($_POST);
                foreach ($lotPreparation->getLotQuantites() as $lq) {
                    $nom = $lq->getProduit()->getNom();

                    foreach ($bonDeCommande->getCommandes() as $o) {
                        if ($o->getProduit() == $lq->getProduit()) {

                            if ($lq->getDeclinaison() == $o->getDeclinaison()) {

                                $declinaison = $devisServices->deleteSpecialChar($lq->getDeclinaison());
                                if (!$lq->getOffert() && !$o->getOffert()) {
                                    //array_push($test, $lq->getOffert());
                                    $value = $request->request->get(str_replace(' ', '_', $nom) . $declinaison);
                                    if ($value && $value > 0 ) {
                                        $lq->setQuantite($value);

                                        $o->setPreparer(0);

                                        $quantityTotal += $lq->getQuantite();
                                        $em->flush();
                                    }
                                } elseif ($lq->getOffert() && $o->getOffert()) {
                                    $value = $request->request->get(str_replace(' ', '_', $nom) . "_offert" . $declinaison);
                                    if ($value && $value > 0 ) {
                                        $lq->setQuantite($value);

                                        $o->setPreparer(0);

                                        $quantityTotal += $lq->getQuantite();
                                        $em->flush();
                                    }
                                }
                            }
                        }
                    }
                }

                //dd($test);

                foreach ($bonDeCommande->getLotCommandes() as $lot) {
                    foreach ($bonDeCommande->getCommandes() as $commande) {
                        foreach ($lot->getLotQuantites() as $quantite) {
                            if (!$commande->getOffert() && !$quantite->getOffert()) {
                                if ($quantite->getProduit() == $commande->getProduit() and $quantite->getDeclinaison() == $commande->getDeclinaison()) {
                                    $t = $commande->getPreparer() + $quantite->getQuantite();
                                    $commande->setPreparer($t);
                                    $em->flush();
                                }
                            } elseif ($commande->getOffert() && $quantite->getOffert()) {
                                if ($quantite->getProduit() == $commande->getProduit() and $quantite->getDeclinaison() == $commande->getDeclinaison()) {
                                    $t = $commande->getPreparer() + $quantite->getQuantite();
                                    $commande->setPreparer($t);
                                    $em->flush();
                                }
                            }
                        }
                    }
                }
            } else {
                //dd($_POST);
                $countOrder = 0;
                foreach ($commandes as $o) {
                    $nom = $o->getProduit()->getNom();

                    if ($o->getOffert()) {
                      
                        if ($o->getDeclinaison()) {
                            $dec = $devisServices->deleteSpecialChar($o->getDeclinaison());

                            $offertPremium = $request->request->get(str_replace(' ', '_', $nom) . "_offert" . "_{$dec}_{$o->getId()}");
                        } else {
                            $offertPremium = $request->request->get(str_replace(' ', '_', $nom) . "_offert_{$o->getId()}");
                        }
                        if ($offertPremium && $offertPremium > 0) {
                            //dd($offertPremium);
                               
                            $lotPriorityQantity = new LotQuantite;
                            $lotPriorityQantity->setQuantite($offertPremium);
                            $lotPriorityQantity->setProduit($o->getProduit());
                            $lotPriorityQantity->setLotCommande($lotPreparation);
                            $lotPriorityQantity->setDeclinaison($o->getDeclinaison());
                            $lotPriorityQantity->setPrixSpecial($o->getPrixSpecial());
                            $lotPriorityQantity->setOffert($o->getOffert());
                            $lotPriorityQantity->setPrix($o->getPrix());
                            $lotPriorityQantity->setCommande($o);

                            $produitFini = $request->request->get('lot_Crf_'.str_replace(' ', '_', $nom)."_offert_{$o->getId()}");

                            if($produitFini)
                            {
                                $lotPriorityQantity->setLotCrf($produitFini);
                            }
                            
                            $Lnicotine = $request->request->get('lot_nicotine_'.str_replace(' ', '_', $nom). "_offert_{$o->getId()}");

                            if($Lnicotine)
                            {
                                $lotPriorityQantity->setLotNicotine($lotNicotineRepository->find($Lnicotine));
                            }
    
                            $Lglycerine = $request->request->get('lot_glycerine_'.str_replace(' ', '_', $nom). "_offert_{$o->getId()}");
    
                            if($Lglycerine)
                            {
                                $lotPriorityQantity->setLotGlycerine($lotGlycerineRepository->find($Lglycerine));
                            }
    
                            
                            $Lisolat = $request->request->get('lot_isolat_'.str_replace(' ', '_', $nom). "_offert_{$o->getId()}");
    
                            if($Lisolat)
                            {
                                $lotPriorityQantity->setLotIsolat($lotIsolatRepository->find($Lisolat));
                            }
    
                            $Lvegetol = $request->request->get('lot_vegetol_'.str_replace(' ', '_', $nom). "_offert_{$o->getId()}");
    
                            if($Lvegetol)
                            {
                                $lotPriorityQantity->setLotVegetol($lotVegetolPdoRepository->find($Lvegetol));
                            }

                            $Larome = $request->request->get('lot_arome_'.str_replace(' ', '_', $nom). "_offert_{$o->getId()}");

                            if($Larome)
                            {
                                $lotPriorityQantity->setLotArome($lotAromeRepository->find($Larome));
                            }

                            $em->persist($lotPriorityQantity);
                            $total = $o->getPreparer() + $lotPriorityQantity->getQuantite();
                            $o->setPreparer($total);

                            $quantityTotal += $lotPriorityQantity->getQuantite();

                             
                            
                            $countOrder++;
                        }
                        

                    } elseif (!$o->getOffert()) {
                       
                        if ($o->getDeclinaison()) {

                            $dec = $devisServices->deleteSpecialChar($o->getDeclinaison());
                            $value = $request->request->get(str_replace(' ', '_', $nom) . "_{$dec}_{$o->getId()}");
                           
                            $produitFini = $request->request->get('lot_Crf_'.str_replace(' ', '_', $nom)."_{$o->getId()}");
                            
                            
                            if ($value && $value > 0) {
                                $lotPriorityQantity = new LotQuantite;
                                $lotPriorityQantity->setQuantite($value);
                                $lotPriorityQantity->setProduit($o->getProduit());
                                $lotPriorityQantity->setLotCommande($lotPreparation);
                                $lotPriorityQantity->setDeclinaison($o->getDeclinaison());
                                $lotPriorityQantity->setPrixSpecial($o->getPrixSpecial());
                                $lotPriorityQantity->setOffert($o->getOffert());
                                $lotPriorityQantity->setPrix($o->getPrix());
                                $lotPriorityQantity->setCommande($o);

                                //dd($produitFini);
                                if($produitFini)
                                {
                                    $lotPriorityQantity->setLotCrf($produitFini);
                                }

                                $Lnicotine = $request->request->get('lot_nicotine_'.str_replace(' ', '_', $nom)."_{$o->getId()}");

                                if($Lnicotine)
                                {
                                    $lotPriorityQantity->setLotNicotine($lotNicotineRepository->find($Lnicotine));
                                }
        
                                $Lglycerine = $request->request->get('lot_glycerine_'.str_replace(' ', '_', $nom)."_{$o->getId()}");
        
                                if($Lglycerine)
                                {
                                    $lotPriorityQantity->setLotGlycerine($lotGlycerineRepository->find($Lglycerine));
                                }
        
                                
                                $Lisolat = $request->request->get('lot_isolat_'.str_replace(' ', '_', $nom)."_{$o->getId()}");
        
                                if($Lisolat)
                                {
                                    $lotPriorityQantity->setLotIsolat($lotIsolatRepository->find($Lisolat));
                                }
        
                                $Lvegetol = $request->request->get('lot_vegetol_'.str_replace(' ', '_', $nom)."_{$o->getId()}");
        
                                if($Lvegetol)
                                {
                                    $lotPriorityQantity->setLotVegetol($lotVegetolPdoRepository->find($Lvegetol));
                                }

                                $Larome = $request->request->get('lot_arome_'.str_replace(' ', '_', $nom)."_{$o->getId()}");

                                if($Larome)
                                {
                                    $lotPriorityQantity->setLotArome($lotAromeRepository->find($Larome));
                                }

                                $em->persist($lotPriorityQantity);

                                
                                $total = $o->getPreparer() + $lotPriorityQantity->getQuantite();
                                
                                $o->setPreparer($total);

                                $quantityTotal += $lotPriorityQantity->getQuantite();

                                $countOrder++;

                            }
                            
                        } else {

                           
                            $val = $request->request->get(str_replace(' ', '_', $nom) . "_{$o->getId()}");
                           
                            $produitFini = $request->request->get('lot_Crf_'.str_replace(' ', '_', $nom)."_{$o->getId()}");
                            

                            if ($val && $val > 0) {
                            
                                $lotPriorityQantity = new LotQuantite;
                                $lotPriorityQantity->setQuantite($val);
                                $lotPriorityQantity->setProduit($o->getProduit());
                                $lotPriorityQantity->setLotCommande($lotPreparation);
                                $lotPriorityQantity->setDeclinaison($o->getDeclinaison());
                                $lotPriorityQantity->setPrixSpecial($o->getPrixSpecial());
                                $lotPriorityQantity->setOffert($o->getOffert());
                                $lotPriorityQantity->setPrix($o->getPrix());
                                $lotPriorityQantity->setCommande($o);


                                if($produitFini)
                                {
                                    $lotPriorityQantity->setLotCrf($produitFini);
                                }
                                
                                $Lnicotine = $request->request->get('lot_nicotine_'.str_replace(' ', '_', $nom)."_{$o->getId()}");

                                if($Lnicotine)
                                {
                                    $lotPriorityQantity->setLotNicotine($lotNicotineRepository->find($Lnicotine));
                                }
        
                                $Lglycerine = $request->request->get('lot_glycerine_'.str_replace(' ', '_', $nom)."_{$o->getId()}");
        
                                if($Lglycerine)
                                {
                                    $lotPriorityQantity->setLotGlycerine($lotGlycerineRepository->find($Lglycerine));
                                }
        
                                
                                $Lisolat = $request->request->get('lot_isolat_'.str_replace(' ', '_', $nom)."_{$o->getId()}");
        
                                if($Lisolat)
                                {
                                    $lotPriorityQantity->setLotIsolat($lotIsolatRepository->find($Lisolat));
                                }
        
                                $Lvegetol = $request->request->get('lot_vegetol_'.str_replace(' ', '_', $nom)."_{$o->getId()}");
        
                                if($Lvegetol)
                                {
                                    $lotPriorityQantity->setLotVegetol($lotVegetolPdoRepository->find($Lvegetol));
                                }

                                $Larome = $request->request->get('lot_arome_'.str_replace(' ', '_', $nom)."_{$o->getId()}");

                                if($Larome)
                                {
                                    $lotPriorityQantity->setLotArome($lotAromeRepository->find($Larome));
                                }

                                $em->persist($lotPriorityQantity);
                                $total = $o->getPreparer() + $lotPriorityQantity->getQuantite();
                                $o->setPreparer($total);

                                $quantityTotal += $lotPriorityQantity->getQuantite();

                        
                                $countOrder++;
                            
                                        
                            }
                        }

                       
                    }
                }


                if($countOrder == 0)
                {
                    $this->addFlash("aucunProduit", "Veuillez remplir au moins une quantitée");
                    return $this->redirectToRoute("back_modification_logistique", ['id' => $bonDeCommande->getId()] );
                }
                

                if($request->get("echantillons"))
                {
                    $echantillons = explode(',', $request->get("samples_products"));
                    
                    foreach($echantillons as $echantillon)
                    {
                        $produit = $produitRepository->find($echantillon);
                        $nomProduit = $produit->getNom();

                        $declinaisons = $produit->getDeclinaison();

                        if($declinaisons)
                        {
                            foreach($declinaisons as $decl)
                            {
                                $dec = $devisServices->deleteSpecialChar($decl);

                                $value = $request->request->get('echantillon'.str_replace(' ', '_', $nomProduit) . $dec);

                                if($value && $value > 0)
                                {
                                    $lotQantity = new LotQuantite;
                                    $lotQantity->setQuantite($value);
                                    $lotQantity->setProduit($produit);
                                    $lotQantity->setLotCommande($lotPreparation);
                                    $lotQantity->setDeclinaison($decl);
                                    $lotQantity->setEchantillon(true);
                                    $lotQantity->setCommande($o);

                                    $em->persist($lotQantity);
                                }
                            }
                        }
                        else
                        {

                            $value = $request->request->get('echantillon'.str_replace(' ', '_', $nomProduit));

                            if($value && $value > 0)
                            {
                                $lotQantity = new LotQuantite;
                                $lotQantity->setQuantite($value);
                                $lotQantity->setProduit($produit);
                                $lotQantity->setLotCommande($lotPreparation);
                                $lotQantity->setEchantillon(true);
                                $lotQantity->setCommande($o);

                                $em->persist($lotQantity);
                            }
                        }
                    }
                }

                $em->persist($lotPreparation);
                $em->flush();
              

                if($request->get('adresseFacturation'))
                {
                    $useful->createBl(true,$lotPreparation, $quantityTotal);   
                }
                else
                {
                    $useful->createBl(null, $lotPreparation, $quantityTotal);
                }
               
                

            }


            $bonDeCommande->setStatusLogistique(($request->request->get('status')) ? $request->request->get('status') : $request->request->get('name') . ' en cours de préparation');
            $em->flush();

            $this->addFlash('success', 'Préparation créée avec succès');

            if($lotPreparation->getDernierLot())
            {
                return $this->redirectToRoute("back_logistique_dernier_lot", ["id"=> $bonDeCommande->getId()]);
            }

            return  $this->redirectToRoute('back_modification_logistique', ['id' => $bonDeCommande->getId()]);
        }

        $lotPreparation = $lotCommandeRepo->findBy(['enPreparation' => true, 'bonDeCommande' => $bonDeCommande]);

        $groups = [];
        $countPreparation = 0;
        foreach($lotPreparation as $lot)
        {
           
            if($lot->getFactureMaitre())
            {

                $factId = $lot->getFactureMaitre()->getId();
                $groups[$factId][] = $lot;

            }
            elseif($lot->getFactureCommande())
            {
                $factId = $lot->getFactureCommande()->getId();
                $groups[$factId][] = $lot;
            }
            else
            {

                $groups['none'][] = $lot;

            }

            $countPreparation++;
            
        }
        
        
        return $this->render('back/logistique/modificationCommande.html.twig', [
            'bonDeCommande' => $bonDeCommande, 'logistiques' => true, 'logistique' => true,
            'listCommande' => $commandes,
            'lotPreparation' => $groups,
            'bl' => $bl->findBy(['bonDeCommande' => $bonDeCommande]),
            'customOrders' => $customOrder,
            'Client' => $bonDeCommande->getClient(),
            'marqueBlanches' => $mbRepo->findBy(['client' => $bonDeCommande->getClient()]),
            'products' => $produitRepository->findAll(),
            'countPreparation' => $countPreparation
        ]);
    }


    public function deleteNonFacture($id,$idBonCommande, LotCommandeRepository $lotCommandeRepo, EntityManagerInterface $em)
    {
        $lot=$lotCommandeRepo->find($id);

        $em->remove($lot);
        $em->flush($lot);

        return  $this->redirectToRoute('back_modification_logistique', ['id' => $idBonCommande]);
        
    }

    public function dernierLot(BonDeCommande $bonDeCommande, Request $request, EntityManagerInterface $em)
    {
        
        $quantiteFinal = 0;
        $prixFinal = null;

        $devis = $bonDeCommande->getDevis();
        $client = $devis->getClient();

        $quantiteCommander = 0;
        $count = 0;
        //dd(count($bonDeCommande->getLotCommandes()));
        foreach($bonDeCommande->getLotCommandes() as $lot)
        {
            foreach($lot->getLotQuantites() as $lq)
            {
                if(!$lq->getEchantillon())
                {     
                    $quantiteFinal += $lq->getQuantite();
    
                    if(!$lq->getOffert() and !$lq->getEchantillon())
                    {
                        
                        if($lq->getPrixSpecial())
                        {
                            $prixFinal += ($lq->getPrixSpecial() * $lq->getQuantite());
                        }
                        else
                        {
                            $prixFinal += ($lq->getPrix() * $lq->getQuantite());
                        }
                    }
                }

            }
            $count++;
        }
      
        if($prixFinal != $devis->getMontant() or $devis->getMontantFinal())
        {
            $devisFinal = null;
            
            if($devis->getMontantFinal())
            {
                $devisFinal = $devis->getMontantFinal();
            }
            else
            {
                $devisFinal = new MontantFinal();
            }

            $devisFinal->setMontant($prixFinal);
    
            $prixFinal = round($prixFinal, 2);
    
            $totalHt = $prixFinal + $devis->getFraisExpedition();
    
            $taxe = $prixFinal * 0.2;
    
            if($client->getPays() != "France")
            {
                $taxe = 0;
            }
            
            $taxe = round($taxe, 2);
    
            $totalTtc = $totalHt + $taxe;
            $totalTtc = round($totalTtc, 2);
    
            $devisFinal->setTotalTtc($totalTtc);
            $devisFinal->setTotalHt($totalHt);
            $devisFinal->setTaxe($taxe);
            $devisFinal->setDevis($devis);
    
            $devisFinal->setNombreProduit($quantiteFinal);
    
            $em->persist($devisFinal);
            $em->flush();
        }

        return $this->redirect($request->headers->get('referer'));

    }

    public function btnEmail(LotCommande $lotCommande, Mailer $mailer, Useful $useful, EntityManagerInterface $em, BonLivraisonRepository $bon, Request $request)
    {

        if ($request->isMethod('post')) {

            $lotCommande->setDateExpedition(new DateTime());

            $po = $lotCommande->getBonDeCommande();

            $quantityTotal = 0;
            foreach ($po->getCommandes() as $o) {
                foreach ($lotCommande->getLotQuantites() as $lotQuantite) {
                    if ($lotQuantite->getProduit() === $o->getProduit()) {
                        $quantityTotal += $lotQuantite->getQuantite();
                    }
                }
            }

            $mailer->preparationEmail($lotCommande);
            $bl = $bon->findOneBy(['bonDeCommande' => $lotCommande->getBonDeCommande(), 'estGenerer' => false]);

            if ($bl) {
                $bonDeLivraison = $bl;
                $bonDeLivraison->setEstGenerer(true);
                $bonDeLivraison->setDateExpedition(new DateTime());
                $em->persist($bonDeLivraison);
            }

            $em->flush();
            $this->addFlash('success', 'Email de preparation envoyé et bon de livraison généré avec succés');

            return $this->redirectToRoute('back_modification_logistique', ['id' => $lotCommande->getBonDeCommande()->getId()]);
        }
    }

    public function downloadBl(BonLivraison $bonLivraison, TrieProduit $trieProduit, LotQuantiteRepository $lqRepo, Pdf $snappy,Useful $useful, PositionGammeClientRepository $positionGammeClientRepo, LogistiqueServices $logistiqueServices, EntityManagerInterface $em)
    {

        $devis = $bonLivraison->getBonDeCommande()->getDevis();

        $bonDeCommande = $bonLivraison->getBonDeCommande();

       
        $position = $useful->ajoutNumBL($bonLivraison, $bonDeCommande);

        $lotCommande = $bonLivraison->getLotCommande();
        
        $client = $bonDeCommande->getClient();

        $client = $bonDeCommande->getClient();
        
        $gammesClient = $positionGammeClientRepo->findBy(['client' => $client ], ['position' => 'asc']);

        $orderLotQuantities = $trieProduit->setGammesClient($gammesClient)
                                    ->setCommandes($lotCommande->getLotQuantites())
                                    ->trieCommande();

        
        if(!$bonLivraison->getCode())
        {

            $code = $devis->getCode().'-'.$position;

            $bonLivraison->setCode($code);
            $em->flush();
        }

        $blRender = $this->renderView('back/pdf/bon-livraison.html.twig', [
            'lotQuantity' => $orderLotQuantities,
            'purchaseOrder' => $bonLivraison->getBonDeCommande(),
            'devis' => $devis,
            'bl' => $bonLivraison,
            'position' => $position
        ]);

        $name = trim($bonLivraison->getLotCommande()->getNom());
        $name = str_replace('é', 'e', $name);
        $name = str_replace('è', 'e', $name);
        $name = str_replace('ê', 'e', $name);
        $name = str_replace('ç', 'c', $name);
        $name = str_replace('à', 'a', $name);
        $name = str_replace(" ", '', $name);
        $name = str_replace("/", '-', $name);
        $name = str_replace("\\", '-', $name);
        
        $blFilename = 'BL-' . $name . '-' . $bonLivraison->getBonDeCommande()->getDevis()->getCode() . '-' . $bonLivraison->getId() . '.pdf';
        
        $footer = $this->renderView( '/back/pdf/footer.html.twig' );
        $header = $this->renderView('/back/pdf/header.html.twig');
        $option = ['footer-html' => $footer, 'header-html' => $header];

        return new PdfResponse(
            $snappy->getOutputFromHtml($blRender, $option),
            $blFilename
        );
    }

    public function showInvoice($id, $idB, BonDeCommandeRepository $bonDeCommandeRepo, FactureCommandeRepository $invoiceRepo, KernelInterface $kernel, Useful $useful)
    {
        $invoice = $invoiceRepo->find($id);
        
        $bonDeCommande = $bonDeCommandeRepo->find($idB);

        $path = "/pdf/order/Facture-{$invoice->getNumero()}.pdf";
        $finalPath = "{$kernel->getProjectDir()}/public$path";
       
        if(file_exists($finalPath))
        {
            unlink($finalPath);
        }

        $useful->regeneratePdf($bonDeCommande, $invoice);

        return $this->redirect($path);
     
    }

    public function showInvoiceMaster($id, $maitre, BonDeCommandeRepository $bonDeCommandeRepo, FactureMaitreRepository $factureMaitreRepository, FactureCommandeRepository $invoiceRepo, KernelInterface $kernel, Useful $useful)
    {

        if($maitre)
        {
            $invoice = $factureMaitreRepository->find($id);
        }
        else
        {
            $invoice = $invoiceRepo->find($id);
        }

        $path = "/pdf/order/Facture-{$invoice->getNumero()}.pdf";
        $finalPath = "{$kernel->getProjectDir()}/public$path";
       
        if(file_exists($finalPath))
        {
            unlink($finalPath);
        }
        
        if($maitre)
        {
            $useful->regeneratePdf(null, null, $invoice );
        }
        else
        {
            $useful->regeneratePdf($invoice->getBonDeCommande(), $invoice );
        }

        return $this->redirect($path);
     
    }
    
    public function sendInvoice(FactureCommande $invoice,Useful $useful, Mailer $mailer, Request $request, EntityManagerInterface $em)
    {
        
        if ($this->isCsrfTokenValid('send' . $invoice->getId(), $request->request->get('_token'))) {

            foreach($invoice->getLotCommandes() as $lot)
            {
                $bl = $lot->getBonLivraison();
                if(!$bl->getCode())
                {
                    $position = $useful->ajoutNumBL($bl, $invoice->getBonDeCommande());

                    $code = $invoice->getDevis()->getCode().'-'.$position;

                    $bl->setCode($code);

                    $em->flush();
                }
            }
            
            $mailer->sendBatchOrderInvoice($invoice);

            $invoice->setExpedier(true);

            $em->flush();

            $this->addFlash("factureEnvoyer", "Facture envoyée avec succès");

            return $this->redirect($request->headers->get('referer'));
            
        } else {
            throw new TokenNotFoundException('Token invalide');
        }
    }


    public function sendInvoiceMaster(FactureMaitre $invoice,Useful $useful, Mailer $mailer, Request $request, EntityManagerInterface $em)
    {
        if ($this->isCsrfTokenValid('send' . $invoice->getId(), $request->request->get('_token'))) {
            
            $mailer->sendMasterInvoice($invoice);

            $invoice->setExpedier(true);

            $em->flush();

            $this->addFlash("factureEnvoyer", "Facture envoyée avec succès");

            return $this->redirect($request->headers->get('referer'));
            
        } else {
            throw new TokenNotFoundException('Token invalide');
        }
    }


    public function generateInvoice(LotCommande $lotCommande, InvoiceProcessing $invoiceProcessing, Request $request, Useful $useful)
    {
        
        if(!$lotCommande->getFactureCommande())
        {
            $bonDeCommande =  $lotCommande->getBonDeCommande();
            
            $invoice = $invoiceProcessing->blInvoice($bonDeCommande, $lotCommande);

            if($invoice instanceof FactureCommande)
            {
                $useful->regeneratePdf($bonDeCommande, $invoice);

                $this->addFlash("invoiceCreate", "Facture généré avec succè");

                return $this->redirect($request->headers->get('referer'));
                
            }
        }

        return $this->redirect($request->headers->get('referer'));

    } 

    public function expedier(LotCommande $lotPreparation, EntityManagerInterface $em, BonLivraisonRepository $blRepo, Request $request, Mailer $mailer, Useful $useful)
    {

        if ($request->isMethod('post')) {

            if (!$lotPreparation->getEstExpedier()) {
                $bonLivraison = $lotPreparation->getBonLivraison();

                if ($request->request->get('track_number')) {
                    $lotPreparation->setNumeroSuivi($request->request->get('track_number'));
                }

                $lotPreparation->setDateExpedition((\DateTime::createFromFormat('d/m/Y', trim($request->request->get('date_shipped')))));
                $bonLivraison->setDateExpedition((\DateTime::createFromFormat('d/m/Y', trim($request->request->get('date_shipped')))));
                $lotPreparation->setEstExpedier(true);
                $po = $lotPreparation->getBonDeCommande();
                $lotPreparation->setStatus('Expédié le ' . $lotPreparation->getDateExpedition()->format('d/m/Y'));
                $po->setStatusLogistique('Expédié le ' . $lotPreparation->getDateExpedition()->format('d/m/Y'));

                $quantityTotal = 0;
                $count = 0;
                $cou = [];
                $orderExiste = [];

                $dernierLot = false;

                //dd()

                $lotPreparation->getDernierLot() ? $dernierLot = true : $dernierLot = false;
               
                //dd(count($po->getCommandes()), count($lotPreparation->getLotQuantites()));
                foreach ($po->getCommandes() as $o) {
                    foreach ($lotPreparation->getLotQuantites() as $lotQuantity) {


                        if ($o->getOffert() && $lotQuantity->getOffert()) {

                            $o->setExpedier($lotQuantity->getQuantite());
                            $quantityTotal += $lotQuantity->getQuantite();
                            $count += 1;
                            array_push($cou, $lotQuantity->getProduit()->getNom());
                        } elseif (!$o->getOffert() && !$lotQuantity->getOffert()) {

                            if ($lotQuantity->getProduit() === $o->getProduit() && $lotQuantity->getDeclinaison() == $o->getDeclinaison()) {

                                if(($lotQuantity->getCommande() && $lotQuantity->getCommande() == $o) or !$lotQuantity->getCommande())
                                {
                                    
                                    if(!in_array($o->getId(), $orderExiste)){
    
                                        array_push($orderExiste,$o->getId());
                                        $o->setExpedier($lotQuantity->getQuantite());
                                        $quantityTotal += $lotQuantity->getQuantite();
    
                                        $count += 1;
                                    }
                                }
                            }
                        }
                    }
                }

                if ( $dernierLot || $quantityTotal == $po->getResteAlivrer()) {
                    $po->setToutEstExpedier(true);
                    $devisFinal = null;
                    $invoice = null;
                    
                    $po->setTraitementTerminer(true);
                    $po->setStatus('Expédition du dernier lot');
                    $po->setStatusLogistique($po->getStatus());
                    
                    $po->setResteAlivrer($po->getResteAlivrer() - $quantityTotal);
                    $em->flush();
                 
                    $this->addFlash('success', 'Expédition le ' . $lotPreparation->getDateExpedition()->format('d/m/Y') . ' et envoie de la facture de solde');


                    if($po->getToutEstPayer()){

                        $mailer->sendShipped('Votre commande est en cours de livraison.', $lotPreparation, $po, $blRepo->findOneBy(['lotCommande' => $lotPreparation, 'estGenerer' => true]), $invoice, $devisFinal);
                    }else{

                        $mailer->sendShipped('Votre commande est en cours de livraison.', $lotPreparation, $po, $blRepo->findOneBy(['lotCommande' => $lotPreparation, 'estGenerer' => true]), $invoice, $devisFinal);
                    }

                    return $this->redirectToRoute('back_modification_logistique', ['id' => $po->getId()]);
                }

                $po->setResteAlivrer($po->getResteAlivrer() - $quantityTotal);
                $em->flush();
                $mailer->sendShipped('Votre commande est en cours de livraison.', $lotPreparation, $po, $blRepo->findOneBy(['lotCommande' => $lotPreparation, 'estGenerer' => true]));

                $this->addFlash('success', 'Expédition le ' . $lotPreparation->getDateExpedition()->format('d/m/Y'));
                return $this->redirectToRoute('back_modification_logistique', ['id' => $po->getId()]);
            }
        }
        return $this->redirectToRoute('back_modification_logistique', ['id' => $lotPreparation->getBonDeCommande()->getId()]);
    }


    public function ajaxCreateInvoice(Request $request, LotCommandeRepository $lotCommandeRepo, Useful $useful, KernelInterface $kernel, InvoiceProcessing $invoiceProcessing, BonDeCommandeRepository $bonDeCommandeRepository)
    {
        if($request->isXmlHttpRequest())
        {
            $data = $request->request->get("data");

            $bonDeCommande = $bonDeCommandeRepository->find($request->request->get("bon"));

            $lots = $lotCommandeRepo->findLots($data);

            //dd($lots);

            $invoice = $invoiceProcessing->oneInvoice($lots, $bonDeCommande);

            $path = "/pdf/order/Facture-{$invoice->getNumero()}.pdf";
            $finalPath = "{$kernel->getProjectDir()}/public$path";
           
            if(file_exists($finalPath))
            {
                unlink($finalPath);
            }
            
            $useful->regeneratePdf($bonDeCommande, $invoice);

            $this->addFlash("success", "Création facture effectuée avec succès");

            return $this->json(['success' => true], 200);
            
        }
    }

    public function modifNumeroSuivi(LotCommande $lotPreparation, Request $request, EntityManagerInterface $em, Mailer $mailer)
    {

        if ($request->isMethod('post')) {
            $lotPreparation->setNumeroSuivi($request->request->get('track_number'));
            $em->flush();

            $mailer->envoieNumeroSuivi($lotPreparation);

            $this->addFlash('success', 'Modification numéro de suivi éffecté avec succè');
            return $this->redirectToRoute('back_modification_logistique', ['id' => $lotPreparation->getBonDeCommande()->getId()]);
        }
    }


    public function bdcExcel(BonDeCommande $bonDeCommande, EntityManagerInterface $em, CommandesRepository $commandeRepo, Useful $useful)
    {
        
        if(!$bonDeCommande->getDownloadDate())
        {
            $bonDeCommande->setDownloadDate(new DateTime());
            $em->flush();
        }
        $reponse = $useful->createExcel($bonDeCommande);
        return $this->file($reponse['temp_file'], $reponse['fileName'], ResponseHeaderBag::DISPOSITION_INLINE);
    }

    public function bonDePreparation(BonDeCommandeRepository $bonDeCommandeRepo)
    {
        return $this->render('back/logistique/listeBonPreparation.html.twig', [
            'bonDeCommande' =>  $bonDeCommandeRepo->findBy(['envoyerAuLogistique' => true],  array('id' => 'DESC')),
            'bonPreparation' => true,
            'production' => true

        ]);
    }


    public function getSamples(Request $request, ProduitRepository $produitRepository)
    {
        if($request->isXmlHttpRequest())
        {
            $samples = $produitRepository->findSamples($request->get('sampleId'));
            
            return new JsonResponse(['content' => $this->renderView('back/logistique/_echantillon.html.twig',['samples' => $samples])]);
        }
    }

    public function addAddress(Request $request,EntityManagerInterface $em, ClientRepository $clientRepository)
    {
        if($request->isXmlHttpRequest())
        {

            $client = $clientRepository->find($request->request->get("client"));
            $adresseClient = $client->getAdresseLivraisons();

            if($adresseClient)
            {
                foreach($adresseClient as $adresse)
                {
                    $adresse->setActive(false);
                }
            }

            $adresse = $request->request->get('newAdresse');
            $codePostal = $request->request->get('newPostCode');
            $ville = $request->request->get('newCity');
            $pays = $request->request->get('newCountry');

            $adresseLivraison = new AdresseLivraison();
            $adresseLivraison->setAdresse($adresse);
            $adresseLivraison->setCodePostal($codePostal);
            $adresseLivraison->setVille($ville);
            $adresseLivraison->setClient($client);
            $adresseLivraison->setActive(true);

            $em->persist($adresseLivraison);
            $em->flush();

            return $this->json($adresseLivraison, 200, [], ["groups" => "livraison"]);
            
        }
    }

    public function selectAddress(Request $request,EntityManagerInterface $em,ClientRepository $clientRepository, AdresseLivraisonRepository $adresseLivraisonRepository)
    {
        if($request->isXmlHttpRequest())
        {
            $adresse = $adresseLivraisonRepository->find($request->request->get("address"));
            $client = $clientRepository->find($request->request->get('client'));

            $adresseClient = $client->getAdresseLivraisons();

            if($adresseClient)
            {
                foreach($adresseClient as $ad)
                {
                    $ad->setActive(false);
                    $em->flush();
                }
            }

            if($adresse)
            {
                $adresse->setActive(true);
                $em->flush();
                return $this->json($adresse, 200, [], ["groups" => "livraison"]);
                
            }
            
            return $this->json($client, 200, [], ["groups" => "livraison"]);

        }
    }
}
