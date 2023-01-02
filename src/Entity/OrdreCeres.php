<?php

namespace App\Entity;

use App\Repository\OrdreCeresRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=OrdreCeresRepository::class)
 */
class OrdreCeres
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $position;

    /**
     * @ORM\OneToOne(targetEntity=Produit::class, inversedBy="ordreCeres", cascade={"persist", "remove"})
     */
    private $produit;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $couleur;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $CustomGamme;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $custom_ref;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $lotNicotine;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $lotGlycerine;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $lotIsolat;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $lotVegetol;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $lotArome;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getProduit(): ?Produit
    {
        return $this->produit;
    }

    public function setProduit(?Produit $produit): self
    {
        $this->produit = $produit;

        return $this;
    }

    public function getCouleur(): ?string
    {
        return $this->couleur;
    }

    public function setCouleur(?string $couleur): self
    {
        $this->couleur = $couleur;

        return $this;
    }

    public function getCustomGamme(): ?string
    {
        return $this->CustomGamme;
    }

    public function setCustomGamme(?string $CustomGamme): self
    {
        $this->CustomGamme = $CustomGamme;

        return $this;
    }

    public function getCustomRef(): ?string
    {
        return $this->custom_ref;
    }

    public function setCustomRef(?string $custom_ref): self
    {
        $this->custom_ref = $custom_ref;

        return $this;
    }

    public function getLotNicotine(): ?bool
    {
        return $this->lotNicotine;
    }

    public function setLotNicotine(?bool $lotNicotine): self
    {
        $this->lotNicotine = $lotNicotine;

        return $this;
    }

    public function getLotGlycerine(): ?bool
    {
        return $this->lotGlycerine;
    }

    public function setLotGlycerine(?bool $lotGlycerine): self
    {
        $this->lotGlycerine = $lotGlycerine;

        return $this;
    }

    public function getLotIsolat(): ?bool
    {
        return $this->lotIsolat;
    }

    public function setLotIsolat(bool $lotIsolat): self
    {
        $this->lotIsolat = $lotIsolat;

        return $this;
    }

    public function getLotVegetol(): ?bool
    {
        return $this->lotVegetol;
    }

    public function setLotVegetol(?bool $lotVegetol): self
    {
        $this->lotVegetol = $lotVegetol;

        return $this;
    }

    public function getLotArome(): ?bool
    {
        return $this->lotArome;
    }

    public function setLotArome(?bool $lotArome): self
    {
        $this->lotArome = $lotArome;

        return $this;
    }
}
