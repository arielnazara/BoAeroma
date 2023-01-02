<?php

namespace App\Entity;

use App\Repository\OrdreExtravapeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=OrdreExtravapeRepository::class)
 */
class OrdreExtravape
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity=Produit::class, inversedBy="ordreExtravape", cascade={"persist", "remove"})
     */
    private $produit;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $position;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $couleur;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $customGamme;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $customRef;

    /**
     * @ORM\ManyToOne(targetEntity=Gamme::class, inversedBy="ordreExtravapes")
     */
    private $gamme;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): self
    {
        $this->position = $position;

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
        return $this->customGamme;
    }

    public function setCustomGamme(?string $customGamme): self
    {
        $this->customGamme = $customGamme;

        return $this;
    }

    public function getCustomRef(): ?string
    {
        return $this->customRef;
    }

    public function setCustomRef(?string $customRef): self
    {
        $this->customRef = $customRef;

        return $this;
    }

    public function getGamme(): ?Gamme
    {
        return $this->gamme;
    }

    public function setGamme(?Gamme $gamme): self
    {
        $this->gamme = $gamme;

        return $this;
    }
}
