<?php


namespace App\Service;

use Knp\Snappy\Pdf;
use App\Entity\Devis;
use App\Entity\LotOrder;
use App\Entity\LotCommande;
use App\Entity\BonLivraison;
use App\Entity\InvoiceOrder;
use App\Entity\BonDeCommande;
use App\Entity\Commandes;
use App\Entity\FactureAvoir;
use App\Entity\PurchaseOrder;
use App\Entity\FactureCommande;
use App\Entity\FactureMaitre;
use App\Entity\MarqueBlanche;
use App\Entity\Prestation;
use App\Repository\BonDeCommandeRepository;
use App\Repository\ClientRepository;
use App\Repository\CommandesRepository;
use App\Repository\DevisProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use App\Repository\FactureCommandeRepository;
use App\Repository\GammeMarqueBlancheRepository;
use App\Repository\GammeRepository;
use App\Repository\MarqueBlancheRepository;
use App\Repository\OrdreCeresRepository;
use App\Repository\PositionGammeClientRepository;
use App\Repository\ProduitRepository;
use App\Repository\ShopRepository;
use DateTime;
use Doctrine\Common\Collections\Collection;
use PhpOffice\PhpSpreadsheet\Calculation\DateTimeExcel\Date;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Shared\OLE\PPS\File;
use PhpOffice\PhpSpreadsheet\Shared\Trend\Trend;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Stripe\Invoice;

use function PHPSTORM_META\elementType;

class Useful
{
    private $em;
    private $mailer;
    private $templating;
    private $snappy_pdf;
    private $publicRoot;
    private $commandeRepo;
    private $produitRepo;
    private $gammeRepo;
    private $frais;
    private $mbRepo;
    private $devisProduitRepo;
    private $logistiqueService;
    private $clientRepo;
    private $ordreCeresRepository;
    private $marqueBlancheRepository;
    private $gammeMarqueBlancheRepository;
    private $trieProduit;
    private $positionGammeClientRepo;
    private $shopRepository;


    public function __construct(EntityManagerInterface $em,OrdreCeresRepository $ordreCeresRepository, PositionGammeClientRepository $positionGammeClientRepo, TrieProduit $trieProduit, GammeMarqueBlancheRepository $gammeMarqueBlancheRepository, MarqueBlancheRepository $marqueBlancheRepository, DevisProduitRepository $devisProduitRepo,ClientRepository $clientRepo, LogistiqueServices $logistiqueServices, GammeRepository $gammeRepository, MarqueBlancheRepository $mbRepo, ProduitRepository $produitRepository, \Swift_Mailer $mailer, EngineInterface $templating, Pdf $snappy_pdf, $publicRoot, CommandesRepository $commandeRepo, ShopRepository $shopRepository)
    {
        $this->em = $em;
        $this->mailer = $mailer;
        $this->templating = $templating;
        $this->snappy_pdf = $snappy_pdf;
        $this->publicRoot = realpath($publicRoot . '/../public');
        $this->commandeRepo = $commandeRepo;
        $this->produitRepo = $produitRepository;
        $this->gammeRepo = $gammeRepository;
        $this->mbRepo = $mbRepo;
        $this->devisProduitRepo = $devisProduitRepo;
        $this->logistiqueService = $logistiqueServices;
        $this->clientRepo = $clientRepo;
        $this->ordreCeresRepository = $ordreCeresRepository;
        $this->marqueBlancheRepository = $marqueBlancheRepository;
        $this->gammeMarqueBlancheRepository = $gammeMarqueBlancheRepository;
        $this->trieProduit = $trieProduit;
        $this->positionGammeClientRepo = $positionGammeClientRepo;
        $this->shopRepository = $shopRepository;

        
    }

    public function getProducts(Devis $devis)
    {
        $products = [];
        $n = 0;
        foreach ($devis->getDevisProduits() as $unit) {
            $product = $unit->getProduct();
            $n++;
            $products[$n]['designation'] = $product->getDesignation();
            $products[$n]['unit'] = $unit->getUnit();
            $products[$n]['price'] = $product->getPrice();
            $products[$n]['code'] = $product->getCode();
            $products[$n]['image'] = $product->getImage();
            $products[$n]['total'] = $unit->getUnit() * $product->getPrice();
            $products[$n]['prixDeVenteConseiller'] = $product->getPrixDeVenteConseiller();
        }

        return ['products' => $products, 'length' => $n];
    }

    public function ajoutNumBL(BonLivraison $bonLivraison, BonDeCommande $bonDeCommande): int
    {
        $position = null;
        $count = 1;
        $lotCommande = $bonLivraison->getLotCommande();

        foreach($bonDeCommande->getLotCommandes() as $lot)
        {
            if($lotCommande == $lot){
                $position = $count;
            }

            $count++;
        }

        return $position;
    }


    public function regeneratePrestationPdf(Prestation $prestation)
    {
        $filesystem = new Filesystem();

        $filename = 'Facture-' . $prestation->getNumero() . ".pdf";

        if ($filesystem->exists($this->publicRoot . '/pdf/order/' . $filename)) {
            $filesystem->remove($this->publicRoot . '/pdf/order/' . $filename);
        }
       
        $html = $this->templating->render('back/pdf/facture_prestation.html.twig', [
            'prestation' => $prestation,
        ]);

        $footer = $this->templating->render('/back/pdf/factfoot.html.twig');
        $header = $this->templating->render('/back/pdf/header.html.twig');
        $option = ['footer-html' => $footer, 'header-html' => $header];

        $this->snappy_pdf->generateFromHtml($html, $this->publicRoot . '/pdf/order/' . $filename, $option);
    }


    public function createBl($adresseFacturation = null,LotCommande $lotCommande, $quantityTotal, $estGenerer = false)
    {
        $next  = $this->logistiqueService->incrementBL();

        $bl = new BonLivraison();

        $client = $lotCommande->getClient();

        if($adresseFacturation)
        {
            $bl->setAdresseFacturation(true);
        }
        elseif($client->getAdresseLivraisons())
        {
            foreach($client->getAdresseLivraisons() as $adresse)
            {
                if($adresse->getActive())
                {
                    $bl->setAdresseLivraison($adresse);
                }
            }
        }

        $bl->incrementNumber();
        $bl->setBonDeCommande($lotCommande->getBonDeCommande());
        $bl->setLotCommande($lotCommande);
        $bl->setEstGenerer($estGenerer);
        $bl->setFacture($lotCommande->getFactureCommande());
        $bl->setNombreColis($lotCommande->getNombreColis());
        $bl->setExpresse($lotCommande->getExpresse());
        $bl->setAffretement($lotCommande->getAffretement());
        $bl->setCode($lotCommande->getBonDeCommande()->getCode()."-$next");
        $bl->setIncrement($next);

        if ($lotCommande->getDateExpedition()) {
            $bl->setDateExpedition($lotCommande->getDateExpedition());
        } else {
            $bl->setDateExpedition($lotCommande->getDate());
        }

        $bl->setQuantite($quantityTotal);

        $this->em->persist($bl);

        $this->em->flush();

        return $bl;
    }



    public function getInvoiceLotOrders(Collection $lotCommande, FactureCommande $invoice)
    {
        $lotOrders = [];
        foreach($lotCommande as $lot)
        {
            if($lot->getFactureCommande() == $invoice)
            {
                array_push($lotOrders, $lot);
            }
        }

        return $lotOrders;
    }

    public function factureAvoir(FactureAvoir $facture)
    {   
        $filesystem = new Filesystem();
        $filename = 'Facture-' . $facture->getNumero() . ".pdf";
        $path = $this->publicRoot . '/pdf/order/' . $filename;
        
        if ($filesystem->exists($path)) {
            unlink($path);
        }
        
        if($facture->getFacture())
        {
            $client = $facture->getFacture()->getClient();
            if($facture->getFacture()->getDevis()->getShop() === "grossiste_greendot")
            {
                $client = $this->clientRepo->find(1);
            }
        }
        else if($facture->getFactureMaitre())
        {
            $client = $facture->getFactureMaitre()->getClient();
        }
        else {
            $client = $facture->getPrestation()->getClient();
            
        }

        $html = $this->templating->render('back/pdf/facture_avoir.html.twig', [
            'facture' => $facture,
            'client' => $client
        ]);

        $footer = $this->templating->render('/back/pdf/factfoot.html.twig');
        $header = $this->templating->render('/back/pdf/header.html.twig');
        $option = ['footer-html' => $footer, 'header-html' => $header];

        $this->snappy_pdf->generateFromHtml($html, $this->publicRoot . '/pdf/order/' . $filename, $option);

        return $filename;

    }

    public function regeneratePdf(?BonDeCommande $purchaseOrder = null,  ?FactureCommande $invoiceOrder = null, ?FactureMaitre $factureMaitre = null)
    {
        
        $filesystem = new Filesystem();
        $shop = null;
        if($factureMaitre)
        {
            $invoiceOrder = $factureMaitre;
        }

        $filename = 'Facture-' . $invoiceOrder->getNumero() . ".pdf";

        if ($filesystem->exists($this->publicRoot . '/pdf/order/' . $filename)) {
            $filesystem->remove($this->publicRoot . '/pdf/order/' . $filename);
        }

        $adresseFacturation = null;

        if($purchaseOrder && $purchaseOrder->getDevis()->getShop() === "grossiste_greendot")
        {
            $email = "patricehennion@gmail.com";
            $adresseFacturation = $this->clientRepo->findOneBy(["email" => $email]);
        }
        
        if($purchaseOrder)
        {
            $lotOrders = $this->getInvoiceLotOrders($purchaseOrder->getLotCommandes(), $invoiceOrder);

            if ($invoiceOrder->getSoldeFinal()) {
                $html = $this->templating->render('back/pdf/invoice-shipped.html.twig', [
                    'invoiceOrder' => $invoiceOrder,
                    'purchaseOrder' => $purchaseOrder,
                    'devis' => $invoiceOrder->getDevis(),
                    'lotOrders' => $lotOrders,
                    'marqueBlanches' => $this->mbRepo->findBy(['client' => $purchaseOrder->getClient()]),
                    'adresseFacturation' => $adresseFacturation ? $adresseFacturation : null
                    
                ]);
            }
        }
        else
        {
            
            $bonDeCommandes = $invoiceOrder->getBonDeCommandes();
            
            if(count($bonDeCommandes) > 0)
            {
                
                $client = $bonDeCommandes[0]->getClient();
                $devis = $bonDeCommandes[0]->getDevis();
                $shop = $devis->getBoutique();
                if ($invoiceOrder->getSoldeFinal()) {
                    
                    $html = $this->templating->render('back/pdf/invoice-master.html.twig', [
                        'invoiceOrder' => $invoiceOrder,
                        'client' => $client,
                        'marqueBlanches' => $this->mbRepo->findBy(['client' => $client]),
                        'adresseFacturation' => $adresseFacturation ? $adresseFacturation : null,
                        'addresseLivraison' => $shop ? $shop : null
                    ]);
                }
            }
        }

      

        $footer = $this->templating->render('/back/pdf/factfoot.html.twig');
        $header = $this->templating->render('/back/pdf/header.html.twig');
        $option = ['footer-html' => $footer, 'header-html' => $header];

        $this->snappy_pdf->generateFromHtml($html, $this->publicRoot . '/pdf/order/' . $filename, $option);
    }

    public function createInvoice(BonDeCommande $purchaseOrder,  FactureCommande $invoiceOrder)
    {
        
        $filesystem = new Filesystem();
        $filename = 'Facture-' . $invoiceOrder->getNumero() . ".pdf";

        if ($filesystem->exists($this->publicRoot . '/pdf/order/' . $filename)) {
            $filesystem->remove($this->publicRoot . '/pdf/order/' . $filename);
        }
        $devis = $invoiceOrder->getDevis();
        $produits = $this->devisProduitRepo->findBy(['devis' => $devis]);
        
        $commandeOrdonner = $this->logistiqueService->trieCommande($produits);

        $produitOrdonner = [];

        foreach($commandeOrdonner as $commande)
        {
            foreach($commande as $com)
            {
                array_push($produitOrdonner, $com);
            }
        }

        $adresseFacturation = null;

        if($purchaseOrder->getDevis()->getShop() === "grossiste_greendot")
        {
            $email = "patricehennion@gmail.com";
            $adresseFacturation = $this->clientRepo->findOneBy(["email" => $email]);
        }
      

        if ($invoiceOrder->getSoldeFinal()) {
            $html = $this->templating->render('back/pdf/invoice.html.twig', [
                'invoiceOrder' => $invoiceOrder,
                'purchaseOrder' => $purchaseOrder,
                'devis' => $devis ,
                'produits' => $produitOrdonner,
                'marqueBlanches' => $this->mbRepo->findBy(['client' => $purchaseOrder->getClient()]),
                'adresseFacturation' => $adresseFacturation ? $adresseFacturation : null
            ]);
        }

        $footer = $this->templating->render('/back/pdf/factfoot.html.twig');
        $header = $this->templating->render('/back/pdf/header.html.twig');
        $option = ['footer-html' => $footer, 'header-html' => $header];

        $this->snappy_pdf->generateFromHtml($html, $this->publicRoot . '/pdf/order/' . $filename, $option);
    }

    public function calculFraisLivraison($poids)
    {
        switch (true) {
            case $poids >= 0 && $poids <= 0.99:
                $this->frais = 8.28;
                break;
            case $poids >= 1 && $poids <= 1.99:
                $this->frais = 9.09;
                break;
            case $poids >= 2 && $poids <= 2.99:
                $this->frais = 9.9;
                break;
            case $poids >= 3 && $poids <= 3.99:
                $this->frais = 10.71;
                break;
            case $poids >= 4 && $poids <= 4.99:
                $this->frais = 11.52;
                break;
            case $poids >= 5 && $poids <= 5.99:
                $this->frais = 12.33;
                break;
            case $poids >= 6 && $poids <= 6.99:
                $this->frais = 13.44;
                break;
            case $poids >= 7 && $poids <= 7.99:
                $this->frais = 13.95;
                break;
            case $poids >= 8 && $poids <= 8.99:
                $this->frais = 14.76;
                break;
            case $poids >= 9 && $poids <= 9.99:
                $this->frais = 15.57;
                break;
            case $poids >= 10 && $poids <= 14.99:
                $this->frais = 19.62;
                break;
            case $poids >= 15 && $poids <= 19.99:
                $this->frais = 23.67;
                break;
            case $poids >= 20 && $poids <= 24.99:
                $this->frais = 27.72;
                break;

            case $poids >= 25:
                $this->frais = 31.77;
                break;
        }

        return $this->frais;
    }

    public function excelHeader ($bonDeCommande){
        
        $spreadsheet = new Spreadsheet();

        $array = false; 

        $dateCommande = null;
        $dateTelechargement = null;

        if($bonDeCommande instanceof BonDeCommande)
        {
            $bonDeCommande = $bonDeCommande;
            $dateCommande = $bonDeCommande->getDate()->format('d/m/Y');

            if($bonDeCommande->getDownloadDate())
            {
                $dateTelechargement = $bonDeCommande->getDownloadDate()->format('d/m/Y');
            }
        }
        else
        {
            $bonDeCommande = $bonDeCommande[0]->getBonDeCommande();
            
            $array = true; 

            $groupe = $bonDeCommande->getGroupeBondeCommandes()[0];
            
            $dateCommande = $bonDeCommande->getDate()->format('d/m/Y');

            if($groupe->getDownloadDate())
            {
                $dateTelechargement = $groupe->getDownloadDate()->format('d/m/Y');
            }

        }

        $client = $bonDeCommande->getClient();

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->getPageSetup()
            ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT)
            ->setScale(50);
            //->setScale(60);

        $styleHeader = [
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
            ],
            'borders' => [
                'outline' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],

        ];

        $styleArray = [
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
            ],
            'borders' => [
                'outline' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                    'color' => ['argb' => '000000'],
                ],
            ],

        ];



        $listeGamme = [];

        foreach ($bonDeCommande->getCommandes() as $com) 
        {
            if (!in_array($com->getProduit()->getGamme()->getId(), $listeGamme)) 
            {
                array_push($listeGamme, $com->getProduit()->getGamme()->getId());
            }
        }

        
        $nbDeclinaison = 5;
       

        $declinaisonCommandes = [];

        foreach($bonDeCommande->getCommandes() as $commande)
        {
            $produit = $commande->getProduit();

            if( !array_key_exists($produit->getId(), $declinaisonCommandes))
            {
                if($produit->getPrincipeActif())
                {
                    $declinaisonCommandes[$produit->getId()] = count($produit->getDeclinaison());
                }
            }
        }

        if (!empty($declinaisonCommandes)) {
            foreach ($declinaisonCommandes as $nombreDeclinaison) {

                if ($nombreDeclinaison > 5) {
                    $nbDeclinaison = $nombreDeclinaison;
                }
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
        
        $colHeader = "A";

        $nbDeclinaison = ($nbDeclinaison + 2) * 3 ;

        $colHeader = $this->increment($colHeader, $nbDeclinaison);

        if($nbDeclinaison > 22)
        {
            $sheet->getPageSetup()
                    ->setScale(50);
        }

        $sheet->mergeCells("A1:B1");

        $sheet->setCellValue('A1', "Date de la commande : ");
        $sheet->getStyle('A1')->getFont()->setBold(true);

        $sheet->setCellValue('C1', $dateCommande);
        $sheet->getStyle('C1')->getFont()->setBold(true);

        $sheet->mergeCells("A2:B2");

        $sheet->setCellValue('A2', "Date de téléchargement : ");
        $sheet->getStyle('A2')->getFont()->setBold(true);

        $sheet->setCellValue('C2', $dateTelechargement);
        $sheet->getStyle('C2')->getFont()->setBold(true);
        
        $sheet->getStyle('A2')
            ->getAlignment()
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)
            ->getWrapText(true);
        
        
        $sheet->setCellValue($this->decrement($colHeader, 4) . '1', 'BON DE PRODUCTION')
            ->getStyle($this->decrement($colHeader, 4) . "1")->getFont()->setSize(18);
        $sheet->getStyle($this->decrement($colHeader, 4) . "1")->getFont()->setBold(true);

        $sheet->mergeCells("A4:B4");

        if(!$array)
        {
            $sheet->setCellValue('A4', 'Numéro de commande client');
        }

        $sheet->getStyle('A4')->getFont()->setBold(true)->setUnderline(true);
        $sheet->getStyle('A4')
            ->getAlignment()
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)
            ->getWrapText(true);

        $sheet->mergeCells("A5:B5");

        if(!$array)
        {
            $sheet->setCellValue('A5', $bonDeCommande->getCode());
        }

        $sheet->getStyle('A5')
            ->getAlignment()
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)
            ->getWrapText(true);

        $sheet->getStyle('A5')
            ->getFont()
            ->setBold(true);

        $sheet->setCellValue($this->decrement($colHeader, 5) . '3', 'Numéro de commande interne');
        $sheet->getStyle($this->decrement($colHeader, 5) . '3')->getFont()->setBold(true)->setUnderline(true);

        $sheet->mergeCells($this->decrement($colHeader, 9) . "4:" . $colHeader . "4");

        if ($bonDeCommande->getCodeInterne()) {
            $sheet->setCellValue($this->decrement($colHeader, 9) . '4', $bonDeCommande->getCodeInterne());
            $sheet->getStyle($this->decrement($colHeader, 9) . '4')->getFont()->setBold(true);
        }

        $sheet->setCellValue('B6', 'Société :');
        $sheet->getStyle('B6')->getFont()->setBold(true)->setUnderline(true);
        $sheet->mergeCells("B7:" . $this->decrement($colHeader, 2) . "10");

        if($array)
        {
            $sheet->setCellValue("B7", "Regroupement extravape")
                ->getStyle("B7")->getFont()->setSize(20);
        }
        elseif($bonDeCommande->getClient()->getRaisonSocial()) 
        {

            if($bonDeCommande->getDevis()->getBoutique())
            {
                $sheet->setCellValue("B7", $bonDeCommande->getDevis()->getBoutique()->getNomShop())
                    ->getStyle("B7")->getFont()->setSize(20);
            }
            else
            {
                $sheet->setCellValue("B7", $bonDeCommande->getClient()->getRaisonSocial())
                    ->getStyle("B7")->getFont()->setSize(20);
            }

        }
        $sheet->getStyle("B7")
            ->getAlignment()
            ->setWrapText(true);

        $sheet->getStyle("B7")
            ->getAlignment()
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        $sheet->getStyle('B7:' . $this->decrement($colHeader, 2) . '10')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('ADD8E6');

        $sheet->getStyle('B6:' . $this->decrement($colHeader, 2) . '10')->applyFromArray($styleHeader);

        $result = 0;
        $b = "B";

        while ($b != $colHeader) {
            $b = $this->increment($b, 1);
            $result++;
        }

        $result = round($result / 2);

        $sheet->mergeCells("B11:" . $this->increment('A', $result - 3) . "11");

        $sheet->setCellValue('B11', 'N° TVA Intracommunautaire :');
        $sheet->getStyle('B11')->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);

        $sheet->getStyle("B11")
            ->getAlignment()
            ->setWrapText(true);

        $sheet->getStyle("B11")
            ->getAlignment()
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        $sheet->mergeCells("B12:" . $this->increment('A', $result - 3) . "13");
        $sheet->setCellValue('B12', $bonDeCommande->getClient()->getNoTva());

        $sheet->getStyle("B13")
            ->getAlignment()
            ->setWrapText(true);

        $sheet->getStyle("B12")
            ->getAlignment()
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        $sheet->mergeCells($this->increment('A', $result - 2) . "11:" . $this->decrement($colHeader, 2) . '11');
        $codification = $this->increment("B", round($nbDeclinaison / 2) + 1);

        $sheet->getStyle('B11:' . $this->decrement($colHeader, 2) . '13')->applyFromArray($styleHeader);

        $sheet->setCellValue($this->increment('A', $result - 2) . "11", 'Votre Codification Interne :');

        $sheet->mergeCells($this->increment('A', $result - 2) . "12:" . $this->decrement($colHeader, 2) . '13');


        $sheet->mergeCells("A15:" . $this->decrement($codification, round($nbDeclinaison / 4) + 3) . '16');
        $sheet->setCellValue('A15', 'Adresse de LIVRAISON :');
        $sheet->getStyle("A15")
            ->getAlignment()
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        $sheet->getStyle('A15')->getFont()->setUnderline(true);

        $lastCell = $this->decrement($codification, round($nbDeclinaison / 4) + 3);


        $sheet->getStyle('A15:' . $lastCell . '20')->applyFromArray($styleHeader);

        $sheet->getStyle('A17:' . $lastCell . '20')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('ADD8E6');

        $sheet->mergeCells("A17:" . $lastCell . '20');

        if(!$array)
        {
            if($bonDeCommande->getDevis()->getBoutique())
            {
                $sheet->setCellValue('A17', $bonDeCommande->getDevis()->getBoutique()->getAdresse() . "\n" . $bonDeCommande->getDevis()->getBoutique()->getCodePostal() . "\n" . $bonDeCommande->getDevis()->getBoutique()->getVille() . "\n" . $bonDeCommande->getDevis()->getBoutique()->getCommune());
    
            }
            else
            {
                $sheet->setCellValue('A17', $bonDeCommande->getClient()->getAdresseServiceAchats() . "\n" . $bonDeCommande->getClient()->getCodePostalServiceAchat() . "\n" . $bonDeCommande->getClient()->getVilleServiceAchat() . "\n" . $bonDeCommande->getClient()->getCommuneServiceAchat());
            }
        }

        $sheet->getStyle("A17")
            ->getAlignment()
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        $sheet->getStyle("A17")
            ->getAlignment()
            ->setWrapText(true);


        $sheet->mergeCells($this->increment($lastCell, 2) . '15:' . $colHeader . '16');
        $sheet->setCellValue($this->increment($lastCell, 2) . '15', 'Adresse de FACTURATION : ');
        $sheet->getStyle($this->increment($lastCell, 2) . '15')
            ->getAlignment()
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        $sheet->getStyle($this->increment($lastCell, 2) . '15')->getFont()->setUnderline(true);

        $sheet->mergeCells($this->increment($lastCell, 2) . '17:' . $colHeader . '20');

        $shop = $bonDeCommande->getDevis()->getShop();

        if($shop && $shop === "grossiste_greendot")
        {
            $hpfi = $this->clientRepo->findOneBy(["email" => "patricehennion@gmail.com"]);

            $sheet->setCellValue($this->increment($lastCell, 2) . '17', $hpfi->getAdresseFacturation() . "\n" . $hpfi->getCodePostalFacturation() . "\n" . $hpfi->getVilleFacturation() . "\n" . $hpfi->getCommuneFacturation());

        }
        else
        {
            $sheet->setCellValue($this->increment($lastCell, 2) . '17', $bonDeCommande->getClient()->getAdresseFacturation() . "\n" . $bonDeCommande->getClient()->getCodePostalFacturation() . "\n" . $bonDeCommande->getClient()->getVilleFacturation() . "\n" . $bonDeCommande->getClient()->getCommuneFacturation());
        }
        $sheet->getStyle($this->increment($lastCell, 2) . '17')
            ->getAlignment()
            ->setWrapText(true);

        $sheet->getStyle($this->increment($lastCell, 2) . '17')
            ->getAlignment()
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        $sheet->getStyle($this->increment($lastCell, 2) . '15:' . $colHeader . '20')->applyFromArray($styleHeader);
        $sheet->getStyle($this->increment($lastCell, 2) . '17:' . $colHeader . '20')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('ADD8E6');

        $sheet->setCellValue('B22', 'Prix de la Fiole HT :');
        $sheet->setCellValue('B23', 'Remise Client : ');
        $sheet->setCellValue('B24', 'Prix Remisé HT:');

        $sheet->getColumnDimension("A")->setWidth(15);
        $sheet->getColumnDimension("B")->setWidth(15);
        $sheet->getColumnDimension("C")->setWidth(15);
        
        return [$sheet,$gammeOrdonner, $styleArray, $spreadsheet, $colHeader];
    }

    public function listeDeclinaison($bonDeCommande,array $unitGammeProduit, $gam){

        $greendot = false;

        $ceres = false;

        if($bonDeCommande instanceof BonDeCommande)
        {

            $devisShop = $bonDeCommande->getDevis()->getShop();
    
            if($devisShop && $devisShop === "grossiste_greendot")
            {
                $greendot = true;
            }
    
            ($bonDeCommande->getClient()->getCeres())? $ceres = true : $ceres = false;
        }


        $show700 = true;
        
        $listeDeclinaison = [];
        $principeActif = null;
          
        foreach ($unitGammeProduit as $commande) {
            
            //foreach($listeProduit as  $produit){

                $produit = $commande->getProduit();
                
                if ($produit->getPrincipeActif()) {
                
                    $principeActif = $produit->getPrincipeActif();
                    foreach ($produit->getDeclinaison() as $decl) {
                      
                        
                        if (!in_array($decl, $listeDeclinaison)) {

                            if(!$show700)
                            {
                                if(intval($decl) != 700)
                                {
                                    array_push($listeDeclinaison, intval($decl));
                                }
                            }
                            else
                            {
                                array_push($listeDeclinaison, intval($decl));
                            }
                        }
                    }
                }else {
                    if(!in_array(-1, $listeDeclinaison)){
                        array_push($listeDeclinaison, -1);
                    }
                }
    
                
            //}
            
        }

        if(in_array(-1, $listeDeclinaison) && count($listeDeclinaison) > 1)
        {
           
            if (($key = array_search(-1, $listeDeclinaison)) !== false) {
                unset($listeDeclinaison[$key]);
            }
           
        }
        
        return [$listeDeclinaison, $principeActif];

    }

    public function excelDeclinaisonListe($listeDeclinaison, $sheet, $colone, $cellule, $styleArray)
    {
    
        foreach ($listeDeclinaison as $declinaison) 
        {
            if ($declinaison == 19) {
                $declinaison = "19,9";
            }

            if ($declinaison == 0) {
                $declinaison = "00";
            }

            if($declinaison == -1){
                $sheet->setCellValue($colone . $cellule, '');
                $sheet->getStyle($colone . $cellule)
                    ->getAlignment()
                    ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            }else {
                
                $base = [1, 5, 115, 125, 500];
                $value = null;

                if(in_array($declinaison, $base))
                {
                    if($declinaison == 1 || $declinaison == 5)
                    {
                        $value = $declinaison . "L";
                    }
                    else
                    {
                        $value = $declinaison . "ml";
                    }
                }
                else
                {
                    $value = $declinaison . "mg";
                }

                $sheet->setCellValue($colone . $cellule, $value);
                $sheet->getStyle($colone . $cellule)
                    ->getAlignment()
                    ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            }
            

            $col1 = $colone;
            $sheet->getStyle($colone . $cellule)->applyFromArray($styleArray);
            $sheet->getStyle($colone++ . $cellule)->applyFromArray($styleArray);
            $sheet->getColumnDimension($colone)->setWidth(1);
            $sheet->getStyle($colone++ . $cellule)->applyFromArray($styleArray);
            $sheet->getColumnDimension($colone)->setWidth(1);

            $sheet->mergeCells($col1 . $cellule . ":" . $colone . $cellule);
            $colone--;
            $sheet->getStyle($colone . $cellule)->applyFromArray($styleArray);
            $colone++;
        }

        return [$cellule, $colone];

    }

    public function getML($bonDeCommande, $taille): bool
    {
        if($bonDeCommande instanceof BonDeCommande)
        {
            
            $commandes = $bonDeCommande->getCommandes();
        }
        else
        {
            $commandes = $bonDeCommande;
            
        }

        foreach($commandes as $c)
        {
            $produit = $c->getProduit();

          
            if($produit->getType() && strpos($produit->getContenant()->getTaille(), strval($taille))  !== false)
            {
                return true;
            }
        }

        return false;
    }

    public function configuration($bonDeCommande, array $listeGamme = null)
    {
        $greendot = false;
        $client = null;
        if($bonDeCommande instanceof BonDeCommande)
        {

            $devis = $bonDeCommande->getDevis();
            $devisShop = $devis->getShop();
            
            $client = $devis->getClient();
            if($devisShop && $devisShop === "grossiste_greendot")
            {
                $client = $this->clientRepo->find(1);
                $greendot = true;
            }
            
            $commandes = $bonDeCommande->getCommandes();
        }
        else
        {
            $commandes = $bonDeCommande;
            $client = $commandes[0]->getDevis()->getClient();
        }

        $marqueBlanches = $this->marqueBlancheRepository->produitsMarqueBlanches($client, $greendot);
        $pasDeMb = false;

        if(count($marqueBlanches) == 0)
        {
            $client = $this->clientRepo->findOneBy(["raisonSocial" => "hpfi"]);

            $marqueBlanches = $this->marqueBlancheRepository->produitsMarqueBlanches($client, $greendot);
            $pasDeMb = true;
            
        }
      

        $offert = [];
        $sopurePdo = [];
        $summerExtra = [];
        $ceresExtravape = [];

        $nonMb = [];
        $positionCeres = false;

        $unitGammeProduit = $this->logistiqueService->positonGammes($client, $greendot);
        foreach($commandes as $commande)
        {
            
            $produit = $commande->getProduit();

            $mbExist = $this->marqueBlancheRepository->findOneBy(['client' => $client, 'produit' => $produit]);

            if($mbExist)
            {

                if($client->getExtravape())
                {
                    foreach($marqueBlanches as $mb)
                    {
                        $produitMb = $mb->getProduit();
    
                        $gammeMb = $mb->getGammeMarqueBlanche();
    
                        if($produit == $produitMb)
                        {
                            
                            if(( $gammeMb && strpos( " ".strtolower($gammeMb->getNom()), "sopure") != false && !in_array($produit, $sopurePdo)) || (strpos( " ".strtolower($produit->getGamme()->getNom()), "sopure") != false && !in_array($produit, $sopurePdo)) )
                            {
                                array_push($sopurePdo, $produit);
                            }
    
                            if(( $gammeMb && strpos( " ".strtolower($gammeMb->getNom()), "summer") != false  && !in_array($produit, $summerExtra)) || (strpos( " ".strtolower($produit->getGamme()->getNom()), "summer") != false  && !in_array($produit, $summerExtra)) )
                            {
                                array_push($summerExtra, $produit);
                            }
    
                            if(( $gammeMb && strpos( " ".strtolower($gammeMb->getNom()), "ceres") != false && !in_array($produit, $ceresExtravape)) || (strpos( " ".strtolower($produit->getGamme()->getNom()), "ceres") != false && !in_array($produit, $ceresExtravape)) )
                            {
                                array_push($ceresExtravape, $produit);
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
                            $defaultMB = $this->mbRepo->findOneBy(['produit' => $produit, 'client' => $hpfi]);
    
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
    
                        if(array_key_exists($nomGamme, $unitGammeProduit))
                        {
    
                            if( ! in_array($produit, $unitGammeProduit[$nomGamme]) )
                            {
                                $unitGammeProduit[$nomGamme][] = $produit ;
                            }
    
                        }
                        
                        if(strtolower($gammeMb->getNom()) == 'booster' && strtolower($gammeMb->getNom()) != 'base' && ! in_array($produit, $offert))
                        {
                            array_push($offert, $produit);
                        }
                    }
    
                }
    
                if($client->getCeres())
                {
    
                    $positionCeres = true;
    
                    $produitsCeres = $this->ordreCeresRepository->findBy([], ['position' => 'asc']);
                    
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

            }
            else
            {
                $gamme = $produit->getGamme()->getNom();

                if(array_key_exists($gamme, $unitGammeProduit))
                {

                    if( ! in_array($produit, $unitGammeProduit[$gamme]) )
                    {
                        $unitGammeProduit[$gamme][] = $produit ;
                    }

                }
                else
                {
                    $nonMb[$gamme][]= $produit;
                }
            }

    
        }
        
        if($positionCeres)
        {
            foreach($unitGammeProduit as $gamme => $produits)
            {
                $unitGammeProduit[$gamme] = $this->logistiqueService->trieCeres($produits);
            }
        }
        else
        {
            foreach($unitGammeProduit as $gamme => $produits)
            {
                $unitGammeProduit[$gamme] = $this->logistiqueService->trieTable($produits,$greendot, $client);
            }
        }
     
        if(count($nonMb) > 0 && $pasDeMb)
        {
            $unitGammeProduit = array_merge($unitGammeProduit, $nonMb);
        }

        if(count($offert) > 0)
        {
            $gamme = $offert[0]->getGamme()->getNom();
            
            $unitGammeProduit[$gamme] = $offert;
        }
        
        return $unitGammeProduit;
    }

    public function configurationCeres(BonDeCommande $bonDeCommande)
    {
        $commandes  = $bonDeCommande->getCommandes();

        $produitOrganiser = [];

        
        foreach($commandes as $commande)
        {
            $produit = $commande->getProduit();

            if($produit->getOrdreCeres())
            {

                $custom = $produit->getOrdreCeres()->getCustomGamme();
            }
            else
            {
                $custom = $produit->getGamme()->getNom();
            }
            
            $produitOrganiser[$custom][]= $produit;
            
        }

       
        foreach($produitOrganiser as $index => $prod)
        {
            $produitOrganiser[$index] = $this->logistiqueService->trieCeres($prod);
        }

        return $produitOrganiser;
    }


    public function customConfig($bonDeCommande)
    {
        $greendot = false;
        $client = null;
        if($bonDeCommande instanceof BonDeCommande)
        {

            $devis = $bonDeCommande->getDevis();
            $devisShop = $devis->getShop();
            
            $client = $devis->getClient();
            if($devisShop && $devisShop === "grossiste_greendot")
            {
                $client = $this->clientRepo->find(1);
                $greendot = true;
            }
            
            $commandes = $bonDeCommande->getCommandes();
        }
        else
        {
            $commandes = $bonDeCommande;
            $client = $commandes[0]->getDevis()->getClient();
        }

        $gammesClient = $this->positionGammeClientRepo->findBy(['client' => $client ], ['position' => 'asc']);

        $commandeTrier = $this->trieProduit->setGammesClient($gammesClient)
                                    ->setCommandes($commandes)
                                    ->unitOrderArray();
        return $commandeTrier;

    }

   
    public function createExcel($bonDeCommande)
    {
       
        $header = $this->excelHeader($bonDeCommande);

        $sheet = $header[0];
        $listeGamme = $header[1];
        $styleArray = $header[2];
        $spreadsheet = $header[3];
        $limite = $header[4];

        $exist = [];
        $cellule = 25;
        $cellVal = 'A';

        

        //dd($unitGammeProduit);
        $fiole10ml = 0;
        $fiole30ml = 0;
        $fioleConcentre = 0;
        $fioleChubby = 0;
        $fiole1L = 0;
        $fiole115ml = 0;
        $fiole5l = 0;
        $fiole130ml = 0;
        
        
        $totalPdoAromatise = 0;
        $total10mlCBD = 0;
        $totalFioleAbsolu = 0;
        $totalFioleBupre = 0;
        
        if( $bonDeCommande instanceof BonDeCommande && $bonDeCommande->getClient()->getCeres())
        {
            $this->createCeresExcel($bonDeCommande, $sheet,$styleArray);
        }
        else
        {
            //$unitGammeProduit = $this->configuration($bonDeCommande, $listeGamme);
            $unitGammeProduit = $this->customConfig($bonDeCommande);
            
            foreach ($unitGammeProduit as $gam => $listeProduit) {        
                if(count($listeProduit) > 0)
                {
                   
                    $gametitre = $gam;
                   
    
                    array_push($exist, $gam);
        
                    //tête table
        
                    $listeDec = $this->listeDeclinaison($bonDeCommande,$listeProduit, $gam);
                    
                    $principeActif = $listeDec[1];
        
                    $listeDeclinaison = $listeDec[0];
                    
                    sort($listeDeclinaison);
                    
                    $compt = 1;
                    $total10ml = 0;
                    $total30ml = 0;
                    $total50ml = 0;
                    $total115ml = 0;
                    $total1L  = 0;
                    $totalChubby = 0;
                    $totalConcentre = 0;
                    $total5L = 0;
    
                    $nbFiolesBpure = 0;
    
                    $nbPdoAromatise = 0;
                    $pdoAromatise = false;
    
                    $cbdSativap = false;
                    $yzyCbd = false;
    
                    $nb10mlCbd = 0;
    
                    $nbFioleAbsolu = 0;
                    $fioleAbsolu = false;
        
                    $couleur = null;
    
                    $cellule = $cellule + 1;
        
                    $cellVal = 'A';
                    $cellVal = $this->increment($cellVal, 1);
                    
                    if( strtolower($gam) == "yzy cbd")
                    {
                        $yzyCbd = true;
                    }

                    if( strtolower($gam) == "new cbd sativap")
                    {
                        $cbdSativap = true;
                    }

                    if( strtolower($gam) == "green leaf aeroma pdo")
                    {
                        $pdoAromatise = true;
                    }
                    
        
                    if($gam == "CONSTELLATION")
                    {
                        $sheet->setCellValue("A" . $cellule, "GAMME : CONSTELLATION PG50/50VG");
                        $couleur = 'd5dde6';
                        $couleurDescription = 'd5dde6';
    
                    }
                    elseif($gam == "PLAISIR")
                    {
                        $sheet->setCellValue("A" . $cellule, "GAMME : PLAISIR VG40/PG60");
                        $couleur = 'FF69B4';
                        $couleurDescription = 'FF69B4';
    
                    }
                    elseif($gam == "YZY_CITIES_SPRING")
                    {
                        $sheet->setCellValue("A" . $cellule, "GAMME : YZY CITIES SPRING");
                        $couleur = 'ffcc99';
                        $couleurDescription = 'ffcc99';
    
                    }
                    elseif($gam == "YZY_CITIES_SUMMER")
                    {
                        $sheet->setCellValue("A" . $cellule, "GAMME : YZY CITIES SUMMER");
                        $couleur = '8DB4E2';
                        $couleurDescription = '8DB4E2';
    
                    }
                    elseif($gam == "GREEN_LEAF_AEROMA_PDO")
                    {
                        $sheet->setCellValue("A" . $cellule, "GAMME : GREEN LEAF AEROMA PDO");
                        $couleur = '008000';
                        $couleurDescription = '008000';
    
                        $pdoAromatise = true;
                        
                    }
                    elseif($gam == "SALTY_DOG")
                    {
                        $sheet->setCellValue("A" . $cellule, "GAMME : SALTY DOG 50 ml");
                        $couleur = 'ffcc99';
                        $couleurDescription = 'ffcc99';
    
                    }
                    elseif($gam == "NEW_CBD_SATIVAP")
                    {
                        $sheet->setCellValue("A" . $cellule, "GAMME : NEW CBD SATIVAP");
                        $couleur = '32CD32';
                        $couleurDescription = '32CD32';
                       
                        $cbdSativap = true;
                        
    
                    }
                    elseif($gam == "YZY_CBD")
                    {
                        $sheet->setCellValue("A" . $cellule, "GAMME : YZY CBD");
                        $couleur = 'FA8072';
                        $couleurDescription = 'FA8072';
    
                        $yzyCbd = true;
    
                    }
                    elseif($gam == "ABSOLU")
                    {
                        $sheet->setCellValue("A" . $cellule, "GAMME : ABSOLU");
                        $couleur = 'FA8072';
                        $couleurDescription = 'FA8072';
    
                        $fioleAbsolu = true;
                    }
                    elseif($gam == "coolex")
                    {
                        $sheet->setCellValue("A" . $cellule, "GAMME COOLEX : Flacon PET sans étui");
                        $couleur = 'B7DEE8';
                        $couleurDescription = "ffffff";
                    }
                    elseif($gam == "coolexOil")
                    {
                        $sheet->setCellValue("A" . $cellule, "GAMME COOLEX OIL -Flacon verre blanc");
                        $couleur = 'B7DEE8';
                        $couleurDescription = "ffffff";
                    }
                    elseif($gam == "originHemp")
                    {
                        $sheet->setCellValue("A" . $cellule, "GAMME ORIGINE HEMP -Flacon verre blanc");
                        $couleur = 'FCD5B4';
                        $couleurDescription = "ffffff";
                    }
                    elseif($gam == "origineEliquide")
                    {
                        $sheet->setCellValue("A" . $cellule, "GAMME ORIGINE E-LIQUIDE : Flacon PET sans étui");
                        $couleur = 'FCD5B4';
                        $couleurDescription = "ffffff";
                    }
                    elseif($gam == "tech")
                    {
                        $sheet->setCellValue("A" . $cellule, "GAMME TECH+ : Flacon verre blanc");
                        $couleur = '92D050';
                        $couleurDescription = "ffffff";
                    }
                    elseif($gam == "GAMME SOPURE PDO")
                    {
                        $sheet->setCellValue("A" . $cellule, "GAMME SOPURE PDO");
                        $couleur = '00B050';
                        $couleurDescription = "ffffff";
                    }
                    elseif($gam == "GAMME CERES")
                    {
                        $sheet->setCellValue("A" . $cellule, "GAMME GAMME CERES");
                        $couleur = 'd5dde6';
                        $couleurDescription = "497DBB";
                    }
                    elseif($gam == "GAMME SUMMER")
                    {
                        $sheet->setCellValue("A" . $cellule, "GAMME SUMMER");
                        $couleur = 'FFC000';
                        $couleurDescription = "ffffff";
                    }
                    else
                    {
                        
                        $gammeTitle = $this->gammeRepo->findOneBy(['nom' => $gam]);
                        
                        
                        if(!$gammeTitle)
                        {
                            
                            $gammeTitle = $this->gammeMarqueBlancheRepository->findOneBy(['nom' => $gam]);
                            
                            $support = $listeProduit[0]->getProduit()->getBase();
                            $couleur = $gammeTitle->getCouleur();
                            
                        }
                        else
                        {
                            
                            if( count($gammeTitle->getProduits()) > 0)
                            {
                                $support = $gammeTitle->getProduits()[0]->getBase();
                                $couleur = $gammeTitle->getCouleur();
                                
                            }
                            
                        }

    
                        $value = strtoupper($gammeTitle->getNom());//.' '.$support->getNom()?? '';
    
                        $sheet->setCellValue("A" . $cellule, "GAMME : $value ");
    
                        $couleur? $couleur : $couleur = 'ffcc99';
                        
                        $couleurDescription = $couleur;
    
                        
                    }
                    
        
                    $sheet->getRowDimension($cellule)->setRowHeight(25);
                    $sheet->getStyle("A" . $cellule)
                        ->getAlignment()
                        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        
                    $sheet->getStyle("A" . $cellule)
                        ->getAlignment()
                        ->setWrapText(true);
        
                    $sheet->mergeCells("A$cellule:C$cellule");
        
                    $decrement = count($listeDeclinaison) * 3;
                    $declCel1 = $this->increment($cellVal, 2);
                    $cellVal = $this->increment($cellVal, $decrement / 2);
                    $sheet->mergeCells($declCel1 . $cellule . ":" . $this->increment($declCel1, $decrement - 1) . $cellule);
                    
                    if (count($listeDeclinaison) == 1 && in_array(-1, $listeDeclinaison)) {
        
                        $sheet->setCellValue($declCel1 . $cellule, "Sans déclinaison ");
                        $sheet->getStyle($declCel1 . $cellule)
                            ->getAlignment()
                            ->setWrapText(true);
        
                        $sheet->getStyle($declCel1 . $cellule)
                            ->getAlignment()
                            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                    } else {
                        
                        if($gametitre && strtolower($gametitre) == "base")
                        {
                            $sheet->setCellValue($declCel1 . $cellule, "Taux de Nicotine : 00 mg/ml");
                            $sheet->getStyle($declCel1 . $cellule)
                                ->getAlignment()
                                ->setWrapText(true); 
                        }
                        else
                        {
                            
                            $sheet->setCellValue($declCel1 . $cellule, "Taux de " . $principeActif);
                                $sheet->getStyle($declCel1 . $cellule)
                                    ->getAlignment()
                                    ->setWrapText(true);
                        }
        
                        $sheet->getStyle($declCel1 . $cellule)
                            ->getAlignment()
                            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                    }
        
                    $sheet->getStyle($declCel1 . $cellule)
                        ->getAlignment()
                        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        
                    $sheet->getStyle($declCel1 . $cellule)
                        ->getAlignment()
                        ->setWrapText(true);
                    
                    $concentreCol = $this->increment($declCel1, count($listeDeclinaison) * 3);
                    $firstConcentreCol = $concentreCol;
    
                    $concentreCol = $this->chubbyConfig("Concentré 30 ML", $sheet, $concentreCol, $firstConcentreCol, $cellule, $styleArray);
        
                    $chubbyCol = $this->increment($concentreCol, 1);
                    $firstChubbyCol = $chubbyCol;
        
                    $chubbyCol = $this->chubbyConfig('Chubby 50ML',$sheet, $chubbyCol, $firstChubbyCol, $cellule, $styleArray);
                    

                    $contenant100Ml = $this->getML($bonDeCommande, 100);

                    $concentre10Ml = $this->getML($bonDeCommande, 10);

                    
                    if($contenant100Ml || $concentre10Ml)
                    {
                        
                        $content = $contenant100Ml ? 'Chubby 100ML' : 'Concentré 10ML';

                        
                        $chubby100Col = $this->increment($chubbyCol, 1);
                        $first100ChubbyCol = $chubby100Col;
            
                        $this->chubbyConfig($content, $sheet, $chubby100Col, $first100ChubbyCol, $cellule, $styleArray);
                    }
        
                    $cellVal = "A";
        
                    $sheet->getStyle("A" . $cellule . ":" . "C$cellule")->applyFromArray($styleArray);
                    $sheet->getStyle("A" . $cellule . ":" . "C" . $cellule)->getFill()
                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()->setARGB($couleur);
        
                    $sheet->getStyle("A" . $cellule . ":" . "C" . $cellule)
                        ->getAlignment()
                        ->setWrapText(true);
        
                    $cellule++;
                    $sheet->setCellValue($cellVal . $cellule, "Description");
                    $sheet->getStyle($cellVal . $cellule)
                        ->getAlignment()
                        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                    $sheet->getStyle($cellVal . $cellule)->applyFromArray($styleArray);
        
        
                    $cellVal = $this->increment($cellVal, 1);
        
                    $sheet->setCellValue($cellVal . $cellule, "Designation \n client");
                    $sheet->getStyle($cellVal . $cellule)
                        ->getAlignment()
                        ->setWrapText(true);
                    $sheet->getStyle($cellVal . $cellule)->applyFromArray($styleArray);
        
                    $sheet->getStyle($cellVal . $cellule)
                        ->getAlignment()
                        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        
        
                    $cellVal = $this->increment($cellVal, 1);
        
                    $sheet->setCellValue($cellVal . $cellule, "Designation \n Aéroma");
                    $sheet->getStyle($cellVal . $cellule)
                        ->getAlignment()
                        ->setWrapText(true);
                    $sheet->getStyle($cellVal . $cellule)->applyFromArray($styleArray);
        
                    $sheet->getStyle($cellVal . $cellule)
                        ->getAlignment()
                        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        
                    $cellVal = $this->increment($cellVal, 1);
                    $colone = $cellVal;
        
                    $enteteListeDeclinaison = $this->excelDeclinaisonListe($listeDeclinaison, $sheet, $colone, $cellule, $styleArray);
        
                    $cellule = $enteteListeDeclinaison[0];
        
                    $colone = $enteteListeDeclinaison[1];
        
                    $col = $this->decrement($colone, 1);
        
                    $sheet->getCell($col++ . $cellule);
                    $sheet->getStyle($col . $cellule)->applyFromArray($styleArray);
                    $sheet->getCell($col++ . $cellule);
                    $sheet->getStyle($col . $cellule)->applyFromArray($styleArray);
                    $sheet->getStyle($col . $cellule)->applyFromArray($styleArray);
        
                    $sheet->getCell($col++ . $cellule);
                    $sheet->getStyle($col . $cellule)->applyFromArray($styleArray);
        
                    $tempCell = $cellule - 1;
        
                    $sheet->mergeCells("$firstConcentreCol$tempCell:$col$cellule");
        
                    $sheet->getCell($col++ . $cellule);
                    $sheet->getStyle($col . $cellule)->applyFromArray($styleArray);
                    $sheet->getStyle($col . $cellule)->applyFromArray($styleArray);
        
                    $sheet->getCell($col++ . $cellule);
                    $sheet->getStyle($col . $cellule)->applyFromArray($styleArray);
                    $sheet->getCell($col++ . $cellule);
                    $sheet->getStyle($col . $cellule)->applyFromArray($styleArray);
                    $sheet->getStyle($col . $cellule)->applyFromArray($styleArray);
        
                    $sheet->mergeCells("$firstChubbyCol$tempCell:$col$cellule");


                    
                    if($contenant100Ml ||$concentre10Ml)
                    {
                        $tempCell = $cellule - 1;
            
                        $sheet->mergeCells("$firstChubbyCol$tempCell:$col$cellule");
            
                        $sheet->getCell($col++ . $cellule);
                        $sheet->getStyle($col . $cellule)->applyFromArray($styleArray);
                        $sheet->getStyle($col . $cellule)->applyFromArray($styleArray);
            
                        $sheet->getCell($col++ . $cellule);
                        $sheet->getStyle($col . $cellule)->applyFromArray($styleArray);
                        $sheet->getCell($col++ . $cellule);
                        $sheet->getStyle($col . $cellule)->applyFromArray($styleArray);
                        $sheet->getStyle($col . $cellule)->applyFromArray($styleArray);
                        $sheet->mergeCells("$first100ChubbyCol$tempCell:$col$cellule");
                    }
        
                    $cellule--;
        
        
                    $sheet->getStyle($cellVal . $cellule . ":" . $col . $cellule)->applyFromArray($styleArray);
        
                    $sheet->getStyle("$cellVal$cellule:$col$cellule")->applyFromArray($styleArray);
        
                    $cellule++;
                    $cellule++;
        
                    $cellGamme = $cellule;
                    $memeProduit = [];
                    $result = [];
                    
                    //corp
                    $listeFlacon = [];
                    
                    foreach ($listeProduit as $index => $commande) {
                        
                        $produit = $commande->getProduit();
                        
                        $referenceProduit = $produit->getReference();
                        $cellVal = 'A';
                        
                        
                        if (!in_array($referenceProduit, $memeProduit)) {
                            
                            if ($produit->getType()) {
                                
                                $result = $this->produitRepo->produitExist($produit->getReference());
                                
                                if ($result && $result != $produit) {
                                    $commandeProduit = $result;
                                    array_push($memeProduit, $commandeProduit->getReference());
                                } else {
                                    $commandeProduit = $produit;
                                }
                            } else {
                                
                                $commandeProduit = $produit;
                            }
                            
                                
                            array_push($memeProduit, $produit->getReference());
                            $sheet->getRowDimension($cellGamme)->setRowHeight(30);
        
                            if ($commandeProduit->getContenant()) 
                            {
                                if($gametitre && strtolower($gametitre) == "base")
                                {
                                    $ratio = [];
                                    foreach($commandeProduit->getBase()->getBaseComposants() as $composant)
                                    {
    
                                        if((int)$composant->getRatio() == 100)
                                        {
                                            array_push($ratio, $composant->getRatio());
                                        }
                                        else
                                        {
                                            array_push($ratio, (int)$composant->getRatio());
                                        }
                                    }
    
                                    $value = null;
    
                                    if(count($ratio) > 1)
                                    {
                                        $value = implode("/", $ratio);
                                    }else{
                                        $value = $ratio[0];
                                    }
                                    $sheet->setCellValue($cellVal . $cellGamme, $value);
    
                                }
                                else
                                {
                                    $sheet->setCellValue($cellVal . $cellGamme, $commandeProduit->getReference() . "\n" . $commandeProduit->getContenant()->getTaille());
                                }
                            } 
                            else 
                            {
        
                                $sheet->setCellValue($cellVal . $cellGamme, '');
                            }
        
                            $mbCell = $this->increment($cellVal, 1);
                            
                            if($bonDeCommande instanceof BonDeCommande)
                            {
                                $clientBdc = $bonDeCommande->getDevis()->getClient();
                            }
                            else
                            {
                                $clientBdc = $bonDeCommande[0]->getBonDeCommande()->getClient();
                            }
                            
                            if ($commandeProduit->getMarqueBlanches()) {
                                foreach ($commandeProduit->getMarqueBlanches() as $mb) {
        
                                    if ( $clientBdc == $mb->getClient()) {
                                        $sheet->setCellValue($mbCell . $cellGamme, $mb->getNom());
        
                                        $sheet->getStyle($mbCell . $cellGamme)
                                            ->getAlignment()
                                            ->setWrapText(true);
                                    }
                                }
                            } else {
                                $sheet->setCellValue($mbCell . $cellGamme, '');
                            }
                            
                            $sheet->getStyle($cellVal . $cellGamme)->getAlignment()->setWrapText(true);
        
                            $sheet->getStyle($cellVal . $cellGamme)->applyFromArray($styleArray);
        
                            $sheet->getStyle($cellVal . $cellGamme)->getFill()
                                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                ->getStartColor()->setARGB($couleurDescription);
        
        
                            $cellVal = $this->increment($cellVal, 1);
        
                            $sheet->getStyle($cellVal . $cellGamme)->applyFromArray($styleArray);
        
                            $cellVal = $this->increment($cellVal, 1);
                            
                            if ($commandeProduit) {
                                
                                $sheet->setCellValue($cellVal . $cellGamme, $commandeProduit->getNom());
    
                                if($gam == "originHemp")
                                {
                                    $sheet->setCellValue($cellVal . $cellGamme, 'HEMP');
    
                                }
        
                                $sheet->getStyle($cellVal . $cellGamme)
                                    ->getFont()
                                    ->setSize(10);
        
                                $sheet->getStyle($cellVal . $cellGamme)
                                    ->getAlignment()
                                    ->setWrapText(true);
                            } else {
                                $sheet->setCellValue($cellVal . $cellGamme, '');
                            }
        
                            $sheet->getStyle($cellVal . $cellGamme)->applyFromArray($styleArray);
        
                            $declCol = 'D';
                            
                            if($bonDeCommande instanceof BonDeCommande)
                            {
                                $decliListe = $this->commandeRepo->findBy(['produit' => $commandeProduit->getId(), 'bonDeCommande' => $bonDeCommande]);
                            }
                            else
                            {
                                $bonDeCommandeIds = $this->getBdcIds($bonDeCommande);
                                $decliListe = $this->commandeRepo->getOrders($bonDeCommandeIds, $commandeProduit);
                            }
                            
                            if($gam == "originHemp")
                            {
                                $reference = "CBDTP-AU";
    
                                $listeId = [];
                                
                                foreach($listeProduit as $produit)
                                {
                                    array_push($listeId, $produit->getId());
                                }
                                
                                $result = $this->commandeRepo->findProducts($listeId, $bonDeCommande->getId());
    
                                //dd($cellGamme);
    
                                $cellVal = 'A';
                                
                                $cellMerge = $cellGamme + 2 ;
    
                                $sheet->mergeCells("$cellVal$cellGamme:$cellVal$cellMerge");
    
                                $sheet->setCellValue($cellVal.$cellGamme, $reference);
                                
                                $sheet->getStyle($cellVal . $cellMerge)
                                    ->getAlignment()
                                    ->setWrapText(true);
    
                                $sheet->getStyle($cellVal . $cellMerge)->getFill()  
                                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                    ->getStartColor()->setARGB($couleurDescription);
    
                                $sheet->getStyle($cellVal . $cellMerge)->applyFromArray($styleArray);
    
                                $cellVal = $this->increment($cellVal, 1);
                                $sheet->mergeCells("$cellVal$cellGamme:$cellVal$cellMerge");
                                $sheet->getStyle($cellVal . $cellMerge)->applyFromArray($styleArray);
                                
    
                                $cellVal = $this->increment($cellVal, 1);
                                $sheet->mergeCells("$cellVal$cellGamme:$cellVal$cellMerge");
                                $sheet->getStyle($cellVal . $cellMerge)->applyFromArray($styleArray);
                                $sheet->getStyle($cellVal . $cellGamme)
                                    ->getBorders()
                                    ->getLeft()
                                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
                                    
                                foreach($listeDeclinaison as $declinaison)
                                {
                                    foreach($result as $commande)
                                    {
                                        if($commande->getDeclinaison() == $declinaison."mg")
                                        {
    
                                            $sheet->setCellValue($declCol.$cellGamme, $commande->getQuantite());
    
                                            $sheet->getStyle($declCol . $cellGamme)
                                                ->getFont()
                                                ->setSize(15);
                                            
                                            $sheet->getStyle($declCol . $cellGamme)
                                                ->getAlignment()
                                                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
    
                                            $colSup = $this->increment($declCol, 1);
                                            $sheet->getStyle($colSup . $cellGamme)
                                                ->getBorders()
                                                ->getLeft()
                                                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
    
                                            $colSup = $this->increment($colSup, 1);
                                            $sheet->getStyle($colSup . $cellGamme)
                                                ->getBorders()
                                                ->getLeft()
                                                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
    
                                            $last = $this->increment($declCol, 2);
                                            $lastCell = $cellGamme + 2;
                                            $sheet->getStyle($declCol . $cellGamme . ':' . $last . $lastCell)->applyFromArray($styleArray);
    
                                            $declCol = $this->increment($declCol, 3);
    
                                            
                                            $quantiT = $commande->getQuantite();
                                            $conteNom =  strtolower($commande->getProduit()->getContenant()->getNom());
                                            $conteTaille = strtolower($commande->getProduit()->getContenant()->getTaille());
    
                                            ($conteTaille) ? $conteTaille : $conteTaille = '';
                                            
                                            if($pdoAromatise)
                                            {
                                                $nbPdoAromatise += $quantiT;
                                                $totalPdoAromatise += $quantiT;
                                                
                                            }
                                            elseif($cbdSativap || $yzyCbd)
                                            {
                                                $nb10mlCbd += $quantiT;
                                                $total10mlCBD += $quantiT;
                                            }
                                            elseif($fioleAbsolu)
                                            {
                                                $nbFioleAbsolu += $quantiT;
                                                $totalFioleAbsolu += $quantiT;
                                            }
                                            else
                                            {
                                                
                                                if ( intval($conteTaille) == 10) {
                                                    $total10ml += $quantiT;
                                                    $fiole10ml += $quantiT;
                                                    
                                                }
                                            }
    
                                            if (intval($conteTaille) == 30) {
                                                
                                                $total30ml += $quantiT;
                                                $fiole30ml += $quantiT;
    
                                            }
    
                                            if (intval($declinaison) == 1) {
                                                
                                                $total1L += $quantiT;
                                                $fiole1L += $quantiT;
    
                                            }
    
                                            if ( intval($declinaison) == 5) {
                                                $total5L += $quantiT;
                                                $fiole5l += $quantiT;
    
                                            }
    
                                            if ( intval($declinaison) == 115) {
                                                $total115ml += $quantiT;
                                                $fiole115ml += $quantiT;
    
                                            }
                                        }
                                    }
                                }
                                
                            }
                            else
                            {
                                
                                foreach ($listeDeclinaison as $declinaison) {
                                    
                                    if ($decliListe) {
            
                                        $bpures = false;
        
                                        $offert = [];
                                        
                                        foreach ($decliListe as $decli) {
                                            if ((!$decli->getProduit()->getType() || $decli->getProduit()->getId() == 481 || $decli->getProduit()->getId() == 482 )) {
                                                ($decli->getDeclinaison()) ? $decl = intval($decli->getDeclinaison()) : $decl = -1;
                                                
                                                
                                                if ($decl == $declinaison) {
                                                   
                                                    if($produit->getReference() == "Bpure 19'9")
                                                    {
                                                        $bpures = true;
                                                        $sheet->getRowDimension($cellGamme)->setRowHeight(50);
        
                                                        $totalOffert = null;
                                                        $totalBpureCommander = null;
        
                                                        if($decli->getOffert())
                                                        {
                                                            $totalOffert = $decli->getQuantite();
                                                            $offert['offert'] = $totalOffert;
                                                        }
        
                                                        if(!$decli->getOffert())
                                                        {
                                                            $totalBpureCommander = $decli->getQuantite();
                                                            $offert['commander'] = $totalBpureCommander;
                                                        }
                                                        
                                                        if(array_key_exists('commander', $offert) &&  array_key_exists('offert', $offert))
                                                        {
        
                                                            $bpureCommander = $offert['commander'];
                                                            $bpureOffert = $offert['offert'];
                                                            $sheet->setCellValue($declCol . $cellGamme, "$bpureCommander +\n$bpureOffert Offerts");
                                                            $sheet->getStyle($declCol . $cellGamme)->getAlignment()->setWrapText(true);
        
                                                        }elseif(!array_key_exists('commander', $offert) &&  array_key_exists('offert', $offert))
                                                        {
                                                            $bpureOffert = $offert['offert'];
                                                            $sheet->setCellValue($declCol . $cellGamme, "$bpureOffert \n Offerts");
                                                            $sheet->getStyle($declCol . $cellGamme)->getAlignment()->setWrapText(true);
        
                                                            
                                                        }elseif(array_key_exists('commander', $offert) &&  !array_key_exists('offert', $offert))
                                                        {
                                                            $bpureOffert = $offert['commander'];
                                                            $sheet->setCellValue($declCol . $cellGamme, "$bpureOffert");
        
                                                        }
        
                                                        $sheet->getStyle($declCol . $cellGamme)
                                                            ->getFont()
                                                            ->setBold(true)
                                                            ->setSize(10);
        
                                                        
                
                                                        $sheet->getStyle($declCol . $cellGamme)
                                                            ->getAlignment()
                                                            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                                                        
                                                    }else{
                                                       
                                                        
                                                        $quantityOrder = $decli->getQuantite();
                                                        
                                                        if($sheet->getCell($declCol.$cellGamme)->getValue())
                                                        {
                                                            $quantityOrder = $quantityOrder + intval($sheet->getCell($declCol.$cellGamme)->getValue());
                                                            
                                                            $sheet->setCellValue($declCol . $cellGamme, $quantityOrder);
                                                        }
                                                        else
                                                        {
                                                            $sheet->setCellValue($declCol . $cellGamme, $quantityOrder);
                                                        }
                                                        $sheet->getStyle($declCol . $cellGamme)
                                                            ->getFont()
                                                            ->setBold(true)
                                                            ->setSize(15);
                
                                                        $sheet->getStyle($declCol . $cellGamme)
                                                            ->getAlignment()
                                                            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                                                    }
            
            
            
                                                    $quantiT = $decli->getQuantite();
                                                    $conteNom = '';
                                                    $conteTaille = null;

                                                    if( $produit->getContenant())
                                                    {
                                                        $conteNom =  strtolower($decli->getProduit()->getContenant()->getNom());
                                                        $conteTaille = strtolower($decli->getProduit()->getContenant()->getTaille());
                                                    }
            
                                                    ($conteTaille) ? $conteTaille : $conteTaille = '';

                                                    
                                                    if( strtolower($produit->getCategorie()->getNom()) == "base" || strtolower($produit->getCategorie()->getNom()) == "bases") 
                                                    {
                                                        $nomFlacon = strtolower("Nombre de base {$decli->getDeclinaison()}");
                                                    }
                                                    else
                                                    {
                                                        $nomFlacon = strtolower("flacons $conteTaille");
                                                    }

                                                    if( ! array_key_exists($nomFlacon, $listeFlacon) )
                                                    {
                                                        $listeFlacon[$nomFlacon] = 0;
                                                    }
                                                    
                                                    
                                                    if(isset($listeFlacon[$nomFlacon]))
                                                    {
                                                        $listeFlacon[$nomFlacon] += $quantiT;
                                                    }


                                                    

                                                    if($gam == "ABSOLU")
                                                    {
                                                        $nbFioleAbsolu += $quantiT;
                                                        $totalFioleAbsolu += $quantiT;

                                                        if($conteTaille == '130ml')
                                                        {
                                                            $fiole130ml += $quantiT;
                                                        }
                                                    }

                                                    if($pdoAromatise)
                                                    {
                                                        $nbPdoAromatise += $quantiT;
                                                        $totalPdoAromatise += $quantiT;
                                                        
                                                    }
                                                    elseif($cbdSativap || $yzyCbd)
                                                    {
                                                        $nb10mlCbd += $quantiT;
                                                        $total10mlCBD += $quantiT;
                                                    }
                                                    elseif($fioleAbsolu)
                                                    {
                                                        //$nbFioleAbsolu += $quantiT;
                                                        // temporaire $totalFioleAbsolu += $quantiT;
                                                    }
                                                    elseif($bpures)
                                                    {
                                                        if ( intval($conteTaille) == 10) {
                                                            $nbFiolesBpure += $quantiT;
                                                            $totalFioleBupre += $quantiT;
                                                        }
                                                    }
                                                    else
                                                    {
                                                        if ( intval($conteTaille) == 10) {
                                                            $total10ml += $quantiT;
                                                            $fiole10ml += $quantiT;
                                                            
                                                        }
                                                    }
        
                                                    if (intval($conteTaille) == 30) {
                                                        $total30ml += $quantiT;
                                                        $fiole30ml += $quantiT;
        
                                                    }
            
                                                    if (intval($declinaison) == 1) {
                                                        $total1L += $quantiT;
                                                        $fiole1L += $quantiT;
        
                                                    }
            
                                                    if ( intval($declinaison) == 5) {

                                                        $total5L += $quantiT;
                                                        $fiole5l += $quantiT;
        
                                                    }
            
                                                    if ( intval($declinaison) == 115) {
                                                        $total115ml += $quantiT;
                                                        $fiole115ml += $quantiT;
        
                                                    }
                                                } else {
                                                    $sheet->getCell($declCol . $cellGamme);
                                                }
                                            } else {
                                                $sheet->getCell($declCol . $cellGamme);
                                            }
                                            $colSup = $this->increment($declCol, 1);
                                            $sheet->getStyle($colSup . $cellGamme)
                                                ->getBorders()
                                                ->getLeft()
                                                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            
                                            $colSup = $this->increment($colSup, 1);
                                            $sheet->getStyle($colSup . $cellGamme)
                                                ->getBorders()
                                                ->getLeft()
                                                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            
                                            $last = $this->increment($declCol, 2);
                                            $lastCell = $cellGamme + 2;
                                            $sheet->getStyle($declCol . $cellGamme . ':' . $last . $lastCell)->applyFromArray($styleArray);
                                        }
                                    } else {
                                        $colSup = $this->increment($declCol, 1);
                                        $sheet->getStyle($colSup . $cellGamme)
                                            ->getBorders()
                                            ->getLeft()
                                            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            
                                        $colSup = $this->increment($colSup, 1);
                                        $sheet->getStyle($colSup . $cellGamme)
                                            ->getBorders()
                                            ->getLeft()
                                            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            
                                        $last = $this->increment($declCol, 2);
                                        $lastCell = $cellGamme + 2;
                                        $sheet->getStyle($declCol . $cellGamme . ':' . $last . $lastCell)->applyFromArray($styleArray);
                                    }
            
                                    $declCol = $this->increment($declCol, 3);
                                }
                            }
                            
        
                            $lis = $this->produitRepo->findChubOrConce($commandeProduit->getReference());
                           
                            $chubAndCon = [];
                            if ($lis) {
                                
                                foreach ($lis as $li) {
                                    $quant = $this->commandeRepo->findOneBy(['produit' => $li, 'bonDeCommande' => $bonDeCommande]);
                                    
                                    if ($quant) {
                                       
                                        if (!in_array($li->getReference(), $memeProduit) or $li->getType()) {
                                            
                                            if ($li->getType()) {
                                                if( $li->getId() != 481 and $li->getId() != 482){
                                                    array_push($memeProduit, $li->getReference());
                                                   
                                                }
                                                $content = str_replace(' ', '', $li->getContenant()->getTaille());

                                                $chubAndCon["{$li->getType()} $content"] = $quant->getQuantite();
                                                
                                            }
                                        }
                                    }
                                }
                            }
                
                            $chubCol = $declCol;
                            
                            if($contenant100Ml)
                            {
                                $chubArray = ["Concentré 30ml", "Chubby 50ml", "Chubby 100ml"];
                            }
                            elseif($concentre10Ml)
                            {
                                $chubArray = ["Concentré 30ml","Chubby 50ml", "Concentré 10ml"];
                                //dd('ok');
                            }
                            else
                            {
                                $chubArray = ["Concentré 30ml", "Chubby 50ml"];

                            }
                            $countChub = 1;
                            //dump($chubArray, $chubAndCon);
                            foreach ($chubArray as $val) {
                                $first = $chubCol;
                                $last = $this->increment($chubCol, 2);
        
                                $lastCell = $cellGamme + 2;
        
                                if (isset($chubAndCon[$val])) {
                                    if ( strpos($val, 'Chubby') !== false) {
                                        $totalChubby += $chubAndCon[$val];
                                        $fioleChubby += $chubAndCon[$val];
                                        $nomFlacon = "$val";
                                       
                                    } elseif (strpos($val, 'Concentré') !== false) {
                                        $fioleConcentre += $chubAndCon[$val];
                                        $totalConcentre += $chubAndCon[$val];

                                        $nomFlacon = "$val";

                                    }

                                    if( ! array_key_exists($nomFlacon, $listeFlacon) )
                                    {
                                        $listeFlacon[$nomFlacon] = 0;
                                    }
                                    //dd($nomFlacon);

                                    if( isset($listeFlacon[$nomFlacon]))
                                    {
                                        $listeFlacon[$nomFlacon] += $chubAndCon[$val];
                                    }

        
                                    $sheet->setCellValue($chubCol . $cellGamme, $chubAndCon[$val]);
                                    $sheet->getStyle($chubCol . $cellGamme)
                                        ->getFont()
                                        ->setBold(true)
                                        ->setSize(15);
                                }
        
                                $sheet->getStyle($chubCol . $cellGamme . ':' . $last . $lastCell)->applyFromArray($styleArray);
        
                                $colSup = $this->increment($chubCol, 1);
                                $sheet->getStyle($colSup . $cellGamme)
                                    ->getBorders()
                                    ->getLeft()
                                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        
                                $colSup = $this->increment($colSup, 1);
                                $sheet->getStyle($colSup . $cellGamme)
                                    ->getBorders()
                                    ->getLeft()
                                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        
                                $nextCell = $cellGamme + 2;
        
                                if ( strpos($val, 'Chubby') !== false ) {
                                    $sheet->getStyle("$first$cellGamme:$last$nextCell")->getFill()
                                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                        ->getStartColor()->setARGB('FFB6C1');
                                } else {
                                    $sheet->getStyle("$first$cellGamme:$last$nextCell")->getFill()
                                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                        ->getStartColor()->setARGB('D3D3D3');
                                }
        
                                $chubCol = $this->increment($chubCol, 3);
                                $countChub++;
                            }
        
                            $firstCell = $cellGamme;
        
                            $sheet->getCell("A" . $cellGamme++);
                            $sheet->getRowDimension($cellGamme)->setRowHeight(15);
        
        
                            $fin = $this->decrement($chubCol, 1);
                            $sheet->getStyle("D$cellGamme:$fin$cellGamme")
                                ->getBorders()
                                ->getTop()
                                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        
                            $sheet->getCell("A" . $cellGamme++);
                            $sheet->getStyle("D$cellGamme:$fin$cellGamme")
                                ->getBorders()
                                ->getTop()
                                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                            $sheet->getRowDimension($cellGamme)->setRowHeight(15);
        
                            $m = 0;
                            $c = "A";
                            while ($m <= 2) 
                            {
                                $sheet->mergeCells($c . $firstCell . ':' . $c . $cellGamme);
        
                                $sheet->getStyle($c . $firstCell . ':' . $c . $cellGamme)->applyFromArray($styleArray);
                                $sheet->getStyle($c . $firstCell)
                                    ->getAlignment()
                                    ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        
                                $c = $this->increment($c, 1);
                                $m++;
                            }
    
                            if($gam == "originHemp")
                            {
                                break;
                            }
        
                            $cellGamme = $cellGamme + 1;
                        }
                           
                        
                    }
                    

                       
                    
                    
        
                    $cellule = $cellGamme + 1;
                    
                    foreach($listeFlacon as $flacon => $quantite)
                    {
                        $sheet->setCellValue("C$cellule", $flacon);
                        $sheet->setCellValue("D$cellule", $quantite);
                        
                        $sheet->getStyle("D$cellule")
                            ->getFont()
                            ->setBold(true)
                            ->setSize(15);
                        
                        $cellule++;

                    }

                    /*if ($nbPdoAromatise > 0) {
                        $sheet->setCellValue("B$cellule", "Nombre de PDO Aromatisé");
                        $sheet->setCellValue("D$cellule", $nbPdoAromatise);
                        $sheet->getStyle("D$cellule")
                            ->getFont()
                            ->setBold(true)
                            ->setSize(15);
                        $cellule++;
                    }
        
                    /*if ($total10ml > 0 or $nb10mlCbd > 0 or $nbFioleAbsolu > 0 or $nbFiolesBpure > 0) {
    
                        $sheet->setCellValue("C$cellule", "Flacons 10 ml");

                        if($fiole130ml == 0)
                        {
                            if($nb10mlCbd > 0)
                            {
                                $sheet->setCellValue("D$cellule", $nb10mlCbd);
                            }
                            elseif($nbFioleAbsolu > 0)
                            {
                                $sheet->setCellValue("D$cellule", $nbFioleAbsolu);
                            }
                            elseif($nbFiolesBpure > 0)
                            {
                                $sheet->setCellValue("D$cellule", $nbFiolesBpure);
                            }
                            else
                            {
                                $sheet->setCellValue("D$cellule", $total10ml);
                            }
        
                           
                        }
                        $cellule++;
                    }*/
        
                    /*if ($fiole130ml) {
                        $sheet->setCellValue("C$cellule", "Flacons 130 ml");
                        $sheet->setCellValue("D$cellule", $fiole130ml);
                        $sheet->getStyle("D$cellule")
                            ->getFont()
                            ->setBold(true)
                            ->setSize(15);
                        $cellule++;
                    }*/

                    /*if ($total30ml) {
                        $sheet->setCellValue("C$cellule", "Flacons 30 ml");
                        $sheet->setCellValue("D$cellule", $total30ml);
                        $sheet->getStyle("D$cellule")
                            ->getFont()
                            ->setBold(true)
                            ->setSize(15);
                        $cellule++;
                    }*/
        
                    /*if ($total50ml) {
                        $sheet->setCellValue("B$cellule", "Nombre de base 50 ml");
                        $sheet->setCellValue("D$cellule", $total50ml);
                        $sheet->getStyle("D$cellule")
                            ->getFont()
                            ->setBold(true)
                            ->setSize(15);
                        $cellule++;
                    }*/
        
                    /*if ($total115ml) {
                        $sheet->setCellValue("B$cellule", "Nombre de base 115 ml");
                        $sheet->setCellValue("D$cellule", $total115ml);
                        $sheet->getStyle("D$cellule")
                            ->getFont()
                            ->setBold(true)
                            ->setSize(15);
                        $cellule++;
                    }
        
                    if ($total1L) {
                        $sheet->setCellValue("B$cellule", "Nombre de base 1L");
                        $sheet->setCellValue("D$cellule", $total1L);
                        $sheet->getStyle("D$cellule")
                            ->getFont()
                            ->setBold(true)
                            ->setSize(15);
                        $cellule++;
                    }
        
                    if ($total5L) {
                        $sheet->setCellValue("B$cellule", "Nombre de base 5L");
                        $sheet->setCellValue("D$cellule", $total5L);
                        $sheet->getStyle("D$cellule")
                            ->getFont()
                            ->setBold(true)
                            ->setSize(15);
                        $cellule++;
                    }*/
        
                    /*if($gametitre && strtolower($gametitre) != "base" or !$gametitre)
                    {
                        $sheet->setCellValue("C$cellule", "Concentrés 30 ml");
                    }
        
                    if ($totalConcentre > 0) {
                        $sheet->setCellValue("D$cellule", $totalConcentre);
                        $sheet->getStyle("D$cellule")
                            ->getFont()
                            ->setBold(true)
                            ->setSize(15);
                    }
        
                    $cellule++;
    
                    if($gametitre && strtolower($gametitre) != "base" or !$gametitre)
                    {
                        $sheet->setCellValue("C$cellule", "Chubby 50 ml");
                    }
        
                    if ($totalChubby > 0) {
                        $sheet->setCellValue("D$cellule", $totalChubby);
                        $sheet->getStyle("D$cellule")
                            ->getFont()
                            ->setBold(true)
                            ->setSize(15);
                    }*/
    
                    if(55 < $cellule and $cellule < 70)
                    {
                        $cellule = 72;
                    }
                    elseif(115 < $cellule and $cellule < 133)
                    {
                        $cellule = 140;
                    }
                    elseif(175 < $cellule and $cellule < 200)
                    {
                        $cellule = 207;
                    }
                    elseif(250 < $cellule and $cellule < 270)
                    {
                        $cellule = 276;
                    }
                    elseif(320 < $cellule and $cellule < 348)
                    {
                        $cellule = 350;
                    }
                    else
                    {
                        $cellule += 3;
    
                    }
                }
               
            }
            
            //dd($fiole10ml, $fioleConcentre, $fioleChubby, $fiole1L, $fiole115ml, $fiole5l );
    
            $sheet->mergeCells("A$cellule:$limite$cellule");
            $sheet->setCellValue("A$cellule", 'RÉCAPITULATIF');
            $sheet->getStyle("A$cellule:$limite$cellule")
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()
                ->setARGB('ADD8E6');
    
            $sheet->getStyle("A$cellule:$limite$cellule")
                ->getFont()
                ->setSize(16)
                ->setBold(true);
    
            
    
            $cellule += 2;
    
            $sheet->setCellValue("B$cellule", "Nombre de Fioles 10 ml :");
            $sheet->getStyle("B$cellule")
                ->getFont()
                ->setSize(12);
    
            $sheet->setCellValue("D$cellule", $fiole10ml);
            $sheet->getStyle("D$cellule")
                ->getFont()
                ->setSize(12)
                ->setBold(true);
    
    
            $cellule += 2;
    
            $sheet->setCellValue("B$cellule", "Nombre de Concentrés :");
            $sheet->getStyle("B$cellule")
                ->getFont()
                ->setSize(12);
    
            $sheet->setCellValue("D$cellule", $fioleConcentre);
            $sheet->getStyle("D$cellule")
                ->getFont()
                ->setSize(12)
                ->setBold(true);
    
            $cellule += 2;
    
            $sheet->setCellValue("B$cellule", "Nombre de fioles 10 ml CBD :");
            $sheet->getStyle("B$cellule")
                ->getFont()
                ->setSize(12);
    
            $sheet->setCellValue("D$cellule", $total10mlCBD);
            $sheet->getStyle("D$cellule")
                ->getFont()
                ->setSize(12)
                ->setBold(true);
    
            $cellule += 2;
    
            $sheet->setCellValue("B$cellule", " Nombre de fioles PDO Aromatisé :");
            $sheet->getStyle("B$cellule")
                    ->getFont()
                    ->setSize(12);
    
            $sheet->setCellValue("D$cellule", $totalPdoAromatise);
            $sheet->getStyle("D$cellule")
                ->getFont()
                ->setSize(12)
                ->setBold(true);
    
            $cellule += 2;
    
            $sheet->setCellValue("B$cellule", " Nombre de Chubby :");
            $sheet->getStyle("B$cellule")
                ->getFont()
                ->setSize(12);
    
            $sheet->setCellValue("D$cellule", $fioleChubby);
            $sheet->getStyle("D$cellule")
                ->getFont()
                ->setSize(12)
                ->setBold(true);
    
            $cellule += 2;
    
            $sheet->setCellValue("B$cellule", " Nombre de fioles ABSOLU :");
            $sheet->getStyle("B$cellule")
                ->getFont()
                ->setSize(12);
    
            $sheet->setCellValue("D$cellule", $totalFioleAbsolu);
            $sheet->getStyle("D$cellule")
                ->getFont()
                ->setSize(12)
                ->setBold(true);
    
            $cellule += 2;
    
            $sheet->setCellValue("B$cellule", "Nombre de Fioles B Pure :");
            $sheet->getStyle("B$cellule")
                ->getFont()
                ->setSize(12);
    
            $sheet->setCellValue("D$cellule", $totalFioleBupre);
            $sheet->getStyle("D$cellule")
                ->getFont()
                ->setSize(12)
                ->setBold(true);
    
    
            if($fiole1L > 0 or $fiole115ml > 0 or $fiole5l > 0)
            {
    
                $cellule += 2;
        
                $sheet->setCellValue("B$cellule", "Nombre de Bases :");
                $sheet->getStyle("B$cellule")
                    ->getFont()
                    ->setSize(12);
    
                $cellule += 2;
        
                $sheet->setCellValue("B$cellule", "115 ml :");
                $sheet->getStyle("B$cellule")
                    ->getFont()
                    ->setSize(12);
    
                $sheet->setCellValue("D$cellule", $fiole115ml);
                $sheet->getStyle("B$cellule")
                    ->getFont()
                    ->setSize(12);
    
    
                $sheet->getStyle("D$cellule")
                    ->getFont()
                    ->setSize(12)
                    ->setBold(true);
    
                $cellule += 2;
        
                $sheet->setCellValue("B$cellule", "1 litre");
                $sheet->setCellValue("D$cellule", $fiole1L);
                $sheet->getStyle("D$cellule")
                    ->getFont()
                    ->setSize(12)
                    ->setBold(true);
    
                $cellule += 2;
        
                $sheet->setCellValue("B$cellule", "5 Litres");
                $sheet->setCellValue("D$cellule", $fiole5l);
                $sheet->getStyle("D$cellule")
                    ->getFont()
                    ->setSize(12)
                    ->setBold(true);
            }
        }
        
        $sheet->getStyle('A:AG')->getAlignment()->setHorizontal('center');
        $writer = new Xlsx($spreadsheet);

        if($bonDeCommande instanceof BonDeCommande)
        {
            $fileName = $bonDeCommande->getDevis()->getCode() . '.xlsx';
        }
        else
        {
            $ids = [];
            $fileName = "groupe";

            foreach($bonDeCommande as $bon)
            {
                $id = $bon->getDevis()->getId();

                if(!in_array($id, $ids))
                {
                    array_push($ids, $id);
                    $fileName .= "-$id";
                }
            }
           
            $fileName .= ".xlsx";
        }


        $temp_file = "excel/bonPreparation/" . $fileName;

        $filesystem = new Filesystem();


        if ($filesystem->exists($this->publicRoot . '/excel/bonPreparation/' . $fileName)) {
            $filesystem->remove($this->publicRoot . '/excel/bonPreparation/' . $fileName);
        }

        $writer->save($temp_file);

        $response = ['temp_file' => $temp_file, 'fileName' => $fileName];
        
        return $response;
    }

    public function getBdcIds( array $bonDeCommandes)
    {
        $ids = [];

        foreach($bonDeCommandes as $bon)
        {
            $id = $bon->getBonDeCommande()->getId();

            if(!in_array($id, $ids))
            {
                array_push($ids, $id);
            }
        }

        return $ids;
    }

    public function setBoldQuantity($sheet, $colone, $cellule)
    {
        $sheet->getStyle($colone . $cellule)
        ->getFont()
        ->setBold(true)
        ->setSize(15);
    }

    public function setBoldTitle($sheet, $colone, $cellule)
    {
        $sheet->getStyle($colone . $cellule)
        ->getFont()
        ->setBold(true)
        ;
    }

    

    public function createCeresExcel(BonDeCommande $bonDeCommande, $sheet)
    {
        $gammeProduit = $this->customConfig($bonDeCommande);

        $border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                ],
            ],
        ];

        $cellule = 27;
        
        
      
        foreach($gammeProduit as $gamme => $commandes)
        {
            
            if(count($commandes) > 0)
            {
                
                $listeDeclinaison = [];
             
                foreach($commandes as $commande)
                {
                    $produit = $commande->getProduit();

                    foreach($produit->getDeclinaison() as $decl)
                    {
                        $decl = intval($decl);
                        if(!in_array($decl, $listeDeclinaison))
                        {
                            array_push($listeDeclinaison, $decl);
                        }
                    }
                    
                }
                
    
                  
                sort($listeDeclinaison);
              
    
                
                $colone = 'A';
    
                $sheet->mergeCells("$colone$cellule:C$cellule");
                
                $sheet->getRowDimension($cellule)->setRowHeight(25);
                $sheet->setCellValue($colone.$cellule, "GAMME $gamme");
               
                $this->setBoldTitle($sheet,$colone,$cellule);
    
                $sheet->getStyle($colone . $cellule)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('DAEEF3');
                
                if(strtolower($gamme) == "gamme cbd")
                {
                    $sheet->getStyle($colone . $cellule)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('C4D79B');
                }
    
                $this->alignHZ($sheet, $colone, $cellule);
    
                
                $sheet->getStyle("$colone$cellule:C$cellule")->applyFromArray($border);
    
                $colone2 = 'D';
    
                $colDecl = $this->increment($colone2, count($listeDeclinaison)*3);
                $colDecl = $this->decrement($colDecl, 1);
                $sheet->mergeCells("$colone2$cellule:$colDecl$cellule");
                $sheet->getStyle("$colone2$cellule:$colDecl$cellule")->applyFromArray($border);
    
                
    
                if( ($produit->getPrincipeActif() and strtolower($produit->getPrincipeActif()->getPrincipeActif()) == "nicotine") or !$produit->getPrincipeActif())
                {
    
                    $sheet->setCellValue($colone2.$cellule, "Taux de nicotine");
                }
                else
                {
                    $sheet->setCellValue($colone2.$cellule, "POURCENTAGE(%) EN MG");
                    
                    if(strtolower($gamme) == "gamme cbd")
                    {
                        $sheet->getStyle($colone2 . $cellule)->getFill()
                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('C4D79B');
                    }
                }
    
                $this->setBoldTitle($sheet,$colone2,$cellule);
    
                
                $this->alignHZ($sheet, $colone2, $cellule);
    
               
    
                if( ($produit->getPrincipeActif() and strtolower($produit->getPrincipeActif()->getPrincipeActif()) == "nicotine") or (!$produit->getPrincipeActif()))
                {
    
                    $col2 = $this->increment($colDecl, 1);
                    
                    $supplement = $this->increment($col2);
                    
                    $concentreCell = $cellule + 1;
                    $sheet->mergeCells("$col2$cellule:$supplement$concentreCell");
            
                    $sheet->getStyle("$col2$cellule:$supplement$concentreCell")->applyFromArray($border);
    
                    $sheet->setCellValue($col2.$cellule, "Concentré \n 30 ML");
                    $sheet->getStyle($col2 . $cellule)
                        ->getAlignment()
                        ->setWrapText(true);
    
                    $this->alignHZ($sheet, $col2, $cellule);
                    $this->setBoldTitle($sheet,$col2,$cellule);
    
                    $sheet->getStyle($col2 . $cellule)->getFill()
                            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()->setARGB('D9D9D9');
    
    
                }
                
    
                $cellule++;
    
                $sheet->setCellValue($colone.$cellule, 'Description');
    
                $this->alignHZ($sheet, $colone, $cellule);
                $this->setBoldTitle($sheet,$colone,$cellule);
    
    
                $sheet->getStyle("$colone$cellule")->applyFromArray($border);
    
                $coloneTemp = $this->increment($colone, 1);
                $sheet->setCellValue($coloneTemp.$cellule, "Désignation \nClient");
                $sheet->getStyle("$coloneTemp$cellule")->applyFromArray($border);
                $sheet->getStyle($coloneTemp . $cellule)
                        ->getAlignment()
                        ->setWrapText(true);
                
                $this->alignHZ($sheet, $coloneTemp, $cellule);
                $this->setBoldTitle($sheet,$coloneTemp,$cellule);
    
    
                $coloneTemp = $this->increment($coloneTemp, 1);
                $sheet->setCellValue($coloneTemp.$cellule, "Désignation \nAéroma");
                $sheet->getStyle("$coloneTemp$cellule")->applyFromArray($border);
                $sheet->getStyle($coloneTemp . $cellule)
                    ->getAlignment()
                    ->setWrapText(true);
                
                $this->alignHZ($sheet, $coloneTemp, $cellule);
                $this->setBoldTitle($sheet,$coloneTemp,$cellule);
    
    
                $declCol = 'D';
                foreach($listeDeclinaison as $declinaison)
                {
                    $colPlus = $this->increment($declCol, 2);
                    $sheet->setCellValue($declCol.$cellule, $declinaison);
                    $sheet->mergeCells("$declCol$cellule:$colPlus$cellule");
    
                    
                    $sheet->getStyle("$declCol$cellule:$colPlus$cellule")->applyFromArray($border);
                    $sheet->getStyle($declCol . $cellule)
                        ->getAlignment()
                        ->setWrapText(true);
                    
                    $this->alignHZ($sheet, $declCol, $cellule);
                    $this->setBoldTitle($sheet,$declCol,$cellule);
    
    
                    $declCol = $this->increment($colPlus, 1);
                    
        
                }
                
                $cellule++;
    
                $memeProduit = [];
                $result = [];
                $nombreDeFioles = null;
                $nombreConcentre = null;
    
                foreach($commandes as $commande)
                {
                    $produit = $commande->getProduit();
                    $reference = $produit->getReference();
    
                    if(!in_array($reference, $memeProduit))
                    {
    
                        $refereceProduits = $this->commandeRepo->findBy(['produit' => $produit->getId(), 'bonDeCommande' => $bonDeCommande]);
                        $concentre = $this->produitRepo->findOneBy(["reference" => "AC$reference"]);
    
                       
    
                        if ($produit->getType()) {
                           
                            $result = $this->produitRepo->produitExist($produit->getReference());
    
                            if ($result && $result != $produit) {
                                $commandeProduit = $result;
                                array_push($memeProduit, $commandeProduit->getReference());
                            } else {
                                $commandeProduit = $produit;
                            }
                        } else {
                            $commandeProduit = $produit;
    
                            if($concentre)
                            {
                                $conc = $this->commandeRepo->findOneBy(['produit' => $concentre->getId(), 'bonDeCommande' => $bonDeCommande]);
                                
                                array_push($memeProduit, $conc->getProduit()->getReference());
                                
                                array_push($refereceProduits, $conc);
                            }
                        }
    
                     
    
                        array_push($memeProduit, $produit->getReference());
            
                        $sheet->getRowDimension($cellule)->setRowHeight(30);
        
                        if($produit->getOrdreCeres()->getCustomRef())
                        {
                            $sheet->setCellValue($colone.$cellule, $produit->getOrdreCeres()->getCustomRef());
                        }
                        else
                        {
                            $sheet->setCellValue($colone.$cellule, $reference);
                            if($produit->getPrincipeActif() and strtolower($produit->getPrincipeActif()->getPrincipeActif()) == 'cbd')
                            {
                        
                                $sheet->setCellValue($colone.$cellule, '');
                            }
                        }
    
                        $couleurRef = $produit->getOrdreCeres()->getCouleur();
                        if($couleurRef)
                        {
                            $sheet->getStyle($colone . $cellule)->getFill()
                            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()->setARGB($couleurRef);
                        }
                        else
                        {
                            $sheet->getStyle($colone . $cellule)->getFill()
                            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()->setARGB('ffffff');
                        }
                       
        
                        //$sheet->getStyle("$colone$cellule:$colPlus$cellule")->applyFromArray($border);
                        $sheet->getStyle($colone . $cellule)
                            ->getAlignment()
                            ->setWrapText(true);
                        
                        $this->alignHZ($sheet, $colone, $cellule);
                        $this->setBoldTitle($sheet,$colone,$cellule);
        
        
                        /*------------------------------------------------------*/
        
                        $coloneDescClient = 'B';
    
                        if (count($commandeProduit->getMarqueBlanches()) > 0) {
                            foreach ($commandeProduit->getMarqueBlanches() as $mb) {
    
                                if ($bonDeCommande->getDevis()->getClient() == $mb->getClient()) {
                                    $sheet->setCellValue($coloneDescClient . $cellule, $mb->getNom());
    
                                    $sheet->getStyle($coloneDescClient . $cellule)
                                        ->getAlignment()
                                        ->setWrapText(true);
                                }
                            }
                        } else {
                           
                            $sheet->setCellValue($coloneDescClient . $cellule, $commandeProduit->getNom());
                        }
    
        
                        //$sheet->setCellValue($coloneDescClient.$cellule, 'test');
        
                        //$sheet->getStyle("$coloneDescClient$cellule:$colPlus$cellule")->applyFromArray($border);
                        $sheet->getStyle($coloneDescClient . $cellule)
                            ->getAlignment()
                            ->setWrapText(true);
                        
                        $this->alignHZ($sheet, $coloneDescClient, $cellule);
                        $this->setBoldTitle($sheet,$coloneDescClient,$cellule);
        
        
                         /*------------------------------------------------------*/
        
                        $coloneDescAeroma = 'C';
    
                        $sheet->setCellValue($coloneDescAeroma.$cellule, '');
        
                        //$sheet->getStyle("$coloneDescAeroma$cellule:$colPlus$cellule")->applyFromArray($border);
                        $sheet->getStyle($coloneDescAeroma . $cellule)
                            ->getAlignment()
                            ->setWrapText(true);
        
                        $this->alignHZ($sheet, $coloneDescAeroma, $cellule);
                        $this->setBoldTitle($sheet,$coloneDescAeroma,$cellule);
    
    
    
                        //declinaison
                       
                        
    
                        $coloneDecl = 'D';
    
                        $oldCell = $cellule;
                        
                        $quantiteConcentrer = 0;
                        foreach($listeDeclinaison as $declinaison)
                        {
                            $sheet->getColumnDimension($coloneDecl)->setWidth(8);
    
    
    
                            foreach($refereceProduits as $prodCommande)
                            {
                                if( !$prodCommande->getProduit()->getType() and intval($prodCommande->getDeclinaison()) === $declinaison)
                                {
                                    if($sheet->getCell($coloneDecl.$cellule)->getValue())
                                    {
                                        $quantiteTemp = intval($sheet->getCell($coloneDecl.$cellule)->getValue()) + $prodCommande->getQuantite();
    
                                        $sheet->setCellValue($coloneDecl.$cellule, $quantiteTemp);
    
                                    }
                                    else
                                    {
                                        $sheet->setCellValue($coloneDecl.$cellule, $prodCommande->getQuantite());
                                    }
                                    $this->alignHZ($sheet, $coloneDecl, $cellule);
                                    $this->setBoldQuantity($sheet,$coloneDecl,$cellule);
    
                                    $nombreDeFioles += $prodCommande->getQuantite();
    
                                }
    
                                if($prodCommande->getProduit()->getType())
                                {
                                    $quantiteConcentrer = $prodCommande->getQuantite();
                                }
                            }
    
                            $sheet->getStyle("$coloneDecl$oldCell:C$cellule")->applyFromArray($border);
    
    
                            $tempCol1 = $this->increment($coloneDecl, 1);
                            
                            $sheet->getStyle("$tempCol1$cellule")
                                ->getBorders()
                                ->getLeft()
                                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
    
                            $sheet->getColumnDimension($tempCol1)->setWidth(2);
    
    
                            $tempCol2 = $this->increment($coloneDecl, 2);
                            
                            $sheet->getColumnDimension($tempCol2)->setWidth(2);
    
                            $temp = $cellule + 1;
                            $sheet->getStyle("$coloneDecl$temp:$tempCol2$temp")
                            ->getBorders()
                            ->getTop()
                            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
    
                            $tempCell = $cellule + 2;
                            $sheet->getStyle("$coloneDecl$tempCell:$tempCol2$tempCell")
                                ->getBorders()
                                ->getBottom()
                                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
    
                            $sheet->getStyle("$tempCol2$cellule")
                                ->getBorders()
                                ->getLeft()
                                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
    
                            $sheet->getStyle("$tempCol2$cellule:$tempCol2$tempCell")
                                ->getBorders()
                                ->getRight()
                                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
    
                            $sheet->getStyle("$coloneDecl$tempCell:$tempCol2$tempCell")
                                ->getBorders()
                                ->getTop()
                                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
    
                            $coloneDecl = $this->increment($coloneDecl, 3);
    
                          
                            // dd($reference,$coloneDecl);
                        }
    
    
    
    
                        
            
                        if( ($produit->getPrincipeActif() and strtolower($produit->getPrincipeActif()->getPrincipeActif()) == "nicotine") or (!$produit->getPrincipeActif()))
                        {
                            $concentre = $this->increment($colDecl, 1);
                            $sheet->setCellValue($concentre.$cellule, '');
                            if($quantiteConcentrer)
                            {
                                $sheet->setCellValue($concentre.$cellule, $quantiteConcentrer);
                                $nombreConcentre = $quantiteConcentrer;
                            }
                            
    
                            $sheet->getStyle("$concentre$cellule:$colPlus$cellule")->applyFromArray($border);
                            $sheet->getStyle($concentre . $cellule)
                                ->getAlignment()
                                ->setWrapText(true);
            
                            $this->alignHZ($sheet, $concentre, $cellule);
                            $this->setBoldQuantity($sheet,$concentre,$cellule);
    
                                
                            $tempCol1 = $this->increment($coloneDecl, 1);
                            
                            $sheet->getColumnDimension($tempCol1)->setWidth(2);
    
    
                            $tempCol2 = $this->increment($coloneDecl, 2);
    
                            $sheet->getColumnDimension($tempCol2)->setWidth(2);
    
                            $temp = $cellule + 1;
                            $sheet->getStyle("$coloneDecl$temp:$tempCol2$temp")
                            ->getBorders()
                            ->getTop()
                            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
    
                            $tempCell = $cellule + 2;
                            $sheet->getStyle("$coloneDecl$tempCell:$tempCol2$tempCell")
                                ->getBorders()
                                ->getBottom()
                                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
    
                            $sheet->getStyle("$tempCol2$cellule")
                                ->getBorders()
                                ->getLeft()
                                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
    
                            $sheet->getStyle("$tempCol2$cellule:$tempCol2$tempCell")
                                ->getBorders()
                                ->getRight()
                                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
    
                            $sheet->getStyle("$coloneDecl$tempCell:$tempCol2$tempCell")
                                ->getBorders()
                                ->getTop()
                                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
    
                            
                            $sheet->getColumnDimension($tempCol2)->setWidth(2);
                            $sheet->getStyle("$col2$cellule:$tempCol2$tempCell")->getFill()
                            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()->setARGB('D9D9D9');
                        }
                        
        
                        $cellule++;
    
                        $sheet->getRowDimension($cellule)->setRowHeight(15);
                       
    
                        $cellule++;
    
                        $sheet->getRowDimension($cellule)->setRowHeight(15);
    
                        $sheet->mergeCells("A$oldCell:A$cellule");
                        $sheet->getStyle("A$oldCell:A$cellule")->applyFromArray($border);
                        
                        $sheet->mergeCells("B$oldCell:B$cellule");
                        $sheet->getStyle("B$oldCell:B$cellule")->applyFromArray($border);
    
                        $sheet->mergeCells("C$oldCell:C$cellule");
                        $sheet->getStyle("C$oldCell:C$cellule")->applyFromArray($border);
    
    
    
                        $cellule++;
                    }
    
                   
                    
                }
    
                $cellule++;
                $sheet->setCellValue("B$cellule","Nombre de fioles :   ");
                $sheet->setCellValue("C$cellule", $nombreDeFioles);
                $this->setBoldQuantity($sheet,'C',$cellule);
                
                $cellule++;
                $sheet->setCellValue("B$cellule","Nombre de concentrés :");
                $sheet->setCellValue("C$cellule",$nombreConcentre);
                $this->setBoldQuantity($sheet,'C',$cellule);
    
                
                $cellule += 5;
                
            }
        }

       

    }

    function alignHZ($sheet, $colone, $cellule)
    {
        $sheet->getStyle($colone.$cellule)
        ->getAlignment()
        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        $sheet->getStyle($colone.$cellule)
        ->getAlignment()
        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    }

    public function chubbyConfig($nom, $sheet, $chubby100Col, $firstChubbyCol, $cellule, $styleArray)
    {
        $sheet->setCellValue($chubby100Col . $cellule, $nom);
        $sheet->getStyle($chubby100Col . $cellule)->applyFromArray($styleArray);
        $sheet->getStyle($firstChubbyCol . $cellule)
            ->getAlignment()
            ->setWrapText(true);

        $color = 'FFB6C1';
        if(strpos( strtolower($nom), "chubby") === false)
        {
            $color = 'D3D3D3' ;
        }

        $sheet->getStyle($chubby100Col . $cellule)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB($color);

        $sheet->getStyle($firstChubbyCol . $cellule)
            ->getAlignment()
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        $sheet->getCell($chubby100Col++ . $cellule);
        $sheet->getStyle($chubby100Col . $cellule)->applyFromArray($styleArray);
        $sheet->getColumnDimension($chubby100Col)->setWidth(1);
        $sheet->getCell($chubby100Col++ . $cellule);
        $sheet->getStyle($chubby100Col . $cellule)->applyFromArray($styleArray);
        $sheet->getColumnDimension($chubby100Col)->setWidth(1);

        return $chubby100Col;
    }

    function increment($val, $increment = 2)
    {
        for ($i = 1; $i <= $increment; $i++) {
            $val++;
        }

        return $val;
    }

    function decrement($val, $decrement = 2)
    {

        if (strlen($val) > 1) {
            $col = str_split($val);

            $abc = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

            $position = strpos($abc,end($col)) + 1;
            $newPosition = $position - $decrement;

            if($newPosition == 0){
                return 'Z';
            }elseif($newPosition > 0){
                return array_shift($col) . chr(ord(end($col)) - $decrement);
            }else{
                return chr(ord('Z') - abs($newPosition));
            }
            
        } else {
            return chr(ord($val) - $decrement);
        }
    }

    /*
    function calculCellule(array $listeProduit, $cellule)
    {
        $produitModifier = [];

        foreach($listeProduit as $produit)
        {
            $reference = strtolower($produit->getReference());
            if(substr($reference, 0, 2) == "sv" or substr($reference, 0, 2) == "ac" )
            {
                $referenceModifier = strtolower(substr($reference, 2));

                if(!in_array($referenceModifier, $produitModifier))
                {
                    array_push($produitModifier, $referenceModifier);
                }
                
            }
            elseif(!in_array($reference, $produitModifier))
            {
                array_push($produitModifier, $reference);
            }
        }

        $nombreProduit = count(($produitModifier));

        $tailleTableSuivant = ($nombreProduit * 3) + 2  ;

        $finTableSuivant = $cellule + $tailleTableSuivant;
        
        return $cellule ;
        
    }*/

}
