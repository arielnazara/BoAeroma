<?php


namespace App\Service;

use DateTime;
use Stripe\Invoice;
use App\Entity\LotCommande;
use App\Entity\BonDeCommande;
use App\Entity\FactureAvoir;
use App\Entity\FactureMaitre;
use App\Entity\FactureCommande;
use App\Entity\Prestation;
use App\Repository\FactureCommandeRepository;
use App\Repository\FactureMaitreRepository;
use App\Repository\PrestationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class InvoiceProcessing
{

    protected $em;
    protected $flashBag;
    protected $factureCommandeRepo;
    protected $factureMaitreRepo;
    protected $prestationRepo;

    public function __construct(EntityManagerInterface $em, FlashBagInterface $flashBag, PrestationRepository $prestationRepo, FactureMaitreRepository $factureMaitreRepo, FactureCommandeRepository $factureCommandeRepo)
    {
        $this->em = $em;
        $this->flashBag = $flashBag;
        $this->factureCommandeRepo = $factureCommandeRepo;
        $this->factureMaitreRepo = $factureMaitreRepo;
        $this->prestationRepo = $prestationRepo;

    }

    public function blInvoice(BonDeCommande $po, LotCommande $lotPreparation): ?FactureCommande
    {
        
        $invoice = null;
        
        if(! $lotPreparation->getFactureCommande()){
           
            $nextFactNumber = $this->incrementInvoice();

            $invoice = new FactureCommande();
                                
            $invoice->setBonDeCommande($po);
            $invoice->setClient($po->getClient());
            $invoice->setDevis($po->getDevis());
            $invoice->setTitre('Facture-final');
            $invoice->setSoldeFinal(true);
            $invoice->setIncrement($nextFactNumber);
            
            $this->em->persist($invoice);
            $this->em->flush();
        
            $lotPreparation->setFactureCommande($invoice);
        
            $date = new DateTime();
        
            $nom = $date->format("ymd")."-".$po->getCode()."-";

            $nom .= str_pad($nextFactNumber, 4, '0', STR_PAD_LEFT);
            
            $invoice->setNumero($nom);

            /**Debut modif */
            /*$client = $lotPreparation->getClient();
            $lastInvoice = $this->factureCommandeRepo->getLastInvoiceByClient($client->getId());
            if( $lastInvoice->getBalance() ){

            }*/
            /** Fin modif */
        
            $this->em->flush();
        
            $sum = $this->calculateInvoice($lotPreparation);
    
            $this->calculTtc($invoice, $sum);
          
            $this->em->flush();
        }

        return $invoice;
    }

    public function calculateInvoice(LotCommande $lotPreparation): float
    {

        $sum = 0;

        foreach($lotPreparation->getLotQuantites() as $lq)
        {
            if(!$lq->getOffert())
            {
                $val = null;

                if($lq->getPrixSpecial())
                {
                    $val = $lq->getPrixSpecial() * $lq->getQuantite();
                }
                else
                {
                    $val = $lq->getPrix() * $lq->getQuantite();
                }

                $sum += $val;
            }

        }
        
        return $sum;
    }

    public function oneInvoice(array $lotCommandes, BonDeCommande $po)
    {

        $nextFactNumber = $this->incrementInvoice();

        $invoice = new FactureCommande();
                                
        $invoice->setTitre('Facture-final');
        $invoice->setSoldeFinal(true);
        $invoice->setBonDeCommande($po);
        $invoice->setClient($po->getClient());
        $invoice->setDevis($po->getDevis());
        $invoice->setIncrement($nextFactNumber);

        $this->em->persist($invoice);
        $this->em->flush();
        

        $total = 0;

        foreach($lotCommandes as $lot)
        {
            $lot->setFactureCommande($invoice);
            $total += $this->calculateInvoice($lot);
        }
        
        $date = new DateTime();

        $nom = $date->format("ymd")."-".$po->getCode()."-";

        $nom .= str_pad($nextFactNumber, 4, '0', STR_PAD_LEFT);
    
        $invoice->setNumero($nom);
    
        
        $this->calculTtc($invoice, $total);
        
        $this->em->flush();
        
        return $invoice;

    }

    public function calculTtc(?FactureCommande $invoice = null, float $sum, ?FactureMaitre $invoiceMaster = null): void
    {
        if($invoiceMaster)
        {
            $invoice = $invoiceMaster;
        }
        
        $invoice->setTotal($sum);

        if($invoiceMaster)
        {
            $totalHt = $sum;
            foreach($invoiceMaster->getBonDeCommandes() as $bon)
            {
                $client = $bon->getDevis()->getClient();
                break;
            }
        }
        else
        {
            $totalHt = $sum + $invoice->getDevis()->getFraisExpedition();
            $client = $invoice->getDevis()->getClient();
        }

        $invoice->setTotalHt($totalHt);

        $tva = $totalHt * 0.2;

        if($client->getPays() != "France")
        {
            $tva = 0;
        }

        $invoice->setTva($tva);

        $ttc = $totalHt + $tva;

        $invoice->setTotalTtc($ttc);

        $invoice->setMontantAPayer($ttc);


    }

    public function extravapeInvoice(array $bdc)
    {
        $nextFactNumber = $this->incrementInvoice();

        $maitre = new FactureMaitre;
                
        $date = new DateTime();
        $finalCode = $date->format("ymd")."-";
        $count = 0;
        $orderCode = null;

        $sum = 0;

        $maitre->setTitre('Facture-final');
        $maitre->setSoldeFinal(true);
        $maitre->setIncrement($nextFactNumber);

        foreach($bdc as $bon)
        {   
           
            $orderCode .= " {$bon->getCode()}";

            $code = explode("-", $bon->getCode());

            if(count($code) > 1)
            {
                $code = $code['1'].'-';
            }
            else
            {
                dd('code');
            }

            $finalCode .= $code;

            
            $maitre->addBonDeCommande($bon);
           
            foreach($bon->getLotCommandes() as $lot)
            {
                if(!$lot->getFactureMaitre())
                {
                   
                    $sum += $this->calculateInvoice($lot);
    
                    $lot->setFacturer(true);

                    $lot->setFactureMaitre($maitre);
                }

            }

            
        }
        
        $this->calculTtc(null, $sum, $maitre);

        $this->em->persist($maitre);
        $this->em->flush();

        //$last = $this->factureCommandeRepo->lastInvoice();

        //$lastTab = explode("-", $last->getNumero());

        $finalCode .= str_pad($nextFactNumber, 4, '0', STR_PAD_LEFT);

        $maitre->setNumero($finalCode);
        $this->em->flush();


        $text = ($count > 1 ) ? 'les commandes' : 'le commande';

        $this->flashBag->add("groupExtravapeSuccess", "Création facture pour $text $orderCode éfféctuée avec succès");

        return $maitre;
    }

    public function incrementInvoice(): int
    {

        $lastFC = null;
        $lastFact = $this->factureCommandeRepo->lastInvoice();

        if($lastFact)
        {
            $lastFC = $lastFact->getIncrement();
        }

        if(!$lastFC || $lastFC && $lastFC < 3894)
        {
            $lastFC = 3894;
        }

        $increment = $lastFC;

        $lastFM = null;

        $lastFactM = $this->factureMaitreRepo->lastMasterInvoice();

        if($lastFactM)
        {
            $lastFM = $lastFactM->getIncrement();
        }

        if(!$lastFM || $lastFM && $lastFM < 3894)
        {
            $lastFM = 3894;
        }

        if($lastFC < $lastFM)
        {
            $increment = $lastFM;
        }

        $lastPrestation = $this->prestationRepo->findBy([], ['increment' => 'desc'], 1);

        if( count($lastPrestation) > 0 ){
            if($lastPrestation[0]->getIncrement() > $increment)
            {
                $increment = $lastPrestation[0]->getIncrement();
            }
        }
        
        return $increment + 1;
        
    }

    function getAllInvoices($paid = false, $notPaid = false, $dateAsc = false, $dateDesc = false): array
    {
        
        if($paid)
        {
            $invoicesOrder = $this->factureCommandeRepo->findBy(['estPayer' => true]);
            $invoicesMaster = $this->factureMaitreRepo->findBy(['estPayer' => true]);
            $prestations = $this->prestationRepo->findBy(['estPayer' => true]);
        }
        elseif($notPaid)
        {
            $invoicesOrder = $this->factureCommandeRepo->getNotPaid();
            $invoicesMaster = $this->factureMaitreRepo->getMasterNotPaid();
            $prestations = $this->prestationRepo->getPrestationNotPaid();
        }
        elseif($dateAsc)
        {
            $invoicesOrder = $this->factureCommandeRepo->findBy([], ['date' => 'asc']);
            $invoicesMaster = $this->factureMaitreRepo->findBy([], ['date' => 'asc']);
            $prestations = $this->prestationRepo->findBy([], ['datePaiement' => 'asc']);
        }
        elseif($dateDesc)
        {
            $invoicesOrder = $this->factureCommandeRepo->findBy([], ['date' => 'desc']);
            $invoicesMaster = $this->factureMaitreRepo->findBy([], ['date' => 'desc']);
            $prestations = $this->prestationRepo->findBy([], ['datePaiement' => 'desc']);
        }
        else
        {
            $invoicesOrder = $this->factureCommandeRepo->findAll();
            $invoicesMaster = $this->factureMaitreRepo->findAll();
            $prestations = $this->prestationRepo->findAll();
        }
        
        $invoices = array_merge($invoicesOrder, $invoicesMaster, $prestations);
        //dd($invoices);
        
        for($i=0; $i<count($invoices)-1; $i++) {
            for($j=0; $j<(count($invoices)-1-$i); $j++) {

                $nombre1  = $invoices[$j];
                $nombre2  = $invoices[$j+1];
                
                 
                $nombre1 = explode("-", $nombre1->getNumero());
                $nombre1 = end($nombre1);
                

                $nombre2 = explode("-", $nombre2->getNumero());
                $nombre2 = end($nombre2);

                if($dateAsc)
                {
                    if ( intval($nombre1) > intval($nombre2) ) {
                        $temp = $invoices[$j+1];
                        $invoices[$j+1] = $invoices[$j];
                        $invoices[$j] = $temp;
                    }
                }
                else
                {
                    if ( intval($nombre1) < intval($nombre2) ) {
                        $temp = $invoices[$j+1];
                        $invoices[$j+1] = $invoices[$j];
                        $invoices[$j] = $temp;
                    }
                }
                
            }
        }
        
        return $invoices;
    }

    public function generationFactureDePrestation(Prestation $prestation, Useful $useful, KernelInterface $kernel)
    {
        $path = "/pdf/order/Facture-{$prestation->getNumero()}.pdf";
        
        $finalPath = "{$kernel->getProjectDir()}/public$path";
       
        if(file_exists($finalPath))
        {
            unlink($finalPath);
        }

        $useful->regeneratePrestationPdf($prestation);

        return $path;
    }


    public function calculEscompte($tHt, $val): array
    {
        $escompte = round(($tHt * $val) / 100, 2);
        $netFinancier = round($tHt - $escompte, 2);
        $tva = round($netFinancier * 0.2, 2);
        $totalTtc = $netFinancier + $tva;

        return [
            "escompte" => $escompte,
            "netFinancier" => $netFinancier, 
            "tva" => $tva, 
            "totalTtc" => $totalTtc
        ];

    }
}