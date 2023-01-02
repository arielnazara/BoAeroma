<?php

namespace App\Entity;

use App\Repository\CommandesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CommandesRepository::class)
 */
class Commandes
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
    private $idCommandePresta;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $quantite;

    /**
     * @ORM\ManyToOne(targetEntity=BonDeCommande::class, inversedBy="commandes")
     */
    private $bonDeCommande;

    /**
     * @ORM\ManyToOne(targetEntity=Produit::class, inversedBy="commandes")
     */
    private $produit;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dateLivraison;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $expedier = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $resteAExpedier;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $preparer = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $priorite = 0;

    /**
     * @ORM\ManyToOne(targetEntity=Devis::class, inversedBy="commandes")
     */
    private $devis;

    /**
     * @ORM\ManyToOne(targetEntity=PrincipeActif::class, inversedBy="commandes")
     */
    private $declineAvec;

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
     * @ORM\OneToMany(targetEntity=LotQuantite::class, mappedBy="commande")
     */
    private $lotQuantites;

    public function __construct()
    {
        $this->lotQuantites = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdCommandePresta(): ?int
    {
        return $this->idCommandePresta;
    }

    public function setIdCommandePresta(?int $idCommandePresta): self
    {
        $this->idCommandePresta = $idCommandePresta;

        return $this;
    }

    public function getQuantite(): ?int
    {
        return $this->quantite;
    }

    public function setQuantite(?int $quantite): self
    {
        $this->quantite = $quantite;
        $this->resteAExpedier = $quantite - $this->expedier;
        
        return $this;
    }

    public function getBonDeCommande(): ?BonDeCommande
    {
        return $this->bonDeCommande;
    }

    public function setBonDeCommande(?BonDeCommande $bonDeCommande): self
    {
        $this->bonDeCommande = $bonDeCommande;

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

    public function getDateLivraison(): ?\DateTimeInterface
    {
        return $this->dateLivraison;
    }

    public function setDateLivraison(?\DateTimeInterface $dateLivraison): self
    {
        $this->dateLivraison = $dateLivraison;

        return $this;
    }

    public function getExpedier(): ?int
    {
        return $this->expedier;
    }

    public function setExpedier(?int $expedier): self
    {
        $this->expedier += $expedier;

        $this->resteAExpedier = $this->resteAExpedier - $expedier;

        $this->resteAExpedier < 0 ? $this->resteAExpedier = 0 : $this->resteAExpedier;
      

        return $this;
    }

    public function getResteAExpedier(): ?int
    {
        return $this->resteAExpedier;
    }

    public function setResteAExpedier(?int $resteAExpedier): self
    {
        $this->resteAExpedier = $resteAExpedier;

        return $this;
    }

    public function getPreparer(): ?int
    {
        return $this->preparer;
    }

    public function setPreparer(?int $preparer): self
    {
        $this->preparer = $preparer;

        return $this;
    }

    public function getPriorite(): ?int
    {
        return $this->priorite;
    }

    public function setPriorite(?int $priorite): self
    {
        $this->priorite = $priorite;

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

    public function getDeclineAvec(): ?PrincipeActif
    {
        return $this->declineAvec;
    }

    public function setDeclineAvec(?PrincipeActif $declineAvec): self
    {
        $this->declineAvec = $declineAvec;

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

    /**
     * @return Collection<int, LotQuantite>
     */
    public function getLotQuantites(): Collection
    {
        return $this->lotQuantites;
    }

    public function addLotQuantite(LotQuantite $lotQuantite): self
    {
        if (!$this->lotQuantites->contains($lotQuantite)) {
            $this->lotQuantites[] = $lotQuantite;
            $lotQuantite->setCommande($this);
        }

        return $this;
    }

    public function removeLotQuantite(LotQuantite $lotQuantite): self
    {
        if ($this->lotQuantites->removeElement($lotQuantite)) {
            // set the owning side to null (unless already changed)
            if ($lotQuantite->getCommande() === $this) {
                $lotQuantite->setCommande(null);
            }
        }

        return $this;
    }
}
