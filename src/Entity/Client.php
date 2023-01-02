<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OrderBy;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups ;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


/**
 * @ORM\Entity(repositoryClass="App\Repository\ClientRepository")
 * @UniqueEntity(fields="email", message="Cette adresse email existe déjà")
 * @UniqueEntity(fields="Siren", message="SIREN existe déjà")
 * 
 */
class Client
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"produit:read", "mb:read"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=191, nullable=true)
     */
    private $raisonSocial;

    /**
     * @ORM\Column(type="string", length=191)
     */
    private $lastName;

    /**
     * @ORM\Column(type="string", length=191)
     */
    private $firstName;


    /**
     * @ORM\Column(type="string", length=191, unique=true)
     */
    private $email;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $date;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $Siren;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $idPrestashop;

    /**
     * @ORM\OneToMany(targetEntity=AdresseLivraison::class, mappedBy="client")
     */
    private $adresseLivraisons;

    /**
     * @ORM\OneToMany(targetEntity=Contact::class, mappedBy="client")
     */
    private $contacts;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $adresseFacturation;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $noTva;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $principaleActivite;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $formeJuridique;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $capital;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $codeNaf;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"livraison"})
     * 
     */
    private $adresseServiceAchats;


    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $remarque;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $domiciliationBancaire;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $iban;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $swift;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $regimeTva;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $horaireLivraison;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $codePostalFacturation;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $villeFacturation;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"livraison"})
     * 
     */
    private $codePostalServiceAchat;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"livraison"})
     * 
     */
    private $villeServiceAchat;

    /**
     * @ORM\OneToMany(targetEntity=PrixDeclinaison::class, mappedBy="client")
     */
    private $prixDeclinaisons;

    /**
     * @ORM\OneToMany(targetEntity=MarqueBlanche::class, mappedBy="client")
     */
    private $marqueBlanches;

    /**
     * @ORM\OneToMany(targetEntity=Marque::class, mappedBy="client")
     */
    private $marques;

    /**
     * @ORM\OneToMany(targetEntity=ClientExclusif::class, mappedBy="client")
     */
    private $clientExclusifs;

    /**
     * @ORM\OneToMany(targetEntity=Devis::class, mappedBy="client")
     */
    private $devis;

    /**
     * @ORM\OneToMany(targetEntity=BonDeCommande::class, mappedBy="client")
     */
    private $bonDeCommandes;

    /**
     * @ORM\OneToMany(targetEntity=LotCommande::class, mappedBy="client")
     */
    private $lotCommandes;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $telephone;

    /**
     * @ORM\OneToMany(targetEntity=FactureCommande::class, mappedBy="client")
     */
    private $factureCommandes;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $pays;

    /**
     * @ORM\OneToOne(targetEntity=GammeClient::class, mappedBy="client", cascade={"persist", "remove"})
     */
    private $gammeClient;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $jourLivraison;

    /**
     * @ORM\OneToMany(targetEntity=Tarif::class, mappedBy="client")
     */
    private $tarifs;

    /**
     * @ORM\ManyToOne(targetEntity=TypeDeClient::class, inversedBy="clients")
     */
    private $typeDeClient;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $MdpTemporaire;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $communeFacturation;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * 
     * 
     */
    private $communeServiceAchat;

    /**
     * @ORM\OneToMany(targetEntity=Shop::class, mappedBy="client")
     */
    private $shops;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isValid;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $nomDestinataire;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $ajoutNumeroLot;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $ceres;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $extravape;

    /**
     * @ORM\OneToMany(targetEntity=PositionGammeClient::class, mappedBy="client")
     * @OrderBy({"position" = "ASC"})
     */
    private $positionGammeClients;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $produitOffert;

     /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $escompte;
    /*
     * @ORM\OneToMany(targetEntity=Prestation::class, mappedBy="client")
     */
    private $prestations;

    /**
     * @ORM\OneToMany(targetEntity=GammeMarqueBlanche::class, mappedBy="client")
     */
    private $gammeMarqueBlanches;

    /**
     * @ORM\OneToMany(targetEntity=CatalogueClient::class, mappedBy="client")
     */
    private $catalogueClients;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $secondEmail;


    public function __construct()
    {
        $this->date = new \DateTime();
        $this->adresseLivraisons = new ArrayCollection();
        $this->contacts = new ArrayCollection();
        $this->prixDeclinaisons = new ArrayCollection();
        $this->marqueBlanches = new ArrayCollection();
        $this->marques = new ArrayCollection();
        $this->clientExclusifs = new ArrayCollection();
        $this->devis = new ArrayCollection();
        $this->bonDeCommandes = new ArrayCollection();
        $this->lotCommandes = new ArrayCollection();
        $this->factureCommandes = new ArrayCollection();
        $this->tarifs = new ArrayCollection();
        $this->shops = new ArrayCollection();
        $this->positionGammeClients = new ArrayCollection();
        $this->prestations = new ArrayCollection();
        $this->gammeMarqueBlanches = new ArrayCollection();
        $this->catalogueClients = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }


    public function getRaisonSocial(): ?string
    {
        return $this->raisonSocial;
    }

    public function setRaisonSocial(?string $raisonSocial): self
    {
        $this->raisonSocial = $raisonSocial;

        return $this;
    }


    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }


    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }


    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getSiren(): ?string
    {
        return $this->Siren;
    }

    public function setSiren(?string $Siren): self
    {
        $this->Siren = $Siren;

        return $this;
    }

    public function getIdPrestashop(): ?int
    {
        return $this->idPrestashop;
    }

    public function setIdPrestashop(?int $idPrestashop): self
    {
        $this->idPrestashop = $idPrestashop;

        return $this;
    }

    /**
     * @return Collection|AdresseLivraison[]
     */
    public function getAdresseLivraisons(): Collection
    {
        return $this->adresseLivraisons;
    }

    public function addAdresseLivraison(AdresseLivraison $adresseLivraison): self
    {
        if (!$this->adresseLivraisons->contains($adresseLivraison)) {
            $this->adresseLivraisons[] = $adresseLivraison;
            $adresseLivraison->setClient($this);
        }

        return $this;
    }

    public function removeAdresseLivraison(AdresseLivraison $adresseLivraison): self
    {
        if ($this->adresseLivraisons->removeElement($adresseLivraison)) {
            // set the owning side to null (unless already changed)
            if ($adresseLivraison->getClient() === $this) {
                $adresseLivraison->setClient(null);
            }
        }

        return $this;
    }

    public function __toString()
    {
        return $this->getFirstName() . ' ' . $this->getLastName();
    }

    /**
     * @return Collection|Contact[]
     */
    public function getContacts(): Collection
    {
        return $this->contacts;
    }

    public function addContact(Contact $contact): self
    {
        if (!$this->contacts->contains($contact)) {
            $this->contacts[] = $contact;
            $contact->setClient($this);
        }

        return $this;
    }

    public function removeContact(Contact $contact): self
    {
        if ($this->contacts->removeElement($contact)) {
            // set the owning side to null (unless already changed)
            if ($contact->getClient() === $this) {
                $contact->setClient(null);
            }
        }

        return $this;
    }

    public function getAdresseFacturation(): ?string
    {
        return $this->adresseFacturation;
    }

    public function setAdresseFacturation(?string $adresseFacturation): self
    {
        $this->adresseFacturation = $adresseFacturation;

        return $this;
    }

    public function getNoTva(): ?string
    {
        return $this->noTva;
    }

    public function setNoTva(?string $noTva): self
    {
        $this->noTva = $noTva;

        return $this;
    }

    public function getPrincipaleActivite(): ?string
    {
        return $this->principaleActivite;
    }

    public function setPrincipaleActivite(?string $principaleActivite): self
    {
        $this->principaleActivite = $principaleActivite;

        return $this;
    }

    public function getFormeJuridique(): ?string
    {
        return $this->formeJuridique;
    }

    public function setFormeJuridique(?string $formeJuridique): self
    {
        $this->formeJuridique = $formeJuridique;

        return $this;
    }

    public function getCapital(): ?string
    {
        return $this->capital;
    }

    public function setCapital(?string $capital): self
    {
        $this->capital = $capital;

        return $this;
    }

    public function getCodeNaf(): ?string
    {
        return $this->codeNaf;
    }

    public function setCodeNaf(?string $codeNaf): self
    {
        $this->codeNaf = $codeNaf;

        return $this;
    }

    public function getAdresseServiceAchats(): ?string
    {
        return $this->adresseServiceAchats;
    }

    public function setAdresseServiceAchats(?string $adresseServiceAchats): self
    {
        $this->adresseServiceAchats = $adresseServiceAchats;

        return $this;
    }


    public function getRemarque(): ?string
    {
        return $this->remarque;
    }

    public function setRemarque(?string $remarque): self
    {
        $this->remarque = $remarque;

        return $this;
    }


    public function getDomiciliationBancaire(): ?string
    {
        return $this->domiciliationBancaire;
    }

    public function setDomiciliationBancaire(?string $domiciliationBancaire): self
    {
        $this->domiciliationBancaire = $domiciliationBancaire;

        return $this;
    }

    public function getIban(): ?string
    {
        return $this->iban;
    }

    public function setIban(?string $iban): self
    {
        $this->iban = $iban;

        return $this;
    }

    public function getSwift(): ?string
    {
        return $this->swift;
    }

    public function setSwift(?string $swift): self
    {
        $this->swift = $swift;

        return $this;
    }

    public function getRegimeTva(): ?string
    {
        return $this->regimeTva;
    }

    public function setRegimeTva(?string $regimeTva): self
    {
        $this->regimeTva = $regimeTva;

        return $this;
    }

    public function getHoraireLivraison(): ?string
    {
        return $this->horaireLivraison;
    }

    public function setHoraireLivraison(?string $horaireLivraison): self
    {
        $this->horaireLivraison = $horaireLivraison;

        return $this;
    }

    public function getCodePostalFacturation(): ?string
    {
        return $this->codePostalFacturation;
    }

    public function setCodePostalFacturation(?string $codePostalFacturation): self
    {
        $this->codePostalFacturation = $codePostalFacturation;

        return $this;
    }

    public function getVilleFacturation(): ?string
    {
        return $this->villeFacturation;
    }

    public function setVilleFacturation(?string $villeFacturation): self
    {
        $this->villeFacturation = $villeFacturation;

        return $this;
    }

    public function getCodePostalServiceAchat(): ?string
    {
        return $this->codePostalServiceAchat;
    }

    public function setCodePostalServiceAchat(?string $codePostalServiceAchat): self
    {
        $this->codePostalServiceAchat = $codePostalServiceAchat;

        return $this;
    }

    public function getVilleServiceAchat(): ?string
    {
        return $this->villeServiceAchat;
    }

    public function setVilleServiceAchat(?string $villeServiceAchat): self
    {
        $this->villeServiceAchat = $villeServiceAchat;

        return $this;
    }

    /**
     * @return Collection|PrixDeclinaison[]
     */
    public function getPrixDeclinaisons(): Collection
    {
        return $this->prixDeclinaisons;
    }

    public function addPrixDeclinaison(PrixDeclinaison $prixDeclinaison): self
    {
        if (!$this->prixDeclinaisons->contains($prixDeclinaison)) {
            $this->prixDeclinaisons[] = $prixDeclinaison;
            $prixDeclinaison->setClient($this);
        }

        return $this;
    }

    public function removePrixDeclinaison(PrixDeclinaison $prixDeclinaison): self
    {
        if ($this->prixDeclinaisons->removeElement($prixDeclinaison)) {
            // set the owning side to null (unless already changed)
            if ($prixDeclinaison->getClient() === $this) {
                $prixDeclinaison->setClient(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|MarqueBlanche[]
     */
    public function getMarqueBlanches(): Collection
    {
        return $this->marqueBlanches;
    }

    public function addMarqueBlanch(MarqueBlanche $marqueBlanch): self
    {
        if (!$this->marqueBlanches->contains($marqueBlanch)) {
            $this->marqueBlanches[] = $marqueBlanch;
            $marqueBlanch->setClient($this);
        }

        return $this;
    }

    public function removeMarqueBlanch(MarqueBlanche $marqueBlanch): self
    {
        if ($this->marqueBlanches->removeElement($marqueBlanch)) {
            // set the owning side to null (unless already changed)
            if ($marqueBlanch->getClient() === $this) {
                $marqueBlanch->setClient(null);
            }
        }

        return $this;
    }

    public function getFullName()
    {
        return $this->getFirstName()." ". $this->getLastName();
    }

    /**
     * @return Collection|Marque[]
     */
    public function getMarques(): Collection
    {
        return $this->marques;
    }

    public function addMarque(Marque $marque): self
    {
        if (!$this->marques->contains($marque)) {
            $this->marques[] = $marque;
            $marque->setClient($this);
        }

        return $this;
    }

    public function removeMarque(Marque $marque): self
    {
        if ($this->marques->removeElement($marque)) {
            // set the owning side to null (unless already changed)
            if ($marque->getClient() === $this) {
                $marque->setClient(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|ClientExclusif[]
     */
    public function getClientExclusifs(): Collection
    {
        return $this->clientExclusifs;
    }

    public function addClientExclusif(ClientExclusif $clientExclusif): self
    {
        if (!$this->clientExclusifs->contains($clientExclusif)) {
            $this->clientExclusifs[] = $clientExclusif;
            $clientExclusif->setClient($this);
        }

        return $this;
    }

    public function removeClientExclusif(ClientExclusif $clientExclusif): self
    {
        if ($this->clientExclusifs->removeElement($clientExclusif)) {
            // set the owning side to null (unless already changed)
            if ($clientExclusif->getClient() === $this) {
                $clientExclusif->setClient(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Devis[]
     */
    public function getDevis(): Collection
    {
        return $this->devis;
    }

    public function addDevi(Devis $devi): self
    {
        if (!$this->devis->contains($devi)) {
            $this->devis[] = $devi;
            $devi->setClient($this);
        }

        return $this;
    }

    public function removeDevi(Devis $devi): self
    {
        if ($this->devis->removeElement($devi)) {
            // set the owning side to null (unless already changed)
            if ($devi->getClient() === $this) {
                $devi->setClient(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|BonDeCommande[]
     */
    public function getBonDeCommandes(): Collection
    {
        return $this->bonDeCommandes;
    }

    public function addBonDeCommande(BonDeCommande $bonDeCommande): self
    {
        if (!$this->bonDeCommandes->contains($bonDeCommande)) {
            $this->bonDeCommandes[] = $bonDeCommande;
            $bonDeCommande->setClient($this);
        }

        return $this;
    }

    public function removeBonDeCommande(BonDeCommande $bonDeCommande): self
    {
        if ($this->bonDeCommandes->removeElement($bonDeCommande)) {
            // set the owning side to null (unless already changed)
            if ($bonDeCommande->getClient() === $this) {
                $bonDeCommande->setClient(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|LotCommande[]
     */
    public function getLotCommandes(): Collection
    {
        return $this->lotCommandes;
    }

    public function addLotCommande(LotCommande $lotCommande): self
    {
        if (!$this->lotCommandes->contains($lotCommande)) {
            $this->lotCommandes[] = $lotCommande;
            $lotCommande->setClient($this);
        }

        return $this;
    }

    public function removeLotCommande(LotCommande $lotCommande): self
    {
        if ($this->lotCommandes->removeElement($lotCommande)) {
            // set the owning side to null (unless already changed)
            if ($lotCommande->getClient() === $this) {
                $lotCommande->setClient(null);
            }
        }

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;

        return $this;
    }

    /**
     * @return Collection|FactureCommande[]
     */
    public function getFactureCommandes(): Collection
    {
        return $this->factureCommandes;
    }

    public function addFactureCommande(FactureCommande $factureCommande): self
    {
        if (!$this->factureCommandes->contains($factureCommande)) {
            $this->factureCommandes[] = $factureCommande;
            $factureCommande->setClient($this);
        }

        return $this;
    }

    public function removeFactureCommande(FactureCommande $factureCommande): self
    {
        if ($this->factureCommandes->removeElement($factureCommande)) {
            // set the owning side to null (unless already changed)
            if ($factureCommande->getClient() === $this) {
                $factureCommande->setClient(null);
            }
        }

        return $this;
    }

    public function getPays(): ?string
    {
        return $this->pays;
    }

    public function setPays(?string $pays): self
    {
        $this->pays = $pays;

        return $this;
    }

    public function getGammeClient(): ?GammeClient
    {
        return $this->gammeClient;
    }

    public function setGammeClient(?GammeClient $gammeClient): self
    {
        // unset the owning side of the relation if necessary
        if ($gammeClient === null && $this->gammeClient !== null) {
            $this->gammeClient->setClient(null);
        }

        // set the owning side of the relation if necessary
        if ($gammeClient !== null && $gammeClient->getClient() !== $this) {
            $gammeClient->setClient($this);
        }

        $this->gammeClient = $gammeClient;

        return $this;
    }

    public function getJourLivraison(): ?string
    {
        return $this->jourLivraison;
    }

    public function setJourLivraison(?string $jourLivraison): self
    {
        $this->jourLivraison = $jourLivraison;

        return $this;
    }

    /**
     * @return Collection|Tarif[]
     */
    public function getTarifs(): Collection
    {
        return $this->tarifs;
    }

    public function addTarif(Tarif $tarif): self
    {
        if (!$this->tarifs->contains($tarif)) {
            $this->tarifs[] = $tarif;
            $tarif->setClient($this);
        }

        return $this;
    }

    public function removeTarif(Tarif $tarif): self
    {
        if ($this->tarifs->removeElement($tarif)) {
            // set the owning side to null (unless already changed)
            if ($tarif->getClient() === $this) {
                $tarif->setClient(null);
            }
        }

        return $this;
    }

    public function getTypeDeClient(): ?TypeDeClient
    {
        return $this->typeDeClient;
    }

    public function setTypeDeClient(?TypeDeClient $typeDeClient): self
    {
        $this->typeDeClient = $typeDeClient;

        return $this;
    }

    public function getMdpTemporaire(): ?string
    {
        return $this->MdpTemporaire;
    }

    public function setMdpTemporaire(?string $MdpTemporaire): self
    {
        $this->MdpTemporaire = $MdpTemporaire;

        return $this;
    }

    public function getCommuneFacturation(): ?string
    {
        return $this->communeFacturation;
    }

    public function setCommuneFacturation(?string $communeFacturation): self
    {
        $this->communeFacturation = $communeFacturation;

        return $this;
    }

    public function getCommuneServiceAchat(): ?string
    {
        return $this->communeServiceAchat;
    }

    public function setCommuneServiceAchat(?string $communeServiceAchat): self
    {
        $this->communeServiceAchat = $communeServiceAchat;

        return $this;
    }

    /**
     * @return Collection|Shop[]
     */
    public function getShops(): Collection
    {
        return $this->shops;
    }

    public function addShop(Shop $shop): self
    {
        if (!$this->shops->contains($shop)) {
            $this->shops[] = $shop;
            $shop->setClient($this);
        }

        return $this;
    }

    public function removeShop(Shop $shop): self
    {
        if ($this->shops->removeElement($shop)) {
            // set the owning side to null (unless already changed)
            if ($shop->getClient() === $this) {
                $shop->setClient(null);
            }
        }

        return $this;
    }

    public function getIsValid(): ?bool
    {
        return $this->isValid;
    }

    public function setIsValid(?bool $isValid): self
    {
        $this->isValid = $isValid;

        return $this;
    }

    public function getNomDestinataire(): ?string
    {
        return $this->nomDestinataire;
    }

    public function setNomDestinataire(?string $nomDestinataire): self
    {
        $this->nomDestinataire = $nomDestinataire;

        return $this;
    }

    public function getAjoutNumeroLot(): ?bool
    {
        return $this->ajoutNumeroLot;
    }

    public function setAjoutNumeroLot(?bool $ajoutNumeroLot): self
    {
        $this->ajoutNumeroLot = $ajoutNumeroLot;

        return $this;
    }

    public function getCeres(): ?bool
    {
        return $this->ceres;
    }

    public function setCeres(?bool $ceres): self
    {
        $this->ceres = $ceres;

        return $this;
    }

    public function getExtravape(): ?bool
    {
        return $this->extravape;
    }

    public function setExtravape(?bool $extravape): self
    {
        $this->extravape = $extravape;

        return $this;
    }

    /**
     * @return Collection<int, PositionGammeClient>
     */
    public function getPositionGammeClients(): Collection
    {
        return $this->positionGammeClients;
    }

    public function addPositionGammeClient(PositionGammeClient $positionGammeClient): self
    {
        if (!$this->positionGammeClients->contains($positionGammeClient)) {
            $this->positionGammeClients[] = $positionGammeClient;
            $positionGammeClient->setClient($this);
        }

        return $this;
    }

    public function removePositionGammeClient(PositionGammeClient $positionGammeClient): self
    {
        if ($this->positionGammeClients->removeElement($positionGammeClient)) {
            // set the owning side to null (unless already changed)
            if ($positionGammeClient->getClient() === $this) {
                $positionGammeClient->setClient(null);
            }
        }

        return $this;
    }

    public function getProduitOffert(): ?bool
    {
        return $this->produitOffert;
    }

    public function setProduitOffert(?bool $produitOffert): self
    {
        $this->produitOffert = $produitOffert;
        return $this;
    }

    public function getEscompte(): ?bool
    {
        return $this->escompte;
    }

    public function setEscompte(?bool $escompte): self
    {
        $this->escompte = $escompte;
        return $this;
    }

    /**
     * @return Collection<int, Prestation>
     */
    public function getPrestations(): Collection
    {
        return $this->prestations;
    }

    public function addPrestation(Prestation $prestation): self
    {
        if (!$this->prestations->contains($prestation)) {
            $this->prestations[] = $prestation;
            $prestation->setClient($this);
        }

        return $this;
    }

    public function removePrestation(Prestation $prestation): self
    {
        if ($this->prestations->removeElement($prestation)) {
            // set the owning side to null (unless already changed)
            if ($prestation->getClient() === $this) {
                $prestation->setClient(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, GammeMarqueBlanche>
     */
    public function getGammeMarqueBlanches(): Collection
    {
        return $this->gammeMarqueBlanches;
    }

    public function addGammeMarqueBlanch(GammeMarqueBlanche $gammeMarqueBlanch): self
    {
        if (!$this->gammeMarqueBlanches->contains($gammeMarqueBlanch)) {
            $this->gammeMarqueBlanches[] = $gammeMarqueBlanch;
            $gammeMarqueBlanch->setClient($this);
        }

        return $this;
    }

    public function removeGammeMarqueBlanch(GammeMarqueBlanche $gammeMarqueBlanch): self
    {
        if ($this->gammeMarqueBlanches->removeElement($gammeMarqueBlanch)) {
            // set the owning side to null (unless already changed)
            if ($gammeMarqueBlanch->getClient() === $this) {
                $gammeMarqueBlanch->setClient(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CatalogueClient>
     */
    public function getCatalogueClients(): Collection
    {
        return $this->catalogueClients;
    }

    public function addCatalogueClient(CatalogueClient $catalogueClient): self
    {
        if (!$this->catalogueClients->contains($catalogueClient)) {
            $this->catalogueClients[] = $catalogueClient;
            $catalogueClient->setClient($this);
        }

        return $this;
    }

    public function removeCatalogueClient(CatalogueClient $catalogueClient): self
    {
        if ($this->catalogueClients->removeElement($catalogueClient)) {
            // set the owning side to null (unless already changed)
            if ($catalogueClient->getClient() === $this) {
                $catalogueClient->setClient(null);
            }
        }

        return $this;
    }

    public function getSecondEmail(): ?string
    {
        return $this->secondEmail;
    }

    public function setSecondEmail(?string $secondEmail): self
    {
        $this->secondEmail = $secondEmail;

        return $this;
    }

}
