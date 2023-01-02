<?php


namespace App\Controller\Back;

use App\Entity\BonDeCommande;
use App\Entity\BonDeCommandeProduit;
use App\Entity\Client;
use App\Entity\Commandes;
use App\Entity\Devis;
use App\Entity\DevisProduit;
use App\Entity\FactureCommande;
use App\Entity\PrincipeActif;
use App\Entity\MarqueBlanche;
use App\Entity\OrdreCeres;
use App\Form\ClientType;
use App\Repository\ClientRepository;
use App\Repository\CommandesRepository;
use App\Repository\DevisProduitRepository;
use App\Repository\DevisRepository;
use App\Repository\GammeRepository;
use App\Repository\MarqueBlancheRepository;
use App\Repository\PrincipeActifRepository;
use App\Repository\OrdreCeresRepository;
use App\Repository\PositionGammeClientRepository;
use App\Repository\ProduitRepository;
use App\Repository\ShopRepository;
use App\Repository\TypeDeClientRepository;
use App\Service\AeromaApi;
use App\Service\CodeGenerate;
use App\Service\DevisServices;
use App\Service\FileUploader;
use App\Service\Mailer;
use App\Service\Token;
use App\Service\TrieProduit;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Stripe\Util\Set;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Exception\TokenNotFoundException;

class DevisController extends AbstractController
{

    public function liste(Request $request,SessionInterface $session, AeromaApi $aeromaApi, DevisProduitRepository $devisProduitRepo, ProduitRepository $produitRepo, ClientRepository $clientRepo, DevisRepository $devisRepo, EntityManagerInterface $em)
    {
        /*f ( $request->isXmlHttpRequest() ) {
            //dd($request->request->get('state'));
            if ($request->request->get('state') && $request->request->get('state') == 'signer') {
                
                $content = $this->renderView('back/devis/listeDevis.html.twig', [
                    'liste_devis' => $devisRepo->findBy(['status' => "Devis signer"])
                ]);

                return new JsonResponse(['content' => $content]);
            }

            if ($request->request->get('state') && $request->request->get('state') == 'valide') {
                
                $content = $this->renderView('back/devis/tab_list.html.twig', [
                    'liste_devis' => $devisRepo->findBy(['status' => "Devis valider"])
                ]);

                return new JsonResponse(['content' => $content]);
            }
        }*/
        $session->set('brouillonEdit', []);
        return $this->render('back/devis/listeDevis.html.twig', [
            'devis' => true,
            "liste_devis" => $devisRepo->findBy([], ['date' => 'desc']),
        ]);
    }

    public function choixClient(ClientRepository $clientRepo, SessionInterface $session, Request $request)
    {
        $session->set('listeProduits', []);
        $session->set('produitChoisi', []);
        $session->set('brouillon', []);
        $session->set('nombreProduitsDejaCommander', []);
        $session->set("shop", "");

        if ($request->isMethod('post')) {
            $val = $request->request->get('_client_');
            if ($val) {

                $ids = explode(",", $val);
                
                $id = (int)$ids[0];

                if(count($ids) > 1)
                {
                    $session->set("shop", $ids[1]);
                }

                if ($id > 0) {

                    return $this->redirectToRoute('back_capture_devis', ['id' => $id]);
                }
            }
        }

        return $this->render('back/devis/choixClient.html.twig', [
            'devis' => true,
            'listeClient' => $clientRepo->findAll(),
            'creation_devis' => true
        ]);
    }

    public function supprimerDevis(Devis $devis, Request $request, EntityManagerInterface $em)
    {
        if ($this->isCsrfTokenValid('delete' . $devis->getId(), $request->request->get('_token'))) {
            $em->remove($devis);
            $em->flush();
            $this->addFlash('successDevis', 'Devis supprimé avec succès');
            return $this->redirectToRoute('back_devis_list');
        } else {
            throw new TokenNotFoundException('Token invalide');
        }
    }

    public function delete(Request $request, DevisProduitRepository $devisP, CommandesRepository $commandeRepository, DevisRepository $devrepo, EntityManagerInterface $em)
    {
        if ($request->isXmlHttpRequest()) {
           
            $produits = $devisP->findBy(['devis' => $request->request->get('devisId'), 'produit' => $request->request->get('produitId')]);
            $devis = $devrepo->find($request->request->get('devisId'));

            $commandes = null;
            $bonDeCommande = $devis->getBonDeCommande();
            $commandeOffert = null;

            if($bonDeCommande)
            {
                $commandes = $commandeRepository->findBy(['devis' => $request->request->get('devisId'), 'produit' => $request->request->get('produitId')]);
                $commandeOffert = $commandeRepository->findOneBy(['devis' => $request->request->get('devisId'), 'offert' => true]);

            }

            if($bonDeCommande)
            {
                
                foreach ($commandes as $prod) {
                    if (str_contains($prod->getProduit()->getContenant()->getNom(), '50') or str_contains($prod->getProduit()->getContenant()->getTaille(), '50')) {
    
                        $bonDeCommande->setNombreProduit($devis->getNombreProduit() - $prod->getQuantite());
                        if ($commandeOffert) {
                            $commandeOffert->setQuantite($commandeOffert->getQuantite() - $prod->getQuantite());
                            if ($commandeOffert->getQuantite() == 0) {
                                $em->remove($commandeOffert);
                            }
                        }
                    }
                    $em->remove($prod);
                    $em->flush();
                }
            }


            $offert = $devisP->findOneBy(['devis' => $request->request->get('devisId'), 'offert' => true]);

            foreach ($produits as $prod) {
                if (str_contains($prod->getProduit()->getContenant()->getNom(), '50') or str_contains($prod->getProduit()->getContenant()->getTaille(), '50')) {

                    $devis->setNombreProduit($devis->getNombreProduit() - $prod->getQuantite());

                    if ($offert) {
                        $offert->setQuantite($offert->getQuantite() - $prod->getQuantite());
                        if ($offert->getQuantite() == 0) {
                            $em->remove($offert);
                        }
                    }
                }
                $em->remove($prod);
                $em->flush();
            }

          
            return $this->json(
                [
                    'code' => 200,
                    'supprimer' => $request->request->get('produitId')
                ],
                200
            );
        }
    }

    public function captureDevis(Client $client, Request $request, SessionInterface $session, Token $token, DevisRepository $devisRepo, GammeRepository $gammeRepo, CodeGenerate $getCode, ProduitRepository $produitRepo, EntityManagerInterface $em)
    {
        
        if ($client) {
            if ($request->isMethod('post')) {

                $session->set('listeProduits', []);
                $countPost = count($_POST);
                $produitChoisi = [];

                $i = 1;

                while ($i <= $countPost) {
                    if (isset($_POST["produit-$i"])) {
                        $prod = $produitRepo->find($_POST["produit-$i"]);
                        if (!in_array($prod->getId(), $produitChoisi)) {

                            array_push($produitChoisi, $prod->getId());
                        }
                    }
                    $i++;
                }
                $session->set('produitChoisi', $produitChoisi);
                return $this->redirectToRoute('back_capture_declinaison_devis', ['id' => $client->getId()]);
            }

            $listeProduit = $session->get('listeProduits');
            $derniereCommandes = null;
            $customListe = null;

            if (!empty($listeProduit)) {
                $customListe =  $produitRepo->findProductList($listeProduit);
            } else {
                $derniereCommandes = $devisRepo->findBy(['client' => $client]);

                $derniereProduits = 0;
                foreach($derniereCommandes as $devis){
                    foreach($devis->getDevisProduits() as $produit){
                        $derniereProduits++;
                    }
                }
                $session->set('nombreProduitsDejaCommander', $derniereProduits + 1);
            }

            $produitChoisi = $session->get('produitChoisi');

            $choisi = null;
            if (!empty($produitChoisi)) {
                $choisi = $produitChoisi;
            }

            return $this->render('back/devis/captureDevis1.html.twig', [
                'client' => $client,
                'listeProduit' => $produitRepo->findAll(),
                'devis' => true,
                'creation_devis' => true,
                'derniereCommandes' => ($derniereCommandes) ? $derniereCommandes : null,
                'customListe' => ($customListe) ? $customListe : null,
                'choisis' => ($choisi) ? $choisi : null
            ]);
        } else {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès a cette page');
        }
    }

    public function captureFromListe(Client $client, Request $request, SessionInterface $session, CodeGenerate $getCode, Token $token, ProduitRepository $produitRepo, EntityManagerInterface $em)
    {
        if ($request->isMethod('post')) {
            
            $session->set('produitChoisi', []);
            $countPost = 0;
            
            if(!empty($session->get('nombreProduitsDejaCommander'))){

                $countPost = $session->get('nombreProduitsDejaCommander');
            }else{
                $count = count($_POST);
            }

            $listeProduits = [];

            $i = 1;

            while ($i <= $countPost) {
                if (isset($_POST["produit-$i"])) {
                    $prod = $produitRepo->find($_POST["produit-$i"]);
                    if (!in_array($prod->getId(), $listeProduits)) {

                        array_push($listeProduits, $prod->getId());
                    }
                }
                $i++;
            }
            $session->set('listeProduits', $listeProduits);

            return $this->redirectToRoute('back_capture_declinaison_devis', ['id' => $client->getId()]);
        }
    }

    public function addToSession(Request $request, SessionInterface $session, ProduitRepository $produitRepo)
    {
        if($request->isXmlHttpRequest())
        {
            
            $type = $request->request->get('type');
            $add = $request->request->get('add');
            $produit = $request->request->get('productId');
            $declinaison = $request->request->get('declinaison');
            $quantite = $request->request->get('quantite');

            if($type)
            {
                if( ! $session->has('brouillonEdit') )
                {
                    $session->set('brouillonEdit', []);
                }
                
                $brouillon = $session->get('brouillonEdit');

            }
            else
            {
                
                if( ! $session->has('brouillon') )
                {
                    $session->set('brouillon', []);
                }
                
                $brouillon = $session->get('brouillon');
            }
          
            if($add)
            {
                $brouillon[$produit][$declinaison]= $quantite;
            }
            else
            {
                unset($brouillon[$produit][$declinaison]);
            }

            if($type)
            {
                $session->set('brouillonEdit', $brouillon);
            }
            else
            {
                $session->set('brouillon', $brouillon);
            }
            
            return $this->json(['ok' => true], 200);
        }
    }

    public function captureDeclinaison(Client $client, DevisProduitRepository $devisProduitRepo, PositionGammeClientRepository $positionGammeClientRepo, TrieProduit $trieProduit, OrdreCeresRepository $ordreCeresRepo, DevisServices $devisServices, $idDevis, ClientRepository $clientRepository, ShopRepository $shopRepo, CodeGenerate $getCode, DevisRepository $devisRepo, Token $token, MarqueBlancheRepository $mbRepo, SessionInterface $session, GammeRepository $gammeRepo, Request $request, ProduitRepository $produitRepo, EntityManagerInterface $em)
    {
        
        $brouillon = [];

        if($request->query->get('all'))
        {
            $session->set('listeProduits', []);
        }

        if($session->has('brouillon'))
        {
            $brouillon = $session->get('brouillon');

        }
        $listeProduits = $session->get('listeProduits', []);
        
        /*$tabTest = [
            0 => 31,
            1 => 167,
            2 => 68,
            3 => 6,
            4 => 58,
            5 => 149,
            6 => 7,
            7 => 57,
            8 => 146,
            9 => 49,
            10 => 108,
            11 => 169,
            12 => 21,
            13 => 69,
            14 => 165,
            15 => 41,
            16 => 170,
            17 => 70,
            18 => 26,
            19 => 71,
            20 => 166,
            21 => 9,
            22 => 107,
            23 => 164,
            24 => 222,
            25 => 223,
            26 => 155,
            27 => 46,
            28 => 72,
            29 => 177,
            30 => 43,
            31 => 224,
            32 => 175,
            33 => 44,
            34 => 225,
            35 => 176,
            36 => 32,
            37 => 226,
            38 => 173,
            39 => 23,
            40 => 73,
            41 => 172,
            42 => 174,
            43 => 38,
            44 => 74,
            45 => 4,
            46 => 76,
            47 => 178,
            48 => 150,
            49 => 14,
            50 => 77,
            51 => 33,
            52 => 97,
            53 => 193,
            54 => 42,
            55 => 99,
            56 => 194,
            57 => 3,
            58 => 100,
            59 => 147,
            60 => 48,
            61 => 101,
            62 => 195,
            63 => 1,
            64 => 102,
            65 => 189,
            66 => 52,
            67 => 91,
            68 => 196,
            69 => 11,
            70 => 92,
            71 => 154,
            72 => 10,
            73 => 93,
            74 => 153,
            75 => 20,
            76 => 94,
            77 => 190,
            78 => 15,
            79 => 95,
            80 => 159,
            81 => 13,
            82 => 96,
            83 => 157,
            84 => 8,
            85 => 98,
            86 => 151,
            87 => 40,
            88 => 78,
            89 => 186,
            90 => 39,
            91 => 79,
            92 => 185,
            93 => 17,
            94 => 80,
            95 => 161,
            96 => 18,
            97 => 81,
            98 => 162,
            99 => 19,
            100 => 82,
            101 => 163,
            102 => 22,
            103 => 83,
            104 => 179,
            105 => 12,
            106 => 84,
            107 => 156,
            108 => 47,
            109 => 87,
            110 => 188,
            111 => 45,
            112 => 88,
            113 => 187,
            114 => 5,
            115 => 89,
            116 => 148,
            117 => 34,
            118 => 90,
            119 => 183,
            120 => 28,
            121 => 59,
            122 => 152,
            123 => 29,
            124 => 60,
            125 => 181,
            126 => 51,
            127 => 61,
            128 => 231,
            129 => 24,
            130 => 62,
            131 => 192,
            132 => 35,
            133 => 63,
            134 => 191,
            135 => 16,
            136 => 64,
            137 => 160,
            138 => 27,
            139 => 65,
            140 => 158,
            141 => 30,
            142 => 75,
            143 => 182,
            144 => 50,
            145 => 66,
            146 => 171,
            147 => 36,
            148 => 67,
            149 => 168,
            150 => 37,
            151 => 85,
            152 => 184,
            153 => 25,
            154 => 86,
            155 => 180,
            156 => 109,
            157 => 111,
            158 => 110,
            159 => 104,
            160 => 103,
            161 => 105,
            162 => 118,
            163 => 106,
            164 => 113,
            165 => 114,
            166 => 115,
            167 => 116,
            168 => 117,
            169 => 201,
            170 => 202,
            171 => 199,
            172 => 200,
            173 => 197,
            174 => 198,
            175 => 228,
            176 => 230,
            177 => 227,
            178 => 229,
            179 => 139,
            180 => 140,
            181 => 141,
            182 => 142,
            183 => 143,
            184 => 144,
            185 => 145,
            186 => 132,
            187 => 133,
            188 => 134,
            189 => 135,
            190 => 136,
            191 => 137,
            192 => 138,
            193 => 204,
            194 => 205,
            195 => 206,
            196 => 207,
            197 => 208,
            198 => 209,
            199 => 210,
            200 => 211,
            201 => 53,
            202 => 54,
            203 => 55,
            204 => 56,
            205 => 112,
            206 => 119
        ];

        if($client->getEmail() == "patricehennion@gmail.com")
        {

            $produitChoisi = $tabTest;
        }
        else
        {*/
            $produitChoisi = $session->get('produitChoisi');
        //}
        $modifDevis = null;
        $marqueBlanche = null;
        
        if ($idDevis) 
        {
            $modifDevis = $devisRepo->find($idDevis);
            
            $shop = $modifDevis->getShop();

            if($session->has('brouillonEdit'))
            {
                $brouillon = $session->get('brouillonEdit');

            }
            
            if($shop && $shop == "grossiste_greendot")
            {
                $vapair = $clientRepository->findOneBy(["email" => "vap-air@orange.fr"]);
                $marqueBlanche = $mbRepo->findBy(['client' => $vapair->getId()]) ;
            }
            else
            {
                $marqueBlanche =  $mbRepo->findBy(['client' => $client]);
            }
        }
        else
        {
            
            $marqueBlanche =  $mbRepo->findBy(['client' => $client]);
            $produitListe = $marqueBlanche;
            
        }
         
        
        $resultat = [];
       
        if (!empty($listeProduits) or !empty($produitChoisi) or $modifDevis) {
            
            if ($request->isMethod('post')) {
                
                ($client->getCeres())? $ceres = true : $ceres = false ;
                
                $countPost = count($_POST);
                
                $noTva = $request->request->get('noTva');
                $nombreProduits = $request->request->get('quantite');
                $taxe = $request->request->get('taxe');
                $montant = $request->request->get('montant');
                $frais = $request->request->get('fraisDeTransport');
                $totalHt = $request->request->get('totalHt');
                $totalTtc = $request->request->get('totalTtc');

                if($noTva)
                {
                    $taxe = 0 ;
                    $totalTtc = $totalHt;
                }

                $devis =  new Devis;

                $devis->setClient($client);
                $devis->setCode($getCode->code(4));
                $devis->setDateSignature(null);
                $devis->setDateValidation(null);
                $devis->setDate(new DateTime());
                $devis->setSigneParClient(false);
                $devis->setStatus("Devis créer");
                $devis->setValider(false);
                $devis->setToken($token->generateToken());
                $devis->setCreerManuellement(true);
                $devis->setNombreProduit($nombreProduits);
                $devis->setTaxe($taxe);
                $devis->setFraisExpedition($frais);
                $devis->setMontant($montant);
                $devis->setTotalHt($totalHt);
                $devis->setTotalTtc($totalTtc);

                if($session->has("shop") && !empty($session->get("shop")))
                {
                    $shop = $shopRepo->find($session->get("shop"));

                    $devis->setBoutique($shop);
                }

                $marqueBlanches = $mbRepo->findBy(['client' => $client]);
                $i = 1;
                $quantiteOffert = 0;
                while ($i <= $countPost) {
                    if (isset($_POST["produit-$i"])) {

                        $prod = $produitRepo->find($_POST["produit-$i"]);

                        $listeDeclinaison = $prod->getDeclinaison();
                        $tarif = $prod->getTarif();
                        
                        foreach ($marqueBlanches as $mb) {
                            if ($mb->getProduit() == $prod) {
                                $tarif = $mb->getTarif();
                            }
                        }
                        
                        if (is_null($listeDeclinaison) or !$prod->getPrincipeActif() or count($listeDeclinaison) == 0) {
                            $listeDeclinaison = [];
                            $id = $prod->getId();

                            if (isset($_POST["quantite-$i-$id"])) {

                                $quantite = intval($_POST["quantite-$i-$id"]);

                                if($quantite > 0)
                                {
                                    $resultat[$prod->getId()]["sans"] = $quantite;
                                    $result = $devisServices->productQuote([true, false],$devis, $client, $prod, $tarif, $quantite, $ceres, null ,$quantiteOffert);
                                    //$quantiteOffert = $result[1];
                                    if($prod->getType() && strtolower($prod->getType()) == "chubby" && strpos($prod->getContenant()->getTaille(), '50') !== false)
                                    {
                                        $quantiteOffert += $quantite;
                                    }
                                    
                                }
                            }
                        }
                        
                        foreach ($listeDeclinaison as $decl) {

                            $tempDecl = $devisServices->deleteSpecialChar($decl); 
                            
                            if (isset($_POST["produit-$i-$tempDecl"])) {

                                $quantite = intval($_POST["quantite-$i-$tempDecl"]);

                                
                                if($quantite > 0)
                                {

                                    $resultat[$prod->getId()][$decl] = $quantite;

                                    $result = $devisServices->productQuote([true, false],$devis, $client, $prod, $tarif, $quantite, $ceres,$decl,$quantiteOffert);
                                    
                                    if($prod->getType() && strtolower($prod->getType()) == "chubby" && strpos($prod->getContenant()->getTaille(), '50') !== false)
                                    {
                                        /*if( $client->getId() == 17 ){
                                            $quantiteOffert = $quantite * 2;
                                        }
                                        else{
                                            $quantiteOffert += $quantite;
                                        }*/
                                        $quantiteOffert += $quantite;
                                        
                                    }
                                    
                                }

                            }
                        }
                    }
                    $i++;
                }
                
                $em->persist($devis);
                
                if ($quantiteOffert > 0 && $client->getProduitOffert()) {
                    /*$booster = null;
                    if ( $client->getId() == 17 ) {
                        $booster = $produitRepo->find(514);
                    }
                    else {
                        $booster = $produitRepo->getBooster("Bpure", 6);   
                    }*/
                    $booster = $produitRepo->getBooster("Bpure", 6);  
                    
                    if($booster)
                    {
                        foreach ($booster->getDeclinaison() as $bDecl) {
                            if( $bDecl == "19,9mg")
                            {

                                $devisP = new DevisProduit;
                                $devisP->setDevis($devis);
                                $devisP->setProduit($booster);
                                $devisP->setDeclinaison($bDecl);
                                $devisP->setQuantite($quantiteOffert);
                                $devisP->setDeclineAvec($booster->getPrincipeActif());
                                $devisP->setOffert(true);
                                $em->persist($devisP);
                            }
                            /*if( $client->getId() == 17 ){
                                
                            }    
                            else{
                                
                            }*/
                            
                        }
                        $devis->setNombreProduit($devis->getNombreProduit() + $quantiteOffert);
                    }

                }

                $em->flush();

                $devis->setCode($devis->getCode() . '' . $devis->getId());
                $coden = 'BDC-' . $devis->getCode() . '-' . $devis->getDate()->format('Y') . '-' . $devis->getId();
                $devis->setCode($coden);
                $em->flush();


                return $this->redirectToRoute('back_devis_capture_devis_change_state', ['id' => $devis->getId()]);
            }
            
            if ($modifDevis) {
                $produitsDecl = $modifDevis;
            } else {
                if (empty($listeProduits)) {
                    $produitsDecl = $produitRepo->findProductList($produitChoisi);
                } else {
                    $produitsDecl = $produitRepo->findProductList($listeProduits);
                }
            }
            
            if($produitsDecl instanceof Devis)
            {
                $devisProduit = $devisProduitRepo->findBy(['devis' => $produitsDecl]);
            }
            else
            {
                $devisProduit = $produitsDecl;
            }

            $gammesClient = $positionGammeClientRepo->findBy(['client' => $client ], ['position' => 'asc']);
        
            $produitsDecl = $trieProduit->setGammesClient($gammesClient)
                                            ->setCommandes($devisProduit)
                                            ->trieCommande();

            return $this->render('back/devis/captureDevis2.html.twig', [
                'listeProduit' => $produitsDecl,
                'produitsListe' => $produitRepo->findAll(),
                'devis' => true,
                'client' => $client,
                'creation_devis' => true,
                'marqueBlanches' => $marqueBlanche,
                'modifier' => ($modifDevis) ? $modifDevis : null,
                'brouillon' => $brouillon
            ]);
        } else {
            $this->addFlash('error', 'Veuillez choisir des produits');
            return $this->redirect($request->headers->get('referer'));

        }
    }


    public function tousLesProduits(Client $client, ProduitRepository $produitRepository, SessionInterface $session, TrieProduit $trieProduit, PositionGammeClientRepository $positionGammeClientRepo)
    {

        $gammesClient = $positionGammeClientRepo->findBy(['client' => $client ], ['position' => 'asc']);

        
        if($session->has('produitChoisi'))
        {
            $produitSelectionner = $session->get('produitChoisi');
        }
        else
        {
            $produitSelectionner = [];
        }    
        
        $produits = $trieProduit->setGammesClient($gammesClient)
                                    ->setCommandes($produitRepository->findAll())
                                    ->trieCommande();

        return $this->render('back/devis/devis_tous_les_produits.html.twig',[
            'produitListe' => $produits,
            'produitSelectionner' => (count($produitSelectionner) > 0) ? $produitRepository->findByIds($produitSelectionner) : null,
            'client' =>$client,
            'produitsId' => $produitSelectionner,
            'devis' => true,
            'creation_devis' => true,
        ]);
    }


    public function ajaxAddProduct(Client $client, Request $request, ProduitRepository $produitRepository, SessionInterface $session, DevisServices $devisServices)
    {
        if($request->isXmlHttpRequest())
        {
            if($session->has('produitChoisi'))
            {
                $produits = $session->get('produitChoisi');
            }
            else
            {
                $produits = [];
            }    
    
            $devisServices->produitChoisis($request->request->all(), $produits);
           
            return $this->json(['add' => true], Response::HTTP_OK);
        }
    }

    public function ajoutProduitManquant(EntityManagerInterface $em, DevisRepository $devisRepository, MarqueBlancheRepository $marqueBlancheRepository, ProduitRepository $produitRepository)
    {
        
        $tabTest = [
            0 => 31,
            1 => 167,
            2 => 68,
            3 => 6,
            4 => 58,
            5 => 149,
            6 => 7,
            7 => 57,
            8 => 146,
            9 => 49,
            10 => 108,
            11 => 169,
            12 => 21,
            13 => 69,
            14 => 165,
            15 => 41,
            16 => 170,
            17 => 70,
            18 => 26,
            19 => 71,
            20 => 166,
            21 => 9,
            22 => 107,
            23 => 164,
            24 => 222,
            25 => 223,
            26 => 155,
            27 => 46,
            28 => 72,
            29 => 177,
            30 => 43,
            31 => 224,
            32 => 175,
            33 => 44,
            34 => 225,
            35 => 176,
            36 => 32,
            37 => 226,
            38 => 173,
            39 => 23,
            40 => 73,
            41 => 172,
            42 => 174,
            43 => 38,
            44 => 74,
            45 => 4,
            46 => 76,
            47 => 178,
            48 => 150,
            49 => 14,
            50 => 77,
            51 => 33,
            52 => 97,
            53 => 193,
            54 => 42,
            55 => 99,
            56 => 194,
            57 => 3,
            58 => 100,
            59 => 147,
            60 => 48,
            61 => 101,
            62 => 195,
            63 => 1,
            64 => 102,
            65 => 189,
            66 => 52,
            67 => 91,
            68 => 196,
            69 => 11,
            70 => 92,
            71 => 154,
            72 => 10,
            73 => 93,
            74 => 153,
            75 => 20,
            76 => 94,
            77 => 190,
            78 => 15,
            79 => 95,
            80 => 159,
            81 => 13,
            82 => 96,
            83 => 157,
            84 => 8,
            85 => 98,
            86 => 151,
            87 => 40,
            88 => 78,
            89 => 186,
            90 => 39,
            91 => 79,
            92 => 185,
            93 => 17,
            94 => 80,
            95 => 161,
            96 => 18,
            97 => 81,
            98 => 162,
            99 => 19,
            100 => 82,
            101 => 163,
            102 => 22,
            103 => 83,
            104 => 179,
            105 => 12,
            106 => 84,
            107 => 156,
            108 => 47,
            109 => 87,
            110 => 188,
            111 => 45,
            112 => 88,
            113 => 187,
            114 => 5,
            115 => 89,
            116 => 148,
            117 => 34,
            118 => 90,
            119 => 183,
            120 => 28,
            121 => 59,
            122 => 152,
            123 => 29,
            124 => 60,
            125 => 181,
            126 => 51,
            127 => 61,
            128 => 231,
            129 => 24,
            130 => 62,
            131 => 192,
            132 => 35,
            133 => 63,
            134 => 191,
            135 => 16,
            136 => 64,
            137 => 160,
            138 => 27,
            139 => 65,
            140 => 158,
            141 => 30,
            142 => 75,
            143 => 182,
            144 => 50,
            145 => 66,
            146 => 171,
            147 => 36,
            148 => 67,
            149 => 168,
            150 => 37,
            151 => 85,
            152 => 184,
            153 => 25,
            154 => 86,
            155 => 180,
            156 => 109,
            157 => 111,
            158 => 110,
            159 => 104,
            160 => 103,
            161 => 105,
            162 => 118,
            163 => 106,
            164 => 113,
            165 => 114,
            166 => 115,
            167 => 116,
            168 => 117,
            169 => 201,
            170 => 202,
            171 => 199,
            172 => 200,
            173 => 197,
            174 => 198,
            175 => 228,
            176 => 230,
            177 => 227,
            178 => 229,
            179 => 139,
            180 => 140,
            181 => 141,
            182 => 142,
            183 => 143,
            184 => 144,
            185 => 145,
            186 => 132,
            187 => 133,
            188 => 134,
            189 => 135,
            190 => 136,
            191 => 137,
            192 => 138,
            193 => 204,
            194 => 205,
            195 => 206,
            196 => 207,
            197 => 208,
            198 => 209,
            199 => 210,
            200 => 211,
            201 => 53,
            202 => 54,
            203 => 55,
            204 => 56,
            205 => 112,
            206 => 119
        ];
        $devis = $devisRepository->find(19);

        $client = $devis->getClient();

        $listeDevisProduit = [];
        $offert = null;
        foreach($devis->getDevisProduits() as $produit)
        {
            if($produit->getOffert()){
                $offert = $produit->getQuantite();
            }
            if(!$produit->getOffert() && !in_array($produit->getProduit()->getId(), $listeDevisProduit))
            {
                array_push($listeDevisProduit, $produit->getProduit()->getId());
            }
        }

        
        foreach($tabTest as $index => $produit)
        {
            foreach($listeDevisProduit as $devi)
            {
                if($produit == $devi)
                {
                    unset($tabTest[$index]);
                }
            }
        }

        $marqueBlanches = $marqueBlancheRepository->findBy(['client' => $client ]);

        $tabTest = [];
        
        $tabTest[1] = 233;

        foreach($tabTest as $test)
        {
            $produit = $produitRepository->find($test);

            $tarif = $produit->getTarif();

            foreach ($marqueBlanches as $mb) 
            {
                if ($mb->getProduit()->getId() == $test) 
                {
                    $tarif = $mb->getTarif();
                }
            }

            if($produit->getDeclinaison())
            {

                foreach($produit->getDeclinaison() as $declinaison)
                {
                    $devisPrix = null;
                    if ($tarif->getMemeTarif()) {
                        if (strtolower($client->getTypeDeClient()->getNom()) == "grossiste") {
                            $devisPrix = $tarif->getPrixDeReferenceGrossiste();
                        } else {
                            $devisPrix = $tarif->getPrixDeReferenceDetaillant();
                        }

                        $devisProduit = new DevisProduit;
                        $devisProduit->setDevis($devis);
                        $devisProduit->setPrix($devisPrix);
                        $devisProduit->setProduit($produit);
                        $devisProduit->setQuantite(2);
                        $devisProduit->setDeclinaison($declinaison);
                        $devisProduit->setDeclineAvec($produit->getPrincipeActif());

                        if ($produit->getType() && strtolower($produit->getType()) == "chubby" && $client->getProduitOffert() )
                        {

                            $devis->setNombreProduit($devis->getNombreProduit() + 2);

                            $offert += 2;
                        }

                        $em->persist($devisProduit);
                        $em->flush();


                    } else {
                        foreach ($tarif->getPrixDeclinaisons() as $prixDecl) {
                            if ($prixDecl->getActif()) {
                                if (strtolower($prixDecl->getTypeDeClient()->getNom()) == strtolower($client->getTypeDeClient()->getNom())) {
    
                                    if ($prixDecl->getDeclinaison() == $declinaison) {

                                        $devisProduit = new DevisProduit;
                                        $devisProduit->setDevis($devis);
                                        $devisProduit->setPrix($devisPrix);
                                        $devisProduit->setProduit($produit);
                                        $devisProduit->setQuantite(2);
                                        $devisProduit->setDeclinaison($declinaison);
                                        $devisProduit->setDeclineAvec($produit->getPrincipeActif());

                                        if ($produit->getType() && strtolower($produit->getType()) == "chubby" && $client->getProduitOffert() )
                                        {

                                            $devis->setNombreProduit($devis->getNombreProduit() + 2);

                                            $offert += 2;
                                        }

                                        $em->persist($devisProduit);
                                        $em->flush();
                                    }
                                }
                            }
                        }
                    }

                }

            }
            else
            {

                $devisPrix = null;

                if (strtolower($client->getTypeDeClient()->getNom()) == "grossiste") {
                    $devisPrix = $tarif->getPrixDeReferenceGrossiste();
                } else {
                    $devisPrix = $tarif->getPrixDeReferenceDetaillant();
                }

                $devisProduit = new DevisProduit;
                $devisProduit->setDevis($devis);
                $devisProduit->setPrix($devisPrix);
                $devisProduit->setProduit($produit);
                $devisProduit->setQuantite(2);
                $devisProduit->setDeclinaison(null);
                $devisProduit->setDeclineAvec($produit->getPrincipeActif());

                if ($produit->getType() && strtolower($produit->getType()) == "chubby" && $client->getProduitOffert())
                {

                    $devis->setNombreProduit($devis->getNombreProduit() + 2);

                    $offert += 2;
                }

                $em->persist($devisProduit);
                $em->flush();


            }
        }

        foreach($devis->getDevisProduits() as $produit)
        {
            if($produit->getOffert()){
                $produit->getQuantite($offert);
            }

            $em->flush();
        }

        return $this->redirectToRoute("back_devis_edit", ['id' => $devis->getId()]);

    }

    public function ajoutProduit(Request $request, ProduitRepository $produitRepo, DevisServices $devisServices, ClientRepository $clientRepo, MarqueBlancheRepository $mbRepo)
    {
        if($request->isXmlHttpRequest())
        {

            $result = $request->request->all();
            
            if(!empty($result))
            {
                $ids = [];

                foreach($result['produits'] as $id )
                {
                    if($id)
                    {
                        array_push($ids, $id);
                    }
                }

                $data = [];
                
                $data = $mbRepo->findByIds($ids, $result['client']);
                
               
                $notFound = $devisServices->checkProducts($ids, $data);

                if(count($notFound) > 0)
                {
                    $dataSupp = $produitRepo->findByIds($notFound);
                    $finalData = array_merge($data, $dataSupp);
                }
                else
                {
                    $finalData = $data;
                }
                
                return new JsonResponse(['content' => $this->renderView('back/devis/_produit_supp.html.twig', ['produits' => $finalData, 'client' => $clientRepo->find($result['client'])])]);

                //return $this->json($finalData, 200, [], ['groups' => 'mb:read']);
                
            }
        }

    }

    public function edit(Devis $devis, DevisServices $devisServices, TrieProduit $trieProduit, PositionGammeClientRepository $positionGammeClientRepo, MarqueBlancheRepository $mbRepo, CommandesRepository $commandeRepository, ClientRepository $clientRepository, DevisProduitRepository $devisProduitRepo, Request $request, MarqueBlancheRepository $marqueBlancheRepository, Mailer $mailer, GammeRepository $gammeRepo, TypeDeClientRepository $typeRepo, EntityManagerInterface $em, Token $token, CodeGenerate $getCode, ProduitRepository $produitRepo)
    {
        
        if ($request->isMethod('post')) {
            
            $count = count($_POST);

            $client = $devis->getClient();

            $noTva = $request->request->get('noTva');
            $nombreProduits = $request->request->get('quantite');
            $taxe = $request->request->get('taxe');
            $montant = $request->request->get('montant');
            $frais = $request->request->get('fraisDeTransport');
            $totalHt = $request->request->get('totalHt');
            $totalTtc = $request->request->get('totalTtc');

            if($noTva)
            {
                $taxe = 0 ;
                $totalTtc = $totalHt;
            }

            
            $devis->setNombreProduit($nombreProduits);
            $devis->setTaxe($taxe);
            $devis->setFraisExpedition($frais);
            $devis->setMontant($montant);
            $devis->setTotalHt($totalHt);
            $devis->setTotalTtc($totalTtc);

            $client = $devis->getClient();

            ($client->getCeres())? $ceres = true : $ceres = false ;

            $quantiteOffert = 0;

            $produitExist = [];

            foreach($devis->getDevisProduits() as $produit)
            {
                if(!in_array($produit->getProduit(), $produitExist))
                {
                    array_push($produitExist, $produit->getProduit());
                }
            }

            $typeDeClient = strtolower($client->getTypeDeClient());
            
            $i = 1;
            $addCom = null;
            while ($i <= $count) {

                if (isset($_POST["produit-$i"])) {
                    $produit = $produitRepo->find($_POST["produit-$i"]);
                
                    $devisServices->traitementDevis($devis, $produit, $client, $ceres);
                    
                }
                
                $i++;
            }

            if($devis->getBonDeCommande())
            {

                $k = 1;
                while ($k <= $count) {
    
                    if (isset($_POST["produit-$k"])) {
                        $produit = $produitRepo->find($_POST["produit-$k"]);
                    
                        $devisServices->traitementDevis($devis->getBonDeCommande(), $produit, $client, $ceres);
                        
                    }
                    
                    $k++;
                }
            }
           
            $offertExist = false;

            $commandes = $commandeRepository->findBy(['devis' => $devis]);

            $quantiteTotalDevis = 0;

            $quantiteOffert = 0;

            foreach($devis->getDevisProduits() as $dp)
            {
                $prod = $dp->getProduit();

                $quantiteTotalDevis += $dp->getQuantite();

                if($prod->getType() && strtolower($prod->getType()) == "chubby" && strpos($prod->getContenant()->getTaille(), '50') !== false)
                {
                    $quantiteOffert += $dp->getQuantite();
                }
            }
            
            $j = 0;
            $quantiteSupplementaire = 0;
            $quantiteSupplementaireOffert = 0;
            while($j <= $count)
            {
                $sup = $request->request->get("produit-supp-$j");
                
                if($sup)
                {
                    $prodSup = $produitRepo->find($sup);
                    
                    if($prodSup)
                    {
                        $t = $prodSup->getTarifMb($client);
                        $quantSup = 0;
                        $id = $prodSup->getId();
                        
                        if($prodSup->getPrincipeActif())
                        {
                            foreach($prodSup->getDeclinaison() as $dec)
                            {
                                $declinaisonCommander = $devisServices->deleteSpecialChar($dec);

                                if($request->request->get("quantite-supp-$id-$declinaisonCommander"))
                                {
                                    $quantSup = $request->request->get("quantite-supp-$id-$declinaisonCommander") ;

                                    $devisServices->productQuote([true, false], $devis, $client, $prodSup, $t, $quantSup, $ceres, $dec);

                                    $quantiteSupplementaire += $quantSup;

                                    if(count($devis->getCommandes()) > 0)
                                    {
                                        $devisServices->productQuote([true, false], $devis->getBonDeCommande(), $client, $prodSup, $t, $quantSup, $ceres, $dec);
                                    }

                                    if($prodSup->getType() && strtolower($prodSup->getType()) == "chubby" && strpos($prodSup->getContenant()->getTaille(), '50') !== false)
                                    {
                                        $quantiteOffert += $quantSup;
                                        $quantiteSupplementaireOffert += $quantSup;
                                    }
                                }
                            }
                        }
                        else
                        {
                            if($request->request->get("quantite-supp-$id-$id"))
                            {
                                $quantSup = $request->request->get("quantite-supp-$id-$id") ;
                               
                                $devisServices->productQuote([true, false], $devis, $client, $prodSup, $t, $quantSup, $ceres, null);

                                if(count($devis->getCommandes()) > 0)
                                {
                                    $devisServices->productQuote([true, false], $devis->getBonDeCommande(), $client, $prodSup, $t, $quantSup, $ceres, null);
                                }
        
                                $quantiteSupplementaire += $quantSup;
                                if($prodSup->getType() && strtolower($prodSup->getType()) == "chubby" && strpos($prodSup->getContenant()->getTaille(), '50') !== false)
                                {
                                    $quantiteOffert += $quantSup;
                                    $quantiteSupplementaireOffert += $quantSup;
                                }
                            }
                        }
                    }
                }
                $j++;
            }

            
            if($devis->getProduitOffert())
            {
                $prod = $devis->getProduitOffert();
                
                $offertExist = true;
                $prod->setQuantite($quantiteOffert);
            }
           
            
            foreach ($commandes as $prod) {
                if ($prod->getOffert()) {
                    $offertExist = true;
                    $prod->setQuantite($quantiteOffert);
                }
            }
            
            if (!$offertExist) {
                if ($quantiteOffert > 0 && $client->getProduitOffert()) {
                    
                    $booster = $produitRepo->getBooster("Bpure", 6);    
                    
                    $quantiteTotalDevis += $quantiteOffert;

                    if($booster)
                    {
                        foreach ($booster->getDeclinaison() as $bDecl) {
    
                            if($bDecl == "19,9mg")
                            {
                                $devisP = new DevisProduit;
                                $devisP->setDevis($devis);
                                $devisP->setProduit($booster);
                                $devisP->setDeclinaison($bDecl);
                                $devisP->setQuantite($quantiteOffert);
                                $devisP->setDeclineAvec($booster->getPrincipeActif());
                                $devisP->setOffert(true);
                                $em->persist($devisP);
    
                                if($devis->getBonDeCommande())
                                {
                                    $addCom = new Commandes();
                                                            
                                    $addCom->setProduit($devisP->getProduit());
                                    $addCom->setQuantite($devisP->getQuantite());
                                    $addCom->setDevis($devis);
                                    $addCom->setDeclineAvec($devisP->getDeclineAvec());
                                    $addCom->setDeclinaison($devisP->getDeclinaison());
                                    $addCom->setOffert($devisP->getOffert());
                                    $addCom->setPrix($devisP->getPrix());
                                    $addCom->setBonDeCommande($devis->getBonDeCommande());
            
                                    $em->persist($addCom);
                                }
                            }
                        }
                    }
                }
            }

            $em->flush();

            $bonDeCommande = $devis->getBonDeCommande();
           

            if( !$offertExist && $client->getProduitOffert() )
            {
                $quantiteFinal = $devisProduitRepo->sumQuantity($devis) + $quantiteSupplementaireOffert;
            }
            else
            {
                $quantiteFinal =  $devisProduitRepo->sumQuantity($devis);
            }
            
            
            $devis->setNombreProduit($quantiteFinal);
            
            if($bonDeCommande)
            {
                $reste = $commandeRepository->sumQuantity($bonDeCommande);
                $bonDeCommande->setNombreProduit($quantiteFinal);
            
                $bonDeCommande->setResteAlivrer($reste);
                
                $bonDeCommande->setTotalTtc($devis->getTotalTtc());
                $bonDeCommande->setResteAPayer($devis->getTotalTtc());
            }
            $em->flush();

            $this->addFlash('success', 'Devis modifié avec succès');

            return $this->redirectToRoute('back_devis_list');
        }

        $shop = $devis->getShop();

        $marqueBlanche = null;
        if($shop && $shop == "grossiste_greendot")
        {
            $vapair = $clientRepository->findOneBy(["email" => "vap-air@orange.fr"]);
            $marqueBlanche = $marqueBlancheRepository->findBy(['client' => $vapair->getId()]) ;
        }
        else
        {
            $marqueBlanche = $marqueBlancheRepository->findBy(['client' => $devis->getClient()->getId()]);
        }
        
        $devisProduit = $devisProduitRepo->findBy(['devis' => $devis]);
        
        $gammesClient = $positionGammeClientRepo->findBy(['client' => $devis->getClient()], ['position' => 'asc']);

        $commandeTrier = $trieProduit->setGammesClient($gammesClient)
                                    ->setCommandes($devisProduit)
                                    ->trieCommande();
        
        return $this->render('back/devis/editDevis.html.twig', [
            'devisProduit' => $commandeTrier,
            'devis' => $devis,
            'creation_devis' => true,
            'types' => $typeRepo->findAll(),
            'marqueBlanche' => $marqueBlanche
        ]);
    }

    public function devisChangeState(Devis $devis, Request $request, EntityManagerInterface $em)
    {

        if ($request->isXmlHttpRequest() && $request->request->get('indice')) {
            $stateTab = [
                1 => 'Bon de commande non signer',
                2 => 'Bon de commande signer',
                3 => 'Bon de commande émis',
                4 => 'Acompte reçu '
            ];
            $indice = (int) $request->request->get('indice');

            if ($indice === 1) {
                return new JsonResponse(['id' => $indice, 'content' => $this->renderView('back/form/devis-non-signer.html.twig')]);
            }

            if ($indice === 2) {
                return new JsonResponse(['id' => $indice, 'content' => $this->renderView('back/form/devis-signer.html.twig')]);
            }

            if ($indice === 3) {
                return new JsonResponse(['id' => $indice, 'content' => $this->renderView('back/form/bc-send.html.twig')]);
            }

            if ($indice === 4) {
                return new JsonResponse(['id' => $indice, 'content' => $this->renderView('back/form/deposit-paid.html.twig', ['deposit' => ($devis->getTotalTtc() * 0.35)])]);
            }
        }
        //dd($products);
        return $this->render('back/devis/change-status.html.twig', [
            'devis' => $devis,
            'create_devis' => true,
            'products' => $devis->getDevisProduits(),
            'url' => $this->generateUrl('back_devis_capture_devis_change_state', ['id' => $devis->getId()]),
            'url_processing' => $this->generateUrl('back_devis_state_processing', ['id' => $devis->getId()])
        ]);
    }

    public function modificationTaxe(Devis $devis, Request $request, EntityManagerInterface $em)
    {
        if($request->isMethod('post'))
        {
            $tva = $request->request->get('taxe');

            $total = $devis->getMontant();
    
            $frais = $devis->getFraisExpedition();
    
            $totalHt = $total + $frais;
            
            $devis->setTotalHt($totalHt);
    
            $devis->setFraisExpedition($frais);
    
            $taxe = $tva;
    
            $taxe = round($taxe, 2);
    
            $devis->setTaxe($taxe);
    
            $totalTtc = $totalHt + $taxe;
            $totalTtc = round($totalTtc, 2);
    
            $devis->setTotalTtc($totalTtc);
    
            if ($devis->getSigneParClient()) {
                $bonDeCommande = $devis->getBonDeCommande();
    
                $bonDeCommande->setTotalTtc($devis->getTotalTtc());
                $bonDeCommande->setResteAPayer($devis->getTotalTtc());
            }
    
    
            if($devis->getMontantFinal())
            {
                $montantF = $devis->getMontantFinal();
    
                $montantf = $montantF->getMontant();
    
                $montantFHt = $montantf + $frais;
    
                $montantF->setTotalHt($montantFHt);
    
                $montantFTva = $tva;
    
                $montantF->setTaxe($montantFTva);
    
                $montantFTtc = $montantFHt + $montantFTva;
    
                $montantF->setTotalTtc($montantFTtc);
    
            }
    
            $factures = null;
    
            if(count($devis->getFactureCommandes()) > 0)
            {
    
                $factures = $devis->getFactureCommandes();
    
            }
            else
            {
                if($devis->getBonDeCommande())
                {
                    $factures = $devis->getBonDeCommande()->getFactureMaitres();
                }
            }
    
            
            foreach($factures as $facture)
            {
                
                $tHt = $facture->getTotal() + $frais;
                
                $facture->setTotalHt($tHt);
    
                $facture->setTva($tva);
    
                $facture->setTva($tva);
                
                $ttc = $tHt + $tva;
                
                $facture->setTotalTtc($ttc);
    
                $facture->setMontantAPayer($ttc);
                
            }
            
    
            $em->flush();
        }


        $this->addFlash('modificationFraisEffectuer', "Modification TVA éffectuée avec succès");
        return $this->redirect($request->headers->get('referer'));
    }

    public function modificationFraisDePort(Devis $devis, Request $request, EntityManagerInterface $em)
    {

        if ($request->isMethod('post')) {
            $frais = $request->request->get('fraisDeLivraison');
            $devis->setFraisExpedition($frais);
            $total = $devis->getMontant();

            $totalHt = $total + $frais;
            
            $devis->setTotalHt($totalHt);

            $devis->setFraisExpedition($frais);

            $noTaxe = false;

            if( floatval($devis->getTaxe()) == 0)
            {
                $noTaxe = true;
            }
            
            $taxe = $total * 0.2;

            $taxe = round($taxe, 2);

            if($noTaxe)
            {
                $taxe = 0;
            }

            $devis->setTaxe($taxe);

            $totalTtc = $totalHt + $taxe;
            $totalTtc = round($totalTtc, 2);

            $devis->setTotalTtc($totalTtc);

            if ($devis->getSigneParClient()) {
                $bonDeCommande = $devis->getBonDeCommande();

                $bonDeCommande->setTotalTtc($devis->getTotalTtc());
                $bonDeCommande->setResteAPayer($devis->getTotalTtc());
            }


            if($devis->getMontantFinal())
            {
                $montantF = $devis->getMontantFinal();

                $montantf = $montantF->getMontant();

                $montantFHt = $montantf + $frais;

                $montantF->setTotalHt($montantFHt);

                $montantFTva = $montantFHt * 0.2;

                if($noTaxe)
                {
                    $montantFTva = 0;
                }

                $montantF->setTaxe($montantFTva);

                $montantFTtc = $montantFHt + $montantFTva;

                $montantF->setTotalTtc($montantFTtc);

            }

            $factures = null;

            if(count($devis->getFactureCommandes()) > 0)
            {

                $factures = $devis->getFactureCommandes();

            }
            else
            {
                if($devis->getBonDeCommande())
                {
                    $factures = $devis->getBonDeCommande()->getFactureMaitres();
                }
            }

            if($factures && count($factures) > 0)
            {
                foreach($factures as $facture)
                {
                    
                    $tHt = $facture->getTotal() + $frais;
                    
                    $facture->setTotalHt($tHt);
    
                    $tva = $facture->getTotalHt() * 0.2;
    
                    if($noTaxe)
                    {
                        $tva = 0;
                    }
    
                    $facture->setTva($tva);
    
                    $facture->setTva($tva);
                    
                    $ttc = $tHt + $tva;
                    
                    $facture->setTotalTtc($ttc);
    
                    $facture->setMontantAPayer($ttc);
                    
                }
            }
            

            $em->flush();

            $this->addFlash('modificationFraisEffectuer', "Modification frais de port éffectuée avec succès");
            return $this->redirect($request->headers->get('referer'));
        }
    }

    public function manque(Devis $devis, Request $request, EntityManagerInterface $em, ProduitRepository $produitRepo, PrincipeActifRepository $principeActifRepository)
    {
        foreach($devis->getDevisProduits() as $d)
        {
            $em->remove($d);
            $em->flush();
        }

        $devis_produit = array(
            array('id' => '2198','devis_id' => '83','produit_id' => '5','quantite' => '20','declinaison' => '00mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '1.18'),
            array('id' => '2199','devis_id' => '83','produit_id' => '5','quantite' => '40','declinaison' => '3mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '1.18'),
            array('id' => '2200','devis_id' => '83','produit_id' => '5','quantite' => '50','declinaison' => '18mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '1.18'),
            array('id' => '2201','devis_id' => '83','produit_id' => '7','quantite' => '20','declinaison' => '00mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '1.18'),
            array('id' => '2202','devis_id' => '83','produit_id' => '7','quantite' => '30','declinaison' => '12mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '1.18'),
            array('id' => '2203','devis_id' => '83','produit_id' => '8','quantite' => '100','declinaison' => '6mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '1.18'),
            array('id' => '2204','devis_id' => '83','produit_id' => '9','quantite' => '50','declinaison' => '6mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '1.18'),
            array('id' => '2205','devis_id' => '83','produit_id' => '10','quantite' => '30','declinaison' => '6mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '1.18'),
            array('id' => '2206','devis_id' => '83','produit_id' => '11','quantite' => '50','declinaison' => '3mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '1.18'),
            array('id' => '2207','devis_id' => '83','produit_id' => '11','quantite' => '30','declinaison' => '6mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '1.18'),
            array('id' => '2208','devis_id' => '83','produit_id' => '11','quantite' => '50','declinaison' => '12mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '1.18'),
            array('id' => '2209','devis_id' => '83','produit_id' => '18','quantite' => '20','declinaison' => '3mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '1.18'),
            array('id' => '2210','devis_id' => '83','produit_id' => '18','quantite' => '50','declinaison' => '6mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '1.18'),
            array('id' => '2211','devis_id' => '83','produit_id' => '18','quantite' => '20','declinaison' => '12mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '1.18'),
            array('id' => '2212','devis_id' => '83','produit_id' => '22','quantite' => '20','declinaison' => '12mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '1.18'),
            array('id' => '2213','devis_id' => '83','produit_id' => '25','quantite' => '30','declinaison' => '6mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '1.18'),
            array('id' => '2214','devis_id' => '83','produit_id' => '27','quantite' => '30','declinaison' => '3mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '1.18'),
            array('id' => '2215','devis_id' => '83','produit_id' => '31','quantite' => '20','declinaison' => '12mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '1.18'),
            array('id' => '2216','devis_id' => '83','produit_id' => '33','quantite' => '30','declinaison' => '3mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '1.18'),
            array('id' => '2217','devis_id' => '83','produit_id' => '33','quantite' => '30','declinaison' => '6mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '1.18'),
            array('id' => '2218','devis_id' => '83','produit_id' => '35','quantite' => '50','declinaison' => '12mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '1.18'),
            array('id' => '2219','devis_id' => '83','produit_id' => '37','quantite' => '20','declinaison' => '00mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '1.18'),
            array('id' => '2220','devis_id' => '83','produit_id' => '37','quantite' => '60','declinaison' => '3mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '1.18'),
            array('id' => '2221','devis_id' => '83','produit_id' => '37','quantite' => '40','declinaison' => '6mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '1.18'),
            array('id' => '2222','devis_id' => '83','produit_id' => '37','quantite' => '20','declinaison' => '12mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '1.18'),
            array('id' => '2223','devis_id' => '83','produit_id' => '43','quantite' => '50','declinaison' => '3mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '1.18'),
            array('id' => '2224','devis_id' => '83','produit_id' => '70','quantite' => '40','declinaison' => NULL,'decline_avec_id' => NULL,'prix_special' => NULL,'offert' => NULL,'prix' => '3.90'),
            array('id' => '2225','devis_id' => '83','produit_id' => '72','quantite' => '30','declinaison' => NULL,'decline_avec_id' => NULL,'prix_special' => NULL,'offert' => NULL,'prix' => '3.90'),
            array('id' => '2226','devis_id' => '83','produit_id' => '83','quantite' => '20','declinaison' => NULL,'decline_avec_id' => NULL,'prix_special' => NULL,'offert' => NULL,'prix' => '3.90'),
            array('id' => '2227','devis_id' => '83','produit_id' => '148','quantite' => '80','declinaison' => '00mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '4.76'),
            array('id' => '2228','devis_id' => '83','produit_id' => '151','quantite' => '50','declinaison' => '00mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '4.76'),
            array('id' => '2229','devis_id' => '83','produit_id' => '153','quantite' => '15','declinaison' => '00mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '4.76'),
            array('id' => '2230','devis_id' => '83','produit_id' => '154','quantite' => '20','declinaison' => '00mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '4.76'),
            array('id' => '2231','devis_id' => '83','produit_id' => '162','quantite' => '30','declinaison' => '00mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '4.76'),
            array('id' => '2232','devis_id' => '83','produit_id' => '163','quantite' => '30','declinaison' => '00mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '4.76'),
            array('id' => '2233','devis_id' => '83','produit_id' => '164','quantite' => '20','declinaison' => '00mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '4.76'),
            array('id' => '2234','devis_id' => '83','produit_id' => '175','quantite' => '70','declinaison' => '00mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '4.76'),
            array('id' => '2235','devis_id' => '83','produit_id' => '177','quantite' => '50','declinaison' => '00mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '4.76'),
            array('id' => '2236','devis_id' => '83','produit_id' => '179','quantite' => '10','declinaison' => '00mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '4.76'),
            array('id' => '2237','devis_id' => '83','produit_id' => '180','quantite' => '50','declinaison' => '00mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '4.76'),
            array('id' => '2238','devis_id' => '83','produit_id' => '181','quantite' => '10','declinaison' => '00mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '4.76'),
            array('id' => '2239','devis_id' => '83','produit_id' => '191','quantite' => '20','declinaison' => '00mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '4.76'),
            array('id' => '2240','devis_id' => '83','produit_id' => '192','quantite' => '40','declinaison' => '00mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '4.76'),
            array('id' => '2241','devis_id' => '83','produit_id' => '193','quantite' => '10','declinaison' => '00mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '4.76'),
            array('id' => '2242','devis_id' => '83','produit_id' => '255','quantite' => '10','declinaison' => NULL,'decline_avec_id' => NULL,'prix_special' => NULL,'offert' => NULL,'prix' => '4.76'),
            array('id' => '2243','devis_id' => '83','produit_id' => '264','quantite' => '20','declinaison' => '00mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '4.76'),
            array('id' => '2244','devis_id' => '83','produit_id' => '380','quantite' => '10','declinaison' => '00mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '4.76'),
            array('id' => '2245','devis_id' => '83','produit_id' => '413','quantite' => '40','declinaison' => '00mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '6.23'),
            array('id' => '2246','devis_id' => '83','produit_id' => '414','quantite' => '30','declinaison' => '00mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '6.23'),
            array('id' => '2247','devis_id' => '83','produit_id' => '415','quantite' => '30','declinaison' => '00mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '6.23'),
            array('id' => '2248','devis_id' => '83','produit_id' => '419','quantite' => '20','declinaison' => '00mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '6.23'),
            array('id' => '2249','devis_id' => '83','produit_id' => '423','quantite' => '20','declinaison' => '00mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '6.23'),
            array('id' => '2250','devis_id' => '83','produit_id' => '428','quantite' => '30','declinaison' => '00mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '6.23'),
            array('id' => '2251','devis_id' => '83','produit_id' => '430','quantite' => '10','declinaison' => '00mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '6.23'),
            array('id' => '2252','devis_id' => '83','produit_id' => '432','quantite' => '20','declinaison' => '00mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '6.23'),
            array('id' => '2253','devis_id' => '83','produit_id' => '436','quantite' => '40','declinaison' => '00mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '6.23'),
            array('id' => '2254','devis_id' => '83','produit_id' => '438','quantite' => '10','declinaison' => '00mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '6.23'),
            array('id' => '2255','devis_id' => '83','produit_id' => '439','quantite' => '20','declinaison' => '00mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '6.23'),
            array('id' => '2256','devis_id' => '83','produit_id' => '450','quantite' => '20','declinaison' => '00mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '6.23'),
            array('id' => '2257','devis_id' => '83','produit_id' => '453','quantite' => '10','declinaison' => '00mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '6.23'),
            array('id' => '2258','devis_id' => '83','produit_id' => '454','quantite' => '30','declinaison' => '00mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '6.23'),
            array('id' => '2350','devis_id' => '85','produit_id' => '262','quantite' => '20','declinaison' => '00mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '4.76'),
            array('id' => '2351','devis_id' => '85','produit_id' => '264','quantite' => '20','declinaison' => '00mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '4.76'),
            array('id' => '2352','devis_id' => '85','produit_id' => '271','quantite' => '10','declinaison' => '3mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '1.15'),
            array('id' => '2353','devis_id' => '85','produit_id' => '271','quantite' => '10','declinaison' => '6mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '1.15'),
            array('id' => '2354','devis_id' => '85','produit_id' => '274','quantite' => '20','declinaison' => '3mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '1.15'),
            array('id' => '2355','devis_id' => '85','produit_id' => '275','quantite' => '20','declinaison' => '6mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '1.15'),
            array('id' => '2356','devis_id' => '85','produit_id' => '275','quantite' => '10','declinaison' => '12mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '1.15'),
            array('id' => '2357','devis_id' => '85','produit_id' => '277','quantite' => '20','declinaison' => NULL,'decline_avec_id' => NULL,'prix_special' => NULL,'offert' => NULL,'prix' => '4.76'),
            array('id' => '2358','devis_id' => '85','produit_id' => '279','quantite' => '30','declinaison' => NULL,'decline_avec_id' => NULL,'prix_special' => NULL,'offert' => NULL,'prix' => '4.76'),
            array('id' => '2359','devis_id' => '85','produit_id' => '280','quantite' => '40','declinaison' => NULL,'decline_avec_id' => NULL,'prix_special' => NULL,'offert' => NULL,'prix' => '4.76'),
            array('id' => '2360','devis_id' => '85','produit_id' => '286','quantite' => '20','declinaison' => '3mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '1.15'),
            array('id' => '2361','devis_id' => '85','produit_id' => '286','quantite' => '20','declinaison' => '12mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '1.15'),
            array('id' => '2363','devis_id' => '85','produit_id' => '287','quantite' => '20','declinaison' => '12mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '1.15'),
            array('id' => '2364','devis_id' => '85','produit_id' => '289','quantite' => '40','declinaison' => '3mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '1.15'),
            array('id' => '2365','devis_id' => '85','produit_id' => '289','quantite' => '70','declinaison' => '6mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '1.15'),
            array('id' => '2366','devis_id' => '85','produit_id' => '455','quantite' => '20','declinaison' => '00mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '6.23'),
            array('id' => '2367','devis_id' => '85','produit_id' => '457','quantite' => '10','declinaison' => '00mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '6.23'),
            array('id' => '2368','devis_id' => '85','produit_id' => '112','quantite' => '160','declinaison' => '19,9mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => '1','prix' => NULL),
            array('id' => '2369','devis_id' => '85','produit_id' => '287','quantite' => '30','declinaison' => '6mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '1.15'),
            array('id' => '2370','devis_id' => '85','produit_id' => '276','quantite' => '20','declinaison' => '00mg','decline_avec_id' => '1','prix_special' => NULL,'offert' => NULL,'prix' => '6.23')
        );

        foreach($devis_produit as $dp)
        {
            $p = new DevisProduit();

            $produit  = $dp['produit_id'];
            $quantite  = $dp['quantite'];
            $declinaison  = $dp['declinaison'];
            $decline_avec_id  = $dp['decline_avec_id'];
            $prix_special  = $dp['prix_special'];

            $offert  = $dp['offert'];
            $prix  = $dp['prix'];
            
            
            
            
            $p->setDevis($devis);
            $p->setProduit($produitRepo->find($produit));
            $p->setQuantite($quantite);
            $p->setDeclinaison($declinaison);
            if($decline_avec_id)
            {
                $p->setDeclineAvec($principeActifRepository->find($decline_avec_id));
            }
            
            if($offert)
            {
                $p->setOffert(true);

            }

            $p->setPrix($prix);

            $em->persist($p);
            $em->flush();
            
        }

        return $this->redirect($request->headers->get('referer'));

    }

    public function captureProcessing(Devis $devis, Request $request, EntityManagerInterface $em, CodeGenerate $getCode, Mailer $mailer, FileUploader $fileUploader)
    {
        if ($request->isMethod('POST')) {

            if ($request->request->get('state_progress') == 1) {
                $devis->setStatus('Devis créé');
                $devis = $this->editDevis($devis, $request->request->get('date'), $request->request->get('number'), $getCode);
                
                if (!is_null($request->request->get('sendmail'))) {
                    $mailer->send($devis, 'manual');
                }

                $em->flush();
            }

            if ($request->request->get('state_progress') == 2) {

                $devis->setStatus("Devis signer");
                $devis = $this->editDevis($devis, $request->request->get('date'), $request->request->get('number'), $getCode);
                $devis->setDateSignature(\DateTime::createFromFormat('d/m/Y', trim($request->request->get('date_signed'))));
                $devis->setSigneParClient(true);
                $devis->setValider(true);
                $devis->setDateValidation(new \DateTime());

                $bonDeCommande = new BonDeCommande();
                $bonDeCommande->setCode('tmp');
                $bonDeCommande->setDevis($devis);
                $bonDeCommande->setClient($devis->getClient());
                $bonDeCommande->setNombreProduit($devis->getNombreProduit());
                $bonDeCommande->setResteAlivrer($devis->getNombreProduit());
                $bonDeCommande->setResteAPayer($devis->getTotalTtc());
                $bonDeCommande->setTotalTtc($devis->getTotalTtc());
                $bonDeCommande->setStatus("En attente de paiement ");
                $bonDeCommande->setEnvoyer(true);
                $em->persist($bonDeCommande);
                $em->flush();

                $code = $devis->getCode(); //.'-'.$purchaseOrder->getDate()->format('Y').'-'.$purchaseOrder->getId();
                $bonDeCommande->setCode($code);

                $em->flush();
                $mailer->sendOrder($bonDeCommande);
                
            }

            if ($request->request->get('state_progress') == 3) {

                $devis->setStatus('Devis signer');
                $devis->setDateSignature(new \DateTime());
                $devis->setSigneParClient(true);
                $devis->setValider(true);
                $devis->setDateValidation(new \DateTime());

                $bonDeCommande = new BonDeCommande();
                $bonDeCommande->setDate(\DateTime::createFromFormat('d/m/Y', trim($request->request->get('date'))));
                $bonDeCommande->setDevis($devis);
                $bonDeCommande->setCode('tmp');
                $bonDeCommande->setClient($devis->getClient());
                $bonDeCommande->setNombreProduit($devis->getNombreProduit());
                $bonDeCommande->setResteAlivrer($devis->getNombreProduit());
                $bonDeCommande->setResteAPayer($devis->getTotalTtc());
                $bonDeCommande->setTotalTtc($devis->getTotalTtc());
                $bonDeCommande->setStatus("En attente d'envoi de la facture");
                $bonDeCommande->setEnvoyer(false);


                $em->persist($bonDeCommande);
                $em->flush();

                if ($request->request->get('number')) {
                    $bonDeCommande->setCode($request->request->get('number') . '-' . $devis->getCode());
                } else {
                    $code = $devis->getCode(); //.'-'.$purchaseOrder->getDate()->format('Y').'-'.$purchaseOrder->getId();
                    $bonDeCommande->setCode($code);
                }

                $em->flush();
            }

            $this->addFlash('success', 'Commande créée');

            return $this->redirectToRoute('back_capture_devis_client');
        }
    }

    public function editDevis($devis, $date, $number, CodeGenerate $getCode)
    {

        $devis->setDate(\DateTime::createFromFormat('d/m/Y', trim($date)));
        if ($number) {
            $devis->setCode($getCode->code(4) . '' . $devis->getId());
            $coden = $number . '-' . $devis->getCode();
            $devis->setCode($coden);
        }

        return $devis;
    }

    public function validate(Devis $devis, EntityManagerInterface $em)
    {
        if ($devis->getSigneParClient()) {
            $devis->setStatus("Devis valider");
        } else {
            $devis->setStatus("En attende de signature");
        }

        $devis->setValider(true);
        $devis->setDateValidation(new \DateTime());
        $em->flush();
        $this->addFlash('success', 'Félicitation, vous venez de valider ce devis :)');
        return  $this->redirectToRoute('back_devis_edit', ['id' => $devis->getId()]);
    }

    public function evoieBonDeCommande(Devis $devis, Request $request, EntityManagerInterface $em, CodeGenerate $getCode, Mailer $mailer)
    {
        $bonDeCommande = $em->getRepository('App:BonDeCommande')->findOneBy(['devis' => $devis]);

        if (!$bonDeCommande) {
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
        }

        $em->flush();
        $code = $devis->getCode();
        $bonDeCommande->setCode($code);

        $em->flush();

        $mailer->sendOrder($bonDeCommande);

        $this->addFlash('success', 'Vous venez de valider le devis no ' . $devis->getCode() . ' et envoyer le bon de commande no ' . $bonDeCommande->getCode());
        return  $this->redirectToRoute('back_devis_list');
    }

    public function signature(Devis $devis, Request $request, EntityManagerInterface $em, CodeGenerate $getCode, Mailer $mailer)
    {
        if ($request->isMethod('post')) {

            if ($request->request->get('state_progress') == 2) {

                $devis->setStatus("Devis signer");
                $devis->setDateSignature(new \DateTime());
                $devis->setSigneParClient(true);
                $devis->setValider(true);
                $devis->setDateValidation(new \DateTime());

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

                $em->flush();

                $code = $devis->getCode();
                $bonDeCommande->setCode($code);

                $em->flush();

                $mailer->sendOrder($bonDeCommande);

                $this->addFlash('success', 'Vous venez de signer le devis no ' . $devis->getCode() . ' et envoyer le bon de commande no ' . $bonDeCommande->getCode());
                return  $this->redirectToRoute('back_devis_list');
            }
        } else {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès a cette page');
        }
    }

    public function sendLinkSignature(Devis $devis, Mailer $mailer)
    {

        $mailer->send($devis, 'manual');
        $this->addFlash('success', 'Lien de signature envoyé à ' . $devis->getClient()->getEmail() . ' pour le devis no ' . $devis->getCode());
        return $this->redirectToRoute('back_devis_list');
    }
}
