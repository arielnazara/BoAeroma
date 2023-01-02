<?php


namespace App\Controller\Back;

use App\Entity\AdresseLivraison;
use App\Entity\BonDeCommande;
use App\Entity\Client;
use App\Entity\Commandes;
use App\Entity\Contact;
use App\Entity\Produit;
use App\Entity\Shop;
use App\Form\AdresseType;
use App\Form\ClientType;
use App\Form\ContactType;
use App\Repository\AdresseLivraisonRepository;
use App\Repository\BonDeCommandeRepository;
use App\Repository\ClientRepository;
use App\Repository\CommandesRepository;
use App\Repository\ContactRepository;
use App\Repository\DevisRepository;
use App\Repository\FactureCommandeRepository;
use App\Repository\GammeClientRepository;
use App\Repository\ProduitRepository;
use App\Repository\ShopRepository;
use App\Service\AeromaApi;
use App\Service\Mailer;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\Security\Core\Exception\TokenNotFoundException;

class ClientController extends AbstractController
{

    /**
     * @var ClientRepository
     */
    private $repository;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(ClientRepository $repository, EntityManagerInterface $em)
    {
        $this->repository = $repository;
        $this->em = $em;
    }

    public function list(AeromaApi $aeromaApi, ClientRepository $clients, EntityManagerInterface $em)
    {

        /* $clientApis = $aeromaApi->getClient(true, false);

        foreach ($clientApis as $clientApi) {
            $exist = $clients->findOneBy(['email' => $clientApi->customer->email]);
            if (!$exist) {
                $client = new Client();
                $client->setLastName($clientApi->customer->lastname);
                $client->setFirstName($clientApi->customer->firstname);
                $client->setEmail($clientApi->customer->email);
                $client->setRaisonSocial($clientApi->customer->company);
                $client->setIdPrestashop($clientApi->customer->id);
                $em->persist($client);
            }
        }

        $em->flush();*/

        return $this->render('back/client/list_client.html.twig', [
            'clients' => $clients->findBy([], ['id' => 'desc']),
            //'clients' => $aeromaApi->getClient()
        ]);
    }

    public function delete(Client $client, Request $request)
    {
        if ($this->isCsrfTokenValid('delete' . $client->getId(), $request->request->get('_token'))) {
            $this->em->remove($client);
            $this->em->flush();
            $this->addFlash('success', 'Client supprimé avec succès');
            return $this->redirectToRoute('back_client_list');
        } else {
            throw new TokenNotFoundException('Token invalide');
        }
    }

    public function create(Request $request)
    {
        $client = new Client();
        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if($request->request->get('ajoutNumeroLot'))
            {
                $client->setAjoutNumeroLot(true);
            }

            if($request->request->get('chubbyOffert'))
            {
                $client->setProduitOffert(true);
            }

            if($request->request->get('escompte'))
            {
                $client->setEscompte(true);
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($client);
            $entityManager->flush();

            $this->addFlash('success', 'Client créé avec succès');

            return $this->redirectToRoute('back_client_list');
        }

        return $this->render('back/client/creationClient.html.twig', [
            'form' => $form->createView(),
            'clients' => true
        ]);
    }

    public function voir($id, Request $request,Mailer $mailer, ClientRepository $clientRepository,EntityManagerInterface $em, BonDeCommandeRepository $BonDecommande, FactureCommandeRepository $factureRepo)
    {
        $client = $clientRepository->find($id);

        if($request->isMethod('post'))
        {
            if($request->get('valider') && !$client->getIsValid())
            {
                $client->setIsValid(true);
                $em->flush();

                $mailer->accountValidation($client);

                $this->addFlash('clientValider', "Validation client éfféctuée avec succès");
            }
        }

        return $this->render('back/client/voir.html.twig', [
            'client' => $client,
            'home' => true,
            'clients' => true,
            'commande' => $BonDecommande->findBy(['client' => $client, 'toutEstExpedier' => null]),
            'facture' => $factureRepo->findBy(['client' => $client])
        ]);
    }

    public function info($id, ClientRepository $client)
    {
        return $this->render('back/client/info.html.twig', [
            'client' => $client->find($id),
            'info' => true,
            'clients' => true
        ]);
    }

    public function commande($id, ClientRepository $client)
    {
        return $this->render('back/client/commandeClient.html.twig', [
            'client' => $client->find($id),
            'Commande' => true,
            'clients' => true
        ]);
    }

    public function listeAdresse(Client $client, AdresseLivraisonRepository $adresse, ClientRepository $clientRepo)
    {
        return $this->render('back/client/listeAdresse.html.twig', [
            'adresse' => $adresse->findBy(['client' => $client]),
            'clients' => true,
            'client' => $clientRepo->find($client->getId()),
            'info' => true,
        ]);
    }

    public function adresse($id, Request $request, ClientRepository $client)
    {
        $adresse = new AdresseLivraison;

        $form = $this->createForm(AdresseType::class, $adresse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $adresse->setAdresse($form->get('adresse')->getData());
            $adresse->setCodePostal($form->get('codePostal')->getData());
            $adresse->setVille($form->get('ville')->getData());
            $adresse->setPays($form->get('pays')->getData());
            $adresse->setClient($client->find($id));
            $entityManager->persist($adresse);
            $entityManager->flush();

            $this->addFlash('success', 'Adresse créée avec succès');

            return $this->redirectToRoute('back_client_adresse_list', ['id' => $id]);
        }

        return $this->render('back/client/adresseLivraison.html.twig', [
            'form' => $form->createView(),
            'client' => $client->find($id),
            'clients' => true,
            'info' => true
        ]);
    }

    public function catalogueClient(Client $client, GammeClientRepository $GCRepo)
    {

        $catalogue = $GCRepo->findOneBy(['client' => $client]);

        return $this->render('back/client/catalogueClient.html.twig', [
            'catalogue' => $catalogue,
            'client' => $client,
            'clients' => true,
        ]);
    }

    public function listeContact(Client $client, ContactRepository $contact, ClientRepository $clientRepo)
    {
        return $this->render('back/client/listeContact.html.twig', [
            'contact' => $contact->findBy(['client' => $client]),
            'clients' => true,
            'client' => $clientRepo->find($client->getId()),
            'info' => true
        ]);
    }

    public function contact($id, Request $request, ClientRepository $client)
    {
        $contact = new Contact;

        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $contact->setFonction($form->get('fonction')->getData());
            $contact->setNom($form->get('nom')->getData());
            $contact->setPrenoms($form->get('prenoms')->getData());
            $contact->setEmail($form->get('email')->getData());
            $contact->setClient($client->find($id));
            $entityManager->persist($contact);
            $entityManager->flush();

            $this->addFlash('success', 'Contact créé avec succès');

            return $this->redirectToRoute('back_client_contact_list', ['id' => $id]);
        }

        return $this->render('back/client/contact.html.twig', [
            'form' => $form->createView(),
            'client' => $client->find($id),
            'clients' => true,
            'info' => true
        ]);
    }

    public function editClient(Client $client, Request $request, EntityManagerInterface $em, ClientRepository $clientRepo)
    {
        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if($request->get('ajoutNumeroLot'))
            {
                $client->setAjoutNumeroLot(true);
            }
            else
            {
                $client->setAjoutNumeroLot(false);
            }

            if($request->request->get('chubbyOffert'))
            {
                $client->setProduitOffert(true);
            }
            else
            {
                $client->setProduitOffert(false);
            }

            if($request->request->get('escompte'))
            {
                $client->setEscompte(true);
            }
            else
            {
                $client->setEscompte(false);
            }

            $em->flush();
            $this->addFlash('success', 'Modification effectuée avec succès');

            return $this->redirectToRoute('back_client_info', ['id' => $client->getId()]);
        }

        return $this->render('back/client/modification.html.twig', [
            'form' => $form->createView(),
            'clients' => true,
            'client' => $clientRepo->find($client->getId())
        ]);
    }

    public function listCommande($id, Request $request, AeromaApi $aeromaApi, DevisRepository $devisRepo, ClientRepository $client, ProduitRepository $produits)
    {

        return $this->render('back/client/listeCommande.html.twig', [
            'commandes' => $devisRepo->findBy(['client' => $id, 'valider' => true], ['id' => 'desc']),
            'produits' => $produits->findAll(),
            'clients' => true,
            'client' => $client->find($id)
        ]);
    }

    public function supprimeAdresse(AdresseLivraison $adresse, EntityManagerInterface $em, Request $request)
    {
        
        if ($this->isCsrfTokenValid('delete' . $adresse->getId(), $request->request->get('_token'))) {
            
            $client = $adresse->getClient()->getId();

            if(count($adresse->getBonLivraisons()) == 0)
            {
                $em->remove($adresse);
                $em->flush();
    
                $this->addFlash('success', 'Adresse supprimée avec succès');
            }
            else
            {
                $a = (count($adresse->getBonLivraisons()) > 1) ? "une bon de livraison" : "des bons de livraisons";
                $this->addFlash('error', 'Cette adresse est liée à '.$a);
            }

            return $this->redirectToRoute('back_client_adresse_list', ['id' => $client]);
        } else {
            throw new TokenNotFoundException('Token invalide');
        }
    }

    public function modificationAdresse(AdresseLivraison $adresse, EntityManagerInterface $em, Request $request)
    {
        $form = $this->createForm(AdresseType::class, $adresse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Modification effectuée avec succès');

            return $this->redirectToRoute('back_client_adresse_list', ['id' => $adresse->getClient()->getId()]);
        }

        return $this->render('back/client/modificationAdresse.html.twig', [
            'form' => $form->createView(),
            'client' => $adresse->getClient(),
            'clients' => true,
            'info' => true,
        ]);
    }

    public function modificationContact(Contact $contact, EntityManagerInterface $em, Request $request)
    {
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Modification effectuée avec succès');

            return $this->redirectToRoute('back_client_contact_list', ['id' => $contact->getClient()->getId()]);
        }

        return $this->render('back/client/modificationContact.html.twig', [
            'form' => $form->createView(),
            'client' => $contact->getClient(),
            'clients' => true,
            'info' => true,
        ]);
    }

    public function supprimeContact(Contact $contact, EntityManagerInterface $em, Request $request)
    {

        if ($this->isCsrfTokenValid('delete' . $contact->getId(), $request->request->get('_token'))) {
            $client = $contact->getClient()->getId();
            $em->remove($contact);
            $em->flush();

            $this->addFlash('success', 'Contact supprimé avec succès');
            return $this->redirectToRoute('back_client_contact_list', ['id' => $client]);
        } else {
            throw new TokenNotFoundException('Token invalide');
        }
    }

    public function facture(Client $client, FactureCommandeRepository $factRepo)
    {

        return $this->render('back/client/facture.html.twig', [
            'client' => $client,
            'clients' => true,
            'invoices' => $factRepo->findBy(['client' => $client], ['id' => 'desc'])
        ]);
    }

    public function ajoutShop(Request $request,ClientRepository $clientRepository, EntityManagerInterface $em)
    {
        if($request->isXmlHttpRequest())
        {
            $client = $clientRepository->find($request->get('clientId'));
            $nombreDeShop = $request->get("nombreShop");

            if(count($client->getShops()) > 0)
            {
                foreach($client->getShops() as $shop)
                {
                    $nom = $request->get("nom{$shop->getId()}");
                    $nomShop = $request->get("shop{$shop->getId()}");
                    $prenom = $request->get("prenom{$shop->getId()}");
                    $email = $request->get("email{$shop->getId()}");
                    $adresse = $request->get("adresse{$shop->getId()}");
                    $codePostal = $request->get("codePostal{$shop->getId()}");
                    $ville = $request->get("ville{$shop->getId()}");
                    $commune = $request->get("commune{$shop->getId()}");
                    $telephone = $request->get("telephone{$shop->getId()}");

                    $shop->setNomShop($nomShop);
                    $shop->setNom($nom);
                    $shop->setPrenom($prenom);
                    $shop->setEmail($email);
                    $shop->setAdresse($adresse);
                    $shop->setCodePostal($codePostal);
                    $shop->setVille($ville);
                    $shop->setCommune($commune);
                    $shop->setClient($client);
                    $shop->setTelephone($telephone);
    
                    $em->flush();
    
                }
            }
            
            if($nombreDeShop > 0)
            {
                $i = 1;
                
                while($i <= $nombreDeShop)
                {
    
                    $nom = $request->get("nom_$i");
                    $nomShop = $request->get("shop_$i");
                    $prenom = $request->get("prenom_$i");
                    $email = $request->get("email_$i");
                    $adresse = $request->get("adresse_$i");
                    $codePostal = $request->get("codePostal_$i");
                    $ville = $request->get("ville_$i");
                    $commune = $request->get("commune_$i");
                    $telephone = $request->get("telephone_$i");
    
                    $newShop = new Shop;
    
                    $newShop->setNomShop($nomShop);
                    $newShop->setNom($nom);
                    $newShop->setPrenom($prenom);
                    $newShop->setEmail($email);
                    $newShop->setAdresse($adresse);
                    $newShop->setCodePostal($codePostal);
                    $newShop->setVille($ville);
                    $newShop->setCommune($commune);
                    $newShop->setClient($client);
                    $newShop->setTelephone($telephone);
    
                    $em->persist($newShop);
                    $em->flush();
                
    
                    $i++;
                }
            }

            return $this->json(['effectuer' => true], 200);
        }
    }


    public function deleteShop(Request $request, EntityManagerInterface $em, ShopRepository $shopRepository)
    {
        if($request->isXmlHttpRequest())
        {
            $shopId = $request->get('id');
            $shop = $shopRepository->find($shopId);

            $em->remove($shop);
            $em->flush();

            return $this->json(['supprimer' => $shopId ], 200);
        }
    }
}
