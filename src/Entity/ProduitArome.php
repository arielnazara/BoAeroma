<?php

namespace App\Entity;

use App\Repository\ProduitAromeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ProduitAromeRepository::class)
 */
class ProduitArome
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Produit::class, inversedBy="produitAromes")
     */
    private $produit;

    /**
     * @ORM\ManyToOne(targetEntity=Arome::class, inversedBy="produitAromes")
     */
    private $arome;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $NumArome;

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

    public function getArome(): ?Arome
    {
        return $this->arome;
    }

    public function setArome(?Arome $arome): self
    {
        $this->arome = $arome;

        return $this;
    }

    public function getNumArome(): ?string
    {
        return $this->NumArome;
    }

    public function setNumArome(?string $NumArome): self
    {
        $this->NumArome = $NumArome;

        return $this;
    }
}
