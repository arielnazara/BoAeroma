<?php

namespace App\Entity;

use App\Repository\GammeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups ;

/**
 * @ORM\Entity(repositoryClass=GammeRepository::class)
 */
class Gamme
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
     * @ORM\Column(type="text", nullable=true)
     *
     */
    private $description;

    /**
     * @ORM\OneToMany(targetEntity=Produit::class, mappedBy="gamme")
     */
    private $produits;


    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $parDefaut;


    /**
     * @ORM\OneToMany(targetEntity=UnitMarqueGamme::class, mappedBy="Gamme")
     */
    private $unitMarqueGammes;

    /**
     * @ORM\OneToMany(targetEntity=UnitGammeClientProduit::class, mappedBy="gamme")
     */
    private $unitGammeClientProduits;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $position;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $couleur;

    /**
     * @ORM\OneToMany(targetEntity=OrdreExtravape::class, mappedBy="gamme")
     */
    private $ordreExtravapes;

    /**
     * @ORM\OneToMany(targetEntity=PositionGammeClient::class, mappedBy="GammePrincipale")
     */
    private $positionGammeClients;

    /**
     * @ORM\OneToMany(targetEntity=GammeMarqueBlanche::class, mappedBy="Gamme")
     */
    private $gammeMarqueBlanches;

    /**
     * @ORM\OneToMany(targetEntity=CatalogueClient::class, mappedBy="gamme")
     */
    private $catalogueClients;

    public function __construct()
    {
        $this->produits = new ArrayCollection();
        $this->unitMarqueGammes = new ArrayCollection();
        $this->unitGammeClientProduits = new ArrayCollection();
        $this->ordreExtravapes = new ArrayCollection();
        $this->positionGammeClients = new ArrayCollection();
        $this->gammeMarqueBlanches = new ArrayCollection();
        $this->catalogueClients = new ArrayCollection();
        
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
            $produit->setGamme($this);
        }

        return $this;
    }

    public function removeProduit(Produit $produit): self
    {
        if ($this->produits->removeElement($produit)) {
            // set the owning side to null (unless already changed)
            if ($produit->getGamme() === $this) {
                $produit->setGamme(null);
            }
        }

        return $this;
    }

    public function __toString()
    {
        return $this->getNom();
    }

    public function getParDefaut(): ?bool
    {
        return $this->parDefaut;
    }

    public function setParDefaut(?bool $parDefaut): self
    {
        $this->parDefaut = $parDefaut;

        return $this;
    }

    /**
     * @return Collection|UnitMarqueGamme[]
     */
    public function getUnitMarqueGammes(): Collection
    {
        return $this->unitMarqueGammes;
    }

    public function addUnitMarqueGamme(UnitMarqueGamme $unitMarqueGamme): self
    {
        if (!$this->unitMarqueGammes->contains($unitMarqueGamme)) {
            $this->unitMarqueGammes[] = $unitMarqueGamme;
            $unitMarqueGamme->setGamme($this);
        }

        return $this;
    }

    public function removeUnitMarqueGamme(UnitMarqueGamme $unitMarqueGamme): self
    {
        if ($this->unitMarqueGammes->removeElement($unitMarqueGamme)) {
            // set the owning side to null (unless already changed)
            if ($unitMarqueGamme->getGamme() === $this) {
                $unitMarqueGamme->setGamme(null);
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
            $unitGammeClientProduit->setGamme($this);
        }

        return $this;
    }

    public function removeUnitGammeClientProduit(UnitGammeClientProduit $unitGammeClientProduit): self
    {
        if ($this->unitGammeClientProduits->removeElement($unitGammeClientProduit)) {
            // set the owning side to null (unless already changed)
            if ($unitGammeClientProduit->getGamme() === $this) {
                $unitGammeClientProduit->setGamme(null);
            }
        }

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

    /**
     * @return Collection<int, OrdreExtravape>
     */
    public function getOrdreExtravapes(): Collection
    {
        return $this->ordreExtravapes;
    }

    public function addOrdreExtravape(OrdreExtravape $ordreExtravape): self
    {
        if (!$this->ordreExtravapes->contains($ordreExtravape)) {
            $this->ordreExtravapes[] = $ordreExtravape;
            $ordreExtravape->setGamme($this);
        }

        return $this;
    }

    public function removeOrdreExtravape(OrdreExtravape $ordreExtravape): self
    {
        if ($this->ordreExtravapes->removeElement($ordreExtravape)) {
            // set the owning side to null (unless already changed)
            if ($ordreExtravape->getGamme() === $this) {
                $ordreExtravape->setGamme(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PositionGammeClient>
     */
    public function getPositionGammeClients(): Collection
    {
        return $this->positionGammeClients;
    }

    public function addPositionGammeClient(PositionGammeClient $positionGammeClient): self
    {
        if (!$this->positionGammeClients->contains($positionGammeClient)) {
            $this->positionGammeClients[] = $positionGammeClient;
            $positionGammeClient->setGammePrincipale($this);
        }

        return $this;
    }

    public function removePositionGammeClient(PositionGammeClient $positionGammeClient): self
    {
        if ($this->positionGammeClients->removeElement($positionGammeClient)) {
            // set the owning side to null (unless already changed)
            if ($positionGammeClient->getGammePrincipale() === $this) {
                $positionGammeClient->setGammePrincipale(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, GammeMarqueBlanche>
     */
    public function getGammeMarqueBlanches(): Collection
    {
        return $this->gammeMarqueBlanches;
    }

    public function addGammeMarqueBlanch(GammeMarqueBlanche $gammeMarqueBlanch): self
    {
        if (!$this->gammeMarqueBlanches->contains($gammeMarqueBlanch)) {
            $this->gammeMarqueBlanches[] = $gammeMarqueBlanch;
            $gammeMarqueBlanch->setGamme($this);
        }

        return $this;
    }

    public function removeGammeMarqueBlanch(GammeMarqueBlanche $gammeMarqueBlanch): self
    {
        if ($this->gammeMarqueBlanches->removeElement($gammeMarqueBlanch)) {
            // set the owning side to null (unless already changed)
            if ($gammeMarqueBlanch->getGamme() === $this) {
                $gammeMarqueBlanch->setGamme(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CatalogueClient>
     */
    public function getCatalogueClients(): Collection
    {
        return $this->catalogueClients;
    }

    public function addCatalogueClient(CatalogueClient $catalogueClient): self
    {
        if (!$this->catalogueClients->contains($catalogueClient)) {
            $this->catalogueClients[] = $catalogueClient;
            $catalogueClient->setGamme($this);
        }

        return $this;
    }

    public function removeCatalogueClient(CatalogueClient $catalogueClient): self
    {
        if ($this->catalogueClients->removeElement($catalogueClient)) {
            // set the owning side to null (unless already changed)
            if ($catalogueClient->getGamme() === $this) {
                $catalogueClient->setGamme(null);
            }
        }

        return $this;
    }
}
