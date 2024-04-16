<?php

namespace App\Controller\Back;

use DateTime;
use App\Service\Mailer;
use App\Service\Useful;
use App\Entity\Prestation;
use App\Form\PrestationType;
use App\Service\CodeGenerate;
use App\Repository\PrestationRepository;
use App\Service\InvoiceProcessing;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Csrf\Exception\TokenNotFoundException;
use League\Csv\Writer;

class PrestationController extends AbstractController
{
    
    public function index(PrestationRepository $prestationRepository): Response
    {
        return $this->render('back/prestation/index.html.twig', [
            'prestations' => $prestationRepository->findBy([], ['id' => 'desc']),
            'invoices' => true,
            'divers' => true
        ]);
    }

    public function payer(Prestation $prestation, EntityManagerInterface $em, Request $request, Mailer $mailer, Useful $useful, KernelInterface $kernel, InvoiceProcessing $invoiceProcessing)
    {
        if($request->isMethod('post'))
        {

            $date = \DateTimeImmutable::createFromFormat('d/m/Y', trim($request->request->get('date_paiement')));
            $montant = $request->request->get('amount_paid');

            if($montant)
            {
                $prestation->setEstPayer(true)
                        ->setDatePaiement($date)
                        ->setMontantPayer($montant);
            }
            else
            {
                $prestation->setDatePaiement($date);
            }

            $em->flush($prestation);
            $this->addFlash(
                'success',
                'Montant ajouté avec succè'
            );

            $invoiceProcessing->generationFactureDePrestation($prestation, $useful, $kernel);

            if($montant)
            {
                $mailer->envoieFacturePrestation($prestation, "Enregistrement de paiement de la facture n° {$prestation->getNumero()}");
            }
        }

        return $this->redirectToRoute('prestation_index');
    }
   
    public function prestation( ? Prestation $prestation = null, Request $request, InvoiceProcessing $invoiceProcessing, PrestationRepository $prestationRepo, EntityManagerInterface $entityManager, CodeGenerate $generate): Response
    {

        $new = false;

        if( !$prestation )
        {
            $new = true;
            $prestation = new Prestation();
        }

        $form = $this->createForm(PrestationType::class, $prestation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $increment = $invoiceProcessing->incrementInvoice();
        
            $date = new DateTime();

            $code = "{$date->format('Ymd')}-{$generate->code(7)}-";

            $code .= str_pad($increment, 4, '0', STR_PAD_LEFT);

            if($new)
            {
                $prestation->setIncrement($increment);
                $prestation->setNumero($code);
            }
            
            $this->addFlash(
                'success',
                'Facture de prestation enregistrée avec succès'
            );

            $entityManager->persist($prestation);
            $entityManager->flush();

            return $this->redirectToRoute('prestation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('back/prestation/new.html.twig', [
            'prestation' => $prestation,
            'form' => $form->createView(),
            'invoices' => true,
            'divers' => true
        ]);
    }


    public function delete(Request $request, Prestation $prestation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$prestation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($prestation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('prestation_index', [], Response::HTTP_SEE_OTHER);
    }

    public function showInvoice(Prestation $prestation, KernelInterface $kernel, Useful $useful, InvoiceProcessing $invoiceProcessing)
    {

       
        $path = $invoiceProcessing->generationFactureDePrestation($prestation, $useful, $kernel);

        return $this->redirect($path);
     
    }

       
    public function sendInvoice(Prestation $prestation, Mailer $mailer, Request $request, EntityManagerInterface $em, InvoiceProcessing $invoiceProcessing, Useful $useful, KernelInterface $kernel)
    {
        if ($this->isCsrfTokenValid('send' . $prestation->getId(), $request->request->get('_token'))) {
            
            $invoiceProcessing->generationFactureDePrestation($prestation, $useful, $kernel);

            $mailer->envoieFacturePrestation($prestation);

            $prestation->setExpedier(true);

            $em->flush();

            $this->addFlash("success", "Facture avoir envoyé avec succèe");

            return $this->redirect($request->headers->get('referer'));
            
        } else {
            throw new TokenNotFoundException('Token invalide');
        }
    }

    public function exportDivers(PrestationRepository $prestationRepository, $exportRoot)
    {
        $headers = ['CODE', 'CLIENT', 'MONTANT (EN EURO)', 'TAXE (EN EURO)', 'MONTANT A REGLER (EN EURO)', 'MONTANT PAYE (EN EURO)', 'ETAT'];

        $objects = $prestationRepository->findBy([], ['id' => 'desc']);

        $columns = [];

        foreach ($objects as $obj) {
            $prestation = $obj;
            $client = '';

            if (!empty($prestation->getClient())) {
                if (!empty($prestation->getClient()->getRaisonSocial())) {
                    $client = $prestation->getClient()->getRaisonSocial();
                } else {
                    $client = $prestation->getClient();
                }
            }

            $etat = '';

            if (!empty($prestation->getEstPayer())) {
                $etat = 'PAYE';
                if ($prestation->getBalance() != 0) {
                    $etat .= ' ' . $prestation->getBalance() . 'euro';
                }
            } else {
                $etat = 'NON PAYE';
            }

            $columns[] = [
                $prestation->getNumero(), // Numéro
                $client, // Client
                $prestation->getMontant(), // Montant
                $prestation->getTva(), // Taxe
                round($prestation->getTotalTtc(), 2), // Montant à régler
                round($prestation->getMontantPayer(), 2), // Montant payé
                $etat, // Etat
            ];
        }

        $data = array_merge([$headers], $columns);
        
        $filename = 'prestations_aeroma_' . (new \DateTime())->format('Y-m-d') . '.csv';
        $csvWriter = Writer::createFromPath($exportRoot . '/csv/' . $filename, 'w+');
        $csvWriter->setDelimiter(';');
        $csvWriter->insertAll($data);
        
        $response = new Response($csvWriter->getContent(), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);

        return $response;
    }
}
