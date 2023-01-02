<?php

namespace App\Entity;

use App\Repository\GroupeBondeCommandeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=GroupeBondeCommandeRepository::class)
 */
class GroupeBondeCommande
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToMany(targetEntity=BonDeCommande::class, inversedBy="groupeBondeCommandes")
     */
    private $bonDeCommande;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $code;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $downloadDate;

    public function __construct()
    {
        $this->bonDeCommande = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, BonDeCommande>
     */
    public function getBonDeCommande(): Collection
    {
        return $this->bonDeCommande;
    }

    public function addBonDeCommande(BonDeCommande $bonDeCommande): self
    {
        if (!$this->bonDeCommande->contains($bonDeCommande)) {
            $this->bonDeCommande[] = $bonDeCommande;
        }

        return $this;
    }

    public function removeBonDeCommande(BonDeCommande $bonDeCommande): self
    {
        $this->bonDeCommande->removeElement($bonDeCommande);

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getDownloadDate(): ?\DateTimeInterface
    {
        return $this->downloadDate;
    }

    public function setDownloadDate(?\DateTimeInterface $downloadDate): self
    {
        $this->downloadDate = $downloadDate;

        return $this;
    }
}
