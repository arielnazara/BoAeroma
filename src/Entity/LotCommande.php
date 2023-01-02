<?php

namespace App\Entity;

use App\Repository\LotCommandeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=LotCommandeRepository::class)
 */
class LotCommande
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Client::class, inversedBy="lotCommandes")
     */
    private $client;

    /**
     * @ORM\ManyToOne(targetEntity=BonDeCommande::class, inversedBy="lotCommandes")
     */
    private $bonDeCommande;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $nom;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $numeroSuivi;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $date;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dateExpedition;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $status;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $enPreparation;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $estPrioriter;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $estExpedier;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $message;

    /**
     * @ORM\OneToMany(targetEntity=LotQuantite::class, mappedBy="lotCommande", cascade={"remove"})
     */
    private $lotQuantites;

  
    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $fraisExpedition;

    /**
     * @ORM\ManyToOne(targetEntity=FactureCommande::class, inversedBy="lotCommandes")
     */
    private $factureCommande;

    /**
     * @ORM\OneToOne(targetEntity=BonLivraison::class, mappedBy="lotCommande", cascade={"persist", "remove"})
     */
    private $bonLivraison;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $nombreColis = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $expresse = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $affretement = 0;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $dernierLot;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $facturer;

    /**
     * @ORM\ManyToOne(targetEntity=FactureMaitre::class, inversedBy="lotCommandes")
     */
    private $factureMaitre;

    /**
     * @ORM\ManyToOne(targetEntity=InfoFactureTemp::class, inversedBy="lotCommande")
     */
    private $infoFactureTemp;

    public function __construct()
    {
        $this->lotQuantites = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getBonDeCommande(): ?BonDeCommande
    {
        return $this->bonDeCommande;
    }

    public function setBonDeCommande(?BonDeCommande $bonDeCommande): self
    {
        $this->bonDeCommande = $bonDeCommande;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getNumeroSuivi(): ?string
    {
        return $this->numeroSuivi;
    }

    public function setNumeroSuivi(?string $numeroSuivi): self
    {
        $this->numeroSuivi = $numeroSuivi;

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

    public function getDateExpedition(): ?\DateTimeInterface
    {
        return $this->dateExpedition;
    }

    public function setDateExpedition(?\DateTimeInterface $dateExpedition): self
    {
        $this->dateExpedition = $dateExpedition;

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

    public function getEnPreparation(): ?bool
    {
        return $this->enPreparation;
    }

    public function setEnPreparation(?bool $enPreparation): self
    {
        $this->enPreparation = $enPreparation;

        return $this;
    }

    public function getEstPrioriter(): ?bool
    {
        return $this->estPrioriter;
    }

    public function setEstPrioriter(?bool $estPrioriter): self
    {
        $this->estPrioriter = $estPrioriter;

        return $this;
    }

    public function getEstExpedier(): ?bool
    {
        return $this->estExpedier;
    }

    public function setEstExpedier(?bool $estExpedier): self
    {
        $this->estExpedier = $estExpedier;

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

    /**
     * @return Collection|LotQuantite[]
     */
    public function getLotQuantites(): Collection
    {
        return $this->lotQuantites;
    }

    public function addLotQuantite(LotQuantite $lotQuantite): self
    {
        if (!$this->lotQuantites->contains($lotQuantite)) {
            $this->lotQuantites[] = $lotQuantite;
            $lotQuantite->setLotCommande($this);
        }

        return $this;
    }

    public function removeLotQuantite(LotQuantite $lotQuantite): self
    {
        if ($this->lotQuantites->removeElement($lotQuantite)) {
            // set the owning side to null (unless already changed)
            if ($lotQuantite->getLotCommande() === $this) {
                $lotQuantite->setLotCommande(null);
            }
        }

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

    public function getFactureCommande(): ?FactureCommande
    {
        return $this->factureCommande;
    }

    public function setFactureCommande(?FactureCommande $factureCommande): self
    {
        $this->factureCommande = $factureCommande;

        return $this;
    }

    public function getBonLivraison(): ?BonLivraison
    {
        return $this->bonLivraison;
    }

    public function setBonLivraison(?BonLivraison $bonLivraison): self
    {
        // unset the owning side of the relation if necessary
        if ($bonLivraison === null && $this->bonLivraison !== null) {
            $this->bonLivraison->setLotCommande(null);
        }

        // set the owning side of the relation if necessary
        if ($bonLivraison !== null && $bonLivraison->getLotCommande() !== $this) {
            $bonLivraison->setLotCommande($this);
        }

        $this->bonLivraison = $bonLivraison;

        return $this;
    }

    public function getNombreColis(): ?int
    {
        return $this->nombreColis;
    }

    public function setNombreColis(?int $nombreColis): self
    {
        $this->nombreColis = $nombreColis;

        return $this;
    }

    public function getExpresse(): ?int
    {
        return $this->expresse;
    }

    public function setExpresse(?int $expresse): self
    {
        $this->expresse = $expresse;

        return $this;
    }

    public function getAffretement(): ?int
    {
        return $this->affretement;
    }

    public function setAffretement(?int $affretement): self
    {
        $this->affretement = $affretement;

        return $this;
    }

    public function getDernierLot(): ?bool
    {
        return $this->dernierLot;
    }

    public function setDernierLot(?bool $dernierLot): self
    {
        $this->dernierLot = $dernierLot;

        return $this;
    }

    public function getFacturer(): ?bool
    {
        return $this->facturer;
    }

    public function setFacturer(?bool $facturer): self
    {
        $this->facturer = $facturer;

        return $this;
    }

    public function getFactureMaitre(): ?FactureMaitre
    {
        return $this->factureMaitre;
    }

    public function setFactureMaitre(?FactureMaitre $factureMaitre): self
    {
        $this->factureMaitre = $factureMaitre;

        return $this;
    }

    public function getInfoFactureTemp(): ?InfoFactureTemp
    {
        return $this->infoFactureTemp;
    }

    public function setInfoFactureTemp(?InfoFactureTemp $infoFactureTemp): self
    {
        $this->infoFactureTemp = $infoFactureTemp;

        return $this;
    }
}
