<?php


namespace App\Service;


use App\Service\TrieProduit;
use App\Entity\FactureMaitre;
use App\Entity\FactureCommande;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\Filesystem\Filesystem;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use App\Repository\PositionGammeClientRepository;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Symfony\Component\HttpKernel\KernelInterface;

class FactureExcel 
{
    private $facture;
    private $sheet;
    private $publicRoot;
    private $trieProduit;
    private $positionGammeClientRepo;
    

    public function __construct(TrieProduit $trieProduit, PositionGammeClientRepository $positionGammeClientRepo, KernelInterface $kernelInterface)
    {
        $this->trieProduit = $trieProduit;
        $this->positionGammeClientRepo = $positionGammeClientRepo;
        $this->positionGammeClientRepo = $positionGammeClientRepo;
        $this->publicRoot = $kernelInterface->getProjectDir(). DIRECTORY_SEPARATOR . 'public';
    }

    public function creer($facture)
    {
        if( ! $facture instanceof FactureCommande && ! $facture instanceof FactureMaitre )
        {
            return;
        }

        $spreadsheet  = new Spreadsheet;
        $writer = new Xlsx($spreadsheet);
        
        $this->sheet = $spreadsheet->getActiveSheet();

        $this->sheet->getPageSetup()
            ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT)
            ->setScale(60);

        $this->facture = $facture;
        $this->entete();
        $this->corps();

        $fileName = $facture->getNumero();
        
        $temp_file = "excel/bonPreparation/" . $fileName;

        $filesystem = new Filesystem();

        if ($filesystem->exists($this->publicRoot . '/excel/bonPreparation/' . $fileName)) {
            $filesystem->remove($this->publicRoot . '/excel/bonPreparation/' . $fileName);
        }

        $writer->save($temp_file);

        $response = ['temp_file' => $temp_file, 'fileName' => $fileName];

        return $response;

    }

    public function getClient()
    {
        return $this->getFacture()->getClient();
    }

    public function getSheet()
    {
        return $this->sheet;
    }

    public function getFacture()
    {
        return $this->facture;
    }

    public function entete()
    {
        $facture = $this->getFacture();
        $shop = $facture->getDevis()->getBoutique();
        $client = $facture->getClient();

        $sheet = $this->getSheet();

        $sheet->mergeCells("A1:E10");
        
        if( ! $shop )
        {
            $shop = $client;
           
            $sheet->setCellValue("A1", "Adresse de livraison\nClient: {$client->getRaisonSocial()}\nNom: {$client->getLastname()}\nPrenom(s): {$client->getFirstname()}\n{$client->getAdresseServiceAchats()}\n{$client->getCodePostalServiceAchat()}\n{$client->getVilleServiceAchat()}\n{$client->getPays()}\nemail : {$client->getEmail()}\nTéléphone : {$client->getTelephone()}");
        }
        else
        {
            $sheet->setCellValue("A1", "ADRESSE DE LIVRAISON\nClient: {$shop->getNomShop()} \nNom: {$shop->getNom()}\nPrenom(s): {$shop->getPrenom()}\n{$shop->getAdresse()}\n{$shop->getCodePostal()}\n{$shop->getVille()}\n{$client->getPays()} \nemail : {$shop->getEmail()}\nTéléphone : {$shop->getTelephone()}");
        }

        $this->alignCenter("A1");

        $sheet->mergeCells("K1:O10");

        $sheet->setCellValue("K1", "ADRESSE DE FACTURATION\nClient: {$client->getRaisonSocial()}\nNom: {$client->getLastname()}\nPrenom(s): {$client->getFirstname()}\n{$client->getAdresseFacturation()}\n{$client->getCodePostalFacturation()}\n{$client->getVilleFacturation()}\n{$client->getPays()}\nemail : {$client->getEmail()}\nTéléphone : {$client->getTelephone()}");

        $this->alignCenter("K1");

        $sheet->mergeCells("A13:O13");
        
        $sheet->setCellValue("A13", "FACTURE N° {$facture->getNumero()}");
        $sheet->getStyle("A13")
            ->getFont()
            ->setBold(true);

        $sheet->getRowDimension("13")->setRowHeight(25);

        $sheet->getStyle("A13")
                ->getFont()
                ->setSize(20);
        $this->alignCenter("A13");
    }

    public function getStyleArray()
    {
        return [
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
    }

    public function corps()
    {
        $sheet = $this->getSheet();
        $facture = $this->getFacture();
        $lots = $facture->getLotCommandes();
        $client = $this->getClient();
        $devis = $facture->getDevis();

        $this->mergeAndAddStyle("A16:C16");
        $this->mergeAndAddStyle("D16:J16");
        $this->mergeAndAddStyle("K16:L16");
        $this->mergeAndAddStyle("M16", false);
        $this->mergeAndAddStyle("N16", false);
        
        $sheet->setCellValue("A16", "Référence")
            ->setCellValue("D16", "Produit")
            ->setCellValue("K16", "Prix unitaire (HT)")
            ->setCellValue("M16", "Quantité")
            ->setCellValue("N16", "Total(HT)")
        ;

        $this->alignCenter("A16");
        $this->alignCenter("D16");
        $this->alignCenter("K16");
        $this->alignCenter("M16");

        $cell = 17;

        foreach($lots as $lot)
        {
           
            $this->mergeAndAddStyle("A$cell:N$cell");

            $this->alignCenter("A$cell:N$cell");


            if($lot->getDate())
            {
                $designationLot = "{$lot->getNom()} expédiée le {$lot->getDate()->format('d-m-Y')}";
            }
            else
            {
                $designationLot = $lot->getNom();
            }

            $sheet->setCellValue("A$cell", $designationLot );
            $sheet->getStyle("A$cell")->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB("b3b3b3");

            $cell++;

            $lqOrdonner = $this->trier($lot->getLotQuantites(), $client);

            foreach($lqOrdonner as $lq)
            {
                $produit = $lq->getProduit();
                $prix = ($lq->getPrixSpecial()) ? $lq->getPrixSpecial() : $lq->getPrix();
                $quantite = $lq->getQuantite();

                $total = $prix * $quantite;

                $nomProduitClient = $produit->getNomMarqueBlanche($client)." ". $lq->getDeclinaison();

                if($lq->getOffert())
                {
                    $nomProduitClient .= ' (Offert)';
                    $total = 0;
                }

                $this->mergeAndAddStyle("A$cell:C$cell");
                $this->mergeAndAddStyle("D$cell:J$cell");
                $this->mergeAndAddStyle("K$cell:L$cell");
                $this->mergeAndAddStyle("M$cell", false);
                $this->mergeAndAddStyle("N$cell", false);

                $sheet->setCellValue("A$cell", $produit->getReference())
                    ->setCellValue("D$cell", $nomProduitClient)
                    ->setCellValue("K$cell", $prix)
                    ->setCellValue("M$cell", $quantite)
                    ->setCellValue("N$cell", $total)

                ;

                $this->addMoneySign("K$cell");
                $this->addMoneySign("N$cell");

                $this->alignCenter("A$cell");
                $this->alignCenter("D$cell");
                $this->alignCenter("K$cell");
                $this->alignCenter("M$cell");
                $this->alignCenter("N$cell");

                $cell++;
            }
        }

        $this->mergeAndAddStyle("K$cell:L$cell");
        $this->mergeAndAddStyle("M$cell:N$cell");
        
        $sheet->setCellValue("K$cell", "Montant")
            ->setCellValue("M$cell", $facture->getTotal())
        ;
        $this->addMoneySign("M$cell");


        $cell++;

        $this->mergeAndAddStyle("K$cell:L$cell");
        $this->mergeAndAddStyle("M$cell:N$cell");

        if($devis->getFraisExpedition() > 0)
        {
            $frais = $devis->getFraisExpedition();
            $this->addMoneySign("M$cell");
        }
        else
        {
            $frais = "Livraison gratuite";
        }

        
        $sheet->setCellValue("K$cell", "Frais de livraison")
            ->setCellValue("M$cell", $frais)
        ;

        $cell++;

        $this->mergeAndAddStyle("K$cell:L$cell");
        $this->mergeAndAddStyle("M$cell:N$cell");
        
        $sheet->setCellValue("K$cell", "Total (HT)")
            ->setCellValue("M$cell", $facture->getTotalHt())
        ;
        
        $this->addMoneySign("M$cell");

        $cell++;

        $this->mergeAndAddStyle("K$cell:L$cell");
        $this->mergeAndAddStyle("M$cell:N$cell");

        $sheet->setCellValue("K$cell", "Taxe total")
            ->setCellValue("M$cell", $facture->getTva())
        ;
        $this->addMoneySign("M$cell");

        $cell++;

        $this->mergeAndAddStyle("K$cell:L$cell");
        $this->mergeAndAddStyle("M$cell:N$cell");

        $sheet->setCellValue("K$cell", "Total TTC")
            ->setCellValue("M$cell", $facture->getTotalTtc())
        ;
        $this->addMoneySign("M$cell");

        if($facture->getEstPayer())
        {
            $cell++;

            $this->mergeAndAddStyle("K$cell:L$cell");
            $this->mergeAndAddStyle("M$cell:N$cell");
    
            $sheet->setCellValue("K$cell", "Montant réglé")
                ->setCellValue("M$cell", $facture->getMontantPayer())
            ;
            $this->addMoneySign("M$cell");
            $cell +=2 ;

          

            $sheet->mergeCells("A$cell:M$cell");

            
            $message = null;

            if( $facture->getBalance() )
            {
                if($facture->getBalance() == 0)
                {
                    $message = "SOLDE :";
                }
                else
                {
                    if($facture->getBalance() < 0)
                    {
                        $message = "Montant à rajouter sur la prochaine facture :";
                    }
                    else
                    {
                        $message = "Montant à déduire sur la prochaine facture :";
                    }
                }
            }

            $sheet->setCellValue("A$cell", $message);
            $sheet->getStyle("A$cell")
                  ->getFont()
                  ->setBold(true)
            ;

            $sheet->getStyle("A$cell")
                 ->getAlignment()
                 ->setHorizontal('right')
                 ->setWrapText(true)
            ;

            $sheet->setCellValue("N$cell",  abs($facture->getBalance()));
            $sheet->getStyle("N$cell")
                  ->getFont()
                  ->setBold(true)
            ;

            $this->addMoneySign("N$cell");

        }
    }

    public function alignCenter(string $cell): void
    {
        $sheet = $this->getSheet();
        $sheet->getStyle($cell)
            ->getAlignment()
            ->setHorizontal('center')
            ->setWrapText(true)
        ;
    }

    public function addMoneySign(string $cell): void
    {
        $sheet = $this->getSheet();

        $sheet->getStyle($cell)
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE)
        ;
    }
    public function mergeAndAddStyle( string $cells, bool $style = true): void
    {
        $sheet = $this->getSheet();
        $styleArray = $this->getStyleArray();

        if($style)
        {
            $sheet->mergeCells($cells);
        }

        $sheet->getStyle($cells)->applyFromArray($styleArray);
    }

    public function trier($commande, $client)
    {
    
        $gammesClient = $this->positionGammeClientRepo->findBy(['client' => $client ], ['position' => 'asc']);

        $commandeTrier = $this->trieProduit->setGammesClient($gammesClient)
                                    ->setCommandes($commande)
                                    ->trieCommande();

        return $commandeTrier;
    }
}