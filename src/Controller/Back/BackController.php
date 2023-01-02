<?php


namespace App\Controller\Back;

use App\Service\Mailer;
use App\Repository\DevisRepository;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\BonDeCommandeRepository;
use App\Repository\FactureCommandeRepository;
use App\Repository\FactureMaitreRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class BackController extends AbstractController
{

    public function index(EntityManagerInterface $em, SessionInterface $session, Request $request, FactureMaitreRepository $factureMaitreRepository, DevisRepository $devisRepo, FactureCommandeRepository $factRepo, BonDeCommandeRepository $bonRepo, ClientRepository $clientRepo)
    {

        if($request->isXmlHttpRequest())
        {
            $session->migrate();
            return $this->json(['success' => true], 200);
        }

        $paidMaster =  $factureMaitreRepository->findBy(['estPayer' => true]);
        $unpaidMaster =  $factureMaitreRepository->findBy(['estPayer' => false]);
        $paidM = 0;
        $unpaidM = 0; 
        
        foreach($paidMaster as $p)
        {
            if( count($p->getBonDeCommandes()) > 0 )
            {
                $paidM += $p->getMontantAPayer();
            }
        }

        foreach($unpaidMaster as $p)
        {
            if( count($p->getBonDeCommandes()) > 0 )
            {
                $unpaidM += $p->getMontantAPayer();
            }
        }
       
        return $this->render('back/index.html.twig', [
            'enAttente' => $devisRepo->standBy(),
            'signer' => $devisRepo->devisSigner(),
            'ca' => ($factRepo->turnover(true) + $paidM ),
            'unpaid' => ($factRepo->turnover(false) + $unpaidM ),
            'tOrder' => $bonRepo->tOrder(),
            'trest' => $bonRepo->trestToDelivred(),
            'tclt' => $clientRepo->tclients()
        ]);
    }

    public function testMail(Request $request, Mailer $mailer, BonDeCommandeRepository $bcRepo)
    {

        $bon = $bcRepo->find(80);

        $mailer->sendOrder($bon, null, null, "rindraramiaramanana@gmail.com");
        
        return $this->redirectToRoute('back_index');

    }
}
