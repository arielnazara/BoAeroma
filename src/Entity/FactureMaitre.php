<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\FactureMaitreRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=FactureMaitreRepository::class)
 * @ORM\HasLifecycleCallbacks()
 */
class FactureMaitre
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"info"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"info"})
     */
    private $numero;


    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $date;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $datePaiement;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $titre;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $balance;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $estPayer = false;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $montantPayer;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $MontantAPayer;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $soldeFinal;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $total;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"info"})
     */
    private $totalHt;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"info"})
     */
    private $tva;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $dejaPayer;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"info"})
     */
    private $totalTtc;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $expedier;

    /**
     * @ORM\ManyToMany(targetEntity=BonDeCommande::class, inversedBy="factureMaitres")
     */
    private $bonDeCommandes;

    /**
     * @ORM\OneToMany(targetEntity=LotCommande::class, mappedBy="factureMaitre")
     */
    private $lotCommandes;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $increment;

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
    private $pourcentageEscompte;

    /**
     * @ORM\OneToMany(targetEntity=FactureAvoir::class, mappedBy="factureMaitre")
     */
    private $factureAvoirs;

    public function __construct()
    {
        $this->bonDeCommandes = new ArrayCollection();
        $this->lotCommandes = new ArrayCollection();
        $this->factureAvoirs = new ArrayCollection();
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreatedAtValue()
    {
        $this->date = new \DateTime();
    }
 
    public function getClient()
    {
        $client = null;

        foreach($this->getBonDeCommandes() as $bon)
        {
            $devis = $bon->getDevis();

            $client = $devis->getClient();
        }

        return $client;
    }


  
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(?string $numero): self
    {
        $this->numero = $numero;

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

    public function getDatePaiement(): ?\DateTimeInterface
    {
        return $this->datePaiement;
    }

    public function setDatePaiement(?\DateTimeInterface $datePaiement): self
    {
        $this->datePaiement = $datePaiement;

        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): self
    {
        $this->titre = $titre;

        return $this;
    }

    public function getBalance(): ?string
    {
        return $this->balance;
    }

    public function setBalance(?string $balance): self
    {
        $this->balance = $balance;

        return $this;
    }

    public function getEstPayer(): ?bool
    {
        return $this->estPayer;
    }

    public function setEstPayer(?bool $estPayer): self
    {
        $this->estPayer = $estPayer;

        return $this;
    }

    public function getMontantPayer(): ?string
    {
        return $this->montantPayer;
    }

    public function setMontantPayer(?string $montantPayer): self
    {
        $this->montantPayer = $montantPayer;

        return $this;
    }

    public function getMontantAPayer(): ?string
    {
        return $this->MontantAPayer;
    }

    public function setMontantAPayer(?string $MontantAPayer): self
    {
        $this->MontantAPayer = $MontantAPayer;

        return $this;
    }

    public function getSoldeFinal(): ?bool
    {
        return $this->soldeFinal;
    }

    public function setSoldeFinal(?bool $soldeFinal): self
    {
        $this->soldeFinal = $soldeFinal;

        return $this;
    }

    public function getTotal(): ?string
    {
        return $this->total;
    }

    public function setTotal(?string $total): self
    {
        $this->total = $total;

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

    public function getTva(): ?string
    {
        return $this->tva;
    }

    public function setTva(?string $tva): self
    {
        $this->tva = $tva;

        return $this;
    }

    public function getDejaPayer(): ?bool
    {
        return $this->dejaPayer;
    }

    public function setDejaPayer(?bool $dejaPayer): self
    {
        $this->dejaPayer = $dejaPayer;

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

    public function getExpedier(): ?bool
    {
        return $this->expedier;
    }

    public function setExpedier(?bool $expedier): self
    {
        $this->expedier = $expedier;

        return $this;
    }

    /**
     * @return Collection<int, BonDeCommande>
     */
    public function getBonDeCommandes(): Collection
    {
        return $this->bonDeCommandes;
    }

    public function addBonDeCommande(BonDeCommande $bonDeCommande): self
    {
        if (!$this->bonDeCommandes->contains($bonDeCommande)) {
            $this->bonDeCommandes[] = $bonDeCommande;
        }

        return $this;
    }

    public function removeBonDeCommande(BonDeCommande $bonDeCommande): self
    {
        $this->bonDeCommandes->removeElement($bonDeCommande);

        return $this;
    }

    /**
     * @return Collection<int, LotCommande>
     */
    public function getLotCommandes(): Collection
    {
        return $this->lotCommandes;
    }

    public function addLotCommande(LotCommande $lotCommande): self
    {
        if (!$this->lotCommandes->contains($lotCommande)) {
            $this->lotCommandes[] = $lotCommande;
            $lotCommande->setFactureMaitre($this);
        }

        return $this;
    }

    public function removeLotCommande(LotCommande $lotCommande): self
    {
        if ($this->lotCommandes->removeElement($lotCommande)) {
            // set the owning side to null (unless already changed)
            if ($lotCommande->getFactureMaitre() === $this) {
                $lotCommande->setFactureMaitre(null);
            }
        }

        return $this;
    }

    public function getIncrement(): ?int
    {
        return $this->increment;
    }

    public function setIncrement(?int $increment): self
    {
        $this->increment = $increment;

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

    public function getPourcentageEscompte(): ?string
    {
        return $this->pourcentageEscompte;
    }

    public function setPourcentageEscompte(?string $pourcentageEscompte): self
    {
        $this->pourcentageEscompte = $pourcentageEscompte;

        return $this;
    }

    function __toString()
    {
        return $this->numero;
    }

    function getInvoiceType(): string
    {
        return "maitre";
    }

    /**
     * @return Collection<int, FactureAvoir>
     */
    public function getFactureAvoirs(): Collection
    {
        return $this->factureAvoirs;
    }

    public function addFactureAvoir(FactureAvoir $factureAvoir): self
    {
        if (!$this->factureAvoirs->contains($factureAvoir)) {
            $this->factureAvoirs[] = $factureAvoir;
            $factureAvoir->setFactureMaitre($this);
        }

        return $this;
    }

    public function removeFactureAvoir(FactureAvoir $factureAvoir): self
    {
        if ($this->factureAvoirs->removeElement($factureAvoir)) {
            // set the owning side to null (unless already changed)
            if ($factureAvoir->getFactureMaitre() === $this) {
                $factureAvoir->setFactureMaitre(null);
            }
        }

        return $this;
    }
    
}
