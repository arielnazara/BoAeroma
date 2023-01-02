<?php

namespace App\Entity;

use App\Repository\LotQuantiteRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=LotQuantiteRepository::class)
 */
class LotQuantite
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
     * @ORM\ManyToOne(targetEntity=Produit::class, inversedBy="lotQuantites")
     */
    private $produit;

    /**
     * @ORM\ManyToOne(targetEntity=LotCommande::class, inversedBy="lotQuantites")
     */
    private $lotCommande;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $declinaison;

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

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $echantillon;

    /**
     * @ORM\ManyToOne(targetEntity=LotNicotine::class, inversedBy="lotQuantites")
     */
    private $lotNicotine;

    /**
     * @ORM\ManyToOne(targetEntity=LotGlycerine::class, inversedBy="lotQuantites")
     */
    private $lotGlycerine;

    /**
     * @ORM\ManyToOne(targetEntity=LotIsolat::class, inversedBy="lotQuantites")
     */
    private $lotIsolat;

    /**
     * @ORM\ManyToOne(targetEntity=LotVegetolPdo::class, inversedBy="lotQuantites")
     */
    private $lotVegetol;

    /**
     * @ORM\ManyToOne(targetEntity=LotArome::class, inversedBy="lotQuantites")
     */
    private $lotArome;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $lotCrf;

    /**
     * @ORM\ManyToOne(targetEntity=Commandes::class, inversedBy="lotQuantites")
     */
    private $commande;


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

    public function getProduit(): ?Produit
    {
        return $this->produit;
    }

    public function setProduit(?Produit $produit): self
    {
        $this->produit = $produit;

        return $this;
    }

    public function getLotCommande(): ?LotCommande
    {
        return $this->lotCommande;
    }

    public function setLotCommande(?LotCommande $lotCommande): self
    {
        $this->lotCommande = $lotCommande;

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

    public function getEchantillon(): ?bool
    {
        return $this->echantillon;
    }

    public function setEchantillon(?bool $echantillon): self
    {
        $this->echantillon = $echantillon;

        return $this;
    }

    public function getLotNicotine(): ?LotNicotine
    {
        return $this->lotNicotine;
    }

    public function setLotNicotine(?LotNicotine $lotNicotine): self
    {
        $this->lotNicotine = $lotNicotine;

        return $this;
    }

    public function getLotGlycerine(): ?LotGlycerine
    {
        return $this->lotGlycerine;
    }

    public function setLotGlycerine(?LotGlycerine $lotGlycerine): self
    {
        $this->lotGlycerine = $lotGlycerine;

        return $this;
    }

    public function getLotIsolat(): ?LotIsolat
    {
        return $this->lotIsolat;
    }

    public function setLotIsolat(?LotIsolat $lotIsolat): self
    {
        $this->lotIsolat = $lotIsolat;

        return $this;
    }

    public function getLotVegetol(): ?LotVegetolPdo
    {
        return $this->lotVegetol;
    }

    public function setLotVegetol(?LotVegetolPdo $lotVegetol): self
    {
        $this->lotVegetol = $lotVegetol;

        return $this;
    }

    public function getLotArome(): ?LotArome
    {
        return $this->lotArome;
    }

    public function setLotArome(?LotArome $lotArome): self
    {
        $this->lotArome = $lotArome;

        return $this;
    }

    public function getLotCrf(): ?string
    {
        return $this->lotCrf;
    }

    public function setLotCrf(?string $lotCrf): self
    {
        $this->lotCrf = $lotCrf;

        return $this;
    }

    public function getCommande(): ?Commandes
    {
        return $this->commande;
    }

    public function setCommande(?Commandes $commande): self
    {
        $this->commande = $commande;

        return $this;
    }

}
