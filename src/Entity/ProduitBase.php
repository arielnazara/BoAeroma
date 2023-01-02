<?php

namespace App\Entity;

use App\Repository\ProduitBaseRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ProduitBaseRepository::class)
 */
class ProduitBase
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Produit::class, inversedBy="produitBases")
     */
    private $produit;

    /**
     * @ORM\ManyToOne(targetEntity=Base::class, inversedBy="produitBases")
     */
    private $base;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $numBase;

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

    public function getBase(): ?Base
    {
        return $this->base;
    }

    public function setBase(?Base $base): self
    {
        $this->base = $base;

        return $this;
    }

    public function getNumBase(): ?string
    {
        return $this->numBase;
    }

    public function setNumBase(?string $numBase): self
    {
        $this->numBase = $numBase;

        return $this;
    }
}
