<?php

namespace App\Entity;

use App\Repository\BonDeCommandeRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\Types\Integer;

/**
 * @ORM\Entity(repositoryClass=BonDeCommandeRepository::class)
 */
class BonDeCommande
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $code;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $date;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $nombreProduit;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $resteAlivrer;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $totalTtc;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $status;

    /**
     * @ORM\OneToOne(targetEntity=Devis::class, inversedBy="bonDeCommande", cascade={"persist", "remove"})
     */
    private $devis;

    /**
     * @ORM\ManyToOne(targetEntity=Client::class, inversedBy="bonDeCommandes")
     */
    private $client;

    /**
     * @ORM\OneToMany(targetEntity=Commandes::class, mappedBy="bonDeCommande", cascade={"remove"})
     */
    private $commandes;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $resteAPayer;


    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $envoyerAuLogistique;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $statusLogistique;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dateLivraison;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $toutEstExpedier;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $prioriter;

    /**
     * @ORM\OneToMany(targetEntity=LotCommande::class, mappedBy="bonDeCommande", cascade={"remove"})
     */
    private $lotCommandes;

    /**
     * @ORM\OneToMany(targetEntity=FactureCommande::class, mappedBy="bonDeCommande")
     */
    private $factureCommandes;

    /**
     * @ORM\OneToMany(targetEntity=BonLivraison::class, mappedBy="bonDeCommande", cascade={"remove"})
     */
    private $bonLivraisons;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $toutEstPayer;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dateToutPaiement;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $traitementTerminer;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $envoyer;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $codeInterne;

    /**
     * @ORM\ManyToMany(targetEntity=GroupeBondeCommande::class, mappedBy="bonDeCommande")
     */
    private $groupeBondeCommandes;

    /**
     * @ORM\ManyToMany(targetEntity=FactureMaitre::class, mappedBy="bonDeCommandes")
     */
    private $factureMaitres;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $downloadDate;

    /** 
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $acompte;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $datePaiementAcompte;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $avoir;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $escompte;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $netFinancier;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $taxe;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $pourcentageEscompte;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $codeInterneClient;


 
    public function __construct()
    {
        $this->commandes = new ArrayCollection();
        $this->lotCommandes = new ArrayCollection();
        $this->factureCommandes = new ArrayCollection();
        $this->bonLivraisons = new ArrayCollection();
        $this->date = new DateTime();
        $this->groupeBondeCommandes = new ArrayCollection();
        $this->factureMaitres = new ArrayCollection();
        
    }

    public function getId(): ?int
    {
        return $this->id;
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

        $this->code = $code;

        return $this;
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

    public function getNombreProduit(): ?int
    {
        return $this->nombreProduit;
    }

    public function setNombreProduit(?int $nombreProduit): self
    {
        $this->nombreProduit = $nombreProduit;

        return $this;
    }

    public function getResteAlivrer(): ?int
    {
        return $this->resteAlivrer;
    }

    public function setResteAlivrer(?int $resteAlivrer): self
    {
        $this->resteAlivrer = $resteAlivrer;

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

    public function getDevis(): ?Devis
    {
        return $this->devis;
    }

    public function setDevis(?Devis $devis): self
    {
        $this->devis = $devis;

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
            $commande->setBonDeCommande($this);
        }

        return $this;
    }

    public function removeCommande(Commandes $commande): self
    {
        if ($this->commandes->removeElement($commande)) {
            // set the owning side to null (unless already changed)
            if ($commande->getBonDeCommande() === $this) {
                $commande->setBonDeCommande(null);
            }
        }

        return $this;
    }


    public function getResteAPayer(): ?string
    {
        return $this->resteAPayer;
    }

    public function setResteAPayer(?string $resteAPayer): self
    {
        $this->resteAPayer = $resteAPayer;

        return $this;
    }

  
    public function getEnvoyerAuLogistique(): ?bool
    {
        return $this->envoyerAuLogistique;
    }

    public function setEnvoyerAuLogistique(?bool $envoyerAuLogistique): self
    {
        $this->envoyerAuLogistique = $envoyerAuLogistique;

        return $this;
    }

    public function getStatusLogistique(): ?string
    {
        return $this->statusLogistique;
    }

    public function setStatusLogistique(?string $statusLogistique): self
    {
        $this->statusLogistique = $statusLogistique;

        return $this;
    }

    public function getDateLivraison(): ?\DateTimeInterface
    {
        return $this->dateLivraison;
    }

    public function setDateLivraison(?\DateTimeInterface $dateLivraison): self
    {
        $this->dateLivraison = $dateLivraison;

        return $this;
    }

    public function getToutEstExpedier(): ?bool
    {
        return $this->toutEstExpedier;
    }

    public function setToutEstExpedier(?bool $toutEstExpedier): self
    {
        $this->toutEstExpedier = $toutEstExpedier;

        return $this;
    }

    public function getPrioriter(): ?bool
    {
        return $this->prioriter;
    }

    public function setPrioriter(?bool $prioriter): self
    {
        $this->prioriter = $prioriter;

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
            $lotCommande->setBonDeCommande($this);
        }

        return $this;
    }

    public function removeLotCommande(LotCommande $lotCommande): self
    {
        if ($this->lotCommandes->removeElement($lotCommande)) {
            // set the owning side to null (unless already changed)
            if ($lotCommande->getBonDeCommande() === $this) {
                $lotCommande->setBonDeCommande(null);
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
            $factureCommande->setBonDeCommande($this);
        }

        return $this;
    }

    public function removeFactureCommande(FactureCommande $factureCommande): self
    {
        if ($this->factureCommandes->removeElement($factureCommande)) {
            // set the owning side to null (unless already changed)
            if ($factureCommande->getBonDeCommande() === $this) {
                $factureCommande->setBonDeCommande(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|BonLivraison[]
     */
    public function getBonLivraisons(): Collection
    {
        return $this->bonLivraisons;
    }

    public function addBonLivraison(BonLivraison $bonLivraison): self
    {
        if (!$this->bonLivraisons->contains($bonLivraison)) {
            $this->bonLivraisons[] = $bonLivraison;
            $bonLivraison->setBonDeCommande($this);
        }

        return $this;
    }

    public function removeBonLivraison(BonLivraison $bonLivraison): self
    {
        if ($this->bonLivraisons->removeElement($bonLivraison)) {
            // set the owning side to null (unless already changed)
            if ($bonLivraison->getBonDeCommande() === $this) {
                $bonLivraison->setBonDeCommande(null);
            }
        }

        return $this;
    }

    public function getToutEstPayer(): ?bool
    {
        return $this->toutEstPayer;
    }

    public function setToutEstPayer(?bool $toutEstPayer): self
    {
        $this->toutEstPayer = $toutEstPayer;

        return $this;
    }

    public function getDateToutPaiement(): ?\DateTimeInterface
    {
        return $this->dateToutPaiement;
    }

    public function setDateToutPaiement(?\DateTimeInterface $dateToutPaiement): self
    {
        $this->dateToutPaiement = $dateToutPaiement;

        return $this;
    }

    public function getTraitementTerminer(): ?bool
    {
        return $this->traitementTerminer;
    }

    public function setTraitementTerminer(?bool $traitementTerminer): self
    {
        $this->traitementTerminer = $traitementTerminer;

        return $this;
    }

    public function getEnvoyer(): ?bool
    {
        return $this->envoyer;
    }

    public function setEnvoyer(?bool $envoyer): self
    {
        $this->envoyer = $envoyer;

        return $this;
    }

    public function getCodeInterne(): ?string
    {
        return $this->codeInterne;
    }

    public function setCodeInterne(?string $codeInterne): self
    {
        $this->codeInterne = $codeInterne;

        return $this;
    }

    /**
     * @return Collection<int, GroupeBondeCommande>
     */
    public function getGroupeBondeCommandes(): Collection
    {
        return $this->groupeBondeCommandes;
    }

    public function addGroupeBondeCommande(GroupeBondeCommande $groupeBondeCommande): self
    {
        if (!$this->groupeBondeCommandes->contains($groupeBondeCommande)) {
            $this->groupeBondeCommandes[] = $groupeBondeCommande;
            $groupeBondeCommande->addBonDeCommande($this);
        }

        return $this;
    }

    public function removeGroupeBondeCommande(GroupeBondeCommande $groupeBondeCommande): self
    {
        if ($this->groupeBondeCommandes->removeElement($groupeBondeCommande)) {
            $groupeBondeCommande->removeBonDeCommande($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, FactureMaitre>
     */
    public function getFactureMaitres(): Collection
    {
        return $this->factureMaitres;
    }

    public function addFactureMaitre(FactureMaitre $factureMaitre): self
    {
        if (!$this->factureMaitres->contains($factureMaitre)) {
            $this->factureMaitres[] = $factureMaitre;
            $factureMaitre->addBonDeCommande($this);
        }

        return $this;
    }

    public function removeFactureMaitre(FactureMaitre $factureMaitre): self
    {
        if ($this->factureMaitres->removeElement($factureMaitre)) {
            $factureMaitre->removeBonDeCommande($this);
        }

        return $this;
    }

    public function getDownloadDate(): ?\DateTimeInterface
    {
        return $this->downloadDate;
    }

    public function setDownloadDate(?\DateTimeInterface $downloadDate): self
    {
        $this->downloadDate = $downloadDate;
        return $this;
    }

    public function getAcompte(): ?string
    {
        return $this->acompte;
    }

    public function setAcompte(?string $acompte): self
    {
        $this->acompte = $acompte;

        return $this;
    }

    public function getDatePaiementAcompte(): ?\DateTimeInterface
    {
        return $this->datePaiementAcompte;
    }

    public function setDatePaiementAcompte(?\DateTimeInterface $datePaiementAcompte): self
    {
        $this->datePaiementAcompte = $datePaiementAcompte;

        return $this;
    }

    public function getAvoir(): ?string
    {
        return $this->avoir;
    }

    public function setAvoir(?string $avoir): self
    {
        $this->avoir = $avoir;

        return $this;
    }

    public function getEscompte(): ?string
    {
        return $this->escompte;
    }

    public function setEscompte(?string $escompte): self
    {
        $this->escompte = $escompte;

        return $this;
    }

    public function getNetFinancier(): ?string
    {
        return $this->netFinancier;
    }

    public function setNetFinancier(?string $netFinancier): self
    {
        $this->netFinancier = $netFinancier;

        return $this;
    }

    public function getTaxe(): ?string
    {
        return $this->taxe;
    }

    public function setTaxe(?string $taxe): self
    {
        $this->taxe = $taxe;

        return $this;
    }

    public function getPourcentageEscompte(): ?string
    {
        return $this->pourcentageEscompte;
    }

    public function setPourcentageEscompte(?string $pourcentageEscompte): self
    {
        $this->pourcentageEscompte = $pourcentageEscompte;

        return $this;
    }

    public function getCodeInterneClient(): ?string
    {
        return $this->codeInterneClient;
    }

    public function setCodeInterneClient(?string $codeInterneClient): self
    {
        $this->codeInterneClient = $codeInterneClient;

        return $this;
    }

}
