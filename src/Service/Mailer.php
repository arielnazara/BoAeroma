<?php


namespace App\Service;

use App\Entity\BonDeCommande;
use App\Entity\BonLivraison;
use App\Entity\Client;
use App\Entity\Devis;
use App\Entity\FactureAvoir;
use App\Entity\FactureCommande;
use App\Entity\FactureMaitre;
use App\Entity\InvoiceOrder;
use App\Entity\LotCommande;
use App\Entity\LotOrder;
use App\Entity\MontantFinal;
use App\Entity\Prestation;
use App\Entity\PurchaseOrder;
use App\Repository\ClientRepository;
use App\Repository\FactureCommandeRepository;
use App\Repository\LotCommandeRepository;
use App\Repository\MarqueBlancheRepository;
use App\Repository\MontantFinalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Snappy\Pdf;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Filesystem\Filesystem;

class Mailer
{
    private $em;
    private $mailer;
    private $templating;
    private $snappy_pdf;
    private $publicRoot;
    private $mbRepo;
    private $clientRepo;
    private $montantFinal;
    private $useful;

    public function __construct(EntityManagerInterface $em, \Swift_Mailer $mailer, Useful $useful, MontantFinalRepository $montantFinalRepository, ClientRepository $clientRepo, EngineInterface $templating, Pdf $snappy_pdf, $publicRoot, MarqueBlancheRepository $mbRepo)
    {
        $this->em = $em;
        $this->mailer = $mailer;
        $this->templating = $templating;
        $this->snappy_pdf = $snappy_pdf;
        $this->publicRoot = realpath($publicRoot . '/../public');
        $this->mbRepo = $mbRepo;
        $this->clientRepo = $clientRepo;
        $this->montantFinal = $montantFinalRepository;
        $this->useful = $useful;
    }

    public function sendBatchOrderInvoice(FactureCommande $invoice)
    {
        $devis = $invoice->getDevis();

        if($devis->getShop() === "grossiste_greendot")
        {
            $email = "patricehennion@gmail.com";
            $adresseFacturation = $this->clientRepo->findOneBy(["email" => $email]);
        }
        else
        {
            $email = $invoice->getClient()->getEmail();
        }

        
        $mail = new \Swift_Message("Facture numéro {$invoice->getNumero()}");
        $mail->addTo($email);
        if ( !empty($invoice->getClient()->getSecondEmail()) && !is_null($invoice->getClient()->getSecondEmail()) ) {
            $mail->setCc([$invoice->getClient()->getSecondEmail()]);
        }
        $mail->addFrom('gestion@aeroma.fr');
        $mail->setBody($this->templating->render('back/mail/batchOrderInvoice.html.twig', ['invoice' => $invoice]), 'text/html');
        
        $invoiceFilename = 'Facture-' . $invoice->getNumero() . ".pdf";

        $attach = \Swift_Attachment::fromPath($this->publicRoot . '/pdf/order/' . $invoiceFilename, 'application/pdf');
        $mail->attach($attach);

        $this->mailer->send($mail);
    }

    public function envoieAvoir(FactureAvoir $factureAvoir)
    {
        if($factureAvoir->getFacture())
        {
            $client = $factureAvoir->getFacture()->getClient();
            if($factureAvoir->getFacture()->getDevis()->getShop() === "grossiste_greendot")
            {
                $client = $this->clientRepo->find(1);
            }
        }
        else
        {
            $client = $factureAvoir->getFactureMaitre()->getClient();
        }

        $mail = new \Swift_Message("Facture avoir numéro {$factureAvoir->getNumero()}");
        $mail->addTo($client->getEmail());
        $mail->addFrom('gestion@aeroma.fr');
        $mail->setBody($this->templating->render('back/mail/facture_avoir.html.twig', ['factureAvoir' => $factureAvoir]), 'text/html');
        
        $invoiceFilename = 'Facture-' . $factureAvoir->getNumero() . ".pdf";

        $attach = \Swift_Attachment::fromPath($this->publicRoot . '/pdf/order/' . $invoiceFilename, 'application/pdf');
        $mail->attach($attach);

        $this->mailer->send($mail);
    }

    public function envoieFacturePrestation(Prestation $prestation, ? string $objet = null)
    {
        $client = $prestation->getClient();

        if($objet)
        {
            $mail = new \Swift_Message("$objet {$prestation->getNumero()}");
        }
        else
        {
            $mail = new \Swift_Message("Facture numéro {$prestation->getNumero()}");
        }
        $mail->addTo($client->getEmail());
        $mail->addFrom('gestion@aeroma.fr');
        
        $mail->setBody($this->templating->render('back/mail/facture_prestation.html.twig', ['prestation' => $prestation, 'objet' => $objet]), 'text/html');
        
        $invoiceFilename = 'Facture-' . $prestation->getNumero() . ".pdf";

        $attach = \Swift_Attachment::fromPath($this->publicRoot . '/pdf/order/' . $invoiceFilename, 'application/pdf');
        $mail->attach($attach);

        $this->mailer->send($mail);
    }

    public function sendMasterInvoice(FactureMaitre $invoice)
    {
        $client = $invoice->getBonDeCommandes()[0]->getClient();

        $mail = new \Swift_Message("Facture de prestation numéro {$invoice->getNumero()}");
        $mail->addTo($client->getEmail());
        $mail->addFrom('gestion@aeroma.fr');
        $mail->setBody($this->templating->render('back/mail/invoiceMaster.html.twig', ['invoice' => $invoice]), 'text/html');
        
        $invoiceFilename = 'Facture-' . $invoice->getNumero() . ".pdf";

        $attach = \Swift_Attachment::fromPath($this->publicRoot . '/pdf/order/' . $invoiceFilename, 'application/pdf');
        $mail->attach($attach);

        $this->mailer->send($mail);
    }

    public function sendValidationCode(Devis $devis, $code)
    {

        $mail = new \Swift_Message('CODE DE VALIDATION BON DE COMMANDE');
        $mail->addTo($devis->getClient()->getEmail());
        $mail->addFrom('gestion@aeroma.fr');
        $mail->setBody($this->templating->render('front/mail/validationCode.html.twig', ['devis' => $devis, 'code' => $code]), 'text/html');
        $this->mailer->send($mail);
    }

    public function sendPassword(Client $client, $pswd)
    {
        $mail = new \Swift_Message("VOICI VOTRE CODE D'ACCÈS");
        $mail->addTo($client->getEmail());
        $mail->addFrom('gestion@aeroma.fr');
        $mail->setBody($this->templating->render('front/mail/codeacces.html.twig', ['code' => $pswd]), 'text/html');
        $this->mailer->send($mail);
    }

    public function accountValidation(Client $client)
    {
        $mail = new \Swift_Message("ACTIVATION COMPTE");
        $mail->addTo($client->getEmail());
        $mail->addFrom('gestion@aeroma.fr');
        $mail->setBody($this->templating->render('front/mail/accountValidation.html.twig'), 'text/html');
        $this->mailer->send($mail);
    }

    public function sendEditPurchaseOrder(BonDeCommande $bonDeCommande)
    {
        $mail = new \Swift_Message("LES TARIFS SUR VOTRE BON DE COMMANDE ONT ETE MODIFIER");
        $mail->addTo($bonDeCommande->getClient()->getEmail());
        $mail->addFrom('gestion@aeroma.fr');

        $filesystem = new Filesystem();
        $filename = $bonDeCommande->getCode();

        if ($filesystem->exists($this->publicRoot . '/pdf/devis/' . $filename)) {
            $filesystem->remove($this->publicRoot . '/pdf/devis/' . $filename);
        }
        $mail->setBody($this->templating->render('front/mail/modificationTarifBDC.html.twig', ['devis' => $bonDeCommande->getDevis()]), 'text/html');
        $html = $this->templating->render('front/pdf/devis.html.twig', [
            'devis' => $bonDeCommande->getDevis(),
            'marqueBlanches' => $this->mbRepo->findBy(['client' => $bonDeCommande->getClient()])
        ]);

        $footer = $this->templating->render('/back/pdf/footer.html.twig');
        $header = $this->templating->render('/back/pdf/header.html.twig');
        $option = ['footer-html' => $footer, 'header-html' => $header];

        $this->snappy_pdf->generateFromHtml($html, $this->publicRoot . '/pdf/devis/' . $filename, $option);

        $attach = \Swift_Attachment::fromPath($this->publicRoot . '/pdf/devis/' . $filename, 'application/pdf');
        $mail->attach($attach);

        $this->mailer->send($mail);
    }

    public function send(Devis $devis, $status, $attach2 = null)
    {

        $titre = ($status == 'online') ? 'Proposition commerciale' : 'Bon de commande ';

        $signed = ($status == 'onlinemade') ? 'signé' : '';

        $mail = new \Swift_Message($titre . ' #' . $devis->getCode() . ' ' . $signed);
        $mail->addTo($devis->getClient()->getEmail());
        $mail->addFrom('gestion@aeroma.fr');

        $filename = $devis->getCode() . ".pdf";
        $filesystem = new Filesystem();

        if ($status == 'manual') {
            $mail->setBody($this->templating->render('front/mail/manuel.signature.html.twig', ['devis' => $devis]), 'text/html');
            //$cgvattach = \Swift_Attachment::fromPath($this->publicRoot . '/pdf/CGV_elican_Biotech_Aeroma.pdf', 'application/pdf');
            //$mail->attach($cgvattach);
        }

        if ($status == 'online') {
            $filename = $devis->getCode() . "-online.pdf";
            $mail->setBody($this->templating->render('front/mail/online.signature.html.twig', ['devis' => $devis]), 'text/html');
        }

        if ($status == 'onlinemade') {
            $mail->setBody($this->templating->render('front/mail/made.online.signature.html.twig', ['devis' => $devis]), 'text/html');
            $filename = $devis->getCode() . "-signed.pdf";
        }

        if ($filesystem->exists($this->publicRoot . '/pdf/devis/' . $filename)) {
            $filesystem->remove($this->publicRoot . '/pdf/devis/' . $filename);
        }

        $html = $this->templating->render('front/pdf/devis.html.twig', [
            'devis' => $devis,
            'marqueBlanches' => $this->mbRepo->findBy(['client' => $devis->getClient()])
        ]);

        $footer = $this->templating->render('/back/pdf/footer.html.twig');
        $header = $this->templating->render('/back/pdf/header.html.twig');
        $option = ['footer-html' => $footer, 'header-html' => $header];

        $this->snappy_pdf->generateFromHtml($html, $this->publicRoot . '/pdf/devis/' . $filename, $option);

        $attach = \Swift_Attachment::fromPath($this->publicRoot . '/pdf/devis/' . $filename, 'application/pdf');

        /*if($attach2){
            $attach2 = \Swift_Attachment::fromPath($this->publicRoot . '/pdf/CGV_elican_Biotech_Aeroma.pdf');
            $mail->attach($attach2);
        }*/

        $mail->attach($attach);

        $this->mailer->send($mail);
    }


    public function sendOrder(BonDeCommande $purchaseOrder, FactureCommande $invoiceOrder = null, $full = null, $email = null)
    {

        $products = [];
        $n = 0;
        $devis = $purchaseOrder->getDevis();

        $sendTo = $purchaseOrder->getClient()->getEmail();
        
        if($email)
        {
            $sendTo = $email;
        }

        $mail = new \Swift_Message('Bon de Commande ' . $purchaseOrder->getCode());
        $mail->addTo($sendTo);
        $mail->addFrom('gestion@aeroma.fr');

        $filename = $purchaseOrder->getCode() . ".pdf";
        $filesystem = new Filesystem();

        $mail->setBody($this->templating->render('back/mail/sendbc.html.twig', [
            'purchaseOrder' => $purchaseOrder,
            'devis' => $devis,
            'full' => $full,
            'marqueBlanches' => $this->mbRepo->findBy(['client' => $devis->getClient()])
        ]), 'text/html');

        if ($filesystem->exists($this->publicRoot . '/pdf/order/' . $filename)) {
            $filesystem->remove($this->publicRoot . '/pdf/order/' . $filename);
        }

        $html = $this->templating->render('back/pdf/bc.html.twig', [
            'purchaseOrder' => $purchaseOrder,
            'devis' => $devis,
            'products' => $products,
            'n' => $n,
            'full' => $full,
            'marqueBlanches' => $this->mbRepo->findBy(['client' => $devis->getClient()])
        ]);

        $footer = $this->templating->render('/back/pdf/footer.html.twig');
        $header = $this->templating->render('/back/pdf/header.html.twig');
        $option = ['footer-html' => $footer, 'header-html' => $header];

        $this->snappy_pdf->generateFromHtml($html, $this->publicRoot . '/pdf/order/' . $filename, $option);

        $attach1 = \Swift_Attachment::fromPath($this->publicRoot . '/pdf/order/' . $filename, 'application/pdf');
        $mail->attach($attach1);

        if ($invoiceOrder) {
            if ($full) {
                $invoiceFilename = 'Facture-' . $invoiceOrder->getNumero() . ".pdf";
                if ($filesystem->exists($this->publicRoot . '/pdf/order/' . $invoiceFilename)) {
                    $filesystem->remove($this->publicRoot . '/pdf/order/' . $invoiceFilename);
                }
                $html = $this->templating->render('back/pdf/invoice.html.twig', [
                    'invoiceOrder' => $invoiceOrder,
                    'purchaseOrder' => $purchaseOrder,
                    'devis' => $devis,
                    'products' => $products,
                    'n' => $n
                ]);
                $footer = $this->templating->render('/back/pdf/footer.html.twig');
                $header = $this->templating->render('/back/pdf/header.html.twig');
                $option = ['footer-html' => $footer, 'header-html' => $header];

                $this->snappy_pdf->generateFromHtml($html, $this->publicRoot . '/pdf/order/' . $invoiceFilename, $option);
            } else {
                $invoiceFilename = 'Facture' . $invoiceOrder->getNumero() . ".pdf";
                if ($filesystem->exists($this->publicRoot . '/pdf/order/' . $invoiceFilename)) {
                    $filesystem->remove($this->publicRoot . '/pdf/order/' . $invoiceFilename);
                }
                $html = $this->templating->render('back/pdf/deposit.html.twig', [
                    'invoiceOrder' => $invoiceOrder,
                    'purchaseOrder' => $purchaseOrder,
                    'devis' => $devis,
                    'products' => $products,
                    'n' => $n
                ]);

                $footer = $this->templating->render('/back/pdf/footer.html.twig');
                $header = $this->templating->render('/back/pdf/header.html.twig');
                $option = ['footer-html' => $footer, 'header-html' => $header];

                $this->snappy_pdf->generateFromHtml($html, $this->publicRoot . '/pdf/order/' . $invoiceFilename, $option);
            }

            $attach2 = \Swift_Attachment::fromPath($this->publicRoot . '/pdf/order/' . $invoiceFilename, 'application/pdf');
            $mail->attach($attach2);
        }

        $this->mailer->send($mail);
    }
    /*
    public function sendInvoice($object, InvoiceOrder $invoiceOrder) {
        $products = [];
        $n = 0;
        $devis = $invoiceOrder->getDevis();
        foreach ($devis->getUnits() as $unit) {
            $product = $unit->getProduct();
            $n++;
            $products[$n]['designation'] = $product->getDesignation();
            $products[$n]['unit'] = $unit->getUnit();
            $products[$n]['total'] = $unit->getUnit() * $product->getPrice();
            $products[$n]['price'] = $product->getPrice();
            $products[$n]['code'] = $product->getCode();
            $products[$n]['prixDeVenteConseiller'] = $product->getPrixDeVenteConseiller();
        }

        $mail = new \Swift_Message($object);
        $mail->addTo($invoiceOrder->getClient()->getEmail());
        $mail->addFrom('gestion@aeroma.fr');

        $filename = 'Facture-' . $invoiceOrder->getNumber().".pdf";
        $filesystem = new Filesystem();

        $mail->setBody($this->templating->render('back/mail/send-deposit-invoice.html.twig', [
            'invoiceOrder' => $invoiceOrder,
            'devis' => $devis,
            'products' => $products,
            'n' => $n, 'deposit' => ($devis->getTotalTtc() * 0.35)
        ]), 'text/html');

        if ($filesystem->exists($this->publicRoot . '/pdf/order/' . $filename) === false) {
            $html = $this->templating->render('back/pdf/deposit.html.twig', [
                'invoiceOrder' => $invoiceOrder,
                'purchaseOrder' => $invoiceOrder->getPurchaseOrder(),
                'devis' => $devis,
                'products' => $products,
                'n' => $n
            ]);

            $footer = $this->templating->render( '/back/pdf/footer.html.twig' );
            $header = $this->templating->render( '/back/pdf/header.html.twig' );
            $option = ['footer-html' => $footer, 'header-html' => $header];

            $this->snappy_pdf->generateFromHtml($html, $this->publicRoot . '/pdf/order/' . $filename, $option);
        }

        $attach = \Swift_Attachment::fromPath($this->publicRoot . '/pdf/order/' . $filename, 'application/pdf');
        $mail->attach($attach);

        return $this->mailer->send($mail);
    }*/

    public function sendShipped($object,  LotCommande $lotOrder, BonDeCommande $purchaseOrder, BonLivraison $bl, ?FactureCommande $invoiceOrder = null, ?MontantFinal $devisFinal = null )
    {
        $devis = $purchaseOrder->getDevis();

        $adresseFacturation = null;
        $email = null;

        if($devis->getShop() === "grossiste_greendot")
        {
            $email = "patricehennion@gmail.com";
            $adresseFacturation = $this->clientRepo->findOneBy(["email" => $email]);
        }
        else
        {
            $email = $purchaseOrder->getClient()->getEmail();
        }

        $position = null;

        $count = 1;

        foreach($purchaseOrder->getLotCommandes() as $lot)
        {
            if($lotOrder == $lot){
                $position = $count;
            }

            $count++;
        }

        $mail = new \Swift_Message($object);
        $mail->addTo($email);
        $mail->addFrom('gestion@aeroma.fr');
        $mail->setBody($this->templating->render('back/mail/send-shipped.html.twig', [
            'lotQuantity' => $lotOrder->getLotQuantites(),
            'devis' => $devis,
            'invoiceOrder' => $invoiceOrder
        ]), 'text/html');

        $filesystem = new Filesystem();

        $blFilename = 'BL-' . $lotOrder->getNom() . '-' . $devis->getCode() . '-' . $bl->getId() . '.pdf';

        if ($filesystem->exists($this->publicRoot . '/pdf/order/' . $blFilename)) {
            $filesystem->remove($this->publicRoot . '/pdf/order/' . $blFilename);
        }

        $html = $this->templating->render('back/pdf/bon-livraison.html.twig', [
            'lotQuantity' => $bl->getLotCommande()->getLotQuantites(),
            'purchaseOrder' => $purchaseOrder,
            'devis' => $purchaseOrder->getDevis(),
            'bl' => $bl,
            'position' => $position
        ]);

        $footer = $this->templating->render('/back/pdf/footer.html.twig');
        $header = $this->templating->render('/back/pdf/header.html.twig');
        $option = ['footer-html' => $footer, 'header-html' => $header];
        $this->snappy_pdf->generateFromHtml($html, $this->publicRoot . '/pdf/order/' . $blFilename, $option);
        $attach_bl = \Swift_Attachment::fromPath($this->publicRoot . '/pdf/order/' . $blFilename, 'application/pdf');
        $mail->attach($attach_bl);

        if ($invoiceOrder && !$devis->getPayerParCarte()) {
            $invoiceFilename = 'Facture-' . $invoiceOrder->getNumero() . ".pdf";
            if ($filesystem->exists($this->publicRoot . '/pdf/order/' . $invoiceFilename)) {
                $filesystem->remove($this->publicRoot . '/pdf/order/' . $invoiceFilename);
            }

            $lotOrders = $this->useful->getInvoiceLotOrders($purchaseOrder->getLotCommandes(), $invoiceOrder);


            $html = $this->templating->render('back/pdf/invoice-shipped.html.twig', [
                'invoiceOrder' => $invoiceOrder,
                'purchaseOrder' => $purchaseOrder,
                'devis' => $devis,
                'lotOrders' => $lotOrders,
                'marqueBlanches' => $this->mbRepo->findBy(['client' => $devis->getClient()]),
                'adresseFacturation' => $adresseFacturation ? $adresseFacturation : null,
                'devisFinal' => $devisFinal
            ]);

            $footer = $this->templating->render('/back/pdf/factfoot.html.twig');
            $header = $this->templating->render('/back/pdf/header.html.twig');
            $option = ['footer-html' => $footer, 'header-html' => $header];

            $this->snappy_pdf->generateFromHtml($html, $this->publicRoot . '/pdf/order/' . $invoiceFilename, $option);
            $attach = \Swift_Attachment::fromPath($this->publicRoot . '/pdf/order/' . $invoiceFilename, 'application/pdf');
            $mail->attach($attach);
        }

        $this->mailer->send($mail);
    }

    public function sendInvoicePaid(BonDeCommande $purchaseOrder, FactureCommande $invoiceOrder)
    {
        $products = [];
        $n = 0;
        $devis = $purchaseOrder->getDevis();

        $mail = new \Swift_Message('Confirmation de paiement');
        $mail->addTo($purchaseOrder->getClient()->getEmail());
        $mail->addFrom('gestion@aeroma.fr');
        $mail->setBody($this->templating->render('back/mail/paid-invoice.html.twig', [
            'purchaseOrder' => $purchaseOrder,
            'devis' => $devis,
            'invoice' => $invoiceOrder
        ]), 'text/html');

        $filesystem = new Filesystem();

        $filename = 'Facture-' . $invoiceOrder->getNumero() . ".pdf";
        if ($filesystem->exists($this->publicRoot . '/pdf/order/' . $filename)) {
            $filesystem->remove($this->publicRoot . '/pdf/order/' . $filename);
        }
        $html = $this->templating->render('back/pdf/invoice.html.twig', [
            'invoiceOrder' => $invoiceOrder,
            'purchaseOrder' => $purchaseOrder,
            'devis' => $devis,
        ]);

        $footer = $this->templating->render('/back/pdf/footer.html.twig');
        $header = $this->templating->render('/back/pdf/header.html.twig');
        $option = ['footer-html' => $footer, 'header-html' => $header];

        $this->snappy_pdf->generateFromHtml($html, $this->publicRoot . '/pdf/order/' . $filename, $option);

        $attach = \Swift_Attachment::fromPath($this->publicRoot . '/pdf/order/' . $filename, 'application/pdf');
        $mail->attach($attach);

        $this->mailer->send($mail);
    }

    public function sendSoldPaid(BonDeCommande $purchaseOrder, FactureCommande $invoiceOrder)
    {

        $devis = $purchaseOrder->getDevis();
        
        $adresseFacturation = null;
        $email = null;

        if($devis->getShop() === "grossiste_greendot")
        {
            $email = "patricehennion@gmail.com";
            $adresseFacturation = $this->clientRepo->findOneBy(["email" => $email]);
        }
        else
        {
            $email = $purchaseOrder->getClient()->getEmail();
        }

        $mail = new \Swift_Message("Confirmation de paiement");
        $mail->addTo($email);
        $mail->addFrom('gestion@aeroma.fr');
        $mail->setBody($this->templating->render('back/mail/paid-invoice.html.twig', [
            'purchaseOrder' => $purchaseOrder,
            'devis' => $devis,
            'invoice' => $invoiceOrder
        ]), 'text/html');

        $filesystem = new Filesystem();
        $invoiceFilename = 'Facture-' . $invoiceOrder->getNumero() . ".pdf";

        if ($filesystem->exists($this->publicRoot . '/pdf/order/' . $invoiceFilename)) {
            unlink($this->publicRoot . '/pdf/order/' . $invoiceFilename);
        }

        $lotOrders = $this->useful->getInvoiceLotOrders($purchaseOrder->getLotCommandes(), $invoiceOrder);


        $html = $this->templating->render('back/pdf/invoice-shipped.html.twig', [
            'invoiceOrder' => $invoiceOrder,
            'purchaseOrder' => $purchaseOrder,
            'devis' => $devis,
            'lotOrders' => $lotOrders,
            'marqueBlanches' => $this->mbRepo->findBy(['client' => $devis->getClient()]),
            'adresseFacturation' => $adresseFacturation ? $adresseFacturation : null
        ]);

        $footer = $this->templating->render('/back/pdf/factfoot.html.twig');
        $header = $this->templating->render('/back/pdf/header.html.twig');
        $option = ['footer-html' => $footer, 'header-html' => $header];

        $this->snappy_pdf->generateFromHtml($html, $this->publicRoot . '/pdf/order/' . $invoiceFilename, $option);

        $attach = \Swift_Attachment::fromPath($this->publicRoot . '/pdf/order/' . $invoiceFilename, 'application/pdf');
        $mail->attach($attach);

        $this->mailer->send($mail);
    }

    public function sendMasterSoldPaid(BonDeCommande $purchaseOrder, FactureMaitre $invoiceOrder , $adresseLivraison)
    {

        $devis = $purchaseOrder->getDevis();
        $client = $purchaseOrder->getClient();
        $adresseFacturation = null;
        
        $email = null;

        if($devis->getShop() === "grossiste_greendot")
        {
            $email = "patricehennion@gmail.com";
            $adresseFacturation = $this->clientRepo->findOneBy(["email" => $email]);
        }
        else
        {
            $email = $client->getEmail();
        }
        
        $mail = new \Swift_Message("Confirmation de paiement");
        $mail->addTo($email);
        $mail->addFrom('gestion@aeroma.fr');
        $mail->setBody($this->templating->render('back/mail/paid-invoice.html.twig', [
            'invoice' => $invoiceOrder
        ]), 'text/html');

        $filesystem = new Filesystem();
        $invoiceFilename = 'Facture-' . $invoiceOrder->getNumero() . ".pdf";

        if ($filesystem->exists($this->publicRoot . '/pdf/order/' . $invoiceFilename)) {
            unlink($this->publicRoot . '/pdf/order/' . $invoiceFilename);
        }
        //dd($adresseLivraison);
        $html = $this->templating->render('back/pdf/invoice-master.html.twig', [
            'invoiceOrder' => $invoiceOrder,
            'marqueBlanches' => $this->mbRepo->findBy(['client' => $client]),
            'adresseFacturation' => $adresseFacturation ? $adresseFacturation : null,
            'addresseLivraison' => $adresseLivraison ? $adresseLivraison : null,
            'client' => $client
        ]);

        $footer = $this->templating->render('/back/pdf/factfoot.html.twig');
        $header = $this->templating->render('/back/pdf/header.html.twig');
        $option = ['footer-html' => $footer, 'header-html' => $header];

        $this->snappy_pdf->generateFromHtml($html, $this->publicRoot . '/pdf/order/' . $invoiceFilename, $option);

        $attach = \Swift_Attachment::fromPath($this->publicRoot . '/pdf/order/' . $invoiceFilename, 'application/pdf');
        $mail->attach($attach);

        $this->mailer->send($mail);
    }


    public function preparationEmail(LotCommande $lotCommande)
    {

        $devis = $lotCommande->getBonDeCommande()->getDevis();

        $email = null;
        if($devis->getShop() === "grossiste_greendot")
        {
            $email = "patricehennion@gmail.com";
        }
        else
        {
            $email = $lotCommande->getClient()->getEmail();
        }

        $mail = new \Swift_Message('Votre commande est en cours de preparation');
        $mail->addTo($email);
        $mail->addFrom('gestion@aeroma.fr');
        $mail->setBody($this->templating->render('back/mail/preparationEmail.html.twig', [
            'lotOrder' => $lotCommande,
            'devis' => $devis
        ]), 'text/html');

        $this->mailer->send($mail);
    }

    public function envoieNumeroSuivi(LotCommande $lotCommande)
    {

        $devis = $lotCommande->getBonDeCommande()->getDevis();

        $mail = new \Swift_Message('Numéro de suivi de votre commande');
        $mail->addTo($lotCommande->getClient()->getEmail());
        $mail->addFrom('gestion@aeroma.fr');
        $mail->setBody($this->templating->render('back/mail/numeroDeSuivi.html.twig', [
            'lotOrder' => $lotCommande,
            //'devis' => $devis
        ]), 'text/html');

        $this->mailer->send($mail);
    }

    /*public function sendStripePaid(PurchaseOrder $purchaseOrder, InvoiceOrder $invoiceOrder) {
        $devis = $purchaseOrder->getDevis();
        $mail = new \Swift_Message("Confirmation de paiement Stripe");
        $mail->addTo($purchaseOrder->getClient()->getEmail());
        $mail->addFrom('gestion@aeroma.fr');
        $mail->setBody($this->templating->render('back/mail/paid-invoice.html.twig', [
            'purchaseOrder' => $purchaseOrder,
            'devis' => $devis,
            'invoice' => $invoiceOrder
        ]), 'text/html');

        $filesystem = new Filesystem();
        $invoiceFilename = 'Facture-' . $invoiceOrder->getNumber().".pdf";

        if ($filesystem->exists($this->publicRoot . '/pdf/order/' . $invoiceFilename)) {
            $filesystem->remove($this->publicRoot . '/pdf/order/' . $invoiceFilename);
        }

        $html = $this->templating->render('back/pdf/invoice-shipped.html.twig', [
            'invoiceOrder' => $invoiceOrder,
            'purchaseOrder' => $purchaseOrder,
            'devis' => $devis,
            'lotOrders' => $purchaseOrder->getLotOrders()
        ]);

        $footer = $this->templating->render( '/back/pdf/footer.html.twig' );
        $header = $this->templating->render( '/back/pdf/header.html.twig' );
        $option = ['footer-html' => $footer, 'header-html' => $header];

        $this->snappy_pdf->generateFromHtml($html, $this->publicRoot . '/pdf/order/' . $invoiceFilename, $option);

        $attach = \Swift_Attachment::fromPath($this->publicRoot . '/pdf/order/' . $invoiceFilename, 'application/pdf');
        $mail->attach($attach);

        $this->mailer->send($mail);
    }*/
}
