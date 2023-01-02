<?php

namespace App\Entity;

use App\Repository\InfoFactureTempRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=InfoFactureTempRepository::class)
 */
class InfoFactureTemp
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToMany(targetEntity=LotCommande::class, mappedBy="infoFactureTemp")
     */
    private $lotCommande;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2)
     */
    private $acompte;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $avoir;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $escompte;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $netFinancier;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $taxe;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $pourcentageEscompte;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $datePaiementAcompte;

    public function __construct()
    {
        $this->lotCommande = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, LotCommande>
     */
    public function getLotCommande(): Collection
    {
        return $this->lotCommande;
    }

    public function addLotCommande(LotCommande $lotCommande): self
    {
        if (!$this->lotCommande->contains($lotCommande)) {
            $this->lotCommande[] = $lotCommande;
            $lotCommande->setInfoFactureTemp($this);
        }

        return $this;
    }

    public function removeLotCommande(LotCommande $lotCommande): self
    {
        if ($this->lotCommande->removeElement($lotCommande)) {
            // set the owning side to null (unless already changed)
            if ($lotCommande->getInfoFactureTemp() === $this) {
                $lotCommande->setInfoFactureTemp(null);
            }
        }

        return $this;
    }

    public function getAcompte(): ?string
    {
        return $this->acompte;
    }

    public function setAcompte(string $acompte): self
    {
        $this->acompte = $acompte;

        return $this;
    }

   

    public function getAvoir(): ?string
    {
        return $this->avoir;
    }

    public function setAvoir(?string $avoir): self
    {
        $this->avoir = $avoir;

        return $this;
    }

    public function getEscompte(): ?string
    {
        return $this->escompte;
    }

    public function setEscompte(?string $escompte): self
    {
        $this->escompte = $escompte;

        return $this;
    }

    public function getNetFinancier(): ?string
    {
        return $this->netFinancier;
    }

    public function setNetFinancier(?string $netFinancier): self
    {
        $this->netFinancier = $netFinancier;

        return $this;
    }

    public function getTaxe(): ?string
    {
        return $this->taxe;
    }

    public function setTaxe(?string $taxe): self
    {
        $this->taxe = $taxe;

        return $this;
    }

    public function getPourcentageEscompte(): ?string
    {
        return $this->pourcentageEscompte;
    }

    public function setPourcentageEscompte(?string $pourcentageEscompte): self
    {
        $this->pourcentageEscompte = $pourcentageEscompte;

        return $this;
    }

    public function getDatePaiementAcompte(): ?\DateTimeInterface
    {
        return $this->datePaiementAcompte;
    }

    public function setDatePaiementAcompte(?\DateTimeInterface $datePaiementAcompte): self
    {
        $this->datePaiementAcompte = $datePaiementAcompte;

        return $this;
    }
}
