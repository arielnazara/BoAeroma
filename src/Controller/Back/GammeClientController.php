<?php


namespace App\Controller\Back;

use App\Entity\CatalogueClient;
use App\Entity\Client;
use App\Entity\Marque;
use App\Entity\GammeClient;
use App\Form\GammeClientType;
use App\Entity\UnitMarqueGamme;
use App\Entity\GammeMarqueBlanche;
use App\Entity\UnitGameClientGame;
use App\Entity\PositionGammeClient;
use App\Entity\UnitCommandeProduit;
use App\Repository\GammeRepository;
use App\Entity\UnitGameClientMarque;
use App\Form\GammeMarqueBlancheType;
use App\Repository\ClientRepository;
use App\Repository\MarqueRepository;
use App\Repository\ProduitRepository;
use App\Entity\UnitGammeClientProduit;
use App\Form\PositionGammeClientsType;
use App\Entity\UnitGameClientCategorie;
use App\Event\GammeClientEvent;
use App\Form\CatalogueClientType;
use App\Repository\CatalogueClientRepository;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\GammeClientRepository;
use App\Repository\GammeMarqueBlancheRepository;
use App\Repository\MarqueBlancheRepository;
use App\Repository\PositionGammeClientRepository;
use App\Repository\PrincipeActifRepository;
use App\Repository\UnitMarqueGammeRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\UnitGameClientGameRepository;
use App\Repository\UnitCommandeProduitRepository;
use App\Repository\UnitGameClientMarqueRepository;
use App\Repository\UnitGammeClientProduitRepository;
use DateTime;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Exception\TokenNotFoundException;

class GammeClientController extends AbstractController
{

    public function refresh(MarqueBlancheRepository $mbRepo, Request $request, PositionGammeClientRepository $positionRepo, EntityManagerInterface $em)
    {
       
        //etape 2
        foreach($mbRepo->findAll() as $mb)
        {
            if( $mb->getProduit() )
            {
                if( ! $mb->getGammeMarqueBlanche() )
                {
                    $position = $positionRepo->findOneBy(['client' => $mb->getClient(), 'GammePrincipale' => $mb->getProduit()->getGamme()]);
                }
                else
                {
                    $position = $positionRepo->findOneBy(['client' => $mb->getClient(), 'CustomGamme' => $mb->getGammeMarqueBlanche()]);
                }
                $mb->setPositionGammeClient($position);
            }

        }

        $em->flush();

        return $this->redirect($request->headers->get('referer'));
        
    }

    public function listeCatalogueClient(PositionGammeClientRepository $positionRepo, GammeRepository $gammeRep, GammeMarqueBlancheRepository $grepo, GammeRepository $gammeRepo)
    {
        /*$dont = [];
        $do = [];

        foreach($grepo->findAll() as $g)
        {
            $exist = $gammeRep->findGammeLike($g->getNom());

            if(  count($exist) > 0 )
            {
                if( count($exist) == 1  )
                {
                    $do[$g->getId()] = $exist[0]->getNom();
                }
                else
                {
                    dd($exist, $g);
                }
            }
            else
            {
                $dont[$g->getId()] = $g->getNom();
                
            }


        }

        dd($do);*/

        return $this->render('back/gammeClient/liste_catalogue.html.twig', [
            'catalogue' => true,
            'produits' => true,
            'produit' => true,
            'gammeListe' => $positionRepo->findWhereCLientExist(),
        ]);
    }

    public function listeDesGammesDuClient(Client $client, PositionGammeClientRepository $positionRepo)
    {
        return $this->render('back/gammeClient/liste_des_gammes_du_client.html.twig', [
            'catalogue' => true,
            'produits' => true,
            'produit' => true,
            'gammeListe' => $positionRepo->findGammeClient($client),
        ]);
    }

    public function listeGammeMB(GammeMarqueBlancheRepository $gammeMbRepo, GammeRepository $gammeRepo)
    {
        return $this->render('back/gammeClient/listeGamme.html.twig', [
            'catalogue' => true,
            'produits' => true,
            'produit' => true,
            'gammeListe' => $gammeMbRepo->findBy([], ['id' => 'desc']),
        ]);
    }

    public function creationGammeMB( ? GammeMarqueBlanche $gamme = null, Request $request, EntityManagerInterface $em)
    {
        
        if(! $gamme)
        {   
            $gamme = new GammeMarqueBlanche;
        }
        
        $form = $this->createForm(GammeMarqueBlancheType::class, $gamme);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) 
        {
            $em->persist($gamme);
            $em->flush();

            $this->addFlash(
                'success',
                'Opération effectuée avec succès'
            );
            return $this->redirectToRoute('back_list_des_gammes_clients');
        }

        return $this->render('back/gammeClient/creation_gamme_client.html.twig', [
            'form' => $form->createView(),
            'produits' => true,
            'produit' => true,
        ]);
    }

    public function creationGammeClient( ? PositionGammeClient $catalogue = null, Request $request, EntityManagerInterface $em, EventDispatcherInterface $dispatcher)
    {
        if(! $catalogue)
        {   
            $catalogue = new PositionGammeClient;
        }
        
        $form = $this->createForm(CatalogueClientType::class, $catalogue);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) 
        {

            $catalogue->setUpdatedAt(new DateTimeImmutable());

            $em->persist($catalogue);
            $em->flush();

            $this->addFlash(
                'success',
                'Opération effectuée avec succès'
            );
            
            return $this->redirectToRoute("back_liste_des_gamme_du_client", ['id' => $catalogue->getClient()->getId()]);
        }

        return $this->render('back/gammeClient/creation_catalogue.html.twig', [
            'form' => $form->createView(),
            'catalogue' => $catalogue,
            'produits' => true,
            'produit' => true,
        ]);
    }

    public function supprimerGammeCatalogueClient(PositionGammeClient $gammeCatalogue, Request $request, EntityManagerInterface $em)
    {
        if ($this->isCsrfTokenValid('delete' . $gammeCatalogue->getId(), $request->request->get('_token'))) {

            $id = $gammeCatalogue->getClient()->getId();

            $em->remove($gammeCatalogue);
            $em->flush();
            $this->addFlash('success', 'Gamme supprimé avec succès');
            
            return $this->redirectToRoute("back_liste_des_gamme_du_client", ['id' => $id ]);
            
        } else {
            throw new TokenNotFoundException('Token invalide');
        }
    }

    public function supprimerGammeClient(GammeMarqueBlanche $gammeClient, Request $request, EntityManagerInterface $em)
    {
        if ($this->isCsrfTokenValid('delete' . $gammeClient->getId(), $request->request->get('_token'))) {
            $em->remove($gammeClient);
            $em->flush();
            $this->addFlash('success', 'Gamme supprimé avec succès');
            return $this->redirectToRoute('back_list_des_gammes_clients');
        } else {
            throw new TokenNotFoundException('Token invalide');
        }
    }


































    public function creationGamme(EntityManagerInterface $em, MarqueRepository $marqueRepo, UnitMarqueGammeRepository $ugmrepo, ProduitRepository $produitRepo, ClientRepository $clientRepo, CategorieRepository $categorieRepo, Request $request, GammeRepository $gammeRepo)
    {
        $gammeClient = new GammeClient;

        $form = $this->createForm(GammeClientType::class, $gammeClient);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if (!$_POST['gamme_client']['toutLesProduits']) {

                $PostCount = count($_POST);

                $k = 1;
                while ($k <= $PostCount) {

                    if (isset($_POST["marqueClient-$k"])) {
                        $marqueUnit = new UnitGameClientMarque;
                        $marqueUnit->setMarque($marqueRepo->find($_POST["marqueClient-$k"]));

                        $produits = $produitRepo->findBy(['marque' => $_POST["marqueClient-$k"]]);

                        if ($produits) {

                            foreach ($produits as $prod) {
                                $gammeUnit = new UnitGammeClientProduit;

                                $gammeUnit->setProduit($prod);
                                $gammeUnit->setToutLesDeclinaisons(true);
                                $gammeUnit->setDeclineAvec($prod->getPrincipeActif());
                                $gammeUnit->setGamme($prod->getGamme());
                                $gammeUnit->setGammeClient($gammeClient);
                                $gammeUnit->setMarque($marqueRepo->find($_POST["marqueClient-$k"]));
                                $em->persist($gammeUnit);
                            }
                        }

                        $marqueUnit->setGammeClient($gammeClient);
                        $marqueUnit->setNumGammeClient("gamme-$k");
                        $em->persist($marqueUnit);


                        $gammes = $ugmrepo->findBy(['marque' => $_POST["marqueClient-$k"]]);

                        if ($gammes) {

                            foreach ($gammes as $gam) {
                                $gamme = new UnitGameClientGame;

                                $gamme->setGamme($gam->getGamme());
                                $gamme->setGammeClient($gammeClient);
                                $gamme->setMarque($marqueRepo->find($_POST["marqueClient-$k"]));
                                $em->persist($gamme);
                            }
                        }
                    }
                    $k++;
                }

                $em->persist($gammeClient);
                $em->flush();

                return $this->redirectToRoute('back_config_produit_gamme_client', ['id' => $gammeClient->getId()]);
            } else {

                $em->persist($gammeClient);
                $em->flush();

                $this->addFlash('successGammeClient', 'Création gamme client effectuèe avec succès');
                return $this->redirectToRoute('back_liste_gamme_client');
            }
        }

        return $this->render('back/gammeClient/creationGammeClient.html.twig', [
            'catalogue' => true,
            'produits' => true,
            'produit' => true,
            'gammeListe' => $gammeRepo->findBy(['parDefaut' => true]),
            'listeCategorie' => $categorieRepo->findAll(),
            'listeMarque' => $marqueRepo->findAll(),
            'form' => $form->createView(),
            //'composants' => $compoRepo->findAll()
        ]);
    }

    public function modificationMarqueClient(GammeClient $gammeClient, ProduitRepository $produitRepo, UnitMarqueGammeRepository $ugmrepo, EntityManagerInterface $em, MarqueRepository $marqueRepo, Request $request, UnitGameClientMarqueRepository $GCMRepo, UnitGammeClientProduitRepository $gprodrepo)
    {
        $marqueExist = $GCMRepo->findBy(['gammeClient' => $gammeClient->getId()]);

        if ($request->isMethod('post')) {

            $gammeClient->setToutLesProduits($_POST['tousleproduit']);
            $em->flush();

            if (!$gammeClient->getToutLesProduits()) {
                $i = 1;
                $PostCount = count($_POST);
                //dd($_POST);
                while ($i <= $PostCount) {

                    if (isset($_POST["marque-$i"])) {
                        $exist = $GCMRepo->findOneBy(['gammeClient' => $gammeClient->getId(), 'marque' => $_POST["marque-$i"]]);

                        if (!$exist) {
                            $new = new UnitGameClientMarque;
                            $new->setGammeClient($gammeClient);
                            $new->setMarque($marqueRepo->find($_POST["marque-$i"]));
                            $new->setNumGammeClient("gamme-$i");

                            $produits = $produitRepo->findBy(['marque' => $marqueRepo->find($_POST["marque-$i"])]);

                            if ($produits) {

                                foreach ($produits as $prod) {
                                    $gammeUnit = new UnitGammeClientProduit;

                                    $gammeUnit->setProduit($prod);
                                    $gammeUnit->setToutLesDeclinaisons(true);
                                    $gammeUnit->setDeclineAvec($prod->getPrincipeActif());
                                    $gammeUnit->setGamme($prod->getGamme());
                                    $gammeUnit->setGammeClient($gammeClient);
                                    $gammeUnit->setMarque($marqueRepo->find($_POST["marque-$i"]));
                                    $em->persist($gammeUnit);
                                }
                            }

                            $gammes = $ugmrepo->findBy(['marque' => $_POST["marque-$i"]]);

                            if ($gammes) {

                                foreach ($gammes as $gam) {
                                    $gamme = new UnitGameClientGame;

                                    $gamme->setGamme($gam->getGamme());
                                    $gamme->setGammeClient($gammeClient);
                                    $gamme->setMarque($marqueRepo->find($_POST["marque-$i"]));
                                    $em->persist($gamme);
                                }
                            }

                            $em->persist($new);
                            $em->flush();
                        }
                    }
                    $i++;
                }

                return $this->redirectToRoute('back_modification_gamme_client', ['id' => $gammeClient->getId()]);
            } else {
                $marques = $GCMRepo->findBy(['gammeClient' => $gammeClient]);

                foreach ($marques as $marque) {
                    $em->remove($marque);
                    $em->flush();
                }

                $produits = $gprodrepo->findBy(['gammeClient' => $gammeClient]);

                if ($produits) {
                    foreach ($produits as $produit) {
                        $em->remove($produit);
                        $em->flush();
                    }
                }
                $this->addFlash('gammeClientSuccess', 'Modification catalogue client effectuée avec succès');
                return $this->redirectToRoute('back_liste_gamme_client');
            }
        }

        return $this->render('back/gammeClient/modificationMarque.html.twig', [
            'gammeClient' => $gammeClient,
            'listeMarque' => $marqueRepo->findAll(),
            'catalogue' => true,
            'produits' => true,
            'produit' => true,
        ]);
    }

    public function configProduit($id, $modif = null, GammeClientRepository $GCRepo, Request $request, UnitGameClientMarqueRepository $unitGMRepo, EntityManagerInterface $em)
    {

        $gammeClient = $GCRepo->find($id);
        $unitGM = $unitGMRepo->findBy(['gammeClient' => $id]);

        return $this->render('back/gammeClient/configProduit.html.twig', [
            'gammeClient' => $gammeClient,
            'produit' => true,
            'catalogue' => true,
            'produits' => true,
            'produit' => true,
            'modifConfig' => $modif
        ]);
    }

    public function ajoutProduit(Request $request, UnitGammeClientProduitRepository $GCPRepo, EntityManagerInterface $em, ProduitRepository $produitRepo, UnitGameClientMarqueRepository $unitGMRepo, MarqueRepository $marqueRepo, GammeClientRepository $GCRepo)
    {
        if ($request->isXmlHttpRequest()) {

            //$config = new UnitGammeClientProduit;
            $config = $GCPRepo->findOneBy(['gammeClient' => $request->request->get('gammeClientId'), 'produit' => $request->request->get('produitId')]);

            $config->setGammeClient($GCRepo->find($request->request->get('gammeClientId')));
            $config->setProduit($produitRepo->find($request->request->get('produitId')));

            $config->setDeclinaison($request->request->get('declinaison'));

            if ($request->request->get('tout')) {
                $config->setToutLesDeclinaisons(true);
                $config->setDeclinaison([]);
                $config->setConfigurer(false);
            } else {
                $config->setToutLesDeclinaisons(false);
                $config->setConfigurer(true);
            }

            $config->setDeclineAvec($produitRepo->find($request->request->get('produitId'))->getPrincipeActif());

            $em->persist($config);
            $em->flush();

            return $this->json(
                [
                    'code' => 200,
                    'reponse' => $config->getProduit()->getNom()
                ],
                200
            );
        }
    }

    public function configProduitTerminer(GammeClient $gammeClient, $idM = null, $modif = null, UnitGameClientMarqueRepository $MGRepo, MarqueRepository $marqueRepo, EntityManagerInterface $em)
    {

        $marque = $MGRepo->findOneBy(['marque' => $idM, 'gammeClient' => $gammeClient->getId()]);
        $marque->setConfigurer(true);
        $em->flush();
        $this->addFlash('configProduit', 'Configuration de la marque ' . $marqueRepo->find($idM)->getNom() . ' éffectuée avec succès');
        return $this->redirectToRoute('back_config_produit_gamme_client', ['id' => $gammeClient->getId(), 'modif' => $modif]);
    }

    public function enregistrerMarqueEtProduit(GammeClient $gammeClient, $terminer, UnitGameClientGameRepository $GCGRepo, EntityManagerInterface $em, ProduitRepository $produitRepo, UnitGameClientMarqueRepository $unitGMRepo, UnitGammeClientProduitRepository $GCPRepo)
    {
        $listeMarque = $unitGMRepo->findBy(['gammeClient' => $gammeClient->getId()]);
        $listeGamme = $GCGRepo->findBy(['gammeClient' => $gammeClient->getId()]);

        foreach ($listeMarque as $lm) {
            if (!$lm->getConfigurer()) {
                $lm->setConfigurer(true);
                $listeProduit = $produitRepo->findBy(['marque' => $lm->getMarque()]);
                foreach ($listeProduit as $produit) {
                    $exist = $GCPRepo->findOneBy(['produit' => $produit, 'gammeClient' => $gammeClient->getId()]);

                    if (!$exist) {
                        $GCP = new UnitGammeClientProduit;
                        $GCP->setMarque($lm->getMarque());
                        $GCP->setGammeClient($gammeClient);
                        $GCP->setProduit($produit);
                        $GCP->setDeclineAvec($produit->getPrincipeActif());
                        $GCP->setToutLesDeclinaisons(true);
                        $em->persist($GCP);
                        $em->flush();
                    }
                }
            }
        }

        $this->addFlash('gammeClientSuccess', 'Gamme client enregistrer avec succès');
        return $this->redirectToRoute('back_liste_gamme_client');
    }

    public function produit($id, $idGammeClient, $idGamme = null, $modif = null, UnitGammeClientProduitRepository $GCPRepo, Request $request, ProduitRepository $produitRepo, EntityManagerInterface $em, MarqueRepository $marqueRepo, GammeClientRepository $GCRepo)
    {

        $marque = $marqueRepo->find($id);
        $gammeClient = $GCRepo->find($idGammeClient);

        if ($idGamme) {
            $prod = $produitRepo->findBy(['marque' => $marque->getId(), 'gamme' => $idGamme]);
        } else {

            $prod = $produitRepo->findBy(['marque' => $marque->getId()]);
        }

        if (!$modif) {
            foreach ($prod as $pro) {
                $prodGam = $GCPRepo->findOneBy(['gammeClient' => $gammeClient->getId(), 'produit' => $pro->getId()]);
                if ($prodGam) {
                    $prodGam->setProduit($pro);
                    $prodGam->setGammeClient($gammeClient);
                    $prodGam->setDeclineAvec($pro->getPrincipeActif());
                    $prodGam->setMarque($marque);
                    $prodGam->setToutLesDeclinaisons(true);
                    $em->persist($prodGam);
                } else {
                    $produitGamme = new UnitGammeClientProduit;
                    $produitGamme->setProduit($pro);
                    $produitGamme->setGammeClient($gammeClient);
                    $produitGamme->setMarque($marque);
                    $produitGamme->setDeclineAvec($pro->getPrincipeActif());
                    $produitGamme->setToutLesDeclinaisons(true);
                    $em->persist($produitGamme);
                }

                $em->flush();
            }
        }

        return $this->redirectToRoute('back_config_list_produit_gamme_client', ['id' => $id, 'idGammeClient' => $idGammeClient, 'idGamme' => $idGamme, 'modif' => $modif]);
    }

    public function listProduitMarqueGamme($id, $idGammeClient, $idGamme, $modif = null, GammeRepository $gammeRepo, PrincipeActifRepository $principeRepo, UnitGammeClientProduitRepository $GCPRepo, Request $request, ProduitRepository $produitRepo, EntityManagerInterface $em, MarqueRepository $marqueRepo, GammeClientRepository $GCRepo)
    {

        $marque = $marqueRepo->find($id);
        $gammeClient = $GCRepo->find($idGammeClient);
        $produitGamme = false;
        if ($idGamme) {
            $listeProduit = $GCPRepo->findBy(['gammeClient' => $idGammeClient, 'marque' => $id, 'gamme' => $idGamme]);

            $totalProduit = $produitRepo->findBy(['marque' => $id, 'gamme' => $idGamme]);

            ($listeProduit) ? $produitGamme = true : $produitGamme = false;
        } else {
            $listeProduit = $GCPRepo->findBy(['gammeClient' => $idGammeClient, 'marque' => $id]);
            $totalProduit = $produitRepo->findBy(['marque' => $id]);
        }

        return $this->render('back/gammeClient/listeProduit.html.twig', [
            'listeProduit' => $listeProduit,
            'listeProduitGamme' => $GCPRepo->findBy(['gammeClient' => $idGammeClient, 'marque' => $id]),
            'marque' => $marque,
            'gammeClient' => $gammeClient,
            'totalProduits' => $totalProduit,
            'catalogue' => true,
            'produits' => true,
            'produit' => true,
            'modifConfig' => $modif,
            'principeActif' => $principeRepo->findAll(),
            'gamme' => ($idGamme) ? $gammeRepo->find($idGamme) : false,
            'produitGamme' => $produitGamme
        ]);
    }

    public function listProduitMarque($id, $idGC,  $modif = null, GammeRepository $gammeRepo, PrincipeActifRepository $principeRepo, UnitGammeClientProduitRepository $GCPRepo, Request $request, ProduitRepository $produitRepo, EntityManagerInterface $em, MarqueRepository $marqueRepo, GammeClientRepository $GCRepo)
    {

        $marque = $marqueRepo->find($id);
        $gammeClient = $GCRepo->find($idGC);

        return $this->render('back/gammeClient/listeProduit.html.twig', [
            'listeProduit' => $GCPRepo->findBy(['gammeClient' => $idGC, 'marque' => $id]),
            'listeProduitGamme' => $GCPRepo->findBy(['gammeClient' => $idGC, 'marque' => $id]),
            'marque' => $marque,
            'gammeClient' => $gammeClient,
            'totalProduits' => $produitRepo->findBy(['marque' => $id]),
            'catalogue' => true,
            'produits' => true,
            'produit' => true,
            'modifConfig' => $modif,
            'principeActif' => $principeRepo->findAll(),
        ]);
    }

    public function supprimeMarqueGamme(Request $request, MarqueRepository $marqueRepo, UnitGameClientMarqueRepository $GCMRepo, UnitGammeClientProduitRepository $GCPRepo, UnitGameClientGameRepository $GCGRepo, EntityManagerInterface $em)
    {

        if ($request->isXMLHttpRequest()) {

            $marqueGamme = $GCMRepo->findOneBy(['marque' => $request->request->get('id'), 'gammeClient' => $request->request->get('idGC')]);

            $gammes = $GCGRepo->findBy(['marque' =>  $request->request->get('id'), 'gammeClient' => $request->request->get('idGC')]);

            $listeProduit = $GCPRepo->findBy(['gammeClient' => $request->request->get('idGC'), 'marque' => $request->request->get('id')]);

            if ($listeProduit) {
                foreach ($listeProduit as $lp) {
                    $em->remove($lp);
                    $em->flush();
                }
            }

            if ($gammes) {
                foreach ($gammes as $g) {
                    $em->remove($g);
                    $em->flush();
                }
            }

            $em->remove($marqueGamme);
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

    public function supprimeProduitGamme(Request $request, UnitGammeClientProduitRepository $GCPRepo, EntityManagerInterface $em)
    {

        if ($request->isXMLHttpRequest()) {

            $produitGamme = $GCPRepo->findOneBy(['produit' => $request->request->get('produitId'), 'gammeClient' => $request->request->get('gammeClientId')]);

            $em->remove($produitGamme);
            $em->flush();
            return $this->json(
                [
                    'code' => 200,
                    'supprimer' => $request->request->get('produitId')
                ],
                200
            );
        }
    }
    /*
    public function gamme($idM, $idGC, $modif = null ,  Request $request, UnitMarqueGammeRepository $MGRepo,MarqueRepository $marqueRepo, UnitGameClientGameRepository $GCGRepo, GammeClientRepository $gammeClientRepo, EntityManagerInterface $em){
        
        $marques = $MGRepo->findBy(['marque' => $idM]);
        $gammeClient = $gammeClientRepo->find($idGC);
        
        if($request->isMethod('post')){
            $i = 1;

            if(!$modif){
                foreach($marques as $marque){
                    $exist = $GCGRepo->findOneBy(['gammeClient' => $gammeClient->getId(), 'gamme' => $marque->getGamme()->getId(), "marque" => $idM]);
                    
                    if(!$exist){
                        $GCgamme = new UnitGameClientGame;
                        $GCgamme->setGamme($marque->getGamme());
                        $GCgamme->setGammeClient($gammeClient);
                        $GCgamme->setMarque( $marqueRepo->find($idM));
                        $em->persist($GCgamme);
                        $em->flush();
                    }
                    
                    $i++;
                }
            }

            return $this->redirectToRoute('back_config_list_gamme_client',  ['idM' => $idM, 'idGC' => $idGC, 'modif' => $modif]);
        }
    }*/

    public function modifAjoutGamme(GammeClient $gammeClient, $idm, $modif, ProduitRepository $prodRepo, MarqueRepository $marqueRepo, Request $request, GammeRepository $gammeRepo, EntityManagerInterface $em, UnitGameClientGameRepository $GCGRepo)
    {

        if ($request->isMethod('post')) {
            $PostCount = count($request->request->all());

            $i = 1;

            while ($i <= $PostCount) {
                $exist = $GCGRepo->findOneBy(['gammeClient' => $gammeClient->getId(), 'gamme' => $request->request->get("gamme-$i"), 'marque' => $idm]);

                if (!$exist) {
                    $GCgamme = new UnitGameClientGame;
                    $GCgamme->setGamme($gammeRepo->find($request->request->get("gamme-$i")));
                    $GCgamme->setGammeClient($gammeClient);
                    $GCgamme->setMarque($marqueRepo->find($idm));

                    $produits = $prodRepo->findBy(['gamme' => $request->request->get("gamme-$i"), 'marque' => $idm]);

                    foreach ($produits as $pro) {
                        $unitProduit = new UnitGammeClientProduit();

                        $unitProduit->setProduit($pro);
                        $unitProduit->setToutLesDeclinaisons(true);
                        $unitProduit->setDeclineAvec($pro->getPrincipeActif());
                        $unitProduit->setGamme($pro->getGamme());
                        $unitProduit->setGammeClient($gammeClient);
                        $unitProduit->setMarque($marqueRepo->find($idm));
                        $em->persist($unitProduit);
                    }

                    $em->persist($GCgamme);
                    $em->flush();
                }
                $i++;
            }

            $this->addFlash('ajoutGamme', 'Gamme ajouter avec succès');
            return $this->redirectToRoute('back_config_list_gamme_client', ['idM' => $idm, 'idGC' => $gammeClient->getId(), 'modif' => $modif]);
        }
    }

    public function listGammeMarque($idM, $idGC, $modif = null, UnitMarqueGammeRepository $unitRepo, UnitMarqueGammeRepository $MGRepo, UnitGameClientGameRepository $GCGRepo, Request $request, ProduitRepository $produitRepo, EntityManagerInterface $em, MarqueRepository $marqueRepo, GammeClientRepository $GCRepo)
    {

        $gammes = $GCGRepo->findBy(['marque' => $idM, 'gammeClient' => $idGC]);

        return $this->render('back/gammeClient/listeGamme.html.twig', [
            'listeGamme' => $gammes,
            'totalGamme' => $MGRepo->findBy(['marque' => $marqueRepo->find($idM)->getId()]),
            'marque' => $marqueRepo->find($idM),
            'gammeClient' => $GCRepo->find($idGC),
            'catalogue' => true,
            'produits' => true,
            'produit' => true,
            'modifConfig' => $modif
        ]);
    }

    public function supprimeGamme(Request $request, UnitGameClientGameRepository $GCGRepo, UnitGammeClientProduitRepository $GPrepo, EntityManagerInterface $em)
    {

        if ($request->isXMLHttpRequest()) {

            $gamme = $GCGRepo->findOneBy(['gamme' => $request->request->get('idGamme'), 'marque' => $request->request->get('idMarque')]);
            $produits = $GPrepo->findBy(['gamme' => $request->request->get('idGamme')]);

            if (!empty($produits)) {

                foreach ($produits as $pro) {
                    $em->remove($pro);
                    $em->flush();
                }
            }

            if (!empty($gamme)) {
                $em->remove($gamme);
                $em->flush();
            }

            return $this->json(
                [
                    'code' => 200,
                    'supprimer' => $request->request->get('idGamme')
                ],
                200
            );
        }
    }

    public function modificationGammeGammeClient(GammeClient $gammeClient, UnitMarqueGammeRepository $MGRepo)
    {
        return $this->render('back/gammeClient/configProduit.html.twig', [
            'gammeClient' => $gammeClient,
            'modifConfig' => true,
            'catalogue' => true,
            'produits' => true,
            'produit' => true,
        ]);
    }

    public function ajoutProduitGamme($id, $idM, $modif, $idG, ProduitRepository $produitRepo, UnitGammeClientProduitRepository $GCPRepo, Request $request, EntityManagerInterface $em, GammeClientRepository $GCRepo, MarqueRepository $marqueRepo)
    {
        $marque = $marqueRepo->find($idM);
        $gammeClient = $GCRepo->find($id);
        if ($request->isMethod('post')) {

            $PostCount = count($_POST);

            $i = 1;

            while ($i <= $PostCount) {
                $exist = $GCPRepo->findOneBy(['gammeClient' => $gammeClient->getId(), 'marque' => $marque->getId(), 'produit' => $produitRepo->find($_POST["produit-$i"])]);

                if (!$exist) {
                    $produit = $produitRepo->find($_POST["produit-$i"]);
                    $unitProduit = new UnitGammeClientProduit;
                    $unitProduit->setGammeClient($gammeClient);
                    $unitProduit->setDeclineAvec($produit->getPrincipeActif());
                    $unitProduit->setGamme($produit->getGamme());
                    $unitProduit->setToutLesDeclinaisons(true);
                    $unitProduit->setProduit($produit);
                    $unitProduit->setMarque($marque);
                    $em->persist($unitProduit);
                }

                $em->flush();
                $i++;
            }
            $this->addFlash('ajoutProduit', 'Opération éffectuée avec succès');
            return $this->redirectToRoute('back_config_list_produit_gamme_client', ['idGammeClient' => $gammeClient->getId(), 'id' => $marque->getId(), 'idGamme' => $idG, 'modif' => true]);
        }
    }

    public function ajoutProduitSupplementaire($id, $idM, $modif, ProduitRepository $produitRepo, UnitGammeClientProduitRepository $GCPRepo, Request $request, EntityManagerInterface $em, GammeClientRepository $GCRepo, MarqueRepository $marqueRepo)
    {
        $marque = $marqueRepo->find($idM);
        $gammeClient = $GCRepo->find($id);
        if ($request->isMethod('post')) {

            $PostCount = count($_POST);

            $i = 1;

            while ($i <= $PostCount) {
                $exist = $GCPRepo->findOneBy(['gammeClient' => $gammeClient->getId(), 'marque' => $marque->getId(), 'produit' => $produitRepo->find($_POST["produit-$i"])]);

                if (!$exist) {
                    $produit = $produitRepo->find($_POST["produit-$i"]);
                    $unitProduit = new UnitGammeClientProduit;
                    $unitProduit->setGammeClient($gammeClient);
                    $unitProduit->setDeclineAvec($produit->getPrincipeActif());
                    $unitProduit->setGamme($produit->getGamme());
                    $unitProduit->setToutLesDeclinaisons(true);
                    $unitProduit->setProduit($produit);
                    $unitProduit->setMarque($marque);
                    $em->persist($unitProduit);
                }

                $em->flush();
                $i++;
            }
            $this->addFlash('ajoutProduit', 'Opération éffectuée avec succès');
            return $this->redirectToRoute('back_config_list_produit_marque_client', ['idGC' => $gammeClient->getId(), 'id' => $marque->getId(), 'modif' => true]);
        }
    }
}
