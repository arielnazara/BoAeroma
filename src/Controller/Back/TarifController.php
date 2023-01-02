<?php


namespace App\Controller\Back;

use App\Entity\Declinaison;
use App\Entity\HistoriqueTarif;
use App\Entity\PrincipeActif;
use App\Entity\PrixDeclinaison;
use App\Entity\PrixDeclinaisonClient;
use App\Entity\PrixDeclinaisonMarque;
use App\Entity\Tarif;
use App\Entity\TarifParClient;
use App\Entity\TarifParMarque;
use App\Form\DeclinaisonType;
use App\Form\ParClientType;
use App\Form\TarifMarqueType;
use App\Form\TarifType;
use App\Repository\BaseRepository;
use App\Repository\CategorieRepository;
use App\Repository\ClientRepository;
use App\Repository\ContenantRepository;
use App\Repository\DeclinaisonRepository;
use App\Repository\HistoriqueTarifRepository;
use App\Repository\MarqueBlancheRepository;
use App\Repository\MarqueRepository;
use App\Repository\PrincipeActifRepository;
use App\Repository\PrixDeclinaisonMarqueRepository;
use App\Repository\PrixDeclinaisonRepository;
use App\Repository\ProduitRepository;
use App\Repository\TarifParClientRepository;
use App\Repository\TarifParMarqueRepository;
use App\Repository\TarifRepository;
use App\Repository\TypeDeClientRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\TokenNotFoundException;
use Symfony\Component\Validator\Constraints\IsNull;

class TarifController extends AbstractController
{

    public function index(TarifRepository $tarif, TypeDeClientRepository $typeClient)
    {
        return $this->render('back/tarif/index.html.twig', [
            'tarif' => true,
            'tarifs' => $tarif->findBy([], ['id' => 'desc']),
            'typeDeClient' => $typeClient->findAll()
        ]);
    }

    public function listeDeclinaison(Declinaison $declinaison)
    {
        return $this->render('back/produit/declinaison/listeDeclinaison.html.twig', [
            'produit' => true,
            'declinaisons' => $declinaison
        ]);
    }

    public function creationTarif(EntityManagerInterface $em, TypeDeClientRepository $typeRepo, TarifRepository $tarifRepo, PrincipeActifRepository $principeRepo, Request $request, BaseRepository $baseRepo, CategorieRepository $categorieRepo, ContenantRepository $contenantRepo)
    {
        $tarif = new Tarif;

        $form = $this->createForm(TarifType::class, $tarif);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $test = [];
            $historiqueTarif = new HistoriqueTarif;

            $historiqueTarif->setTarif($tarif);
            $historiqueTarif->setDate(new DateTime());
            $historiqueTarif->setActif(true);

            if ($tarif->getMemeTarif()) {
                $historiqueTarif->setMemeTarif(true);
            } else {
                $historiqueTarif->setMemeTarif(false);
            }

            $historiqueTarif->setPrixDetaillant($tarif->getPrixDeReferenceDetaillant());
            $historiqueTarif->setPrixGrossiste($tarif->getPrixDeReferenceGrossiste());

            $em->persist($historiqueTarif);

            if ($_POST['tarif']['declineAvec']) {

                $declineAvec = $principeRepo->find($tarif->getDeclineAvec());

                $declinaisons = $declineAvec->getDeclinaisons();

                if (!$tarif->getMemeTarif()) {
                    foreach ($typeRepo->findAll() as $type) {
                        foreach ($declinaisons as $decl) {

                            $nom = $declineAvec->getPrincipeActif() . $decl->getDeclinaison() . $type->getNom();

                            
                            $nom = str_replace(" ", '', $nom);
                            $nom = str_replace("/", '_', $nom);
                            $nom = str_replace("\\", '_', $nom);
                            $nom = str_replace(",", '_', $nom);
                            $nom = str_replace("%", '_', $nom);

                            
                            if (!empty($_POST[$nom])) {

                                $tarifDecl = new PrixDeclinaison;
                                $tarifDecl->setTarif($tarif);
                                $tarifDecl->setPrix($_POST[$nom]);
                                $tarifDecl->setDeclinaison($decl->getDeclinaison());
                                $tarifDecl->setActif(true);
                                $tarifDecl->setDate(new DateTime());
                                $tarifDecl->setTypeDeClient($type);
                                $tarifDecl->setHistorique($historiqueTarif);

                                $em->persist($tarifDecl);
                            }
                        }
                    }
                }
            } else {

                $tarif->setDeclineAvec(null);
            }
            

            $em->persist($tarif);

            $em->flush();

            if($request->query->get('client'))
            {
                $this->addFlash('successTarif', 'Création tarif de réference efféctué avec succès');
                return $this->redirectToRoute('back_tarif_list');
            }
            

            $this->addFlash('successTarif', 'Création tarif de réference efféctué avec succès');
            return $this->redirectToRoute('back_tarif_marque_creation', ['id' => $tarif->getId()]);
        }

        return $this->render('back/tarif/creationTarif.html.twig', [
            'parGamme' => true,
            'tarif' => true,
            'form' => $form->createView(),
            'principes' => $principeRepo->findAll(),
            'configurateur' => true,
            'tarifRef' => true,
            'types' => $typeRepo->findAll()
        ]);
    }

    public function modificationTarif(Tarif $tarif, Request $request, ProduitRepository $produitRepo, MarqueBlancheRepository $mbRepo, TypeDeClientRepository $type, PrixDeclinaisonRepository $prixDeclRepo, PrincipeActifRepository $principeRepo, EntityManagerInterface $em)
    {

        $form = $this->createForm(TarifType::class, $tarif);

        $form->handleRequest($request);

        $listeProduits = $produitRepo->findBy(['tarif' => $tarif ]);

        $poruditsMb = $mbRepo->findBy(['tarif' => $tarif]);
    
        foreach($poruditsMb as $mb)
        {
            $mbProd = $mb->getProduit();
            if(! in_array($mbProd, $listeProduits))
            {
                array_push($listeProduits, $mbProd);
            }
        }

        return $this->render('back/tarif/modificationTarif.html.twig', [
            'tarifs' => true,
            'listeProduits' => $listeProduits,
            'tarif' => $tarif,
            'form' => $form->createView(),
            'principes' => $principeRepo->findAll(),
            'prixDecl' => $prixDeclRepo->findBy(['tarif' => $tarif, 'actif' => true]),
            'configurateur' => true,
            'hide' => true,
            'types' => $type->findAll()
        ]);
    }

    public function ajoutTarif($id, Request $request, CategorieRepository $categorieRepo, TypeDeClientRepository $typeRepo, HistoriqueTarifRepository $historiqueRepo, PrixDeclinaisonRepository $prixDeclRepo, PrincipeActifRepository $principeRepo, BaseRepository $baseRepo, ContenantRepository $contenantRepo, EntityManagerInterface $em, TarifRepository $tarifRepo)
    {
        $idList = [];
        $previousID = 0;
        $count = 0;
        $tarif = $tarifRepo->find($id);

        $prixRefDetaillant = $tarif->getPrixDeReferenceDetaillant();
        $prixRefGrossiste = $tarif->getPrixDeReferenceGrossiste();

        if ($request->getMethod('post')) {
            
            $dec = $tarif->getDeclineAvec();
            $memeTarif = $_POST['tarif']['memeTarif'];

            $contenant = $contenantRepo->find($_POST['tarif']['contenance']);
            $categorie = $categorieRepo->find($_POST['tarif']['categorie']);
            $base = $baseRepo->find($_POST['tarif']['base']);

            $tarif->setContenance($contenant);
            $tarif->setCategorie($categorie);
            $tarif->setBase($base);

           
            $newPrincipe = $principeRepo->find($_POST['tarif']['declineAvec']);
            
            $declineAvec =  $tarif->getDeclineAvec();

            
            if (!$memeTarif) {
                
                $nouvelleHistorique = false;

                $modifier = false;
                $tarifDetaillantModifier = false;

                $countType = 0;

                $typeDeClient = $typeRepo->findAll();

                if($tarif->getDeclineAvec() != $newPrincipe)
                {
                    
                    $tarif->setDeclineAvec($newPrincipe);

                    $typeDeClient = $typeRepo->findAll();
                    foreach( $typeDeClient as $type )
                    {
    
                        foreach ($prixDeclRepo->findBy(['tarif' => $tarif, 'actif' => true, 'typeDeClient' => $type]) as $prixAct) {
                            $prixAct->setActif(false);
                        }
                        
                       
    
                        if(!$nouvelleHistorique)
                        {
                            
                            foreach ($historiqueRepo->findBy(['tarif' => $tarif, 'actif' => true]) as $histo) {
                                $histo->setActif(false);
                            }
                            
                            $historiqueTarif = new HistoriqueTarif;
    
                            $historiqueTarif->setTarif($tarif);
                            $historiqueTarif->setDate(new DateTime());
                            $historiqueTarif->setPrixDetaillant($_POST['tarif']['prixDeReferenceDetaillant']);
                            $historiqueTarif->setPrixGrossiste($_POST['tarif']['prixDeReferenceGrossiste']);
                            $historiqueTarif->setActif(true);
                            $historiqueTarif->setMemeTarif(false);
                          
                            $em->persist($historiqueTarif);
                            //$em->flush();
    
                            $nouvelleHistorique = $historiqueTarif;
    
                        }
    
                        foreach($newPrincipe->getDeclinaisons() as $declinaison)
                        {
                            $nom = $newPrincipe->getPrincipeActif();
                         
                            $declinaison = $declinaison->getDeclinaison();

                            $nom = $nom.$declinaison.$type;

                            $nom = str_replace(" ", '', $nom);
                            $nom = str_replace("/", '_', $nom);
                            $nom = str_replace("\\", '_', $nom);
                            $nom = str_replace(",", '_', $nom);
                            $nom = str_replace("%", '_', $nom);

                            
                            
                            if($request->request->get($nom))
                            {
                                
                                $tarifDecl = new PrixDeclinaison;
                                $tarifDecl->setTarif($tarif);
                                $tarifDecl->setPrix($request->request->get($nom));
                                $tarifDecl->setDeclinaison($declinaison);
                                $tarifDecl->setActif(true);
                                $tarifDecl->setDate(new DateTime());
                                $tarifDecl->setTypeDeClient($type);
                                $tarifDecl->setHistorique($historiqueTarif);

                                $em->persist($tarifDecl);
                            }
                        }
                        
                        $em->flush();
    
                    }
                }
                else
                {
                    if($tarif->getDeclineAvec() != $newPrincipe)
                    {
                        
                        $tarif->setDeclineAvec($newPrincipe);
                    }
                    
                    foreach( $typeDeClient as $type)
                    {
                       
                        $ok = [];
    
                        $countType++;
    
                        foreach($declineAvec->getDeclinaisons() as $declinaison)
                        {
                            
                            $postName = "{$declineAvec->getPrincipeActif()}{$declinaison->getDeclinaison()}{$type->getNom()}";
                            
                            $postName = str_replace(" ", '', $postName);
                            $postName = str_replace("/", '_', $postName);
                            $postName = str_replace("\\", '_', $postName);
                            $postName = str_replace(",", '_', $postName);
                            $postName = str_replace("%", '_', $postName);

                            
                           
                            if( isset($_POST[$postName]) && $_POST[$postName] > 0)
                            {
                                $ok[$declinaison->getDeclinaison()] = $_POST[$postName];
                            }
                        }
                       
                       
                        $nombrePrix = count($prixDeclRepo->findBy(['actif' => true, 'typeDeClient' => $type, 'tarif' => $tarif]));
                      
                        if(count($ok) >0 || $nombrePrix > 0)
                        {
                           
                            if( count($ok) == $nombrePrix)
                            {
                                
                                if(count($tarif->getPrixDeclinaisons()) > 0)
                                {
                                    
                                    foreach($tarif->getPrixDeclinaisons() as $tarifDecl)
                                    {
                                        if($tarifDecl->getActif() && $tarifDecl->getTypeDeClient() == $type)
                                        {
                                            $postName = "{$declineAvec->getPrincipeActif()}{$tarifDecl->getDeclinaison()}{$type->getNom()}";
            
                                            if( isset($_POST[$postName]) && $_POST[$postName] > 0)
                                            {
                                                if($_POST[$postName] != $tarifDecl->getPrix())
                                                {
                                                    
                                                    $modifier = true;
                                                    $tarifDecl->setPrix($_POST[$postName]);
                                                    $em->flush();
                                                }
                                            }
                                            else
                                            {
                                                
            
                                                $modifier = true;
                                            }
                                        }
                                    }
                                }
                                else
                                {
                                    $modifier = true;
                                }
                            }
                            else
                            {
                                
                                $modifier = true;
    
                            }
    
                        }
                       
                        
                        if($modifier)
                        {
                           
                            if($countType == 1)
                            {
                                $tarifDetaillantModifier = true;
                            }
                            
    
                            foreach ($prixDeclRepo->findBy(['tarif' => $tarif, 'actif' => true, 'typeDeClient' => $type]) as $prixAct) {
                                $prixAct->setActif(false);
        
                                $em->flush();
                            }
                            
                           
    
                            if(!$nouvelleHistorique)
                            {
                                
                                foreach ($historiqueRepo->findBy(['tarif' => $tarif, 'actif' => true]) as $histo) {
                                    $histo->setActif(false);
                                }
                                
                                $historiqueTarif = new HistoriqueTarif;
        
                                $historiqueTarif->setTarif($tarif);
                                $historiqueTarif->setDate(new DateTime());
                                $historiqueTarif->setPrixDetaillant($_POST['tarif']['prixDeReferenceDetaillant']);
                                $historiqueTarif->setPrixGrossiste($_POST['tarif']['prixDeReferenceGrossiste']);
                                $historiqueTarif->setActif(true);
                                $historiqueTarif->setMemeTarif(false);
                              
                                $em->persist($historiqueTarif);
                                $em->flush();
    
                                $nouvelleHistorique = $historiqueTarif;
        
                            }
    
    
                            foreach($ok as $dec => $p)
                            {
                                
                                $nouvellePrixDecl = new PrixDeclinaison();
        
                                $nouvellePrixDecl->setTarif($tarif);
                                $nouvellePrixDecl->setDeclinaison($dec);
                                $nouvellePrixDecl->setPrix($p);
                                $nouvellePrixDecl->setHistorique($nouvelleHistorique);
                                $nouvellePrixDecl->setActif(true);
                                $nouvellePrixDecl->setDate(new DateTime());
                                $nouvellePrixDecl->setTypeDeClient($type);
    
                                $em->persist($nouvellePrixDecl);
    
                                $em->flush();
                            }
                            
                            if($countType == 2 && !$tarifDetaillantModifier)
                            {
                                $typeClient = $typeDeClient[$countType - 2];
    
                                foreach ($prixDeclRepo->findBy(['tarif' => $tarif, 'actif' => true, 'typeDeClient' => $typeClient]) as $prixAct) {
                                    $prixAct->setActif(false);
            
                                    $prixDecl = new PrixDeclinaison();
        
                                    $prixDecl->setTarif($tarif);
                                    $prixDecl->setDeclinaison($prixAct->getDeclinaison());
                                    $prixDecl->setPrix($prixAct->getPrix());
                                    $prixDecl->setHistorique($nouvelleHistorique);
                                    $prixDecl->setActif(true);
                                    $prixDecl->setDate(new DateTime());
                                    $prixDecl->setTypeDeClient($typeClient);
        
                                    $em->persist($prixDecl);
                                    
                                    $em->flush();
                                }
    
                            }
                            
                        }
    
                    }
                }
                
                
                
                
            } else {

                if($tarif->getDeclineAvec() != $newPrincipe)
                {
                    
                    $tarif->setDeclineAvec($newPrincipe);
                }
                
                foreach ($typeRepo->findAll() as $type) {

                    foreach ($prixDeclRepo->findBy(['tarif' => $tarif, 'actif' => true, 'typeDeClient' => $type]) as $prixAct) {
                        $prixAct->setActif(false);

                        $em->flush();
                    }
                }
                foreach ($historiqueRepo->findBy(['tarif' => $tarif, 'actif' => true]) as $histo) {
                    $histo->setActif(false);
                }

                $historiqueTarif = new HistoriqueTarif;

                $historiqueTarif->setTarif($tarif);
                $historiqueTarif->setDate(new DateTime());
                $historiqueTarif->setPrixDetaillant($_POST['tarif']['prixDeReferenceDetaillant']);
                $historiqueTarif->setPrixGrossiste($_POST['tarif']['prixDeReferenceGrossiste']);
                $historiqueTarif->setActif(true);
                $historiqueTarif->setMemeTarif(true);
                $em->persist($historiqueTarif);

                $em->flush();
            }

            
            $tarif->setPrixDeReferenceDetaillant($_POST['tarif']['prixDeReferenceDetaillant']);
            $tarif->setPrixDeReferenceGrossiste($_POST['tarif']['prixDeReferenceGrossiste']);

            $tarif->setNom($_POST['tarif']['nom']);
            $tarif->setDeclineAvec($tarif->getDeclineAvec());
            $tarif->setMemeTarif($_POST['tarif']['memeTarif']);

            $em->persist($tarif);
            $em->flush();

            $this->addFlash('successTarif', 'Modification tarif efféctuée avec succès');
            return $this->redirectToRoute('back_tarif_list');
        }
    }

    public function historique(Tarif $tarif, HistoriqueTarifRepository $tarifRepo, TypeDeClientRepository $typeRepo)
    {
        return $this->render('back/tarif/historique.html.twig', [
            'tarif' => $tarif,
            'tarifHistorique' => $tarifRepo->findBy(['tarif' => $tarif], ['id' => 'desc']),
            'configurateur' => true,
            'types' => $typeRepo->findAll()
        ]);
    }

    public function infoTarif(Tarif $tarif)
    {

        return $this->render('back/tarif/infoTarif.html.twig', [
            'tarif' => $tarif,

        ]);
    }

    public function tarifParMarque($id, Request $request, TarifRepository $tarifRepo, TypeDeClientRepository $typeRepo, PrincipeActifRepository $principeRepo, MarqueRepository $marqueRepo, EntityManagerInterface $em)
    {

        if ($id) {
            $tarif = $tarifRepo->find($id);
        }

        $tarifMarque = new Tarif;

        $form = $this->createForm(TarifType::class, $tarifMarque);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //dd($_POST);
            if (!$id) {
                $tarif = $tarifRepo->find($_POST['tarifReference']);
            }

            $historiqueTarif = new HistoriqueTarif;

            $historiqueTarif->setTarif($tarifMarque);
            $historiqueTarif->setDate(new DateTime());
            $historiqueTarif->setActif(true);

            if ($tarif->getMemeTarif()) {
                $historiqueTarif->setMemeTarif(true);
            } else {
                $historiqueTarif->setMemeTarif(false);
            }


            if ($_POST['tarif']['prixDeReferenceDetaillant']) {
                $historiqueTarif->setPrixDetaillant($_POST['tarif']['prixDeReferenceDetaillant']);
            } else {
                $historiqueTarif->setPrixDetaillant($tarif->getPrixDeReferenceDetaillant());
            }

            if ($_POST['tarif']['prixDeReferenceGrossiste']) {
                $historiqueTarif->setPrixGrossiste($_POST['tarif']['prixDeReferenceGrossiste']);
            } else {
                $historiqueTarif->setPrixGrossiste($tarif->getPrixDeReferenceGrossiste());
            }

            $em->persist($historiqueTarif);

            if ($tarif->getDeclineAvec()) {

                $declineAvec = $principeRepo->find($tarif->getDeclineAvec());

                $declinaisons = $declineAvec->getDeclinaisons();

                if (!$_POST['tarif']['memeTarif']) {

                    $tarifMarque->setPrixDeReferenceDetaillant($tarif->getPrixDeReferenceDetaillant());
                    $tarifMarque->setPrixDeReferenceGrossiste($tarif->getPrixDeReferenceGrossiste());

                    foreach ($typeRepo->findAll() as $type) {
                        foreach ($declinaisons as $decl) {
                            if (!empty($_POST[$declineAvec->getPrincipeActif() . $decl->getDeclinaison() . $type->getNom()])) {

                                $tarifDecl = new PrixDeclinaison;
                                $tarifDecl->setTarif($tarifMarque);
                                $tarifDecl->setPrix($_POST[$declineAvec->getPrincipeActif() . $decl->getDeclinaison() . $type->getNom()]);
                                $tarifDecl->setDeclinaison($decl->getDeclinaison());
                                $tarifDecl->setActif(true);
                                $tarifDecl->setDate(new DateTime());
                                $tarifDecl->setTypeDeClient($type);
                                $tarifDecl->setHistorique($historiqueTarif);

                                $em->persist($tarifDecl);
                            }
                        }
                    }
                } else {
                    $tarifMarque->setPrixDeReferenceDetaillant($_POST['tarif']['prixDeReferenceDetaillant']);
                    $tarifMarque->setPrixDeReferenceGrossiste($_POST['tarif']['prixDeReferenceGrossiste']);
                }
            } else {

                $declineAvec = null;

                if ($_POST['tarif']['memeTarif']) {
                    $tarifMarque->setPrixDeReferenceDetaillant($_POST['tarif']['prixDeReferenceDetaillant']);
                    $tarifMarque->setPrixDeReferenceGrossiste($_POST['tarif']['prixDeReferenceGrossiste']);
                } else {
                    $tarifMarque->setPrixDeReferenceDetaillant($tarif->getPrixDeReferenceDetaillant());
                    $tarifMarque->setPrixDeReferenceGrossiste($tarif->getPrixDeReferenceGrossiste());
                }
            }

            $marque = $marqueRepo->find($_POST['tarif']['marque']);

            $tarifMarque->setNom($tarif->getNom());
            $tarifMarque->setCategorie($tarif->getCategorie());
            $tarifMarque->setContenance($tarif->getContenance());
            $tarifMarque->setBase($tarif->getBase());
            $tarifMarque->setMarque($marque);
            $tarifMarque->setMemeTarif($_POST['tarif']['memeTarif']);
            $tarifMarque->setDeclineAvec($declineAvec);

            $em->persist($tarifMarque);

            $em->flush();

            $this->addFlash('successTarif', 'Tarif par marque créé avec succès');

            if ($id) {
                return $this->redirectToRoute('back_tarif_client_creation', ['id' => $id]);
            } else {
                return $this->redirectToRoute('back_tarif_list');
            }
        }

        return $this->render('back/tarif/creationTarifParMarque.html.twig', [
            'tarif' => ($id) ? $tarif : null,
            'form' => $form->createView(),
            'principes' => $principeRepo->findAll(),
            'tarifMarque' => true,
            'configurateur' => true,
            'tarifListe' => $tarifRepo->findBy(['client' => null, 'marque' => null]),
            'types' => $typeRepo->findAll()
        ]);
    }


    public function tarifParClient($id, Request $request, TarifRepository $tarifRepo, TypeDeClientRepository $typeRepo, PrincipeActifRepository $principeRepo, BaseRepository $baseRepo, EntityManagerInterface $em, ClientRepository $clientRepo, CategorieRepository $categorieRepo, ContenantRepository $contenantRepo)
    {

        if ($id) {
            $tarif = $tarifRepo->find($id);
        }

        $tarifClient = new Tarif;

        $form = $this->createForm(TarifType::class, $tarifClient);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if (!$id) {
                $tarif = $tarifRepo->find($_POST['tarifReference']);
            }

            $historiqueTarif = new HistoriqueTarif;

            $historiqueTarif->setTarif($tarifClient);
            $historiqueTarif->setDate(new DateTime());
            $historiqueTarif->setActif(true);

            if ($tarif->getMemeTarif()) {
                $historiqueTarif->setMemeTarif(true);
            } else {
                $historiqueTarif->setMemeTarif(false);
            }

            if ($_POST['tarif']['prixDeReferenceDetaillant'] or $_POST['tarif']['prixDeReferenceDetaillant'] == 0) {

                $historiqueTarif->setPrixDetaillant($_POST['tarif']['prixDeReferenceDetaillant']);
            } else {
                $historiqueTarif->setPrixDetaillant($tarif->getPrixDeReferenceDetaillant());
            }

            if ($_POST['tarif']['prixDeReferenceGrossiste'] or $_POST['tarif']['prixDeReferenceGrossiste'] == 0) {

                $historiqueTarif->setPrixGrossiste($_POST['tarif']['prixDeReferenceGrossiste']);
            } else {
                $historiqueTarif->setPrixGrossiste($tarif->getPrixDeReferenceGrossiste());
            }

            $em->persist($historiqueTarif);

            if ($tarif->getDeclineAvec()) {

                $declineAvec = $principeRepo->find($tarif->getDeclineAvec());

                $declinaisons = $declineAvec->getDeclinaisons();

                if (!$_POST['tarif']['memeTarif']) {

                    if ($_POST['tarif']['prixDeReferenceDetaillant'] == 0) {
                        $tarifClient->setPrixDeReferenceDetaillant($_POST['tarif']['prixDeReferenceDetaillant']);
                    } else {

                        $tarifClient->setPrixDeReferenceDetaillant($tarif->getPrixDeReferenceDetaillant());
                    }

                    if ($_POST['tarif']['prixDeReferenceGrossiste'] == 0) {
                        $tarifClient->setPrixDeReferenceGrossiste($_POST['tarif']['prixDeReferenceGrossiste']);
                    } else {

                        $tarifClient->setPrixDeReferenceGrossiste($tarif->getPrixDeReferenceGrossiste());
                    }

                    foreach ($typeRepo->findAll() as $type) {
                        foreach ($declinaisons as $decl) {

                            $postName = $declineAvec->getPrincipeActif() . $decl->getDeclinaison() . $type->getNom();

                            $postName = str_replace(" ", '', $postName);
                            $postName = str_replace("/", '_', $postName);
                            $postName = str_replace("\\", '_', $postName);
                            $postName = str_replace(",", '_', $postName);
                            $postName = str_replace("%", '_', $postName);

                            if (!empty($_POST[$postName])) {

                                $tarifDecl = new PrixDeclinaison;
                                $tarifDecl->setTarif($tarifClient);
                                $tarifDecl->setPrix($_POST[$postName]);
                                $tarifDecl->setDeclinaison($decl->getDeclinaison());
                                $tarifDecl->setActif(true);
                                $tarifDecl->setDate(new DateTime());
                                $tarifDecl->setTypeDeClient($type);
                                $tarifDecl->setHistorique($historiqueTarif);

                                $em->persist($tarifDecl);
                            }
                        }
                    }
                } else {
                    $tarifClient->setPrixDeReferenceDetaillant($_POST['tarif']['prixDeReferenceDetaillant']);
                    $tarifClient->setPrixDeReferenceGrossiste($_POST['tarif']['prixDeReferenceGrossiste']);
                }
            } else {

                $declineAvec = null;

                if ($_POST['tarif']['memeTarif']) {
                    $tarifClient->setPrixDeReferenceDetaillant($_POST['tarif']['prixDeReferenceDetaillant']);
                    $tarifClient->setPrixDeReferenceGrossiste($_POST['tarif']['prixDeReferenceGrossiste']);
                } else {
                    $tarifClient->setPrixDeReferenceDetaillant($tarif->getPrixDeReferenceDetaillant());
                    $tarifClient->setPrixDeReferenceGrossiste($tarif->getPrixDeReferenceGrossiste());
                }
            }

            $client = $clientRepo->find($_POST['tarif']['client']);

            $tarifClient->setNom($tarif->getNom());
            $tarifClient->setCategorie($tarif->getCategorie());
            $tarifClient->setContenance($tarif->getContenance());
            $tarifClient->setBase($tarif->getBase());
            $tarifClient->setClient($client);
            $tarifClient->setMemeTarif($_POST['tarif']['memeTarif']);
            $tarifClient->setDeclineAvec($declineAvec);

            $em->persist($tarifClient);

            $em->flush();

            $this->addFlash('successTarif', 'Tarif client créé avec succès');

            return $this->redirectToRoute('back_tarif_list');
        }

        return $this->render('back/tarif/creationTarifParClient.html.twig', [
            'tarifClient' => true,
            'tarif' => ($id) ? $tarif : null,
            'form' => $form->createView(),
            'principes' => $principeRepo->findAll(),
            'configurateur' => true,
            'tarifListe' => $tarifRepo->findBy(['client' => null, 'marque' => null]),
            'types' => $typeRepo->findAll()
        ]);
    }

    public function listeTarifClient(TarifRepository $tarif, MarqueRepository $marqueRepo, ClientRepository $clientRepo)
    {
        return $this->render('back/tarif/listeTarifClient.html.twig', [
            'tarif' => true,
            'parClient' => $tarif->findBy(['parClient' => true]),
            'marques' => $marqueRepo->findAll(),
            'listeClient' => $clientRepo->findAll()
        ]);
    }



    public function modificationTarifClient(Tarif $tarif, Request $request, EntityManagerInterface $em, MarqueRepository $marqueRepo, ClientRepository $clientRepo)
    {

        $form = $this->createForm(TarifType::class, $tarif);

        $form->handleRequest($request);

        return $this->render('back/tarif/modificationTarifClient.html.twig', [
            'parClient' => true,
            'tarif' => $tarif,
            'form' => $form->createView(),
            'marques' => $marqueRepo->findAll(),
            'listeClients' => $clientRepo->findAll()
        ]);
    }


    /*public function modificationPrix($id, TarifRepository $tarifRepo, TarifParMarqueRepository $PMrepo, Request $request, EntityManagerInterface $em)
    {
        $tarif = $tarifRepo->find($id);
        $parMarque = $PMrepo->find($id);

        if ($request->getMethod('post')) {
            if (isset($_POST['prix']) && $_POST['prix'] != null) {
                $tarif->setPrixDeReference($_POST['prix']);
                $id = $tarif->getId();
            }

            if (isset($_POST['prixMarque']) && $_POST['prixMarque'] != null) {
                $parMarque->setPrix($_POST['prixMarque']);
                $id = $parMarque->getTarif()->getId();
            }
            $em->flush();

            $this->addFlash('successPrix', 'Modification prix éffectuée avec succès');
            return $this->redirectToRoute('back_tarif_marque_info', ['id' => $id]);
        }
    }*/

    public function supprimerTarif(Tarif $tarif, Request $request, EntityManagerInterface $em)
    {
        if ($this->isCsrfTokenValid('delete' . $tarif->getId(), $request->request->get('_token'))) {
            if( count($tarif->getMarqueBlanches()) == 0){
                $em->remove($tarif);
                $em->flush();
                $this->addFlash('successTarif', 'Tarif supprimé avec succès');
            }else{
                $this->addFlash('alertTarif', 'Ce tarif client est lié à un produit');
            }
            return $this->redirectToRoute('back_tarif_list');
        } else {
            throw new TokenNotFoundException('Token invalide');
        }
    }
}
