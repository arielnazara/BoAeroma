<?php

namespace App\Entity;

use App\Repository\HistoriqueTarifRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=HistoriqueTarifRepository::class)
 */
class HistoriqueTarif
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Tarif::class, inversedBy="historiqueTarifs")
     */
    private $tarif;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $date;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $actif;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $prixDetaillant;

    /**
     * @ORM\OneToMany(targetEntity=PrixDeclinaison::class, mappedBy="historique")
     */
    private $prixDeclinaisons;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $prixGrossiste;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $memeTarif;

    public function __construct()
    {
        $this->prixDeclinaisons = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTarif(): ?Tarif
    {
        return $this->tarif;
    }

    public function setTarif(?Tarif $tarif): self
    {
        $this->tarif = $tarif;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getActif(): ?bool
    {
        return $this->actif;
    }

    public function setActif(?bool $actif): self
    {
        $this->actif = $actif;

        return $this;
    }

    public function getPrixDetaillant(): ?string
    {
        return $this->prixDetaillant;
    }

    public function setPrixDetaillant(?string $prixDetaillant): self
    {
        $this->prixDetaillant = $prixDetaillant;

        return $this;
    }

    /**
     * @return Collection|PrixDeclinaison[]
     */
    public function getPrixDeclinaisons(): Collection
    {
        return $this->prixDeclinaisons;
    }

    public function addPrixDeclinaison(PrixDeclinaison $prixDeclinaison): self
    {
        if (!$this->prixDeclinaisons->contains($prixDeclinaison)) {
            $this->prixDeclinaisons[] = $prixDeclinaison;
            $prixDeclinaison->setHistorique($this);
        }

        return $this;
    }

    public function removePrixDeclinaison(PrixDeclinaison $prixDeclinaison): self
    {
        if ($this->prixDeclinaisons->removeElement($prixDeclinaison)) {
            // set the owning side to null (unless already changed)
            if ($prixDeclinaison->getHistorique() === $this) {
                $prixDeclinaison->setHistorique(null);
            }
        }

        return $this;
    }

    public function getPrixGrossiste(): ?string
    {
        return $this->prixGrossiste;
    }

    public function setPrixGrossiste(?string $prixGrossiste): self
    {
        $this->prixGrossiste = $prixGrossiste;

        return $this;
    }

    public function getMemeTarif(): ?bool
    {
        return $this->memeTarif;
    }

    public function setMemeTarif(?bool $memeTarif): self
    {
        $this->memeTarif = $memeTarif;

        return $this;
    }
}
