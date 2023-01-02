<?php

namespace App\Entity;

use App\Repository\UnitGammeClientProduitRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=UnitGammeClientProduitRepository::class)
 */
class UnitGammeClientProduit
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=GammeClient::class, inversedBy="unitGammeClientProduits")
     */
    private $gammeClient;

    /**
     * @ORM\ManyToOne(targetEntity=Produit::class, inversedBy="unitGammeClientProduits")
     */
    private $produit;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $toutLesDeclinaisons;

    /**
     * @ORM\ManyToOne(targetEntity=Marque::class, inversedBy="unitGammeClientProduits")
     */
    private $marque;

  

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $declinaison = [];

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $configurer;

    /**
     * @ORM\ManyToOne(targetEntity=PrincipeActif::class, inversedBy="unitGammeClientProduits")
     */
    private $declineAvec;

    /**
     * @ORM\ManyToOne(targetEntity=Gamme::class, inversedBy="unitGammeClientProduits")
     */
    private $gamme;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGammeClient(): ?GammeClient
    {
        return $this->gammeClient;
    }

    public function setGammeClient(?GammeClient $gammeClient): self
    {
        $this->gammeClient = $gammeClient;

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

    public function getToutLesDeclinaisons(): ?bool
    {
        return $this->toutLesDeclinaisons;
    }

    public function setToutLesDeclinaisons(?bool $toutLesDeclinaisons): self
    {
        $this->toutLesDeclinaisons = $toutLesDeclinaisons;

        return $this;
    }

    public function getMarque(): ?Marque
    {
        return $this->marque;
    }

    public function setMarque(?Marque $marque): self
    {
        $this->marque = $marque;

        return $this;
    }

    public function getDeclinaison(): ?array
    {
        return $this->declinaison;
    }

    public function setDeclinaison(?array $declinaison): self
    {
        $this->declinaison = $declinaison;

        return $this;
    }

    public function getConfigurer(): ?bool
    {
        return $this->configurer;
    }

    public function setConfigurer(?bool $configurer): self
    {
        $this->configurer = $configurer;

        return $this;
    }

    public function getDeclineAvec(): ?PrincipeActif
    {
        return $this->declineAvec;
    }

    public function setDeclineAvec(?PrincipeActif $declineAvec): self
    {
        $this->declineAvec = $declineAvec;

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
