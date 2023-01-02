<?php

namespace App\Entity;

use App\Repository\DevisProduitRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DevisProduitRepository::class)
 */
class DevisProduit
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $quantite;

    /**
     * @ORM\ManyToOne(targetEntity=Devis::class, inversedBy="devisProduits", cascade={"persist"})
     */
    private $devis;

    /**
     * @ORM\ManyToOne(targetEntity=Produit::class, inversedBy="devisProduits")
     */
    private $produit;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $declinaison;

    /**
     * @ORM\ManyToOne(targetEntity=PrincipeActif::class, inversedBy="devisProduits")
     */
    private $declineAvec;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $prixSpecial;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $offert;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $prix;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantite(): ?int
    {
        return $this->quantite;
    }

    public function setQuantite(?int $quantite): self
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function getDevis(): ?Devis
    {
        return $this->devis;
    }

    public function setDevis(?Devis $devis): self
    {
        $this->devis = $devis;

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

    public function getDeclinaison(): ?string
    {
        return $this->declinaison;
    }

    public function setDeclinaison(?string $declinaison): self
    {
        $this->declinaison = $declinaison;

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

    public function getPrixSpecial(): ?string
    {
        return $this->prixSpecial;
    }

    public function setPrixSpecial(?string $prixSpecial): self
    {
        $this->prixSpecial = $prixSpecial;

        return $this;
    }

    public function getOffert(): ?bool
    {
        return $this->offert;
    }

    public function setOffert(?bool $offert): self
    {
        $this->offert = $offert;

        return $this;
    }

    public function getPrix(): ?string
    {
        return $this->prix;
    }

    public function setPrix(?string $prix): self
    {
        $this->prix = $prix;

        return $this;
    }
}
