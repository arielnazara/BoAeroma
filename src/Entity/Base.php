<?php

namespace App\Entity;

use App\Repository\BaseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups ;

/**
 * @ORM\Entity(repositoryClass=BaseRepository::class)
 */
class Base
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * 
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * 
     */
    private $nom;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * 
     */
    private $reference;

    /**
     * @ORM\OneToMany(targetEntity=ProduitBase::class, mappedBy="base")
     */
    private $produitBases;

    /**
     * @ORM\OneToMany(targetEntity=BaseComposant::class, mappedBy="base", cascade = {"remove"})
     */
    private $baseComposants;

    /**
     * @ORM\OneToMany(targetEntity=Produit::class, mappedBy="base")
     */
    private $produits;

    /**
     * @ORM\OneToMany(targetEntity=Tarif::class, mappedBy="base")
     * 
     */
    private $tarifs;

    public function __construct()
    {
        $this->produitBases = new ArrayCollection();
        $this->baseComposants = new ArrayCollection();
        $this->produits = new ArrayCollection();
        $this->tarifs = new ArrayCollection();
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

    public function __toString()
    {
        return $this->getNom();
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

    /**
     * @return Collection|ProduitBase[]
     */
    public function getProduitBases(): Collection
    {
        return $this->produitBases;
    }

    public function addProduitBasis(ProduitBase $produitBasis): self
    {
        if (!$this->produitBases->contains($produitBasis)) {
            $this->produitBases[] = $produitBasis;
            $produitBasis->setBase($this);
        }

        return $this;
    }

    public function removeProduitBasis(ProduitBase $produitBasis): self
    {
        if ($this->produitBases->removeElement($produitBasis)) {
            // set the owning side to null (unless already changed)
            if ($produitBasis->getBase() === $this) {
                $produitBasis->setBase(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|BaseComposant[]
     */
    public function getBaseComposants(): Collection
    {
        return $this->baseComposants;
    }

    public function addBaseComposant(BaseComposant $baseComposant): self
    {
        if (!$this->baseComposants->contains($baseComposant)) {
            $this->baseComposants[] = $baseComposant;
            $baseComposant->setBase($this);
        }

        return $this;
    }

    public function removeBaseComposant(BaseComposant $baseComposant): self
    {
        if ($this->baseComposants->removeElement($baseComposant)) {
            // set the owning side to null (unless already changed)
            if ($baseComposant->getBase() === $this) {
                $baseComposant->setBase(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Produit[]
     */
    public function getProduits(): Collection
    {
        return $this->produits;
    }

    public function addProduit(Produit $produit): self
    {
        if (!$this->produits->contains($produit)) {
            $this->produits[] = $produit;
            $produit->setBase($this);
        }

        return $this;
    }

    public function removeProduit(Produit $produit): self
    {
        if ($this->produits->removeElement($produit)) {
            // set the owning side to null (unless already changed)
            if ($produit->getBase() === $this) {
                $produit->setBase(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Tarif[]
     */
    public function getTarifs(): Collection
    {
        return $this->tarifs;
    }

    public function addTarif(Tarif $tarif): self
    {
        if (!$this->tarifs->contains($tarif)) {
            $this->tarifs[] = $tarif;
            $tarif->setBase($this);
        }

        return $this;
    }

    public function removeTarif(Tarif $tarif): self
    {
        if ($this->tarifs->removeElement($tarif)) {
            // set the owning side to null (unless already changed)
            if ($tarif->getBase() === $this) {
                $tarif->setBase(null);
            }
        }

        return $this;
    }
}
