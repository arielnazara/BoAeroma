<?php

namespace App\Entity;

use App\Repository\GammeClientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass=GammeClientRepository::class)
 * @UniqueEntity(fields="client", message="Ce client possède déjà un catalogue")
 */
class GammeClient
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToMany(targetEntity=UnitGameClientGame::class, mappedBy="gammeClient", cascade = {"remove"})
     */
    private $unitGameClientGames;

    /**
     * @ORM\OneToMany(targetEntity=UnitGameClientMarque::class, mappedBy="gammeClient", cascade = {"remove"})
     */
    private $unitGameClientMarques;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $toutLesProduits;

    /**
     * @ORM\OneToMany(targetEntity=UnitGammeClientProduit::class, mappedBy="gammeClient", cascade = {"remove"})
     */
    private $unitGammeClientProduits;

    /**
     * @ORM\OneToOne(targetEntity=Client::class, inversedBy="gammeClient")
     */
    private $client;

    public function __construct()
    {
        $this->unitGameClientGames = new ArrayCollection();
        
        $this->unitGameClientMarques = new ArrayCollection();
        $this->unitGammeClientProduits = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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
            $unitGameClientGame->setGammeClient($this);
        }

        return $this;
    }

    public function removeUnitGameClientGame(UnitGameClientGame $unitGameClientGame): self
    {
        if ($this->unitGameClientGames->removeElement($unitGameClientGame)) {
            // set the owning side to null (unless already changed)
            if ($unitGameClientGame->getGammeClient() === $this) {
                $unitGameClientGame->setGammeClient(null);
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
            $unitGameClientMarque->setGammeClient($this);
        }

        return $this;
    }

    public function removeUnitGameClientMarque(UnitGameClientMarque $unitGameClientMarque): self
    {
        if ($this->unitGameClientMarques->removeElement($unitGameClientMarque)) {
            // set the owning side to null (unless already changed)
            if ($unitGameClientMarque->getGammeClient() === $this) {
                $unitGameClientMarque->setGammeClient(null);
            }
        }

        return $this;
    }

    public function getToutLesProduits(): ?bool
    {
        return $this->toutLesProduits;
    }

    public function setToutLesProduits(?bool $toutLesProduits): self
    {
        $this->toutLesProduits = $toutLesProduits;

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
            $unitGammeClientProduit->setGammeClient($this);
        }

        return $this;
    }

    public function removeUnitGammeClientProduit(UnitGammeClientProduit $unitGammeClientProduit): self
    {
        if ($this->unitGammeClientProduits->removeElement($unitGammeClientProduit)) {
            // set the owning side to null (unless already changed)
            if ($unitGammeClientProduit->getGammeClient() === $this) {
                $unitGammeClientProduit->setGammeClient(null);
            }
        }

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
 }
