<?php


namespace App\Controller\Back;

use DateTime;
use Exception;
use Knp\Snappy\Pdf;
use App\Entity\Devis;
use App\Entity\Order;
use App\Entity\Client;
use League\Csv\Reader;
use App\Service\Mailer;
use App\Service\Useful;
use App\Entity\LotOrder;
use App\Entity\Commandes;
use App\Service\AeromaApi;
use App\Entity\LotQuantity;
use App\Entity\DevisProduit;
use App\Entity\BonDeCommande;
use App\Entity\PurchaseOrder;
use App\Entity\DeliveryAddress;
use App\Entity\FactureCommande;
use App\Entity\HistoriqueTarif;
use App\Entity\PrixDeclinaison;
use App\Repository\AdresseLivraisonRepository;
use App\Repository\DevisRepository;
use App\Repository\TarifRepository;
use App\Repository\ClientRepository;
use App\Repository\ProduitRepository;
use App\Repository\CommandesRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\LotCommandeRepository;
use App\Repository\DevisProduitRepository;
use App\Repository\TypeDeClientRepository;
use App\Repository\BonDeCommandeRepository;
use App\Repository\MarqueBlancheRepository;
use App\Repository\PurchaseOrderRepository;
use Symfony\Component\Filesystem\Filesystem;
use App\Repository\FactureCommandeRepository;
use App\Repository\FactureMaitreRepository;
use App\Repository\HistoriqueTarifRepository;
use App\Repository\PositionGammeClientRepository;
use App\Service\DevisServices;
use App\Service\LogistiqueServices;
use App\Service\TrieProduit;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Bic;
use Symfony\Component\HttpFoundation\JsonResponse;
use Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\TokenNotFoundException;

class CommandeController extends AbstractController
{

    public function liste(Request $request, AeromaApi $aeromaApi,Useful $useful, TypeDeClientRepository $typeCli, DevisProduitRepository $devisProduitRepo, ProduitRepository $produitRepo, DevisRepository $devisRepo, ClientRepository $clientRepo, BonDeCommandeRepository $bonDeCommandeRepo, EntityManagerInterface $em)
    {

        if ($request->isXmlHttpRequest()) {

            if ($request->request->get('hideIsLogistic') == "true") {

                $result = $bonDeCommandeRepo->findBy(['envoyerAuLogistique' => null], ['id' => 'desc']);
            } else {

                $result = $bonDeCommandeRepo->findBy([], ['id' => 'desc']);
            }

            $content = $this->renderView('back/commande/tab_list.html.twig', [
                'commandes' => $result
            ]);
            return new JsonResponse([
                'content' => $content,
                'ostate' => $request->request->get('hideIsLogistic')
            ]);
        }
        return $this->render('back/commande/listeCommande.html.twig', [
            'commandes' => $bonDeCommandeRepo->findBy([], ['date' => 'desc']),
            'commande' => true
        ]);
    }

    public function ajoutCodeInterneClient(BonDeCommande $bonDeCommande, Request $request, EntityManagerInterface $em)
    {

        if($request->isMethod('post'))
        {
            $code = $request->request->get('code_client');

            $bonDeCommande->setCodeInterneClient($code);

            $em->flush($bonDeCommande);

            $this->addFlash(
                'success',
                'Code interne client ajouté avec succès'
            );

        }

        return $this->redirect($request->headers->get('referer'));

    }

    public function actualiser(Devis $devis, Request $request, DevisServices $devisServices)
    {

        $devisServices->refreshOrder($devis);
        return $this->redirect($request->headers->get('referer'));

    }

    public function cancelOrder(BonDeCommande $bonDeCommande, Request $request, EntityManagerInterface $em)
    {
        if($request->isMethod('post'))
        {
            $devis = $bonDeCommande->getDevis();

            $devis->setAbandonner(true);
            $devis->setValider(false);
            $devis->setStatus("Devis annuler");
            $devis->setSigneParClient(false);
            $em->flush();

            $this->addFlash(
                'success',
                "La commande n° {$bonDeCommande->getCode()} à été annulée, vous pouvez le supprimer dans la liste des devis"
            );

            return $this->redirectToRoute("back_commande");
        }
    }

    public function modification(BonDeCommande $bonDeCommande, PositionGammeClientRepository $positionGammeClientRepo, TrieProduit $trieProduit, LogistiqueServices $logistiqueServices, MarqueBlancheRepository $mbRepo, EntityManagerInterface $em, CommandesRepository $commandeRepo)
    {
        $client = $bonDeCommande->getClient();
        
        $gammesClient = $positionGammeClientRepo->findBy(['client' => $client ], ['position' => 'asc']);

        $commandeTrier = $trieProduit->setGammesClient($gammesClient)
                                    ->setCommandes($bonDeCommande->getCommandes())
                                    ->trieCommande();
    
        $logistiqueServices->checkEmptyPrice($bonDeCommande);

        return $this->render('back/commande/modificationCommande.html.twig', [
            'commande' => $bonDeCommande, 'home' => true,
            'devi' => $bonDeCommande->getDevis(),
            'commandes' => $commandeTrier,
            'Client' => $client,
            'marqueBlanches' => $mbRepo->findBy(['client' => $bonDeCommande->getClient()])
        ]);
    }


    public function escompteCommande(BonDeCommande $bonDeCommande, Request $request, EntityManagerInterface $em)
    {
        
        if($request->isMethod('post'))
        {

            $val = $request->request->get('escompte');

            if($val > 0)
            {
              
                $devis = $bonDeCommande->getDevis();
                
                $tHt = $devis->getTotalHt();
    
                $escompte = round(($tHt * $val) / 100, 2);
    
                $bonDeCommande->setEscompte($escompte);
    
                $netFinancier = round($tHt - $escompte, 2);
    
                $bonDeCommande->setNetFinancier($netFinancier);
    
                $tva = round($netFinancier * 0.2, 2);
    
                $bonDeCommande->setTaxe($tva);
    
                $totalTtc = $netFinancier + $tva;
    
                $bonDeCommande->setTotalTtc($totalTtc);
                $bonDeCommande->setPourcentageEscompte($val);
                $bonDeCommande->getResteAPayer($totalTtc);

                $em->flush();

                $this->addFlash('success', 'Escompte ajouté avec succè');

            }
            
            return $this->redirect($request->headers->get('referer'));

        }
        

    }

    public function modificationTarif(BonDeCommande $bonDeCommande, LotCommandeRepository $lotRepo, $logistique, HistoriqueTarifRepository $historiqueRepo, MarqueBlancheRepository $mbRepo, Useful $useful, Mailer $mailer, ProduitRepository $produitRepo, Request $request, EntityManagerInterface $em)
    {
        $commandes = $bonDeCommande->getCommandes();

        if ($request->isMethod('post')) {
            $produit = $produitRepo->find($request->request->get('produit'));
            $customPrice = $request->request->get('prix');
            $declinaison = $request->request->get("declinaison");
            $devis = $bonDeCommande->getDevis();
            $type = $devis->getClient()->getTypeDeClient();
            $typeDeClient = $type->getNom();
            $total = 0;
            $poids = 0;

            foreach ($commandes as $commande) {
                if ($commande->getProduit() == $produit) {
                    if ($commande->getDeclinaison() == $declinaison) {
                        if ($request->request->get('operation') == 2) {
                            $commande->setPrix($customPrice);
                        }
                        $commande->setPrixSpecial($customPrice);

                        $total += $customPrice * $commande->getQuantite();
                    } else {
                        $total +=  $commande->getPrix() * $commande->getQuantite();
                    }
                } else {
                    $total +=  $commande->getPrix() * $commande->getQuantite();
                }
                $poids += $commande->getProduit()->getPoids() * $commande->getQuantite();
            }

            if ($request->request->get('operation') == 2) {

                $tarifModifier = $produit->getTarif();

                $marqueBlanches = $mbRepo->findBy(['client' => $bonDeCommande->getClient()]);

                foreach ($marqueBlanches as $mb) {
                    if ($mb->getProduit() == $produit) {
                        $tarifModifier = $mb->getTarif();
                    }
                }

                $his = $historiqueRepo->findOneBy(['tarif' => $tarifModifier, 'actif' => true]);

                $his->setActif(false);
                $em->flush();

                $histo = new HistoriqueTarif;
                $histo->setTarif($tarifModifier);
                $histo->setActif(true);
                $histo->setMemeTarif(false);
                $histo->setDate(new DateTime());

                if ($tarifModifier->getMemeTarif()) {
                    if (strtolower($typeDeClient) == "grossiste") {
                        $tarifModifier->setPrixDeReferenceGrossiste($customPrice);
                        $histo->setPrixGrossiste($customPrice);
                    } else {
                        $tarifModifier->setPrixDeReferenceDetaillant($customPrice);
                        $histo->setPrixDetaillant($customPrice);
                    }
                } else {
                    $histo->setPrixDetaillant($tarifModifier->getPrixDeReferenceDetaillant());
                    $histo->setPrixGrossiste($tarifModifier->getPrixDeReferenceGrossiste());
                }

                $em->persist($histo);
                $em->flush();

                if (!$tarifModifier->getMemeTarif()) {
                    foreach ($tarifModifier->getPrixDeclinaisons() as $prix) {
                        if ($prix->getActif()) {

                            $prix->setActif(false);
                            $em->flush();

                            $prixD = new PrixDeclinaison;
                            $prixD->setTarif($tarifModifier);
                            $prixD->setDeclinaison($prix->getDeclinaison());
                            if ($prix->getDeclinaison() == $declinaison) {
                                $prixD->setPrix($customPrice);
                            } else {
                                $prixD->setPrix($prix->getPrix());
                            }
                            $prixD->setHistorique($histo);
                            $prixD->setActif(true);
                            $prixD->setDate(new DateTime());
                            $prixD->setTypeDeClient($type);
                            $em->persist($prixD);
                            $em->flush();
                        }
                    }
                }
            }

            foreach ($devis->getDevisProduits() as $prod) {
                if ($prod->getProduit() == $produit && $prod->getDeclinaison() == $declinaison) {
                    $prod->setPrixSpecial($customPrice);

                    if ($request->request->get('operation') == 2) {
                        $prod->setPrix($customPrice);
                    }
                    $em->flush();
                }
            }

            $lotPreparation = $lotRepo->findBy(['bonDeCommande' => $bonDeCommande]);

            foreach ($lotPreparation as $lot) {
                foreach ($lot->getLotQuantites() as $lq) {
                    if ($lq->getProduit() == $produit && $lq->getDeclinaison() == $declinaison) {
                        $lq->setPrixSpecial($customPrice);

                        if ($request->request->get('operation') == 2) {
                            $lq->setPrix($customPrice);
                        }

                        $em->flush();
                    }
                }
            }

            $frais = round($useful->calculFraisLivraison($poids), 2);

            $total = round($total, 2);

            $devis->setMontant($total);

            $totalHt = $total + $frais;

            $devis->setTotalHt($totalHt);

            $devis->setFraisExpedition($frais);

            $taxe = $total * 0.2;
            
            if($devis->getClient()->getPays() != "France")
            {
                $taxe = 0;
            }

            $taxe = round($taxe, 2);

            $devis->setTaxe($taxe);

            $totalTtc = $totalHt + $taxe;
            $totalTtc = round($totalTtc, 2);

            $devis->setTotalTtc($totalTtc, 2);

            $bonDeCommande->setTotalTtc($devis->getTotalTtc());
            $bonDeCommande->setResteAPayer($devis->getTotalTtc());

            $facture = $bonDeCommande->getFactureCommandes();

            if ($facture) {
                foreach ($facture as $fact) {
                    $fact->setMontantAPayer($totalTtc);
                    $fact->setTva($taxe);
                    $fact->setTotalHt($totalHt);
                    $fact->setTotalTtc($totalTtc);
                    $em->flush();
                }
            }

            $em->flush();

            if ($request->request->get('sendmail')) {
                $mailer->sendEditPurchaseOrder($bonDeCommande);
            }

            $this->addFlash('modificationTarifOk', 'Modification tarif effectuée avec succès');


            if ($logistique) {
                return $this->redirectToRoute("back_modification_logistique", ['id' => $bonDeCommande->getId()]);
            } else {
                return $this->redirectToRoute("back_modification_commande", ['id' => $bonDeCommande->getId(), 'number' => $bonDeCommande->getCode()]);
            }
        }
    }

    public function modificationClient(BonDeCommande $bonDeCommande, Request $request)
    {

        if ($request->isMethod('post')) {
            $client = $bonDeCommande->getClient();
            //$client->setAgreement($request->request->get('agreement'));
            $this->getDoctrine()->getManager()->flush();
        }
        $client = $bonDeCommande->getClient();
        $boutique = null;

        if($bonDeCommande->getDevis()->getBoutique())
        {
            $boutique = $bonDeCommande->getDevis()->getBoutique();
        }

        return $this->render('back/commande/modifClient.html.twig', [
            'commande' => $bonDeCommande,
            'commandes' => true,
            'devi' => $bonDeCommande->getDevis(),
            'client' => $client,
            'boutique'=> $boutique
        ]);
    }

    public function paiementFacture(Request $request, FactureMaitreRepository $factureMaitreRepo, Mailer $mailer, EntityManagerInterface $em, FactureCommandeRepository $factureRepo)
    {
        if ($request->isMethod('post')) {
            $shop = null;
            if ($request->request->get('invoice') && $request->request->get('date_paiement')) {
                
                if($request->request->get('facture_maitre'))
                {
                    
                    $invoice = $factureMaitreRepo->find((int)$request->request->get('invoice'));
                    $bonDeCommande = $invoice->getBonDeCommandes()[0];
                    $devis = $bonDeCommande->getDevis();
                    $shop = $devis->getBoutique();
                    
                }
                else
                {
                    $invoice = $factureRepo->find((int)$request->request->get('invoice'));
                    $bonDeCommande = $invoice->getBonDeCommande();
                }
                
                $invoice->setDatePaiement(\DateTime::createFromFormat('d/m/Y', trim($request->request->get('date_paiement'))));
                if (!$invoice->getEstPayer()) {
                    
                    $invoice->setEstPayer(true);
                    
                    if ($request->request->get('amount_paid')) {
                        
                        $invoice->setMontantPayer(floatval($request->request->get('amount_paid')));

                        $balance =  $invoice->getMontantPayer() - $invoice->getMontantAPayer();
                        $invoice->setBalance($balance);
                             
                        if ($balance < 0) {
                            $bonDeCommande->setStatus('Partiel acompte payé');
                        } else {
                            $bonDeCommande->setToutEstPayer(true);
                            $bonDeCommande->setDateToutPaiement(new \DateTime());

                            if($request->request->get('facture_maitre') && $balance <= 0)
                            {
                                foreach($invoice->getBonDeCommandes() as $bon)
                                {
                                    $bon->setToutEstPayer(true);
                                    $bon->setDateToutPaiement(new \DateTime());
                                    $bon->setResteAPayer(0);
                                }
                            }
                        }

                        if( ! $request->request->get('facture_maitre') )
                        {

                            $reste = $bonDeCommande->getResteAPayer() - floatval($request->request->get('amount_paid'));
    
                            $aPayer = $reste;
    
                            if($reste < 0)
                            {
                                $aPayer = 0;
    
                                $bonDeCommande->setAvoir($aPayer);
                            }
    
                            $bonDeCommande->setResteAPayer($reste);
                        }

                    } else {
                        
                        if( ! $request->request->get('facture_maitre') )
                        {
                            $bonDeCommande->setResteAPayer($bonDeCommande->getResteAPayer() - $invoice->getMontantAPayer());
                        }
                        //dump($purchaseOrder->getRemainToPaid());
                    }

                    if ($invoice->getSoldeFinal()) {

                        if($request->request->get('facture_maitre'))
                        {
                            $mailer->sendMasterSoldPaid($bonDeCommande,$invoice, $shop);
                        }
                        else
                        {
                            $mailer->sendSoldPaid($bonDeCommande, $invoice);
                        }
                    }
                    $this->addFlash('success', 'Paiement facture n° ' . $invoice->getNumero(). ' éffectuée avec succès');
                }
                else {
                
                    $bcNouveauResteApayer = $bonDeCommande->getResteAPayer() - floatval($request->request->get('amount_paid'));
                    $bonDeCommande->setResteAPayer($bcNouveauResteApayer);

                    $nouveauMontantPayer = $invoice->getMontantPayer() + floatval($request->request->get('amount_paid'));
                    $newBalance = floatval($request->request->get('amount_paid')) - abs($invoice->getBalance());
                    $invoice->setBalance($newBalance);
                    $invoice->setMontantPayer($nouveauMontantPayer);

                   
                }
                $em->flush();
                return $this->redirect($request->headers->get('referer'));
            }
        }

    }

    public function facture(BonDeCommande $bonDeCommande, FactureCommandeRepository $factureRepo, FactureMaitreRepository $factureMaitreRepo, EntityManagerInterface $em, Request $request, Mailer $mailer)
    {
        
        $factureMaitre = false;

        if( count($bonDeCommande->getFactureMaitres()) > 0)
        {
            $invoices = $bonDeCommande->getFactureMaitres();
            $factureMaitre = true;

        }
        else
        {
            $invoices = $bonDeCommande->getFactureCommandes();

        }

        return $this->render('back/commande/edit-invoice.html.twig', [
            'commande' => $bonDeCommande,
            'commandes' => true,
            'devi' => $bonDeCommande->getDevis(),
            'invoices' => $invoices,
            'factureMaitre' => $factureMaitre
        ]);
    }

    public function ajoutAcompte(Request $request, BonDeCommande $bonDeCommande, EntityManagerInterface $em)
    {
        if($request->isMethod('post'))
        {
            $acompte = $request->request->get("acompte_payer");
            $date = \DateTime::createFromFormat('d/m/Y', trim($request->request->get('date_paiement_acompte')));
            
            $prixTtc = floatval($bonDeCommande->getTotalTtc()) - floatval($acompte);

            $bonDeCommande->setAcompte($acompte);
            $bonDeCommande->setTotalTtc($prixTtc);
            $bonDeCommande->getDevis()->setTotalTtc($prixTtc);
            $bonDeCommande->setDatePaiementAcompte($date);
            $bonDeCommande->setStatus('Partiel acompte payé');
            $bonDeCommande->setResteAPayer($prixTtc);
            

            $em->flush();
            
            $this->addFlash('success', 'Acompte ajouté avec succès');

            return $this->redirect($request->headers->get('referer'));
            
        }
    }

    public function creationCommande(BonDeCommande $bonDeCommande, Useful $useful, EntityManagerInterface $em, CommandesRepository $commandeRepo, Request $request)
    {
        
        $dateLivraison = $bonDeCommande->getDate();
        $dateLivraison->modify('+10 days');
        $bonDeCommande->setDateLivraison($dateLivraison);
        $unit = $bonDeCommande->getDevis();

        foreach ($unit->getDevisProduits() as $uni) {

            if ($uni->getDeclinaison()) {

                $commande = new Commandes();
                $commande->setDateLivraison($dateLivraison);
                $commande->setBonDeCommande($bonDeCommande);
                //if($unit->getProduit)
                $commande->setProduit($uni->getProduit());
                $commande->setQuantite($uni->getQuantite());
                $commande->setDevis($unit);
                $commande->setDeclineAvec($uni->getDeclineAvec());
                $commande->setDeclinaison($uni->getDeclinaison());
                $commande->setOffert($uni->getOffert());
                $commande->setPrix($uni->getPrix());
                $em->persist($commande);
            } else {

                $exist = $commandeRepo->findOneBy(['devis' => $unit->getId(), 'produit' => $uni->getProduit()]);

                if (!$exist) {
                    $commande = new Commandes();
                    $commande->setDateLivraison($dateLivraison);
                    $commande->setBonDeCommande($bonDeCommande);
                    //if($unit->getProduit)
                    $commande->setProduit($uni->getProduit());
                    $commande->setQuantite($uni->getQuantite());
                    $commande->setDevis($unit);
                    $commande->setOffert($uni->getOffert());
                    $commande->setDeclineAvec($uni->getDeclineAvec());
                    $commande->setPrix($uni->getPrix());
                    $em->persist($commande);
                } else {
                    $exist->setQuantite($exist->getQuantite() + $uni->getQuantite());
                }
            }


            $em->flush();
        }
        $bonDeCommande->setEnvoyerAuLogistique(true);
        $bonDeCommande->setStatusLogistique("Non traitée");
        
        $em->flush();

        if ($request->isXmlHttpRequest()) {
            return new Response(200);
        } else {
            return $this->redirectToRoute('back_commande');
        }
    }

    public function telechargerBdc(Devis $devis, Pdf $snappy, MarqueBlancheRepository $mbRepo)
    {

        $html = $this->renderView('front/pdf/devis.html.twig', [
            'devis' => $devis,
            'marqueBlanches' => $mbRepo->findBy(['client' => $devis->getClient()])
        ]);

        $filename = $devis->getCode() . ".pdf";

        $footer = $this->renderView('/back/pdf/footer.html.twig');
        $header = $this->renderView('/back/pdf/header.html.twig');
        $option = ['footer-html' => $footer, 'header-html' => $header];

        return new PdfResponse(
            $snappy->getOutputFromHtml($html, $option),
            $filename
        );
    }

    public function ajoutCodeInterne(BonDeCommande $bonDeCommande, $preparation, Request $request, EntityManagerInterface $em)
    {

        if ($request->isMethod('post')) {

            $bonDeCommande->setCodeInterne($request->request->get('numCommandeInterne'));
            $em->flush();

            $this->addFlash('codeInterneSuccess', 'Ajout numéro de commande interne effectuée avec succès');

            if ($preparation) {
                return $this->redirectToRoute('back_logistique_bonPreparation');
            }

            return $this->redirectToRoute('back_modification_commande', ['number' => $bonDeCommande->getCode(), 'id' => $bonDeCommande->getId()]);
        }
    }
}
