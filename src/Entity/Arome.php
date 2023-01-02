<?php

namespace App\Entity;

use App\Repository\AromeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=AromeRepository::class)
 */
class Arome
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
    private $nom;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

 

    /**
     * @ORM\ManyToOne(targetEntity=Fournisseur::class, inversedBy="aromes")
     */
    private $fournisseur;

    /**
     * @ORM\OneToMany(targetEntity=ProduitArome::class, mappedBy="arome")
     */
    private $produitAromes;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $reference;


    public function __construct()
    {
        $this->produitAromes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function __toString()
    {
        return $this->getNom();
    }

    public function getFournisseur(): ?Fournisseur
    {
        return $this->fournisseur;
    }

    public function setFournisseur(?Fournisseur $fournisseur): self
    {
        $this->fournisseur = $fournisseur;

        return $this;
    }

    /**
     * @return Collection|ProduitArome[]
     */
    public function getProduitAromes(): Collection
    {
        return $this->produitAromes;
    }

    public function addProduitArome(ProduitArome $produitArome): self
    {
        if (!$this->produitAromes->contains($produitArome)) {
            $this->produitAromes[] = $produitArome;
            $produitArome->setArome($this);
        }

        return $this;
    }

    public function removeProduitArome(ProduitArome $produitArome): self
    {
        if ($this->produitAromes->removeElement($produitArome)) {
            // set the owning side to null (unless already changed)
            if ($produitArome->getArome() === $this) {
                $produitArome->setArome(null);
            }
        }

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }
}
