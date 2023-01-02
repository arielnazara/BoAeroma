<?php


namespace App\Controller\Back;

use App\Service\Useful;
use App\Entity\LotArome;
use App\Entity\LotIsolat;
use App\Form\LotAromeType;
use App\Entity\LotNicotine;
use App\Form\LotIsolatType;
use App\Entity\LotGlycerine;
use App\Entity\LotVegetolPdo;
use App\Form\LotNicotineType;
use App\Form\LotGlycerineType;
use App\Form\LotVegetolPdoType;
use App\Entity\GroupeBondeCommande;
use App\Repository\LotAromeRepository;
use App\Repository\LotIsolatRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\LotNicotineRepository;
use App\Repository\LotGlycerineRepository;
use App\Repository\BonDeCommandeRepository;
use App\Repository\LotVegetolPdoRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\GroupeBondeCommandeRepository;
use DateTime;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\TokenNotFoundException;

class ProductionController extends AbstractController
{
   

    public function listeGroupe(GroupeBondeCommandeRepository $groupeRepo)
    {
        return $this->render('back/production/groupe.html.twig', [
            'production' => true, 
            'groupes' => $groupeRepo->findBy([], ['id' => 'desc']),
        ]);
    }

    public function ajoutCodeInterne(GroupeBondeCommande $groupe, Request $request, EntityManagerInterface $em)
    {

        if ($request->isMethod('post')) {

            $groupe->setCode($request->request->get('numCommandeInterne'));
            $em->flush();

            $this->addFlash('codeInterneSuccess', 'Ajout numéro de commande interne effectuée avec succès');

            return $this->redirectToRoute('back_groupe_production_liste');
        }
    }

    public function bdcExcelGrouper(GroupeBondeCommande $groupe, EntityManagerInterface $em, Useful $useful)
    {
        
        $bonDeCommandes = $groupe->getBonDeCommande();
        
        $commandes = [];
        if($bonDeCommandes)
        {
            
            foreach($bonDeCommandes as $bonCommande)
            {
               
                $groupe = $bonCommande->getGroupeBondeCommandes()[0];
                
                if(!$groupe->getDownloadDate())
                {
                    $groupe->setDownloadDate( new DateTime());
                    $em->flush();
                }
                foreach($bonCommande->getCommandes() as $com)
                {
    
                    array_push($commandes, $com);
    
                }
            }

            $reponse = $useful->createExcel($commandes);
    
            return $this->file($reponse['temp_file'], $reponse['fileName'], ResponseHeaderBag::DISPOSITION_INLINE);
        }
    }

    public function fusionner(BonDeCommandeRepository $bonDeCommandeRepo, Request $request, Useful $useful, EntityManagerInterface $em)
    {

        if($request->isMethod('POST'))
        {
            $data = $request->request->all();

            $data = array_unique($data);
            
            $commandes = [];

            if(count($data) > 0)
            {
                $group = new GroupeBondeCommande;
                
                foreach($data as $d)
                {
                    $bonCommande = $bonDeCommandeRepo->find($d);
    
                    if($bonCommande)
                    {
                        $group->addBonDeCommande($bonCommande);
                        $em->persist($group);
                        $em->flush();
    
                        if($bonCommande)
                        {
                            foreach($bonCommande->getCommandes() as $com)
                            {
        
                                array_push($commandes, $com);
        
                            }
                        }
                    }
                }
            }

            $this->addFlash('groupSuccess', 'Opération éfféctuée avec succès');

            return $this->redirectToRoute('back_groupe_production_liste');
        }

        return $this->render('back/production/fusionner.html.twig', [
            'production' => true, 
            'groupes' => true,
            'bonDeCommandes' =>  $bonDeCommandeRepo->fetchExtravapOrder(),
        ]);
    }

    //Lot nicotine

    public function index(LotNicotineRepository $lotNicotineRepository)
    {
        return $this->render('back/production/lotNicotine/index.html.twig', [
            'production' => true, 
            'listeProduction' => true,
            'nicotines' => true,
            'lotActives' => $lotNicotineRepository->findBy(['active' => true])
        ]);
    }

    public function listeArchiveNicotine(LotNicotineRepository $lotNicotineRepository)
    {
        return $this->render('back/production/lotNicotine/archiveNicotine.html.twig', [
            'production' => true, 
            'listeProduction' => true,
            'nicotines' => $lotNicotineRepository->findBy(["active" => false]),
        ]);
    }

    public function lotNicotine($id = null, Request $request, EntityManagerInterface $em, LotNicotineRepository $lotNicotineRepository)
    {
        if($id)
        {
            $lotNicotine = $lotNicotineRepository->find($id);
        }
        else
        {
            $lotNicotine = new LotNicotine;
        }


        $form = $this->createForm(LotNicotineType::class, $lotNicotine);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            
            $em->persist($lotNicotine);
            $em->flush();

            if($id)
            {
                $this->addFlash("lotCréer", "Modification lot nicotine éfféctué avec succè");

            }
            else
            {
                $this->addFlash("lotCréer", "Création lot nicotine éfféctué avec succè");
            }
                

            if(!$lotNicotine->getActive())
            {
                return $this->redirectToRoute("back_production_liste_archives_nicotine");
            }

            return $this->redirectToRoute("back_production_index");
        }

        return $this->render('back/production/lotNicotine/lot_nicotine.html.twig', [
            'production' => true, 
            'listeProduction' => true,
            'nicotines' => true,
            'form' => $form->createView(),
            'id' => $id
        ]);
    }


    public function deleteLotNicotine(LotNicotine $lotNicotine, Request $request, EntityManagerInterface $em)
    {
        if ($this->isCsrfTokenValid('delete' . $lotNicotine->getId(), $request->request->get('_token'))) {
            
            $archive = false;

            ($lotNicotine->getActive())? $archive = true: $archive = false;
            
            $em->remove($lotNicotine);
            $em->flush();
            $this->addFlash('successDelete', 'Numéro de lot supprimé avec succès');

            if(!$archive)
            {
                return $this->redirectToRoute("back_production_liste_archives_nicotine");
            }

            return $this->redirectToRoute('back_production_index');
        } else {
            throw new TokenNotFoundException('Token invalide');
        }
    }


    //Lot glycerine

    public function listeGlycerine(LotGlycerineRepository $lotGlycerineRepository)
    {
        return $this->render('back/production/lotGlycerine/listeGlycerine.html.twig', [
            'production' => true, 
            'listeProduction' => true,
            'lotGlycerines' => $lotGlycerineRepository->findBy(['active' => true])
        ]);
    }

    public function lotGlycerine($id = null, Request $request, EntityManagerInterface $em, LotGlycerineRepository $lotGlycerineRepository)
    {
        if($id)
        {
            $lotGlycerine = $lotGlycerineRepository->find($id);
        }
        else
        {
            $lotGlycerine = new LotGlycerine;
        }


        $form = $this->createForm(LotGlycerineType::class, $lotGlycerine);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $em->persist($lotGlycerine);
            $em->flush();

           
            if($id)
            {
                $this->addFlash("lotCréer", "Modification lot glycerine éfféctué avec succè");

            }
            else
            {
                $this->addFlash("lotCréer", "Création lot glycerine éfféctué avec succè");
            }
                

            if(!$lotGlycerine->getActive())
            {
                return $this->redirectToRoute("back_production_liste_archives_glycerine");
            }

            return $this->redirectToRoute("back_production_liste_glycerine");
        }

        return $this->render('back/production/lotGlycerine/lotGlycerine.html.twig', [
            'production' => true, 
            'listeProduction' => true,
            'lotGlycerines' => true,
            'form' => $form->createView(),
            'id' => $id
        ]);
    }

    public function deleteLotGlycerine(LotGlycerine $lotGlycerine, Request $request, EntityManagerInterface $em)
    {
        if ($this->isCsrfTokenValid('delete' . $lotGlycerine->getId(), $request->request->get('_token'))) {

            $archive = false;

            ($lotGlycerine->getActive())? $archive = true: $archive = false;
            
            $em->remove($lotGlycerine);
            $em->flush();
            $this->addFlash('successDelete', 'Numéro de lot supprimé avec succès');

            if(!$archive)
            {
                return $this->redirectToRoute("back_production_liste_archives_glycerine");
            }

            return $this->redirectToRoute('back_production_liste_glycerine');
        } else {
            throw new TokenNotFoundException('Token invalide');
        }
    }

    public function listeArchiveGlycerine(LotGlycerineRepository $lotGlycerineRepository)
    {
        return $this->render('back/production/lotGlycerine/archiveGlycerine.html.twig', [
            'production' => true, 
            'listeProduction' => true,
            'lotGlycerines' => $lotGlycerineRepository->findBy(["active" => false]),
        ]);
    }

    //lot isolat

    public function listIsolat(LotIsolatRepository $lotIsolatRepository)
    {
        return $this->render('back/production/lotIsolat/listeIsolat.html.twig', [
            'production' => true, 
            'listeProduction' => true,
            'lotIsolats' => $lotIsolatRepository->findBy(['active' => true])
        ]);
    }

    public function lotIsolat($id = null, Request $request, EntityManagerInterface $em, LotIsolatRepository $lotIsolatRepository)
    {
        if($id)
        {
            $lotIsolat = $lotIsolatRepository->find($id);
        }
        else
        {
            $lotIsolat = new LotIsolat;
        }


        $form = $this->createForm(LotIsolatType::class, $lotIsolat);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $em->persist($lotIsolat);
            $em->flush();

           
            if($id)
            {
                $this->addFlash("lotCréer", "Modification lot isolat éfféctué avec succè");

            }
            else
            {
                $this->addFlash("lotCréer", "Création lot isolat éfféctué avec succè");
            }
                

            if(!$lotIsolat->getActive())
            {
                return $this->redirectToRoute("back_production_liste_archives_isolat");
            }

            return $this->redirectToRoute("back_production_liste_isolat");
        }

        return $this->render('back/production/lotIsolat/lotIsolat.html.twig', [
            'production' => true, 
            'listeProduction' => true,
            'lotIsolats' => true,
            'form' => $form->createView(),
            'id' => $id
        ]);
    }

    public function deleteLotIsolat(LotIsolat $lotIsolat, Request $request, EntityManagerInterface $em)
    {
        if ($this->isCsrfTokenValid('delete' . $lotIsolat->getId(), $request->request->get('_token'))) {

            $archive = false;

            ($lotIsolat->getActive())? $archive = true: $archive = false;
            
            $em->remove($lotIsolat);
            $em->flush();
            $this->addFlash('successDelete', 'Numéro de lot supprimé avec succès');

            if(!$archive)
            {
                return $this->redirectToRoute("back_production_liste_archives_isolat");
            }

            return $this->redirectToRoute('back_production_liste_isolat');
        } else {
            throw new TokenNotFoundException('Token invalide');
        }
    }

    
    public function listeArchiveIsolat(LotIsolatRepository $lotIsolatRepository)
    {
        return $this->render('back/production/lotIsolat/archiveIsolat.html.twig', [
            'production' => true, 
            'listeProduction' => true,
            'lotIsolats' => $lotIsolatRepository->findBy(["active" => false]),
        ]);
    }

    //vegetol

    public function listVegetol(LotVegetolPdoRepository $lotVegetolPdoRepository)
    {
        return $this->render('back/production/lotVegetol/listeVegetol.html.twig', [
            'production' => true, 
            'listeProduction' => true,
            'lotVegetol' => $lotVegetolPdoRepository->findBy(['active' => true])
        ]);
    }

    public function lotVegetol($id = null, Request $request, EntityManagerInterface $em, LotVegetolPdoRepository $lotVegetolPdoRepository)
    {
        if($id)
        {
            $lotVegetol = $lotVegetolPdoRepository->find($id);
        }
        else
        {
            $lotVegetol = new LotVegetolPdo;
        }


        $form = $this->createForm(LotVegetolPdoType::class, $lotVegetol);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $em->persist($lotVegetol);
            $em->flush();

           
            if($id)
            {
                $this->addFlash("lotCréer", "Modification lot isolat éfféctué avec succè");

            }
            else
            {
                $this->addFlash("lotCréer", "Création lot isolat éfféctué avec succè");
            }
                

            if(!$lotVegetol->getActive())
            {
                return $this->redirectToRoute("back_production_liste_archives_vegetol");
            }

            return $this->redirectToRoute("back_production_liste_vegetol");
        }

        return $this->render('back/production/lotVegetol/lotVegetol.html.twig', [
            'production' => true, 
            'listeProduction' => true,
            'lotVegetol' => true,
            'form' => $form->createView(),
            'id' => $id
        ]);
    }

    public function deleteLotVegetol(LotVegetolPdo $lotVegetolPdo, Request $request, EntityManagerInterface $em)
    {
        if ($this->isCsrfTokenValid('delete' . $lotVegetolPdo->getId(), $request->request->get('_token'))) {

            $archive = false;

            ($lotVegetolPdo->getActive())? $archive = true: $archive = false;
            
            $em->remove($lotVegetolPdo);
            $em->flush();
            $this->addFlash('successDelete', 'Numéro de lot supprimé avec succès');

            if(!$archive)
            {
                return $this->redirectToRoute("back_production_liste_archives_vegetol");
            }

            return $this->redirectToRoute('back_production_liste_vegetol');
        } else {
            throw new TokenNotFoundException('Token invalide');
        }
    }

    
    public function listeArchiveVegetol(LotVegetolPdoRepository $lotVegetolPdoRepository)
    {
        return $this->render('back/production/lotVegetol/archiveVegetol.html.twig', [
            'production' => true, 
            'listeProduction' => true,
            'lotVegetol' => $lotVegetolPdoRepository->findBy(["active" => false]),
        ]);
    }



    //arome

    public function listArome(LotAromeRepository $lotAromeRepository)
    {
        return $this->render('back/production/lotArome/listeArome.html.twig', [
            'production' => true, 
            'listeProduction' => true,
            'lotArome' => $lotAromeRepository->findBy(['active' => true])
        ]);
    }

    public function lotArome($id = null, Request $request, EntityManagerInterface $em, LotAromeRepository $lotAromeRepository)
    {
        if($id)
        {
            $lotArome = $lotAromeRepository->find($id);
        }
        else
        {
            $lotArome = new LotArome;
        }


        $form = $this->createForm(LotAromeType::class, $lotArome);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $em->persist($lotArome);
            $em->flush();

           
            if($id)
            {
                $this->addFlash("lotCréer", "Modification lot isolat éfféctué avec succè");

            }
            else
            {
                $this->addFlash("lotCréer", "Création lot isolat éfféctué avec succè");
            }
                

            if(!$lotArome->getActive())
            {
                return $this->redirectToRoute("back_production_liste_archives_arome");
            }

            return $this->redirectToRoute("back_production_liste_arome");
        }

        return $this->render('back/production/lotArome/lotArome.html.twig', [
            'production' => true, 
            'listeProduction' => true,
            'lotArome' => true,
            'form' => $form->createView(),
            'id' => $id
        ]);
    }

    public function deleteLotArome(LotArome $lotArome, Request $request, EntityManagerInterface $em)
    {
        if ($this->isCsrfTokenValid('delete' . $lotArome->getId(), $request->request->get('_token'))) {

            $archive = false;

            ($lotArome->getActive())? $archive = true: $archive = false;
            
            $em->remove($lotArome);
            $em->flush();
            $this->addFlash('successDelete', 'Numéro de lot supprimé avec succès');

            if(!$archive)
            {
                return $this->redirectToRoute("back_production_liste_archives_arome");
            }

            return $this->redirectToRoute('back_production_liste_arome');
        } else {
            throw new TokenNotFoundException('Token invalide');
        }
    }

    
    public function listeArchiveArome(LotAromeRepository $lotAromeRepository)
    {
        return $this->render('back/production/lotArome/archiveArome.html.twig', [
            'production' => true, 
            'listeProduction' => true,
            'lotArome' => $lotAromeRepository->findBy(["active" => false]),
        ]);
    }


    //archiver
    public function archiver($id = null, $idGlycerine = null, $idIsolat = null,$idVegetol = null, $idArome = null, LotAromeRepository $lotAromeRepository, LotVegetolPdoRepository $lotVegetolPdoRepository, LotIsolatRepository $lotIsolatRepository, LotNicotineRepository $lotNicotineRepository, LotGlycerineRepository $lotGlycerineRepository, Request $request, EntityManagerInterface $em)
    {
        $objet = null;
        if($id)
        {
            $objet = $lotNicotineRepository->find($id);
        }
      

        if($idGlycerine)
        {
            $objet = $lotGlycerineRepository->find($idGlycerine);
        }

        if($idIsolat)
        {
            $objet = $lotIsolatRepository->find($idIsolat);
        }

        if($idVegetol)
        {
            $objet = $lotVegetolPdoRepository->find($idVegetol);
        }

        if($idArome)
        {
            $objet = $lotAromeRepository->find($idArome);
        }

        $retour = $this->ajouterAuxArchives($objet, $em, $request);

        if($retour)
        {
            if($id)
            {
                return $this->redirectToRoute('back_production_liste_archives_nicotine');
            }

            if($idGlycerine)
            {
                return $this->redirectToRoute('back_production_liste_archives_glycerine');
            }

            if($idVegetol)
            {
                return $this->redirectToRoute('back_production_liste_archives_vegetol');
            }

            if($idArome)
            {
                return $this->redirectToRoute('back_production_liste_archives_arome');
            }

            return $this->redirectToRoute('back_production_liste_archives_isolat');

        }
    }

    public function ajouterAuxArchives($objet, EntityManagerInterface $em, Request $request)
    {
        if ($this->isCsrfTokenValid('archiver' . $objet->getId(), $request->request->get('_token'))) {
            $objet->setActive(false);
            $em->flush();
            $this->addFlash('successArchive', 'Numéro de lot ajouté dans les archives');
            return true;
        } else {
            throw new TokenNotFoundException('Token invalide');
        }
    }
}
