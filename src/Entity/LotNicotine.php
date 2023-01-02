<?php

namespace App\Entity;

use App\Repository\LotNicotineRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=LotNicotineRepository::class)
 */
class LotNicotine
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $numero;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dateReception;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dateDebutUtilisation;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $active;

    /**
     * @ORM\OneToMany(targetEntity=LotQuantite::class, mappedBy="lotNicotine")
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

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(string $numero): self
    {
        $this->numero = $numero;

        return $this;
    }

    public function getDateReception(): ?\DateTimeInterface
    {
        return $this->dateReception;
    }

    public function setDateReception(?\DateTimeInterface $dateReception): self
    {
        $this->dateReception = $dateReception;

        return $this;
    }

    public function getDateDebutUtilisation(): ?\DateTimeInterface
    {
        return $this->dateDebutUtilisation;
    }

    public function setDateDebutUtilisation(?\DateTimeInterface $dateDebutUtilisation): self
    {
        $this->dateDebutUtilisation = $dateDebutUtilisation;

        return $this;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(?bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return Collection|LotQuantite[]
     */
    public function getLotQuantites(): Collection
    {
        return $this->lotQuantites;
    }

    public function addLotQuantite(LotQuantite $lotQuantite): self
    {
        if (!$this->lotQuantites->contains($lotQuantite)) {
            $this->lotQuantites[] = $lotQuantite;
            $lotQuantite->setLotNicotine($this);
        }

        return $this;
    }

    public function removeLotQuantite(LotQuantite $lotQuantite): self
    {
        if ($this->lotQuantites->removeElement($lotQuantite)) {
            // set the owning side to null (unless already changed)
            if ($lotQuantite->getLotNicotine() === $this) {
                $lotQuantite->setLotNicotine(null);
            }
        }

        return $this;
    }
}
