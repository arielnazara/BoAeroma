<?php


namespace App\Controller\Back;

use App\Entity\Declinaison;
use App\Entity\PrincipeActif;
use App\Form\DeclinaisonType;
use App\Form\PrincipeActifType;
use App\Repository\DeclinaisonRepository;
use App\Repository\PrincipeActifRepository;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class DeclinaisonController extends AbstractController
{
    public function index(PrincipeActifRepository $principeRepo)
    {
        return $this->render('back/produit/declinaison/index.html.twig', [
            'produits' => true,
            'configuration' => true,
            'declinaisons' => $principeRepo->findAll()
        ]);
    }

    /*public function listeDeclinaison(Declinaison $declinaison){
        return $this->render('back/produit/declinaison/listeDeclinaison.html.twig',[
            'produits' => true,
            'configuration' => true,
            'declinaisons'=> $declinaison
        ]);
    }*/


    public function ajoutDeclinaison(Request $request, EntityManagerInterface $em, DeclinaisonRepository $declinaisonRepo)
    {

        $principeActif = new PrincipeActif;

        $form = $this->createForm(PrincipeActifType::class, $principeActif);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $countPost = count($_POST);

            $i = 1;

            while ($i <= $countPost) {
                if (isset($_POST["declinaison-$i"])) {
                    $declinaison = new Declinaison;
                    $declinaison->setDeclinaison($_POST["declinaison-$i"]);
                    $declinaison->setPrincipeActif($principeActif);
                    $em->persist($declinaison);
                }
                $i++;
            }

            $em->persist($principeActif);
            $em->flush();

            $this->addFlash('successAjoutDeclinaison', 'Principe actif enregistré avec succès');
            return $this->redirectToRoute('back_produit_declinaison');
        }
        return $this->render('back/produit/declinaison/creation.html.twig', [
            'produits' => true,
            'configuration' => true,
            'declinaisons' => true,
            'form' => $form->createView()
        ]);
    }

    public function modification(PrincipeActif $principeActif, Request $request, EntityManagerInterface $em)
    {

        $form = $this->createForm(PrincipeActifType::class, $principeActif);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $countPost = count($_POST);

            $i = 1;

            $declinaisonTab = $principeActif->getDeclinaisons();

            while ($i <= $countPost) {
                if (isset($_POST["declinaison-$i"])) {
                    $exist = false;
                    foreach ($declinaisonTab as $decli) {
                        if ($decli->getdeclinaison() == $_POST["declinaison-$i"]) {
                            $exist = true;
                        }
                    }

                    if (!$exist) {
                        $declinaison = new Declinaison;
                        $declinaison->setPrincipeActif($principeActif);
                        $declinaison->setDeclinaison($_POST["declinaison-$i"]);
                        $em->persist($declinaison);
                    }
                }
                $i++;
            }

            $em->flush();

            $this->addFlash('successAjoutDeclinaison', 'Modification principe actif éffectuée avec succès');
            return $this->redirectToRoute('back_produit_declinaison');
        }
        return $this->render('back/produit/declinaison/modification.html.twig', [
            'produits' => true,
            'configuration' => true,
            'form' => $form->createView(),
            'principeActif' => $principeActif
        ]);
    }

    public function supprimePrincipeActif(PrincipeActif $principeActif, DeclinaisonRepository $declinaisonRepo, Request $request, EntityManagerInterface $em)
    {

        if ($request->isXMLHttpRequest()) {
            //dd($request->request->all());

            $listeDeclinaison = $declinaisonRepo->findOneBy(['principeActif' => $request->request->get('id'), 'declinaison' => $request->request->get('decli')]);

            $em->remove($listeDeclinaison);

            $em->flush();

            return $this->json(
                [
                    'code' => 200,
                    'supprimer' => str_replace(',', '', $request->request->get('decli'))
                ],
                200
            );
        }
    }
}
