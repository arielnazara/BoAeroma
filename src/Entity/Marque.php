<?php

namespace App\Entity;

use App\Repository\MarqueRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups ;


/**
 * @ORM\Entity(repositoryClass=MarqueRepository::class)
 */
class Marque
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
     * @ORM\OneToMany(targetEntity=Produit::class, mappedBy="marque")
     */
    private $produits;

    /**
     * @ORM\OneToMany(targetEntity=UnitGameClientMarque::class, mappedBy="marque")
     */
    private $unitGameClientMarques;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $proprietaire;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $exclusivite;

    /**
     * @ORM\ManyToOne(targetEntity=Client::class, inversedBy="marques")
     */
    private $client;

    /**
     * @ORM\ManyToOne(targetEntity=Fournisseur::class, inversedBy="marques")
     */
    private $fournisseur;

    /**
     * @ORM\OneToMany(targetEntity=ClientExclusif::class, mappedBy="marque", cascade={"remove"})
     */
    private $clientExclusifs;

    /**
     * @ORM\OneToMany(targetEntity=UnitGammeClientProduit::class, mappedBy="marque")
     */
    private $unitGammeClientProduits;

    /**
     * @ORM\OneToMany(targetEntity=UnitMarqueGamme::class, mappedBy="marque", cascade={"remove"})
     */
    private $unitMarqueGammes;

    /**
     * @ORM\OneToMany(targetEntity=UnitGameClientGame::class, mappedBy="marque")
     */
    private $unitGameClientGames;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $avecGamme;

    /**
     * @ORM\OneToMany(targetEntity=Tarif::class, mappedBy="marque")
     * 
     */
    private $tarifs;


    public function __construct()
    {
        $this->ManyToOne = new ArrayCollection();
        $this->produits = new ArrayCollection();
        $this->unitGameClientMarques = new ArrayCollection();
        $this->clientExclusifs = new ArrayCollection();
        $this->unitGammeClientProduits = new ArrayCollection();
        $this->unitMarqueGammes = new ArrayCollection();
        $this->unitGameClientGames = new ArrayCollection();
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
            $produit->setMarque($this);
        }

        return $this;
    }

    public function removeProduit(Produit $produit): self
    {
        if ($this->produits->removeElement($produit)) {
            // set the owning side to null (unless already changed)
            if ($produit->getMarque() === $this) {
                $produit->setMarque(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|UnitGameClientMarque[]
     */
    public function getUnitGameClientMarques(): Collection
    {
        return $this->unitGameClientMarques;
    }

    public function addUnitGameClientMarque(UnitGameClientMarque $unitGameClientMarque): self
    {
        if (!$this->unitGameClientMarques->contains($unitGameClientMarque)) {
            $this->unitGameClientMarques[] = $unitGameClientMarque;
            $unitGameClientMarque->setMarque($this);
        }

        return $this;
    }

    public function removeUnitGameClientMarque(UnitGameClientMarque $unitGameClientMarque): self
    {
        if ($this->unitGameClientMarques->removeElement($unitGameClientMarque)) {
            // set the owning side to null (unless already changed)
            if ($unitGameClientMarque->getMarque() === $this) {
                $unitGameClientMarque->setMarque(null);
            }
        }

        return $this;
    }

    public function getProprietaire(): ?string
    {
        return $this->proprietaire;
    }

    public function setProprietaire(?string $proprietaire): self
    {
        $this->proprietaire = $proprietaire;

        return $this;
    }

    public function getExclusivite(): ?bool
    {
        return $this->exclusivite;
    }

    public function setExclusivite(?bool $exclusivite): self
    {
        $this->exclusivite = $exclusivite;

        return $this;
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
     * @return Collection|ClientExclusif[]
     */
    public function getClientExclusifs(): Collection
    {
        return $this->clientExclusifs;
    }

    public function addClientExclusif(ClientExclusif $clientExclusif): self
    {
        if (!$this->clientExclusifs->contains($clientExclusif)) {
            $this->clientExclusifs[] = $clientExclusif;
            $clientExclusif->setMarque($this);
        }

        return $this;
    }

    public function removeClientExclusif(ClientExclusif $clientExclusif): self
    {
        if ($this->clientExclusifs->removeElement($clientExclusif)) {
            // set the owning side to null (unless already changed)
            if ($clientExclusif->getMarque() === $this) {
                $clientExclusif->setMarque(null);
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
            $unitGammeClientProduit->setMarque($this);
        }

        return $this;
    }

    public function removeUnitGammeClientProduit(UnitGammeClientProduit $unitGammeClientProduit): self
    {
        if ($this->unitGammeClientProduits->removeElement($unitGammeClientProduit)) {
            // set the owning side to null (unless already changed)
            if ($unitGammeClientProduit->getMarque() === $this) {
                $unitGammeClientProduit->setMarque(null);
            }
        }

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
            $unitMarqueGamme->setMarque($this);
        }

        return $this;
    }

    public function removeUnitMarqueGamme(UnitMarqueGamme $unitMarqueGamme): self
    {
        if ($this->unitMarqueGammes->removeElement($unitMarqueGamme)) {
            // set the owning side to null (unless already changed)
            if ($unitMarqueGamme->getMarque() === $this) {
                $unitMarqueGamme->setMarque(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|UnitGameClientGame[]
     */
    public function getUnitGameClientGames(): Collection
    {
        return $this->unitGameClientGames;
    }

    public function addUnitGameClientGame(UnitGameClientGame $unitGameClientGame): self
    {
        if (!$this->unitGameClientGames->contains($unitGameClientGame)) {
            $this->unitGameClientGames[] = $unitGameClientGame;
            $unitGameClientGame->setMarque($this);
        }

        return $this;
    }

    public function removeUnitGameClientGame(UnitGameClientGame $unitGameClientGame): self
    {
        if ($this->unitGameClientGames->removeElement($unitGameClientGame)) {
            // set the owning side to null (unless already changed)
            if ($unitGameClientGame->getMarque() === $this) {
                $unitGameClientGame->setMarque(null);
            }
        }

        return $this;
    }

    public function getAvecGamme(): ?bool
    {
        return $this->avecGamme;
    }

    public function setAvecGamme(?bool $avecGamme): self
    {
        $this->avecGamme = $avecGamme;

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
            $tarif->setMarque($this);
        }

        return $this;
    }

    public function removeTarif(Tarif $tarif): self
    {
        if ($this->tarifs->removeElement($tarif)) {
            // set the owning side to null (unless already changed)
            if ($tarif->getMarque() === $this) {
                $tarif->setMarque(null);
            }
        }

        return $this;
    }

}
