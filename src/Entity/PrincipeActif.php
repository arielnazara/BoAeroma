<?php

namespace App\Entity;

use App\Repository\PrincipeActifRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups ;
/**
 * @ORM\Entity(repositoryClass=PrincipeActifRepository::class)
 */
class PrincipeActif
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"produit:read", "principeActif"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"produit:read", "principeActif"})
     * 
     */
    private $principeActif;

    /**
     * @ORM\OneToMany(targetEntity=Declinaison::class, mappedBy="principeActif", cascade = {"persist", "remove"})
     * 
     */
    private $declinaisons;

    /**
     * @ORM\OneToMany(targetEntity=Tarif::class, mappedBy="declineAvec")
     * 
     */
    private $tarifs;

    /**
     * @ORM\OneToMany(targetEntity=Produit::class, mappedBy="principeActif")
     */
    private $produits;

    /**
     * @ORM\OneToMany(targetEntity=Commandes::class, mappedBy="declineAvec")
     */
    private $commandes;

    /**
     * @ORM\OneToMany(targetEntity=DevisProduit::class, mappedBy="declineAvec")
     */
    private $devisProduits;

    /**
     * @ORM\OneToMany(targetEntity=UnitGammeClientProduit::class, mappedBy="declineAvec")
     */
    private $unitGammeClientProduits;


    public function __construct()
    {
        $this->declinaisons = new ArrayCollection();
        $this->tarifs = new ArrayCollection();
        $this->produits = new ArrayCollection();
        $this->commandes = new ArrayCollection();
        $this->devisProduits = new ArrayCollection();
        $this->unitGammeClientProduits = new ArrayCollection();
        
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrincipeActif(): ?string
    {
        return $this->principeActif;
    }

    public function setPrincipeActif(?string $principeActif): self
    {
        $this->principeActif = $principeActif;

        return $this;
    }

    /**
     * @return Collection|Declinaison[]
     */
    public function getDeclinaisons(): Collection
    {
        return $this->declinaisons;
    }

    public function addDeclinaison(Declinaison $declinaison): self
    {
        if (!$this->declinaisons->contains($declinaison)) {
            $this->declinaisons[] = $declinaison;
            $declinaison->setPrincipeActif($this);
        }

        return $this;
    }

    public function removeDeclinaison(Declinaison $declinaison): self
    {
        if ($this->declinaisons->removeElement($declinaison)) {
            // set the owning side to null (unless already changed)
            if ($declinaison->getPrincipeActif() === $this) {
                $declinaison->setPrincipeActif(null);
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
            $tarif->setDeclineAvec($this);
        }

        return $this;
    }

    public function removeTarif(Tarif $tarif): self
    {
        if ($this->tarifs->removeElement($tarif)) {
            // set the owning side to null (unless already changed)
            if ($tarif->getDeclineAvec() === $this) {
                $tarif->setDeclineAvec(null);
            }
        }

        return $this;
    }

    public function __toString()
    {
        return $this->getPrincipeActif();
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
            $produit->setPrincipeActif($this);
        }

        return $this;
    }

    public function removeProduit(Produit $produit): self
    {
        if ($this->produits->removeElement($produit)) {
            // set the owning side to null (unless already changed)
            if ($produit->getPrincipeActif() === $this) {
                $produit->setPrincipeActif(null);
            }
        }

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
            $commande->setDeclineAvec($this);
        }

        return $this;
    }

    public function removeCommande(Commandes $commande): self
    {
        if ($this->commandes->removeElement($commande)) {
            // set the owning side to null (unless already changed)
            if ($commande->getDeclineAvec() === $this) {
                $commande->setDeclineAvec(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|DevisProduit[]
     */
    public function getDevisProduits(): Collection
    {
        return $this->devisProduits;
    }

    public function addDevisProduit(DevisProduit $devisProduit): self
    {
        if (!$this->devisProduits->contains($devisProduit)) {
            $this->devisProduits[] = $devisProduit;
            $devisProduit->setDeclineAvec($this);
        }

        return $this;
    }

    public function removeDevisProduit(DevisProduit $devisProduit): self
    {
        if ($this->devisProduits->removeElement($devisProduit)) {
            // set the owning side to null (unless already changed)
            if ($devisProduit->getDeclineAvec() === $this) {
                $devisProduit->setDeclineAvec(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|UnitGammeClientProduit[]
     */
    public function getUnitGammeClientProduits(): Collection
    {
        return $this->unitGammeClientProduits;
    }

    public function addUnitGammeClientProduit(UnitGammeClientProduit $unitGammeClientProduit): self
    {
        if (!$this->unitGammeClientProduits->contains($unitGammeClientProduit)) {
            $this->unitGammeClientProduits[] = $unitGammeClientProduit;
            $unitGammeClientProduit->setDeclineAvec($this);
        }

        return $this;
    }

    public function removeUnitGammeClientProduit(UnitGammeClientProduit $unitGammeClientProduit): self
    {
        if ($this->unitGammeClientProduits->removeElement($unitGammeClientProduit)) {
            // set the owning side to null (unless already changed)
            if ($unitGammeClientProduit->getDeclineAvec() === $this) {
                $unitGammeClientProduit->setDeclineAvec(null);
            }
        }

        return $this;
    }

}
