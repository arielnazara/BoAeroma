<?php


namespace App\Controller\Front;

use DateTime;
use App\Entity\Unit;
use App\Entity\Devis;
use App\Entity\Client;
use App\Service\Token;
use App\Entity\Product;
use App\Entity\Produit;
use App\Service\Mailer;
use App\Service\Stripe;
use App\Service\Useful;
use App\Form\ClientType;
use App\Entity\DevisPrice;
use App\Service\AeromaApi;
use App\Entity\Declinaison;
use App\Service\CodeNumber;
use App\Entity\BonLivraison;
use App\Entity\DevisProduit;
use App\Entity\InvoiceOrder;
use App\Entity\BonDeCommande;
use App\Entity\PurchaseOrder;
use App\Service\CodeGenerate;
use Doctrine\DBAL\Types\Type;
use App\Entity\DeliveryAddress;
use App\Entity\FactureCommande;
use App\Entity\AdresseLivraison;
use App\Entity\MarqueBlanche;
use App\Entity\PrincipeActif;
use App\Entity\Shop;
use App\Repository\DevisRepository;
use App\Repository\GammeRepository;
use App\Repository\ClientRepository;
use App\Repository\ProductRepository;
use App\Repository\ProduitRepository;
use App\Entity\UnitGammeClientProduit;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\GammeClientRepository;
use App\Repository\DevisProduitRepository;
use App\Repository\InvoiceOrderRepository;
use App\Repository\TypeDeClientRepository;
use App\Repository\BonDeCommandeRepository;
use App\Repository\MarqueBlancheRepository;
use App\Repository\MarqueRepository;
use App\Repository\PrincipeActifRepository;
use App\Repository\ShopRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\UnitGammeClientProduitRepository;
use App\Service\InvoiceProcessing;
use Exception;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class FrontController extends AbstractController
{

    public function index(Request $request, EntityManagerInterface $em, SessionInterface $session, CodeNumber $pswdGenerator, Mailer $mail, DevisRepository $devisRepo, GammeClientRepository $gcrepo)
    {
        if (!$request->query->get('email')) {

            $request->getSession()->clear();
        }

        $client = null;
        $nouveau = null;
        if ($em->getRepository('App:Client')->findOneBy(['email' => $request->query->get('email')])) {

            $client = $em->getRepository('App:Client')->findOneBy(['email' => $request->query->get('email')]);

            if( strtolower($client->getTypeDeClient()->getNom()) == "grossiste" and !$client->getIsValid())
            {
                $this->addFlash('warningGrosssite', "Votre compte est en attente de validation");
                return $this->redirectToRoute('front_index');
            }

            if (!$session->get('pswdEnvoyer')) {

                $pswd = $pswdGenerator->generatePassword();
                $client->setMdpTemporaire($pswd);
                $em->flush();
                $mail->sendPassword($client, $pswd);
                $session->set('pswdEnvoyer', true);
                $this->addFlash('pswdEnvoyer', 'Nous venons de vous envoyer un mot de passe par email');
            }
        } else {
            $client = new Client();
            $nouveau = true;
        }

        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        
        if ($form->isSubmitted() && $form->isValid()) {

            if(!$request->get("client")['adresseServiceAchats'])
            {
                $client->setAdresseServiceAchats($request->get('client')['adresseFacturation']);
                $client->setCodePostalServiceAchat($request->get('client')['codePostalFacturation']);
                $client->setCommuneServiceAchat($request->get('client')['communeFacturation']);
                $client->setVilleServiceAchat($request->get('client')['villeFacturation']);

            }

            $em->persist($client);
            $em->flush();

            $indice = (int) $request->request->get('_index_');
            if ($indice > 0) {
                for ($i = 0; $i <= $indice; $i++) {
                    if ($request->request->get('_name-' . $i)) {
                        $deliveryAddress = new AdresseLivraison();
                        $deliveryAddress->setRaisonSocial($request->request->get('_raison_social-' . $i));
                        $deliveryAddress->setNom($request->request->get('_name-' . $i));
                        $deliveryAddress->setALattentionDe($request->request->get('_for-' . $i));
                        $deliveryAddress->setAdresse($request->request->get('_adr-' . $i));
                        $deliveryAddress->setCodePostal($request->request->get('_cp-' . $i));
                        $deliveryAddress->setPays($request->request->get('_city-' . $i));
                        $deliveryAddress->setTelephone($request->request->get('_tel-' . $i));
                        $deliveryAddress->setEmail($request->request->get('_email-' . $i));
                        $deliveryAddress->setClient($client);
                        $em->persist($deliveryAddress);
                    }
                }
                $em->flush();
            }


            if( strtolower($client->getTypeDeClient()->getNom()) == "grossiste" and $nouveau and !$client->getIsValid())
            {
                $this->addFlash('validationGrosssite', "Vous recevrez un email d'activation une fois que votre compte validé");
                return $this->redirectToRoute('front_index');
            }


            $request->getSession()->set('id_clt', $client->getId());

            $devisExist = $devisRepo->findBy(['client' => $client, 'valider' => true]);
            $gammeClient = $gcrepo->findOneBy(['client' => $client]);

            if (($devisExist and $gammeClient) or ($devisExist and !$gammeClient)) {

                return $this->redirectToRoute('front_liste_categorie');
            } elseif (!$devisExist and $gammeClient) {
                return $this->redirectToRoute('front_gamme_client');
            } else {
                return  $this->redirectToRoute('front_generate_devis');
            }
        }

        $deliveryAddressRender = $this->renderView('front/_form_delivery_address.html.twig');

        return $this->render('front/index.html.twig', [
            'form' => $form->createView(),
            'deliveryAddress' => $deliveryAddressRender,
            'pswdEnvoyer' => ($session->get('pswdEnvoyer')) ? true : false,
            'pswdValid' => ($session->get('pswdValid')) ? true : false,
            'nouveau' => $nouveau
        ]);
    }

    public function checkPswd(Request $request, ClientRepository $clientRepo, EntityManagerInterface $em, SessionInterface $session)
    {
        if ($request->isMethod('post')) {

            $client = $clientRepo->findOneBy(['email' => $request->request->get('email')]);
            if ($request->request->get('password') == $client->getMdpTemporaire()) {

                if (!$session->get('pswdValid')) {
                    $session->set('pswdValid', true);

                    $client->setMdpTemporaire(null);
                    $em->flush();
                }
            } else {
                $this->addFlash('mdpError', 'Mot de passe incorrect');
            }

            return $this->redirectToRoute('front_index', ['email' => $request->request->get('email')]);
        }
    }

    public function productList(Request $request, ProduitRepository $produitRepo, MarqueBlancheRepository $mbRepo, TypeDeClientRepository $typeRepo, GammeClientRepository $gCrepo, DevisRepository $devisRepo, CategorieRepository $categorieRepo, ClientRepository $clientRepo)
    {
        $session = $request->getSession();

        if ($session->has(('id_clt'))) {
            $client = $clientRepo->find($session->get('id_clt'));

            $devisExist = $devisRepo->findBy(['client' => $client, 'valider' => true]);

            $categorieId = [];
            $categories = [];
            $n = 0;

            if ($gCrepo->findOneBy(['client' => $client])) {

                if (!$gCrepo->findOneBy(['client' => $client])->getToutLesProduits()) {
                    $status = true;
                } else {
                    $status = false;
                }
            } else {
                $status = null;
            }

            $sansCategorie = false;
            if ($devisExist) {

                foreach ($devisExist as $devi) {
                    foreach ($devi->getDevisProduits() as $devpro) {
                        if ($devpro->getProduit()->getCategorie()) {
                            if (!in_array($devpro->getProduit()->getCategorie()->getId(), $categorieId)) {
                                array_push($categorieId, $devpro->getProduit()->getCategorie()->getId());
                                array_push($categories, $devpro->getProduit()->getCategorie());
                            }
                        } else {
                            $sansCategorie = true;
                        }
                    }
                }
            }

            $panier = $session->get('panier', []);
            $total = 0;
            foreach ($panier as $i => $dec) {

                foreach ($dec as $quantite) {
                    $total += $quantite;
                }
            }

            return $this->render('front/listeParCategorie.html.twig', [
                'commander' => $devisExist,
                'commande' => true,
                'categories' => $categories,
                'client' => $client,
                'total' => $total,
                'panier' => $panier,
                'status' => $status,
                'sansCategorie' => $sansCategorie,
                'types' => $typeRepo->findAll(),
                'marqueBlanches' => $mbRepo->findBy(['client' => $client])

            ]);
        } else {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès a cette page');
        }
    }

    public function gammeClient(Request $request, UnitGammeClientProduitRepository $unitProduitRepo, TypeDeClientRepository $typeRepo, ProduitRepository $produitRepo, GammeClientRepository $gCrepo, DevisRepository $devisRepo, CategorieRepository $categorieRepo, ClientRepository $clientRepo)
    {
        $session = $request->getSession();

        if ($session->has(('id_clt'))) {
            $client = $clientRepo->find($session->get('id_clt'));

            $gammeClient = $gCrepo->findOneBy(['client' => $client]);

            if ($gammeClient) {

                if ($gammeClient->getToutLesProduits()) {
                    return $this->redirectToRoute('front_generate_devis');
                } else {
                    $produitGamme = $unitProduitRepo->findBy(['gammeClient' => $gammeClient]);
                }
            } else {
                $produitGamme = null;
            }

            $devisExist = $devisRepo->findBy(['client' => $client, 'valider' => true]);

            $categorieId = [];
            $categories = [];
            $n = 0;

            if ($devisExist) {
                $status = true;
            } else {
                $status = false;
            }

            $sansCategorie = false;
            foreach ($produitGamme as $prod) {
                if ($prod->getProduit()->getCategorie()) {
                    if (!in_array($prod->getProduit()->getCategorie()->getId(), $categorieId)) {
                        array_push($categorieId, $prod->getProduit()->getCategorie()->getId());
                        array_push($categories, $prod->getProduit()->getCategorie());
                    }
                } else {
                    $sansCategorie = true;
                }
            }

            $panier = $session->get('panier', []);
            $total = 0;
            foreach ($panier as $i => $dec) {

                foreach ($dec as $quantite) {
                    $total += $quantite;
                }
            }

            return $this->render('front/gammeClient.html.twig', [
                'gamme' => $produitGamme,
                'game' => ($produitGamme) ? true : false,
                'categories' => $categories,
                'client' => $client,
                'total' => $total,
                'panier' => $panier,
                'status' => $status,
                'sansCategorie' => $sansCategorie,
                'types' => $typeRepo->findAll()

            ]);
        } else {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès a cette page');
        }
    }

    public function quoteGeneration(Request $request, $all,TypeDeClientRepository $typeRepo, MarqueBlancheRepository $mbRepo, PrincipeActifRepository $principeRepo, DevisRepository $devisRepo, ProduitRepository $produitRepo, EntityManagerInterface $em, CodeGenerate $getCode, Token $token,GammeRepository $gammeRepository, UnitGammeClientProduitRepository $gcrepo, PaginatorInterface $paginator)
    {
        $session = $request->getSession();

        if ($session->has('id_clt')) {
            $client = $em->getRepository('App:Client')->find((int)$session->get('id_clt'));

            if ($request->isMethod('POST')) {

                $amount = (floatval($request->request->get('amount')) > 0) ? floatval($request->request->get('amount')) : 0;
                $fee = (floatval($request->request->get('fee')) > 0) ? floatval($request->request->get('fee')) : 0;
                $taxe = (floatval($request->request->get('taxe')) > 0) ? floatval($request->request->get('taxe')) : 0;
                $totalHt = (floatval($request->request->get('totalHt')) > 0) ? floatval($request->request->get('totalHt')) : 0;
                $totalTtc = (floatval($request->request->get('totalTtc')) > 0) ? floatval($request->request->get('totalTtc')) : 0;
                $quantiteTotal = ((int)$request->request->get('quantiteTotal') > 0) ? (int)$request->request->get('quantiteTotal') : 0;

                $devis = new Devis();
                $devis->setDate(new DateTime());
                $devis->setCode('cn');
                $devis->setCode($getCode->code(4));
                $devis->setClient($client);
                $devis->setMontant($amount);
                $devis->setFraisExpedition($fee);
                $devis->setTaxe($taxe);
                $devis->setTotalHt($totalHt);
                $devis->setTotalTtc($totalTtc);
                $devis->setNombreProduit($quantiteTotal);
                $devis->setMessage($request->request->get('_msg'));

                $devis->setToken($token->generateToken());

                $countPost = count($request->request->all());
                $i = 1;
                $marqueBlanches = $mbRepo->findBy(['client' => $client]);

                while ($i <= $countPost) {
                    if (isset($_POST["produit-$i"])) {
                        $prod = $produitRepo->find($_POST["produit-$i"]);
                        $tarif = $prod->getTarif();
                        $listeDeclinaison = $prod->getDeclinaison();
                        foreach ($marqueBlanches as $mb) {
                            if ($mb->getProduit() == $prod) {
                                $tarif = $mb->getTarif();
                            }
                        }
                        $j = 1;

                        $sansDecl = false;

                        if (!$prod->getPrincipeActif()) {
                            $sansDecl = true;
                        }

                        while ($j <= $countPost) {
                            if (isset($_POST["declinaison-$i-$j"])) {

                                $devisProduit = new DevisProduit;
                                $devisProduit->setDevis($devis);
                                $devisProduit->setProduit($prod);
                                $devisProduit->setDeclinaison($_POST["declinaison-$i-$j"]);
                                $devisProduit->setQuantite($_POST["quantite-$i-$j"]);
                                $devisProduit->setDeclineAvec($prod->getPrincipeActif());

                                if ($sansDecl or $tarif->getMemeTarif()) {
                                    if (strtolower($client->getTypeDeclient()->getNom()) == "grossiste") {
                                        $devisProduit->setPrix($tarif->getPrixDeReferenceGrossiste());
                                    } else {
                                        $devisProduit->setPrix($tarif->getPrixDeReferenceDetaillant());
                                    }
                                } else {
                                    foreach ($tarif->getPrixDeclinaisons() as $prixDecl) {
                                        if ($prixDecl->getActif()) {
                                            if (strtolower($prixDecl->getTypeDeClient()->getNom()) == strtolower($client->getTypeDeClient()->getNom())) {
                                                if ($prixDecl->getDeclinaison() == $_POST["declinaison-$i-$j"]) {
                                                    $devisProduit->setPrix($prixDecl->getPrix());
                                                }
                                            }
                                        }
                                    }
                                }
                                $em->persist($devisProduit);
                            }
                            $j++;
                        }
                    } elseif (isset($_POST["produit-$i-offert"])) {
                        $prod = $produitRepo->find($_POST["produit-$i-offert"]);

                        $l = 1;

                        while ($l <= $countPost) {
                            if (isset($_POST["declinaison-$i-$l-offert"])) {

                                $devisProd = new DevisProduit;
                                $devisProd->setDevis($devis);
                                $devisProd->setProduit($prod);
                                $devisProd->setDeclinaison($_POST["declinaison-$i-$l-offert"]);
                                $devisProd->setQuantite($_POST["quantite-$i-$l-offert"]);
                                $devisProd->setDeclineAvec($prod->getPrincipeActif());
                                $devisProd->setOffert(true);
                                $em->persist($devisProd);
                            }
                            $l++;
                        }
                    }
                    $i++;
                }

                $em->persist($devis);
                $em->flush();

                $devis->setCode($devis->getCode() . '' . $devis->getId());
                $coden = 'BDC-' . $devis->getCode() . '-' . $devis->getDate()->format('Y') . '-' . $devis->getId();
                $devis->setCode($coden);

                $em->flush();

                $session->set('id_devis', $devis->getId());
                return $this->redirectToRoute('front_signature_devis');
            }

            $session->set('listeProduits', []);

            $donnees = $em->getRepository('App:Produit')->findBy([], ['id' => 'desc'], 6);

            $listeProduits = [];

            foreach ($donnees as $donne) {
                array_push($listeProduits, $donne->getId());
            }

            $session->set('listeProduits', $listeProduits);

            $panier = $session->get('panier', []);
            $total = 0;
            foreach ($panier as $i => $dec) {

                foreach ($dec as $quantite) {
                    $total += $quantite;
                }
            }

            if ($client->getGammeClient()) {
                if (!$client->getGammeClient()->getToutLesProduits()) {
                    $game = true;
                } else {
                    $game = false;
                }
            } else {
                $game = null;
            }


            $gammeList = [
                "Tabac" => $gammeRepository->findOneBy(['nom' => "Tabac"]) ? $gammeRepository->findOneBy(['nom' => "Tabac"])->getId() : null,
                "Menthe" => $gammeRepository->findOneBy(['nom' => "Menthe"]) ? $gammeRepository->findOneBy(['nom' => "Menthe"])->getId() : null,
                "Plaisir" => "plaisir",
                "Gourmand" => $gammeRepository->findOneBy(['nom' => "Gourmand"]) ? $gammeRepository->findOneBy(['nom' => "Gourmand"])->getId() : null,
                "Fruit" => $gammeRepository->findOneBy(['nom' => "Fruit"]) ? $gammeRepository->findOneBy(['nom' => "Fruit"])->getId() : null,
                "Constellation" => "constellation", 
                "Green Leaf" => "greenLeaf",
                "Salty dog" => "saltyDog",
                "Yzy cities spring" => "spring",
                "Yzy cities summer" => "summer",
                "New CBD sativap" => "sativap",
                "Yzy CBD" => "cbdYzy",
                "Absolu" => "absolu",
                "Booster" => $gammeRepository->findOneBy(['nom' => "Booster"]) ? $gammeRepository->findOneBy(['nom' => "Booster"])->getId() : null,
                "Base" => $gammeRepository->findOneBy(['nom' => "Base"]) ? $gammeRepository->findOneBy(['nom' => "Base"])->getId() : null,
                "Coolex" => "coolex",
                "Coolex Oil" => "coolexOil",
                "Origine Hemp" => "origineHemp",
                "Origine e-liquide" => "origineEliquide",
                "Tech + " => "tech"
            ];

            return $this->render('front/calcul_generation_devis.html.twig', [
                'client' => $client,
                'produits' => $donnees,
                'total' => $total,
                'all' => $all,
                'game' => $game,
                'commande' => ($devisRepo->findBy(['client' => $client, 'valider' => true])) ? true : false,
                'declinaisons' => $principeRepo->findAll(),
                'nombreDeProduits' => count($produitRepo->findAll()), 
                'gammes' => $gammeList,
                'types' => $typeRepo->findAll(),
                'marqueBlanches' => $mbRepo->findBy(['client' => $client])

            ]);
        } else {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès a cette page');
        }
    }

    public function recuperationProduit(Request $request, ClientRepository $clientRepository, SerializerInterface $serializer, NormalizerInterface $normalizer, MarqueBlancheRepository $marqueBlancheRepository, ProduitRepository $produitRepo, SessionInterface $session, EntityManagerInterface $em)
    {
        if ($request->isXMLHttpRequest()) {
            
            $client = $clientRepository->find($session->get('id_clt'));
            $typeDeClient = $client->getTypeDeClient();

            $marqueBlanches = $marqueBlancheRepository->findBy(['client' => $client]);

            $exist = $session->get('listeProduits');

            $data = $produitRepo->findByThree($exist);

            if ($data) {
                foreach ($data as $da) {
                    array_push($exist, $da->getId());
                }

                //id,declinaison, image, reference, PrincipeActif, tarif

                $dataFinal = [];
                foreach($data as $dat)
                {
                    
                    $infoProduit = [];

                    $infoProduit['id'] = $dat->getId();
                    $infoProduit['reference'] = $dat->getReference();
                    $infoProduit['imageName'] = $dat->getImageName();
                    $infoProduit['nom'] = $dat->getNom();

                   
                    $infoProduit['principeActif'] = $normalizer->normalize($dat->getPrincipeActif(), '', ['groups' => 'principeActif']);
                    $infoProduit['declinaison'] = $dat->getDeclinaison();

                    $tarif = $dat->getTarif();

                    $tarifDeclinaison = [];

                    foreach($marqueBlanches as $mb)
                    {

                        if( $dat && $mb->getProduit() && $dat->getId() == $mb->getProduit()->getId())
                        {
                            $tarif = $mb->getTarif();

                            if(!$tarif->getMemeTarif())
                            {
                                foreach($tarif->getPrixDeclinaisons() as $prixDecl)
                                {
                                    if($prixDecl->getActif() && $prixDecl->getTypeDeClient() == $typeDeClient)
                                    {

                                        $tarifDeclinaison[$prixDecl->getDeclinaison()] = $prixDecl->getPrix();
                                    }
                                }
                            }
                        }
                    }

                    $infoProduit['tarif'] = $normalizer->normalize($tarif, '', ['groups' => 'tarif'] );

                   

                    if($tarif && !$tarif->getMemeTarif())
                    {
                        if( empty($tarifDeclinaison))
                        {
                            foreach($tarif->getPrixDeclinaisons() as $prixDecl)
                            {
                                if($prixDecl->getActif() && $prixDecl->getTypeDeClient() == $typeDeClient)
                                {

                                    $tarifDeclinaison[$prixDecl->getDeclinaison()] = $prixDecl->getPrix();
                                }
                            }
                        }
                    }

                    $infoProduit['typeClient'] = $typeDeClient->getNom();
                    $infoProduit['prixDeclinaison'] = $tarifDeclinaison;

                    array_push($dataFinal, $infoProduit);
                }

              

                $session->set('listeProduits', $exist);
                
                return new JsonResponse(json_encode($dataFinal), 200, [], true);
               
                
            } else {
                return new JsonResponse(['fin' => true, 200]);
            }
        }
    }

    public function filtre(Request $request, MarqueRepository $marqueBlancheRepository, ClientRepository $clientRepository, SessionInterface $session, NormalizerInterface $normalizer, ProduitRepository $produitRepo, SerializerInterface $serialize)
    {
        if ($request->isXMLHttpRequest()) {

            $client = $clientRepository->find($session->get('id_clt'));
            $typeDeClient = $client->getTypeDeClient();

            $marqueBlanches = $marqueBlancheRepository->findBy(['client' => $client]);

            $value = $request->request->get('id');
            
            $data = $produitRepo->findBy( ["gamme" => $value ]);

            if(empty($data))
            {
                $data = $produitRepo->findBy(["$value" => true]);
            }

            $dataFinal = [];
            foreach($data as $dat)
            {
                
                $infoProduit = [];

                $infoProduit['id'] = $dat->getId();
                $infoProduit['reference'] = $dat->getReference();
                $infoProduit['imageName'] = $dat->getImageName();
                $infoProduit['nom'] = $dat->getNom();

               
                $infoProduit['principeActif'] = $normalizer->normalize($dat->getPrincipeActif(), '', ['groups' => 'principeActif']);
                $infoProduit['declinaison'] = $dat->getDeclinaison();

                $tarif = $dat->getTarif();

                $tarifDeclinaison = [];

                foreach($marqueBlanches as $mb)
                {

                    if($dat->getId() == $mb->getProduit()->getId())
                    {
                        $tarif = $mb->getTarif();

                        if(!$tarif->getMemeTarif())
                        {
                            foreach($tarif->getPrixDeclinaisons() as $prixDecl)
                            {
                                if($prixDecl->getActif() && $prixDecl->getTypeDeClient() == $typeDeClient)
                                {

                                    $tarifDeclinaison[$prixDecl->getDeclinaison()] = $prixDecl->getPrix();
                                }
                            }
                        }
                    }
                }

                $infoProduit['tarif'] = $normalizer->normalize($tarif, '', ['groups' => 'tarif'] );

               

                if($tarif && !$tarif->getMemeTarif())
                {
                    if( empty($tarifDeclinaison))
                    {
                        foreach($tarif->getPrixDeclinaisons() as $prixDecl)
                        {
                            if($prixDecl->getActif() && $prixDecl->getTypeDeClient() == $typeDeClient)
                            {

                                $tarifDeclinaison[$prixDecl->getDeclinaison()] = $prixDecl->getPrix();
                            }
                        }
                    }
                }

                $infoProduit['typeClient'] = $typeDeClient->getNom();
                $infoProduit['prixDeclinaison'] = $tarifDeclinaison;

                array_push($dataFinal, $infoProduit);
            }
            
            return new JsonResponse(json_encode($dataFinal), 200, [], true);
        }
    }

    public function chercher(Request $request, MarqueBlancheRepository $marqueBlancheRepository, ClientRepository $clientRepository, SessionInterface $session, NormalizerInterface $normalizer, EntityManagerInterface $em, SerializerInterface $serializer, ProduitRepository $produitRepo)
    {
        if ($request->isXmlHttpRequest()) {

            $value = $request->request->get('val');

            $data = $produitRepo->findByString($value);

            $client = $clientRepository->find($session->get('id_clt'));
            $typeDeClient = $client->getTypeDeClient();

            $marqueBlanches = $marqueBlancheRepository->findBy(['client' => $client]);


            if( empty($data))
            {
                $data = $produitRepo->findByRefs($value);
                
            }

            
            $dataFinal = [];
            foreach($data as $dat)
            {
                
                $infoProduit = [];

                $infoProduit['id'] = $dat->getId();
                $infoProduit['reference'] = $dat->getReference();
                $infoProduit['imageName'] = $dat->getImageName();
                $infoProduit['nom'] = $dat->getNom();

               
                $infoProduit['principeActif'] = $normalizer->normalize($dat->getPrincipeActif(), '', ['groups' => 'principeActif']);
                $infoProduit['declinaison'] = $dat->getDeclinaison();

                $tarif = $dat->getTarif();

                $tarifDeclinaison = [];

                foreach($marqueBlanches as $mb)
                {

                    if($dat->getId() == $mb->getProduit()->getId())
                    {
                        $tarif = $mb->getTarif();

                        if(!$tarif->getMemeTarif())
                        {
                            foreach($tarif->getPrixDeclinaisons() as $prixDecl)
                            {
                                if($prixDecl->getActif() && $prixDecl->getTypeDeClient() == $typeDeClient)
                                {

                                    $tarifDeclinaison[$prixDecl->getDeclinaison()] = $prixDecl->getPrix();
                                }
                            }
                        }
                    }
                }

                $infoProduit['tarif'] = $normalizer->normalize($tarif, '', ['groups' => 'tarif'] );

               

                if($tarif && !$tarif->getMemeTarif())
                {
                    if( empty($tarifDeclinaison))
                    {
                        foreach($tarif->getPrixDeclinaisons() as $prixDecl)
                        {
                            if($prixDecl->getActif() && $prixDecl->getTypeDeClient() == $typeDeClient)
                            {

                                $tarifDeclinaison[$prixDecl->getDeclinaison()] = $prixDecl->getPrix();
                            }
                        }
                    }
                }

                $infoProduit['typeClient'] = $typeDeClient->getNom();
                $infoProduit['prixDeclinaison'] = $tarifDeclinaison;

                array_push($dataFinal, $infoProduit);
            }
            
            return new JsonResponse(json_encode($dataFinal), 200, [], true);

        }
    }

    public function trouver(Request $request, ClientRepository $clientRepository, SessionInterface $session, MarqueBlancheRepository $marqueBlancheRepository, NormalizerInterface $normalizer, EntityManagerInterface $em, SerializerInterface $serializer, ProduitRepository $produitRepo)
    {
        if ($request->isXmlHttpRequest()) {

            $dat = $produitRepo->find($request->request->get('val'));


            $dataFinal = [];

            
            $client = $clientRepository->find($session->get('id_clt'));
            $typeDeClient = $client->getTypeDeClient();

            $marqueBlanches = $marqueBlancheRepository->findBy(['client' => $client]);

            $infoProduit = [];

            $infoProduit['id'] = $dat->getId();
            $infoProduit['reference'] = $dat->getReference();
            $infoProduit['imageName'] = $dat->getImageName();
            $infoProduit['nom'] = $dat->getNom();

           
            $infoProduit['principeActif'] = $normalizer->normalize($dat->getPrincipeActif(), '', ['groups' => 'principeActif']);
            $infoProduit['declinaison'] = $dat->getDeclinaison();

            $tarif = $dat->getTarif();

            $tarifDeclinaison = [];

            foreach($marqueBlanches as $mb)
            {

                if($dat->getId() == $mb->getProduit()->getId())
                {
                    $tarif = $mb->getTarif();

                    if(!$tarif->getMemeTarif())
                    {
                        foreach($tarif->getPrixDeclinaisons() as $prixDecl)
                        {
                            if($prixDecl->getActif() && $prixDecl->getTypeDeClient() == $typeDeClient)
                            {

                                $tarifDeclinaison[$prixDecl->getDeclinaison()] = $prixDecl->getPrix();
                            }
                        }
                    }
                }
            }

            $infoProduit['tarif'] = $normalizer->normalize($tarif, '', ['groups' => 'tarif'] );

           

            if($tarif && !$tarif->getMemeTarif())
            {
                if( empty($tarifDeclinaison))
                {
                    foreach($tarif->getPrixDeclinaisons() as $prixDecl)
                    {
                        if($prixDecl->getActif() && $prixDecl->getTypeDeClient() == $typeDeClient)
                        {

                            $tarifDeclinaison[$prixDecl->getDeclinaison()] = $prixDecl->getPrix();
                        }
                    }
                }
            }

            $infoProduit['typeClient'] = $typeDeClient->getNom();
            $infoProduit['prixDeclinaison'] = $tarifDeclinaison;

            array_push($dataFinal, $infoProduit);

            return new JsonResponse(json_encode($dataFinal), 200, [], true);
            
        }
    }

    public function detail(Client $client, $all, Request $request, MarqueBlancheRepository $mbRepo, GammeRepository $gammeRepo,  TypeDeClientRepository $typeRepo, SessionInterface $session, ProduitRepository $produitRepo)
    {

        $panier = $session->get('panier', []);

        $commande = [];
        $offert =  0;
        $i = 0;

        foreach ($panier as $produit => $value) {
            $prod = $produitRepo->find($produit);

            $commande[$i]['produit'] = $prod;

            $commande[$i]['declinaison'] = $value;

            $commande[$i]['offert'] = false;

            if (str_contains($prod->getContenant()->getTaille(), "50")) {
                foreach ($value as $val) {

                    $offert += $val;
                }
            }
            $i++;
        }

        if ($offert > 0) {
           
            $booster = $produitRepo->getBooster();
            $boostDecl = [];

            foreach ($booster->getDeclinaison() as $bDecl) {
                if( $bDecl == "19,9mg")
                {
                    $boostDecl[$bDecl] = $offert;
                }
            }

            $commande[$i]['produit'] = $booster;
            $commande[$i]['declinaison'] = $boostDecl;
            $commande[$i]['offert'] = true;
        }

        return $this->render('front/detailPanier.html.twig', [
            'commandes' => $commande,
            'client' => $client,
            'all' => ($all) ? $all : null,
            'types' => $typeRepo->findAll(),
            'marqueBlanches' => $mbRepo->findBy(['client' => $client])
        ]);
    }
    public function add(SessionInterface $session, MarqueBlancheRepository $mbRepo, ClientRepository $clientRepository, Request $request, TypeDeClientRepository $typeRepo, ProduitRepository $produitRepo, GammeClientRepository $GCrepo, ClientRepository $clientRepo)
    {
        //dd($request->request->all());

        $produit = $produitRepo->find($request->request->get('produit'));

        $client = $clientRepository->find($session->get('id_clt'));

        if ($request->isXmlHttpRequest()) {

            $panier = $session->get('panier', []);
            $declinaison = [];

            if ($produit->getDeclinaison()) {
                foreach ($produit->getDeclinaison() as $decl) {
                    $dec = str_replace(',', '', $decl);
                    if ($request->request->get($dec)) {

                        $declinaison[$decl] = $_POST["quantite-$dec"];
                    }
                }
            }
            else
            {
                $declinaison["sans"] = $request->request->get("quantiteSansDecl");   
            }



            if (!empty($declinaison)) {

                $panier[$produit->getId()] = $declinaison;

                $session->set('panier', $panier);


                $panier = $session->get('panier', []);
                $total = 0;
                foreach ($panier as $i => $dec) {

                    foreach ($dec as $quantite) {
                        $total += $quantite;
                    }
                }

                return $this->json(['success' => "Produit ajouté avec succès", 'panier' => $total]);
            } else {
                
                return $this->json(["error" => 'Vous devez au moins séléctionner un déclinaison' ]);
            }
        }


        
/*
        return $this->render('front/detailProduit.html.twig', [
            'produit' => $produit,
            'produitClient' => ($client->getGammeClient()) ? $GCrepo->findOneBy(['client' => $client])->getUnitGammeClientProduits() : null,
            'all' => ($all) ? $all : null,
            'client' => $client,
            'panier' => $total,
            'types' => $typeRepo->findAll(),
            'marqueBlanches' => $mbRepo->findBy(['client' => $client])
        ]);*/
    }

    public function ajaxAddCart(SessionInterface $session, Request $request, ProduitRepository $produitRepo, GammeClientRepository $GCrepo, ClientRepository $clientRepo)
    {

        if ($request->isXMLHttpRequest()) {

            $produit = $produitRepo->find($request->request->get('idProduit'));
            $client = $clientRepo->find($request->request->get('idClient'));

            $panier = $session->get('panier', []);
            $declinaison = [];

            if ($produit->getDeclinaison()) {
                foreach ($produit->getDeclinaison() as $decl) {
                    foreach ($request->request->get('quantiteDecl') as $key => $quantiteDecl) {
                        $dec = str_replace(',', '', $decl);
                        if ($dec == $key) {

                            $declinaison[$decl] = $quantiteDecl;
                        }
                    }
                }
            } else {
                $declinaison['sans'] = $request->request->get('sans');
            }


            if (!empty($declinaison)) {

                $panier[$produit->getId()] = $declinaison;

                $session->set('panier', $panier);
            }


            $total = 0;
            foreach ($panier as $i => $dec) {

                foreach ($dec as $quantite) {
                    $total += $quantite;
                }
            }

            return $this->json(
                [
                    'code' => 200,
                    'produit' => $produit->getId(),
                    'panier' => $total,
                ],
                200
            );
        }
    }

    public function removePanier($id, $idC, $decl, ClientRepository $clientRepo, SessionInterface $session)
    {
        $panier = $session->get('panier', []);

        if (!empty($panier[$id])) {
            foreach ($panier[$id] as $decli => $quantite) {
                if ($decli == $decl) {
                    unset($panier[$id][$decli]);
                }
            }
        }

        $session->set('panier', $panier);
        $this->addFlash('successSupp', 'Produit supprimer du panier avec succès');
        return $this->redirectToRoute('front_detail_panier', ['id' => $idC]);
    }

    public function signature(Request $request, EntityManagerInterface $em, DevisRepository $devisRepo, Mailer $mailer)
    {
        $session = $request->getSession();

        if ($session->has('id_clt') && $session->has('id_devis')) {
            $devis = $devisRepo->find((int) $session->get('id_devis'));

            if (!$session->has('sendonline')) {
                $mailer->send($devis, 'online', true);
                $session->set('sendonline', true);
            }

            return $this->render('front/signature_request.html.twig', [
                'devis' => $devis,

            ]);
        } else {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès a cette page');
        }
    }

    public function signatureMailSend(Request $request, EntityManagerInterface $em, Mailer $mailer, DevisRepository $devisRepo)
    {
        $session = $request->getSession();
        if ($session->has('id_clt') && $session->has('id_devis')) {
            $devis = $devisRepo->find($session->get('id_devis'));
            if (!$session->has('send')) {
                $mailer->send($devis, 'manual');
                $session->set('send', true);
            }
            return $this->render('front/signature_request.html.twig', [
                'devis' => $devis,
                'email_send' => true,
            ]);
        } else {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès a cette page');
        }
    }

    public function code($token, Request $request, EntityManagerInterface $em, MarqueBlancheRepository $mbRepo, TypeDeClientRepository $typeRepo, DevisRepository $devisRepo,  Mailer $mailer, ProduitRepository $produit, CodeNumber $codeNumber)
    {

        $session = $request->getSession();

        if ($session->has('id_clt') && $session->has('id_devis') or $token) {

            if ($token) {
                $devis = $devisRepo->findOneBy(['token' => $token]);
            } else {
                $devis = $devisRepo->find((int) $session->get('id_devis'));
            }


            if ($request->isMethod('post')) {

                if ($_POST['email'] == $devis->getClient()->getEmail()) {

                    $code = $codeNumber->Genere_number(6);

                    $devis->setCodeDeValidation($code);

                    $mailer->sendValidationCode($devis, $code);

                    $em->flush();

                    $this->addFlash('success', 'Nous venons de vous adresser votre code de validation par e-mail');

                    if ($token) {
                        return  $this->redirectToRoute('front_signature_devis_online', ['token' => $token]);
                    } else {
                        return  $this->redirectToRoute('front_signature_devis_online');
                    }
                } else {

                    $this->addFlash('alert', 'L’adresse e-mail que vous avez saisie est incorrecte');

                    return  $this->redirectToRoute('front_send_code');
                }
            }

            return $this->render('front/signature_online.html.twig', [
                'devis' => $devis,
                'produit' => $produit->findAll(),
                'code' => false,
                'token' => ($token) ? $token : null,
                'types' => $typeRepo->findAll(),
                'marqueBlanches' => $mbRepo->findBy(['client' => $devis->getClient()])
            ]);
        } else {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès a cette page');
        }
    }

    public function signatureOnline($token, Request $request, MarqueBlancheRepository $mbRepo, EntityManagerInterface $em, TypeDeClientRepository $typeRepo, DevisRepository $devisRepo,  Mailer $mailer, ProduitRepository $produit)
    {
        $session = $request->getSession();

        if ($session->has('id_clt') && $session->has('id_devis') or $token) {

            if ($token) {
                $devis = $devisRepo->findOneBy(['token' => $token]);
            } else {
                $devis = $devisRepo->find((int) $session->get('id_devis'));
            }


            if ($request->isMethod('post')) {

                if ($_POST['number'] == $devis->getCodeDeValidation()) {

                    $devis->setSigneParClient(true);
                    $devis->setDateSignature(new \DateTime());

                    $devis->setValider(true);
                    $devis->setStatus("Devis valider");

                    $bonDeCommande = new BonDeCommande;
                    $bonDeCommande->setCode('tmp');
                    $bonDeCommande->setDevis($devis);
                    $bonDeCommande->setClient($devis->getClient());
                    $bonDeCommande->setNombreProduit($devis->getNombreProduit());
                    $bonDeCommande->setResteAlivrer($devis->getNombreProduit());
                    $bonDeCommande->setResteAPayer($devis->getTotalTtc());
                    $bonDeCommande->setTotalTtc($devis->getTotalTtc());
                    $bonDeCommande->setStatus("En attente de traitement");
                    $bonDeCommande->setEnvoyer(true);
                    $em->persist($bonDeCommande);

                    $code = $devis->getCode();
                    $bonDeCommande->setCode($code);

                    $em->flush();


                    if (!$session->has('sendonlinemade')) {

                        $mailer->send($devis, 'onlinemade');
                        $session->set('sendonlinemade', true);
                    }


                    $session->set('signed', true);

                    if ($token) {
                        return  $this->redirectToRoute('front_signature_devis_online_made', ['token' => $token]);
                    } else {
                        return  $this->redirectToRoute('front_signature_devis_online_made');
                    }
                } else {
                    $this->addFlash('alert', 'Le code que vous avez saisie est incorrect');
                }
            }

            return $this->render('front/signature_online.html.twig', [
                'devis' => $devis,
                'produit' => $produit->findAll(),
                'code' => true,
                'token' => ($token) ? $token : null,
                'types' => $typeRepo->findAll(),
                'marqueBlanches' => $mbRepo->findBy(['client' => $devis->getClient()])
            ]);
        } else {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès a cette page');
        }
    }

    public function signatureOnlineMade($token, Request $request, MarqueBlancheRepository $mbRepo, EntityManagerInterface $em, DevisRepository $devisRepo)
    {
        $session = $request->getSession();
        if ($session->has('id_clt') && $session->has('id_devis') && $session->has('signed') or $token) {

            if ($token) {
                $devis = $devisRepo->findOneBy(['token' => $token]);
            } else {
                $devis = $devisRepo->find((int) $session->get('id_devis'));
            }

            return $this->render('front/made_signature_online.html.twig', [
                'devis' => $devis,

            ]);
        } else {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès a cette page');
        }
    }

    public function fetchPrestashopOrders(Request $request,BonDeCommandeRepository $bdRepo,ShopRepository $shopRepository, InvoiceProcessing $invoiceProcessing, MarqueBlancheRepository $marqueBlancheRepo, AeromaApi $aeromaApi,Useful $useful, TypeDeClientRepository $typeCli, DevisProduitRepository $devisProduitRepo, ProduitRepository $produitRepo, DevisRepository $devisRepo, ClientRepository $clientRepo, BonDeCommandeRepository $bonDeCommandeRepo, EntityManagerInterface $em)
    {
        
        /*$commandeNonPaye = $bdRepo->fetchPrestaOrder();
        
        if( !empty($commandeNonPaye)){
            foreach($commandeNonPaye as $nonPaye){
                $shop = $nonPaye->getDevis()->getShop();
                $devis = $nonPaye->getDevis();
    
                $idShop =  $nonPaye->getDevis()->getShopOrderId();
    
                if( $shop == "aeroma_prostore"){
                    $prestaOrder =$aeromaApi->findOrder($idShop, true, [], false);
                }elseif($shop == "yzy_vape"){
                    $prestaOrder =$aeromaApi->findOrder($idShop, false, [true, false], false);
                    //$prestaOrder =$aeromaApi->findOrder($idShop, false, [false, true], false);

                }else{
                    $prestaOrder =$aeromaApi->findOrder($idShop, false, [], true);
                }
                //dd($shop);
                if($prestaOrder->order->current_state == 2){
                    
                    $devis->setPayerParCarte(true);
                    $nonPaye->setStatus("En attente de livraison");
                    $devis->setMontantPayerParCarte($prestaOrder->order->total_paid_real);
                    $nonPaye->setToutEstPayer(true);
                    $nonPaye->setResteAPayer($prestaOrder->order->total_paid_tax_incl - $prestaOrder->order->total_paid_real);
                    $nonPaye->setDateToutPaiement( new DateTime($prestaOrder->order->invoice_date));
    
                    $invoice = new FactureCommande();
                    $invoice->setBonDeCommande($nonPaye);
                    $invoice->setClient($nonPaye->getClient());
                    $invoice->setDevis($nonPaye->getDevis());
                    $invoice->setTitre('Facture-final');
                    $invoice->setSoldeFinal(true);
                    $invoice->setDate(new DateTime($prestaOrder->order->invoice_date));
                    $invoice->setDatePaiement(new DateTime($prestaOrder->order->invoice_date));
    
                    $alreadyPaid = $nonPaye->getTotalTtc() - $nonPaye->getResteAPayer();
                    $invoice->setDejaPayer($alreadyPaid);
    
                    $ttc = $devis->getTotalTtc();
    
                    $amountToBePaid = $ttc - $alreadyPaid;
    
                    $invoice->setTotal($devis->getMontant());
                    $invoice->setTotalHt($devis->getTotalHt());
                    $invoice->setTva($devis->getTaxe());
                    $invoice->setTotalTtc($ttc);
                    $invoice->setEstPayer(true);
                    $invoice->setBalance($amountToBePaid);
                    $invoice->setMontantAPayer($ttc);
                    $invoice->setMontantPayer($prestaOrder->order->total_paid_real);
    
                    $invoice->setNumero($nonPaye->getCode());
                    $em->persist($invoice);
                    $em->flush();
    
                    $useful->createInvoice($nonPaye,$invoice);
                }
            }
        }*/
        $this->fetchAeromaProstore($request,$bdRepo, $invoiceProcessing, $aeromaApi,$useful, $typeCli, $devisProduitRepo, $produitRepo, $devisRepo, $clientRepo, $bonDeCommandeRepo, $em);

        //$this->fetchYzy(true, $request,$bdRepo, $marqueBlancheRepo, $aeromaApi,$useful, $typeCli, $devisProduitRepo, $produitRepo, $devisRepo, $clientRepo, $bonDeCommandeRepo, $em);
        $this->fetchYzy(false, $request,$bdRepo, $shopRepository, $marqueBlancheRepo, $aeromaApi,$useful, $typeCli, $devisProduitRepo, $produitRepo, $devisRepo, $clientRepo, $bonDeCommandeRepo, $em);

        $this->fetchGrossisteGreendot($request,$bdRepo, $aeromaApi,$useful, $typeCli, $devisProduitRepo, $produitRepo, $devisRepo, $clientRepo, $bonDeCommandeRepo, $em);

        return $this->redirectToRoute('front_index');
    }


    public function fetchAeromaProstore(Request $request,BonDeCommandeRepository $bdRepo, InvoiceProcessing $invoiceProcessing, AeromaApi $aeromaApi,Useful $useful, TypeDeClientRepository $typeCli, DevisProduitRepository $devisProduitRepo, ProduitRepository $produitRepo, DevisRepository $devisRepo, ClientRepository $clientRepo, BonDeCommandeRepository $bonDeCommandeRepo, EntityManagerInterface $em){

        $typeClient = $typeCli->find(1);
        $historique = [];
        //aeroma-prostore

        $fetchOrder = false;

        if ($devisRepo->lastProstore()) {
            $lastProstore = $devisRepo->lastProstore()->getShopOrderId();
            if ($aeromaApi->findOrder($lastProstore + 1, true, [], false)) {
                $fetchOrder = true;
            }
        } else {
            $lastProstore = 17;
            $fetchOrder = true;
        }

        $countTest = [];
        if ($fetchOrder) {
            $historique = $aeromaApi->fetchOrderHistory(true, [], false);
            $commandes = $aeromaApi->getOrders(true, [] ,false);

            foreach ($commandes as $command) {

                if ($command->order->id > $lastProstore) {

                    if($command->order->current_state == "" or (!empty($historique) and  $historique[$command->order->id] and !in_array(6, $historique[$command->order->id]))){
                        $commandeExiste = $devisRepo->findOneBy(['code' => $command->order->reference, 'shop' => 'aeroma_prostore']);
    
                        if (!$commandeExiste) {
    
                            $devis = new Devis;
                            $devis->setShopOrderId($command->order->id);
    
                            $nombreBpureKdo = 0;
    
                            $client = $aeromaApi->findClient($command->order->id_customer, []);
    
                            $clientExist = $clientRepo->findOneBy(['email' => $client->customer->email]);
    
                            if ($clientExist) {
                                $devis->setClient($clientExist);
                            } else {
                                $cli = new Client();
    
                                $cli->setLastName($client->customer->lastname);
                                $cli->setFirstName($client->customer->firstname);
                                $cli->setEmail($client->customer->email);
                                $cli->setRaisonSocial($client->customer->company);
                                $cli->setIdPrestashop($client->customer->id);
                                $cli->setTypeDeClient($typeClient);
                                $em->persist($cli);
                                $em->flush();
                                $devis->setClient($cli);
                            }
    
                            $devis->setDate(new DateTime($command->order->date_add));
                            $devis->setCode($command->order->reference);
                            $devis->setMontant($command->order->total_products);
                            $devis->setFraisExpedition($command->order->total_shipping);
                            $devis->setValider(true);
                            $devis->setShop('aeroma_prostore');
                            $devis->setSigneParClient(true);
                            $taxe = $command->order->total_products * 0.2;
                            $devis->setTaxe($taxe);
                            $devis->setDateSignature(new DateTime($command->order->date_add));
                            $devis->setTotalTtc($command->order->total_paid_tax_incl);
                            $devis->setTotalHt($command->order->total_paid_tax_excl);
                            $devis->setStatus("Devis valider");
                            $devis->setSigneParClient(true);
                            $devis->setDateValidation(new DateTime($command->order->date_upd));
    
                            $total = 0;
                            foreach ($command->order->associations->order_rows as $produit) {
                                if ($produit->product_reference) {
    
                                    $reference = $aeromaApi->findProduct($produit->product_id, true, false, false)->product->reference;
    
                                    $refProduitCommander = $produit->product_reference;

                                    $prod = $produitRepo->findOneBy(['reference' => $reference]);
    
                                    //$exist = $devisProduitRepo->exist($prod->getId(), $devis->getId());
                                    //manampy bpure ra chuby;

                                     
                                    if($prod->getType() && $prod->getType()  == 'Chubby')
                                    {
                                        $nombreBpureKdo += $produit->product_quantity;
                                    }
    
                                    $devisProduit = new DevisProduit;
                                    $devisProduit->setProduit($prod);
                                    $devisProduit->setDevis($devis);
                                    $devisProduit->setQuantite($produit->product_quantity);
                                    $devisProduit->setDeclineAvec($prod->getPrincipeActif());
                                    $devisProduit->setPrix($produit->product_price);
    
                                   
                                    $declinaison = null;
                                    if ($prod->getPrincipeActif()) {
    
                                        $nbDeclinaison = count($prod->getDeclinaison());
    
                                        $nbReferenceDeclinaison = null;

                                        if($prod->getReferenceDeclinaison())
                                        {

                                            $nbReferenceDeclinaison = count ($prod->getReferenceDeclinaison());
                                            
                                            if($nbReferenceDeclinaison > 1)
                                            {
                                                foreach($prod->getReferenceDeclinaison() as $decl => $refDecl)
                                                {
                                                    if( strtolower($refProduitCommander) ==  strtolower($refDecl))
                                                    {
                                                        $declinaison = $decl;
                                                    }
                                                }
                                            }

                                        }

                                        if ($nbDeclinaison > 1 && is_null($declinaison)) 
                                        {

                                            $prestaRef = str_split($produit->product_reference);
                                            $boRef = str_split($prod->getReference());
                                            
                                            if(count($prestaRef) > count($boRef)){

                                                $result = implode('', array_diff_assoc($prestaRef, $boRef));
                                                
                                                foreach($prod->getDeclinaison() as $decl){
                                                    
                                                    if(intval($decl) == intval($result)){
                                                        
                                                        $declinaison = $decl;
                                                        array_push($countTest, $decl);
                                                    }
                                                }

                                            }elseif(count($prestaRef) == count($boRef)){
                                                $declinaison = '00mg';
                                            }

                                            if(!$declinaison)
                                            {
                                                $nom = $produit->product_name;

                                                if($nom)
                                                {

                                                    foreach($prod->getDeclinaison() as $dec)
                                                    {
                                                        if(strpos($nom, $dec) !== false )
                                                        {
                                                            $declinaison = $dec;
                                                        }
                                                    }
                                                }
                                            }
                                        
                                        } 
                                        elseif($nbDeclinaison == 1 && is_null($declinaison))
                                        {
                                            $declinaison = $prod->getDeclinaison()[0];
                                        }
                                    }
    
                                    $devisProduit->setDeclinaison($declinaison);
    
                                    $em->persist($devisProduit);
                                    $em->flush();
    
                                    $total += $produit->product_quantity;
                                }
                            }

                            if($nombreBpureKdo > 0){

                                $Kdo = $produitRepo->getBooster("Bpure", 6);    
                    
                                if($Kdo)
                                {
                                    $total += $nombreBpureKdo;
                                    
                                    foreach ($Kdo->getDeclinaison() as $bDecl) {
    
                                        if($bDecl == "19,9mg")
                                        {
                                            $produitKdo = new DevisProduit;
                                            $produitKdo->setProduit($Kdo);
                                            $produitKdo->setDevis($devis);
                                            $produitKdo->setQuantite($nombreBpureKdo);
                                            $produitKdo->setDeclineAvec($Kdo->getPrincipeActif());
                                            $produitKdo->setPrix(null);
                                            $produitKdo->setOffert(true);
                                            $produitKdo->setDeclinaison($Kdo->getDeclinaison()[0]);
            
                                            $em->persist($produitKdo);
                                            $em->flush();
                                        }
                                    }
                                }

                            }


                            $devis->setNombreProduit($total);
                            $bonDeCommande = new BonDeCommande;
                            $bonDeCommande->setDevis($devis);
                            $bonDeCommande->setDate(new DateTime($command->order->date_add));
                            $bonDeCommande->setClient($devis->getClient());
                            $bonDeCommande->setCode($command->order->reference);
                            $bonDeCommande->setTotalTtc($command->order->total_paid_tax_incl);
                            
                            $bonDeCommande->setNombreProduit($total);
                            $bonDeCommande->setResteAlivrer($total);
                            
                            $invoice = null;
                            $datePaiement = null;

                            $nextFactNumber = null;
                            if(in_array(2, $historique[$command->order->id])){

                                $devis->setPayerParCarte(true);
                                $bonDeCommande->setStatus("En attente de livraison");
                                $devis->setMontantPayerParCarte($command->order->total_paid_real);
                                $bonDeCommande->setToutEstPayer(true);
                                $bonDeCommande->setResteAPayer($command->order->total_paid_tax_incl - $command->order->total_paid_real);
                                $bonDeCommande->setDateToutPaiement( new DateTime($command->order->invoice_date));

                                $nextFactNumber = $invoiceProcessing->incrementInvoice();

                                $invoice = new FactureCommande();
                                $invoice->setBonDeCommande($bonDeCommande);
                                $invoice->setClient($bonDeCommande->getClient());
                                $invoice->setDevis($bonDeCommande->getDevis());
                                $invoice->setTitre('Facture-final');
                                $invoice->setSoldeFinal(true);
                                $invoice->setDate(new DateTime($command->order->invoice_date));
                                $invoice->setDatePaiement(new DateTime($command->order->invoice_date));
                                $invoice->setIncrement($nextFactNumber);

                                $alreadyPaid = $bonDeCommande->getTotalTtc() - $bonDeCommande->getResteAPayer();
                                $invoice->setDejaPayer($alreadyPaid);
            
                                $ttc = $devis->getTotalTtc();

                                $amountToBePaid = $ttc - $alreadyPaid;

                                $invoice->setTotal($devis->getMontant());
                                $invoice->setTotalHt($devis->getTotalHt());
                                $invoice->setTva($devis->getTaxe());
                                $invoice->setTotalTtc($ttc);
                                $invoice->setEstPayer(true);
                                $invoice->setBalance($amountToBePaid);
                                $invoice->setMontantAPayer($ttc);
                                $invoice->setMontantPayer($command->order->total_paid_real);

                                $datePaiement = new DateTime($command->order->invoice_date);

                                $em->persist($invoice);
                               
                            }else{
                                $bonDeCommande->setStatus("En attente de paiement");
                                $bonDeCommande->setResteAPayer($command->order->total_paid_tax_incl);
                            }
    
                            $em->persist($devis);
                            $em->persist($bonDeCommande);
                            $em->flush();
                            
                            if(in_array(2, $historique[$command->order->id])){
                                
                                $nom =  $datePaiement->format("ymd")."-".$bonDeCommande->getCode()."-";

                                $nom .= str_pad($nextFactNumber, 4, '0', STR_PAD_LEFT);

                                $invoice->setNumero($nom);

                                $em->flush();

                                $useful->createInvoice($bonDeCommande,$invoice);
                            }

                        }
                    }
                }
            }
            $em->flush();
        }
    }


    public function fetchGrossisteGreendot(Request $request,BonDeCommandeRepository $bdRepo, AeromaApi $aeromaApi,Useful $useful, TypeDeClientRepository $typeCli, DevisProduitRepository $devisProduitRepo, ProduitRepository $produitRepo, DevisRepository $devisRepo, ClientRepository $clientRepo, BonDeCommandeRepository $bonDeCommandeRepo, EntityManagerInterface $em){
        $fetchGreendotOrder = false;

        $historique = [];

        $typeClient = $typeCli->find(2);
        if ($devisRepo->lastGreendot()) {
            $lastGreendot = $devisRepo->lastGreendot()->getShopOrderId();
            if ($aeromaApi->findOrder($lastGreendot + 1, false, [], true)) {
                $fetchGreendotOrder = true;
            }
        } else {
            $lastGreendot = 6;
            $fetchGreendotOrder = true;
        }

        $countTest = [];
        if ($fetchGreendotOrder) {
            $historique = $aeromaApi->fetchOrderHistory(false, [], true);
            $commandes = $aeromaApi->getOrders(false, [] ,true);

            foreach ($commandes as $command) {

                if ($command->order->id > $lastGreendot) {

                    if($command->order->current_state == "" or (!empty($historique) and  $historique[$command->order->id] and !in_array(6, $historique[$command->order->id]))){
                        $commandeExiste = $devisRepo->findOneBy(['code' => $command->order->reference, 'shop' => 'grossiste_greendot']);
    
                        if (!$commandeExiste) {
    
                            $devis = new Devis;
                            $devis->setShopOrderId($command->order->id);
    
                            $nombreBpureKdo = 0;
    
                            $client = $aeromaApi->findClient($command->order->id_customer,[], true);
    
                            $clientExist = $clientRepo->findOneBy(['email' => $client->customer->email]);

                            //$clientExist = $clientRepo->findOneBy(['email' => "vap-air@orange.fr"]);

                            if ($clientExist) {
                                $devis->setClient($clientExist);
                            } else {
                                $cli = new Client();
                                
                                $cli->setLastName($client->customer->lastname);
                                $cli->setFirstName($client->customer->firstname);
                                $cli->setEmail($client->customer->email);
                                $cli->setRaisonSocial($client->customer->company);
                                //$cli->setIdPrestashop($client->customer->id);
                                $cli->setTypeDeClient($typeClient);

                                /*
                                $cli->setLastName("Vapair");
                                $cli->setFirstName("Vapair");
                                $cli->setEmail("vap-air@orange.fr");
                                $cli->setRaisonSocial("ALTERNA DJC (ALTERNA-CIG)");*/
                                
                                //$cli->setTypeDeClient($typeClient);

                                $em->persist($cli);
                                $em->flush();
                                $devis->setClient($cli);
                            }
    
                            $devis->setDate(new DateTime($command->order->date_add));
                            $devis->setCode($command->order->reference);
                            
                            $devis->setFraisExpedition($command->order->total_shipping);
                            $devis->setValider(true);
                            $devis->setShop('grossiste_greendot');
                            $devis->setSigneParClient(true);
                            
                            
                            $devis->setDateSignature(new DateTime($command->order->date_add));
                            
                            
                            $devis->setStatus("Devis valider");
                            $devis->setSigneParClient(true);
                            $devis->setDateValidation(new DateTime($command->order->date_upd));
    
                            $total = 0;
                            $prixTotalProduit = 0;

                            foreach ($command->order->associations->order_rows as $produit) {
                                if ($produit->product_reference) {
                                    $produitPresta = null;
                                    
                                    $produitPresta = $aeromaApi->findProduct($produit->product_id, false, false, true);
                                    $reference = $produitPresta->product->reference;
                                    
                                    $refProduitCommander = $produit->product_reference;
    
                                    $prod = $produitRepo->findOneBy(['reference' => $reference]);

                                    if( is_null($prod)){
                                        if(str_contains($reference,'CBDOLIS')){
                                            $prod = $produitRepo->findOneBy(['reference' => 'CBDOLISCE']);
                                        }
                                    }
                                    //$exist = $devisProduitRepo->exist($prod->getId(), $devis->getId());
                                    //manampy bpure ra chuby;

                                    /*if($prod->getType() && $prod->getType()  == 'Chubby')
                                    {
                                        $nombreBpureKdo += $produit->product_quantity;
                                    }*/
                                    
                                    $devisProduit = new DevisProduit;
                                    $devisProduit->setProduit($prod);
                                    $devisProduit->setDevis($devis);
                                    $devisProduit->setQuantite($produit->product_quantity);
                                    $devisProduit->setDeclineAvec($prod->getPrincipeActif());


                                    $declinaison = null;
    
                                    if ($prod->getPrincipeActif()) {
    
                                        $nbDeclinaison = count($prod->getDeclinaison());
                                        
                                        $nbReferenceDeclinaison = null;
                                        
                                        if($prod->getReferenceDeclinaison())
                                        {

                                            $nbReferenceDeclinaison = count ($prod->getReferenceDeclinaison());
                                            
                                            if($nbReferenceDeclinaison > 1)
                                            {
                                                foreach($prod->getReferenceDeclinaison() as $decl => $refDecl)
                                                {
                                                    if( strtolower($refProduitCommander) ==  strtolower($refDecl))
                                                    {
                                                        $declinaison = $decl;
                                                    }
                                                }
                                            }

                                        }
    

                                        if ($nbDeclinaison > 1 && is_null($declinaison)) 
                                        {

                                            $prestaRef = str_split($produit->product_reference);
                                            $boRef = str_split($prod->getReference());
                                            
                                            if(count($prestaRef) > count($boRef))
                                            {

                                                $result = implode('', array_diff_assoc($prestaRef, $boRef));
                                                
                                                foreach($prod->getDeclinaison() as $decl)
                                                {
                                                    
                                                    if(intval($decl) == intval($result))
                                                    {
                                                        
                                                        $declinaison = $decl;
                                                        array_push($countTest, $decl);
                                                    }
                                                }

                                            }
                                            elseif(count($prestaRef) == count($boRef))
                                            {
                                                $declinaison = '00mg';
                                            }


                                            if(!$declinaison)
                                            {
                                                $nom = $produit->product_name;

                                                if($nom)
                                                {

                                                    foreach($prod->getDeclinaison() as $dec)
                                                    {
                                                        if(strpos($nom, $dec) !== false )
                                                        {
                                                            $declinaison = $dec;
                                                        }
                                                    }
                                                }
                                            }
                                        
                                        } 
                                        elseif($nbDeclinaison == 1 && is_null($declinaison)) 
                                        {
                                            $declinaison = $prod->getDeclinaison()[0];
                                        }
                                    }
    
                                    $devisProduit->setDeclinaison($declinaison);
    
                                    $vapair = $clientRepo->findOneBy(["email" => "vap-air@orange.fr"]);

                                    $prix = null;
                                    foreach($prod->getMarqueBlanches() as $marqueBlanche)
                                    {
                                        if($marqueBlanche->getClient() == $vapair)
                                        {
                                            $tarif = $marqueBlanche->getTarif();

                                            if(!$tarif->getMemeTarif())
                                            {

                                                if($declinaison)
                                                {
                                                    foreach($tarif->getPrixDeclinaisons() as $prixDecl)
                                                    {
                                                        if($declinaison == $prixDecl->getDeclinaison())
                                                        {
                                                            $prix = $prixDecl->getPrix();
                                                        }
                                                    }
                                                }
                                            }
                                            else
                                            {
                                                $prix = $tarif->getPrixDeReferenceGrossiste();
                                            }
                                        }
                                    }

                                    if(!$prix)
                                    {
                                        $prix = $produit->product_price;
                                    }

                                    $devisProduit->setPrix($prix);
    
                                   
                                    
                                    $em->persist($devisProduit);
                                    $em->flush();
    
                                    $prixTotalProduit += $prix * $produit->product_quantity ;
                                    $total += $produit->product_quantity;
                                }
                            }
/*
                            if($nombreBpureKdo > 0){
                                $Kdo = $produitRepo->findOneBy(['reference' => "Bpure 19'9"]);

                                if(is_null($Kdo)){
                                    $Kdo = $produitRepo->findOneBy(['reference' => "Bpure19'9"]);
                                }
                                $total += $nombreBpureKdo;
                                $produitKdo = new DevisProduit;
                                $produitKdo->setProduit($Kdo);
                                $produitKdo->setDevis($devis);
                                $produitKdo->setQuantite($nombreBpureKdo);
                                $produitKdo->setDeclineAvec($Kdo->getPrincipeActif());
                                $produitKdo->setPrix(null);
                                $produitKdo->setOffert(true);
                                $produitKdo->setDeclinaison($Kdo->getDeclinaison()[0]);

                                $em->persist($produitKdo);
                                $em->flush();

                            }*/

                            $devis->setNombreProduit($total);


                            $devis->setMontant($command->order->total_products);

                            $devis->setMontant($prixTotalProduit);
                            $taxe = $prixTotalProduit * 0.2;
                            $devis->setTaxe(round($taxe, 2));

                            $totalHt = $prixTotalProduit + $command->order->total_shipping;

                            $devis->setTotalHt(round($totalHt, 2));

                            $totalTtc = $totalHt + $taxe;

                            $devis->setTotalTtc(round($totalTtc, 2));


                            $bonDeCommande = new BonDeCommande;
                            $bonDeCommande->setDevis($devis);
                            $bonDeCommande->setDate(new DateTime($command->order->date_add));
                            $bonDeCommande->setClient($devis->getClient());
                            $bonDeCommande->setCode($command->order->reference);
                            $bonDeCommande->setTotalTtc($devis->getTotalTtc());
                            
                            $bonDeCommande->setNombreProduit($total);
                            $bonDeCommande->setResteAlivrer($total);
                            
                            /*if(in_array(2, $historique[$command->order->id])){

                                $devis->setPayerParCarte(true);
                                $bonDeCommande->setStatus("En attente de livraison");
                                $devis->setMontantPayerParCarte($command->order->total_paid_real);
                                $bonDeCommande->setToutEstPayer(true);
                                $bonDeCommande->setResteAPayer($command->order->total_paid_tax_incl - $command->order->total_paid_real);
                                $bonDeCommande->setDateToutPaiement( new DateTime($command->order->invoice_date));

                                $invoice = new FactureCommande();
                                $invoice->setBonDeCommande($bonDeCommande);
                                $invoice->setClient($bonDeCommande->getClient());
                                $invoice->setDevis($bonDeCommande->getDevis());
                                $invoice->setTitre('Facture-final');
                                $invoice->setSoldeFinal(true);
                                $invoice->setDate(new DateTime($command->order->invoice_date));
                                $invoice->setDatePaiement(new DateTime($command->order->invoice_date));

                                $alreadyPaid = $bonDeCommande->getTotalTtc() - $bonDeCommande->getResteAPayer();
                                $invoice->setDejaPayer($alreadyPaid);
            
                                $ttc = $devis->getTotalTtc();

                                $amountToBePaid = $ttc - $alreadyPaid;

                                $invoice->setTotal($devis->getMontant());
                                $invoice->setTotalHt($devis->getTotalHt());
                                $invoice->setTva($devis->getTaxe());
                                $invoice->setTotalTtc($ttc);
                                $invoice->setEstPayer(true);
                                $invoice->setBalance($amountToBePaid);
                                $invoice->setMontantAPayer($ttc);
                                $invoice->setMontantPayer($command->order->total_paid_real);

                                $invoice->setNumero($bonDeCommande->getCode());
                                $em->persist($invoice);
                               
                            }else{*/
                                $bonDeCommande->setStatus("En attente de paiement");
                                $bonDeCommande->setResteAPayer($devis->getTotalTtc());
                            //}
    
                            $em->persist($devis);
                            $em->persist($bonDeCommande);
                            $em->flush();
                            
                            /*if(in_array(2, $historique[$command->order->id])){
                                $useful->createInvoice($bonDeCommande,$invoice);
                            }*/

                        }
                    }
                }
            }
            $em->flush();
        }
    }

    public function fetchYzy($store, Request $request,BonDeCommandeRepository $bdRepo, ShopRepository $shopRepository, MarqueBlancheRepository $marqueBlancheRepo, AeromaApi $aeromaApi,Useful $useful, TypeDeClientRepository $typeCli, DevisProduitRepository $devisProduitRepo, ProduitRepository $produitRepo, DevisRepository $devisRepo, ClientRepository $clientRepo, BonDeCommandeRepository $bonDeCommandeRepo, EntityManagerInterface $em){
        //yzyvape
        
        $typeClient = $typeCli->find(2);
        $historique = [];
        $fetchOrderYzy = false;

       

        if ($devisRepo->lastYzy()) {
            $lastYzy = $devisRepo->lastYzy()->getShopOrderId();

            if($store){
                                
                if ($aeromaApi->findOrder($lastYzy + 1, false, [true, false],false)) {
                    $fetchOrderYzy = true;
                }

            }else{

                if ($aeromaApi->findOrder($lastYzy + 1, false, [false, true],false)) {
                    $fetchOrderYzy = true;
                }

            }
        } else {
            $lastYzy = 41;
            $fetchOrderYzy = true;
        }

        $countTest = [];

        if ($fetchOrderYzy) {
            $commandesYzy = null;
            $izyvapefr = null;
            $historique = $aeromaApi->fetchOrderHistory(false, [true, false], false);
            if($store){
                $commandesYzy = $aeromaApi->getOrders(false, [true, false],false);
            }else{
                //$historique = $aeromaApi->fetchOrderHistory(false, [false, true], false);
                $commandesYzy = $aeromaApi->getOrders(false, [false, true],false);
                $izyvapefr = true;
            }
            
            //dd($commandesYzy, $lastYzy);
            foreach ($commandesYzy as $command) {

                if ($command->order->id > $lastYzy) {
                    
                    if($command->order->current_state == "" or (!empty($historique) and  $historique[$command->order->id] and !in_array(6, $historique[$command->order->id]))){
                    
                        $commandeExiste = $devisRepo->findOneBy(['code' => $command->order->reference, 'shop' => 'yzy_vape']);
    
                        if (!$commandeExiste) {
    
                            $devis = new Devis;
                            $devis->setShopOrderId($command->order->id);

                            $nombreBpureKdo = 0;
                            $boutique = null;
                            $client = null;
                            
                            if($izyvapefr)
                            {
                                $client = $aeromaApi->findClient($command->order->id_customer, [false,true]);
                                
                                $boutique = $shopRepository->findOneBy(['email' => $client->customer->email]);
                            }
                            
                            $clientExist = $clientRepo->findOneBy(['email' => 'patricehennion@gmail.com']);
                            
    
                            $clientMb = null;

                            if ($clientExist) {
                                $devis->setClient($clientExist);

                                if($boutique)
                                {
                                    $devis->setBoutique($boutique);
                                }
                                else
                                {
                                    $shop = new Shop;
                                    $shop->setEmail($client->customer->email);
                                    $shop->setClient($clientExist);

                                    $em->persist($shop);
                                    $em->flush();

                                    $devis->setBoutique($shop->getId());

                                }

                                $clientMb = $clientExist;
                            } else {
                                $cli = new Client();
    
                                $cli->setLastName('Hennion');
                                $cli->setFirstName('Patrice');
                                $cli->setEmail('patricehennion@gmail.com');
                                $cli->setRaisonSocial('HPFI');
                                $cli->setTypeDeClient($typeClient);
                                //$cli->setIdPrestashop($client->customer->id);
                                $em->persist($cli);
                                $em->flush();
                                $devis->setClient($cli);

                                $clientMb = $cli;

                            }
    
                            $devis->setDate(new DateTime($command->order->date_add));
                            $devis->setCode($command->order->reference);
                            
                            $devis->setFraisExpedition($command->order->total_shipping);
                            $devis->setValider(true);
                            $devis->setShop('yzy_vape');
                            $devis->setSigneParClient(true);
                            
                            
                            $devis->setDateSignature(new DateTime($command->order->date_add));
                            
                            
                            $devis->setStatus("Devis valider");
                            $devis->setSigneParClient(true);
                            $devis->setDateValidation(new DateTime($command->order->date_upd));
    
                            $total = 0;
                            $prixTotalProduit = 0;

                            foreach ($command->order->associations->order_rows as $produit) {
                                if ($produit->product_reference) {
    
                                    $reference = $aeromaApi->findProduct($produit->product_id, false, true, false)->product->reference;
    
                                    $refProduitCommander = $produit->product_reference;

                                    $prod = $produitRepo->findOneBy(['reference' => $reference]);
                                    
                                    $referenceMb = null;
                                    foreach($prod->getMarqueBlanches() as $marqueBlanche){
                                        if($marqueBlanche->getClient() == $clientMb ){
                                            $referenceMb = $marqueBlanche->getReference();
                                        }
                                    }
                                    //$exist = $devisProduitRepo->exist($prod->getId(), $devis->getId());
                                    //manampy bpure ra chuby;
                                    
                                    if($prod->getType() && $prod->getType()  == 'Chubby')
                                    {
                                        $nombreBpureKdo += $produit->product_quantity;
                                    }

                                    $devisProduit = new DevisProduit;
                                    $devisProduit->setProduit($prod);
                                    $devisProduit->setDevis($devis);
                                    $devisProduit->setQuantite($produit->product_quantity);
                                    $devisProduit->setDeclineAvec($prod->getPrincipeActif());
    
                                    $declinaison = null;
    
                                    if ($prod->getPrincipeActif()) {
    
                                        $nbDeclinaison = count($prod->getDeclinaison());

                                        $nbReferenceDeclinaison = null;

                                        if($prod->getReferenceDeclinaison())
                                        {

                                            $nbReferenceDeclinaison = count ($prod->getReferenceDeclinaison());

                                            if($nbReferenceDeclinaison > 1)
                                            {
                                                foreach($prod->getReferenceDeclinaison() as $decl => $refDecl)
                                                {
                                                    if( strtolower($refProduitCommander) ==  strtolower($refDecl))
                                                    {
                                                        $declinaison = $decl;
                                                    }
                                                }
                                            }
        
                                        }

                                        
                                        if ($nbDeclinaison > 1 && is_null($declinaison)) 
                                        {

                                            $prestaRef = str_split($produit->product_reference);

                                            if($referenceMb){
                                                $referenceFinal = $referenceMb;
                                            }else{
                                                $referenceFinal = $prod->getReference();
                                            }

                                            $boRef = str_split($referenceFinal);

                                           
                                            if(count($prestaRef) > count($boRef)){

                                                $result = implode('', array_diff_assoc($prestaRef, $boRef));
                                                
                                                foreach($prod->getDeclinaison() as $decl){
                                                    
                                                    if(intval($decl) == intval($result)){
                                                        
                                                        $declinaison = $decl;
                                                        array_push($countTest, $decl);
                                                    }
                                                }

                                            }elseif(count($prestaRef) == count($boRef) && $prestaRef == $boRef){
                                                $declinaison = '00mg';
                                            }

                                            if(!$declinaison)
                                            {
                                                $nom = $produit->product_name;

                                                if($nom)
                                                {

                                                    foreach($prod->getDeclinaison() as $dec)
                                                    {
                                                        if(strpos($nom, $dec) !== false )
                                                        {
                                                            $declinaison = $dec;
                                                        }
                                                    }
                                                }
                                            }
                                        
                                        } 
                                        elseif($nbDeclinaison == 1 && is_null($declinaison))
                                        {
                                            $declinaison = $prod->getDeclinaison()[0];
                                        }
                                    }

                                    $devisProduit->setDeclinaison($declinaison);

                                    $client = $devis->getClient();

                                    $prix = null;
                                    foreach($prod->getMarqueBlanches() as $marqueBlanche)
                                    {
                                        if($marqueBlanche->getClient() == $client)
                                        {
                                            $tarif = $marqueBlanche->getTarif();

                                            if(!$tarif->getMemeTarif())
                                            {

                                                if($declinaison)
                                                {
                                                    foreach($tarif->getPrixDeclinaisons() as $prixDecl)
                                                    {
                                                        if($declinaison == $prixDecl->getDeclinaison())
                                                        {
                                                            $prix = $prixDecl->getPrix();
                                                        }
                                                    }
                                                }
                                            }
                                            else
                                            {
                                                $prix = $tarif->getPrixDeReferenceGrossiste();
                                            }
                                        }
                                    }

                                    if(!$prix)
                                    {
                                        $prix = $produit->product_price;
                                    }

                                    $devisProduit->setPrix($prix);
    
                                    $em->persist($devisProduit);
                                    $em->flush();
    
                                    $prixTotalProduit += $prix * $produit->product_quantity ;

                                    $total += $produit->product_quantity;

                                }
                            }

                            if($nombreBpureKdo > 0){

                                $Kdo = $produitRepo->getBooster("Bpure", 6);    
                                
                                if($Kdo)
                                {
                                    $total += $nombreBpureKdo;
                                    
                                    foreach ($Kdo->getDeclinaison() as $bDecl) {
    
                                        if($bDecl == "19,9mg")
                                        {
                                            $produitKdo = new DevisProduit;
                                            $produitKdo->setProduit($Kdo);
                                            $produitKdo->setDevis($devis);
                                            $produitKdo->setQuantite($nombreBpureKdo);
                                            $produitKdo->setDeclineAvec($Kdo->getPrincipeActif());
                                            $produitKdo->setPrix(null);
                                            $produitKdo->setOffert(true);
                                            $produitKdo->setDeclinaison($Kdo->getDeclinaison()[0]);
            
                                            $em->persist($produitKdo);
                                            $em->flush();
                                        }
                                    }
                                }

                            }

                            $devis->setNombreProduit($total);

                            $devis->setMontant($prixTotalProduit);
                            $taxe = $prixTotalProduit * 0.2;
                            $devis->setTaxe(round($taxe, 2));

                            $totalHt = $prixTotalProduit + $command->order->total_shipping;

                            $devis->setTotalHt(round($totalHt, 2));

                            $totalTtc = $totalHt + $taxe;

                            $devis->setTotalTtc(round($totalTtc, 2));

                            $bonDeCommande = new BonDeCommande;
                            $bonDeCommande->setDevis($devis);
                            $bonDeCommande->setDate(new DateTime($command->order->date_add));
                            $bonDeCommande->setClient($devis->getClient());
                            $bonDeCommande->setCode($command->order->reference);
                            $bonDeCommande->setTotalTtc($devis->getTotalTtc());
                            
                            $bonDeCommande->setNombreProduit($total);
                            $bonDeCommande->setResteAlivrer($total);
                            
                            /*if(in_array(2, $historique[$command->order->id])){

                                //$devis->setPayerParCarte(true);
                                $bonDeCommande->setStatus("En attente de livraison");
                                //$devis->setMontantPayerParCarte($command->order->total_paid_real);
                                $bonDeCommande->setToutEstPayer(true);
                                $bonDeCommande->setResteAPayer($command->order->total_paid_tax_incl - $command->order->total_paid_real);
                                $bonDeCommande->setDateToutPaiement( new DateTime($command->order->invoice_date));

                                $invoice = new FactureCommande();
                                $invoice->setBonDeCommande($bonDeCommande);
                                $invoice->setClient($bonDeCommande->getClient());
                                $invoice->setDevis($bonDeCommande->getDevis());
                                $invoice->setTitre('Facture-final');
                                $invoice->setSoldeFinal(true);
                                $invoice->setDate(new DateTime($command->order->invoice_date));
                                $invoice->setDatePaiement(new DateTime($command->order->invoice_date));

                                $alreadyPaid = $bonDeCommande->getTotalTtc() - $bonDeCommande->getResteAPayer();
                                $invoice->setDejaPayer($alreadyPaid);
            
                                $ttc = $devis->getTotalTtc();

                                $amountToBePaid = $ttc - $alreadyPaid;

                                $invoice->setTotal($devis->getMontant());
                                $invoice->setTotalHt($devis->getTotalHt());
                                $invoice->setTva($devis->getTaxe());
                                $invoice->setTotalTtc($ttc);
                                $invoice->setEstPayer(true);
                                $invoice->setBalance($amountToBePaid);
                                $invoice->setMontantAPayer($ttc);
                                $invoice->setMontantPayer($command->order->total_paid_real);

                                $invoice->setNumero($bonDeCommande->getCode());
                                $em->persist($invoice);
                               
                            }else{*/
                                $bonDeCommande->setStatus("En attente de paiement");
                                $bonDeCommande->setResteAPayer($devis->getTotalTtc());
                            //}
    
                            $em->persist($devis);
                            $em->persist($bonDeCommande);
                            $em->flush();
                            
                            /*if(in_array(2, $historique[$command->order->id])){
                                $useful->createInvoice($bonDeCommande,$invoice);
                            }*/

                        }
                    }
                }
            }
            $em->flush();
        }
        
    }

}
