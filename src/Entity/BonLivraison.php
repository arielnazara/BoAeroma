<?php

namespace App\Entity;

use App\Repository\BonLivraisonRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=BonLivraisonRepository::class)
 */
class BonLivraison
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=BonDeCommande::class, inversedBy="bonLivraisons")
     */
    private $bonDeCommande;

    /**
     * @ORM\ManyToOne(targetEntity=FactureCommande::class, inversedBy="bonLivraisons")
     */
    private $facture;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $numero;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $quantite;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dateExpedition;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $estGenerer;

    /**
     * @ORM\OneToOne(targetEntity=LotCommande::class, inversedBy="bonLivraison", cascade={"persist", "remove"})
     */
    private $lotCommande;

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
     * @ORM\ManyToOne(targetEntity=AdresseLivraison::class, inversedBy="bonLivraisons")
     */
    private $adresseLivraison;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $adresseFacturation;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $code;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $increment;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function incrementNumber() {
        $this->numero += 1;
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

    public function getFacture(): ?FactureCommande
    {
        return $this->facture;
    }

    public function setFacture(?FactureCommande $facture): self
    {
        $this->facture = $facture;

        return $this;
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

    public function getQuantite(): ?int
    {
        return $this->quantite;
    }

    public function setQuantite(?int $quantite): self
    {
        $this->quantite = $quantite;

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

    public function getEstGenerer(): ?bool
    {
        return $this->estGenerer;
    }

    public function setEstGenerer(?bool $estGenerer): self
    {
        $this->estGenerer = $estGenerer;

        return $this;
    }

    public function getLotCommande(): ?LotCommande
    {
        return $this->lotCommande;
    }

    public function setLotCommande(?LotCommande $lotCommande): self
    {
        $this->lotCommande = $lotCommande;

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

    public function getAdresseLivraison(): ?AdresseLivraison
    {
        return $this->adresseLivraison;
    }

    public function setAdresseLivraison(?AdresseLivraison $adresseLivraison): self
    {
        $this->adresseLivraison = $adresseLivraison;

        return $this;
    }

    public function getAdresseFacturation(): ?bool
    {
        return $this->adresseFacturation;
    }

    public function setAdresseFacturation(?bool $adresseFacturation): self
    {
        $this->adresseFacturation = $adresseFacturation;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): self
    {
        $this->code = $code;

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

   
}
