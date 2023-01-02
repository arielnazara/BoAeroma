<?php

namespace App\Entity;

use App\Repository\DevisRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DevisRepository::class)
 */
class Devis
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $date;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $code;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $valider;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dateValidation;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $signeParClient;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dateSignature;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2,  nullable=true)
     */
    private $taxe;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $totalTtc;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $status;

    /**
     * @ORM\ManyToOne(targetEntity=Client::class, inversedBy="devis")
     */
    private $client;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $totalHt;

    /**
     * @ORM\OneToOne(targetEntity=BonDeCommande::class, mappedBy="devis", cascade={"persist", "remove"})
     */
    private $bonDeCommande;

    /**
     * @ORM\OneToMany(targetEntity=DevisProduit::class, mappedBy="devis", cascade={"remove"})
     */
    private $devisProduits;

    /**
     * @ORM\OneToMany(targetEntity=FactureCommande::class, mappedBy="devis", cascade={"remove"})
     */
    private $factureCommandes;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $nombreProduit;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $fraisExpedition;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $montant;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $abandonner;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $message;

    /**
     * @ORM\Column(type="string", length=500, nullable=true)
     */
    private $token;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $codeDeValidation;

    /**
     * @ORM\OneToMany(targetEntity=Commandes::class, mappedBy="devis")
     */
    private $commandes;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $creerManuellement;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $shop;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $shopOrderId;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $payerParCarte;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $montantPayerParCarte;

    /**
     * @ORM\ManyToOne(targetEntity=Shop::class, inversedBy="devis")
     */
    private $boutique;

    /**
     * @ORM\OneToOne(targetEntity=MontantFinal::class, mappedBy="devis", cascade={"persist", "remove"})
     */
    private $montantFinal;

    public function __construct()
    {
        $this->devisProduits = new ArrayCollection();
        $this->factureCommandes = new ArrayCollection();
        $this->commandes = new ArrayCollection();
    }

    public function getProduitOffert()
    {
        foreach($this->getDevisProduits() as $d)
        {
            if($d->getOffert())
            {
                return $d;
            }
        }

        return null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): self
    {

        $code = trim($code);
        $code = str_replace('é', 'e', $code);
        $code = str_replace('è', 'e', $code);
        $code = str_replace('ê', 'e', $code);
        $code = str_replace('ç', 'c', $code);
        $code = str_replace('à', 'a', $code);

        $code = preg_replace('/[^A-Za-z0-9\-]/', '-', $code);
        
        $this->code = $code;

        return $this;
    }

    public function getValider(): ?bool
    {
        return $this->valider;
    }

    public function setValider(?bool $valider): self
    {
        $this->valider = $valider;

        return $this;
    }

    public function getDateValidation(): ?\DateTimeInterface
    {
        return $this->dateValidation;
    }

    public function setDateValidation(?\DateTimeInterface $dateValidation): self
    {
        $this->dateValidation = $dateValidation;

        return $this;
    }

    public function getSigneParClient(): ?bool
    {
        return $this->signeParClient;
    }

    public function setSigneParClient(?bool $signeParClient): self
    {
        $this->signeParClient = $signeParClient;

        return $this;
    }

    public function getDateSignature(): ?\DateTimeInterface
    {
        return $this->dateSignature;
    }

    public function setDateSignature(?\DateTimeInterface $dateSignature): self
    {
        $this->dateSignature = $dateSignature;

        return $this;
    }

    public function getTaxe(): ?string
    {
        return $this->taxe;
    }

    public function setTaxe(string $taxe): self
    {
        $this->taxe = $taxe;

        return $this;
    }

    public function getTotalTtc(): ?string
    {
        return $this->totalTtc;
    }

    public function setTotalTtc(?string $totalTtc): self
    {
        $this->totalTtc = $totalTtc;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getTotalHt(): ?string
    {
        return $this->totalHt;
    }

    public function setTotalHt(?string $totalHt): self
    {
        $this->totalHt = $totalHt;

        return $this;
    }

    public function getBonDeCommande(): ?BonDeCommande
    {
        return $this->bonDeCommande;
    }

    public function setBonDeCommande(?BonDeCommande $bonDeCommande): self
    {
        $this->bonDeCommande = $bonDeCommande;

        // set (or unset) the owning side of the relation if necessary
        $newDevis = null === $bonDeCommande ? null : $this;
        if ($bonDeCommande->getDevis() !== $newDevis) {
            $bonDeCommande->setDevis($newDevis);
        }

        return $this;
    }

    /**
     * @return Collection|DevisProduit[]
     */
    public function getDevisProduits(): Collection
    {
        return $this->devisProduits;
    }

    public function addDevisProduit(DevisProduit $devisProduit): self
    {
        if (!$this->devisProduits->contains($devisProduit)) {
            $this->devisProduits[] = $devisProduit;
            $devisProduit->setDevis($this);
        }

        return $this;
    }

    public function removeDevisProduit(DevisProduit $devisProduit): self
    {
        if ($this->devisProduits->removeElement($devisProduit)) {
            // set the owning side to null (unless already changed)
            if ($devisProduit->getDevis() === $this) {
                $devisProduit->setDevis(null);
            }
        }

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
            $factureCommande->setDevis($this);
        }

        return $this;
    }

    public function removeFactureCommande(FactureCommande $factureCommande): self
    {
        if ($this->factureCommandes->removeElement($factureCommande)) {
            // set the owning side to null (unless already changed)
            if ($factureCommande->getDevis() === $this) {
                $factureCommande->setDevis(null);
            }
        }

        return $this;
    }

    public function getNombreProduit(): ?int
    {
        return $this->nombreProduit;
    }

    public function setNombreProduit(?int $nombreProduit): self
    {
        $this->nombreProduit = $nombreProduit;

        return $this;
    }

    public function getFraisExpedition(): ?string
    {
        return $this->fraisExpedition;
    }

    public function setFraisExpedition(?string $fraisExpedition): self
    {
        $this->fraisExpedition = $fraisExpedition;

        return $this;
    }

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(?string $montant): self
    {
        $this->montant = $montant;

        return $this;
    }

    public function getAbandonner(): ?bool
    {
        return $this->abandonner;
    }

    public function setAbandonner(?bool $abandonner): self
    {
        $this->abandonner = $abandonner;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getCodeDeValidation(): ?string
    {
        return $this->codeDeValidation;
    }

    public function setCodeDeValidation(?string $codeDeValidation): self
    {
        $this->codeDeValidation = $codeDeValidation;

        return $this;
    }

    public function __toString()
    {
        return $this->status;
    }

    /**
     * @return Collection|Commandes[]
     */
    public function getCommandes(): Collection
    {
        return $this->commandes;
    }

    public function addCommande(Commandes $commande): self
    {
        if (!$this->commandes->contains($commande)) {
            $this->commandes[] = $commande;
            $commande->setDevis($this);
        }

        return $this;
    }

    public function removeCommande(Commandes $commande): self
    {
        if ($this->commandes->removeElement($commande)) {
            // set the owning side to null (unless already changed)
            if ($commande->getDevis() === $this) {
                $commande->setDevis(null);
            }
        }

        return $this;
    }

    public function getCreerManuellement(): ?bool
    {
        return $this->creerManuellement;
    }

    public function setCreerManuellement(?bool $creerManuellement): self
    {
        $this->creerManuellement = $creerManuellement;

        return $this;
    }

    public function getShop(): ?string
    {
        return $this->shop;
    }

    public function setShop(?string $shop): self
    {
        $this->shop = $shop;

        return $this;
    }

    public function getShopOrderId(): ?int
    {
        return $this->shopOrderId;
    }

    public function setShopOrderId(?int $shopOrderId): self
    {
        $this->shopOrderId = $shopOrderId;

        return $this;
    }

    public function getPayerParCarte(): ?bool
    {
        return $this->payerParCarte;
    }

    public function setPayerParCarte(?bool $payerParCarte): self
    {
        $this->payerParCarte = $payerParCarte;

        return $this;
    }

    public function getMontantPayerParCarte(): ?string
    {
        return $this->montantPayerParCarte;
    }

    public function setMontantPayerParCarte(?string $montantPayerParCarte): self
    {
        $this->montantPayerParCarte = $montantPayerParCarte;

        return $this;
    }

    public function getBoutique(): ?Shop
    {
        return $this->boutique;
    }

    public function setBoutique(?Shop $boutique): self
    {
        $this->boutique = $boutique;

        return $this;
    }

    public function getMontantFinal(): ?MontantFinal
    {
        return $this->montantFinal;
    }

    public function setMontantFinal(?MontantFinal $montantFinal): self
    {
        // unset the owning side of the relation if necessary
        if ($montantFinal === null && $this->montantFinal !== null) {
            $this->montantFinal->setDevis(null);
        }

        // set the owning side of the relation if necessary
        if ($montantFinal !== null && $montantFinal->getDevis() !== $this) {
            $montantFinal->setDevis($this);
        }

        $this->montantFinal = $montantFinal;

        return $this;
    }

    public function getProductsId(): array
    {
        $val = [];

        foreach($this->getDevisProduits() as $d)
        {
            array_push($val, $d->getProduit()->getId());
        }

        return $val;
    }
}
