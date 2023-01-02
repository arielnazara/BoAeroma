<?php

namespace App\Entity;

use App\Repository\PrixDeclinaisonRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups ;

/**
 * @ORM\Entity(repositoryClass=PrixDeclinaisonRepository::class)
 */
class PrixDeclinaison
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups("produit:read")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups("produit:read")
     */
    private $declinaison;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     * @Groups("produit:read")
     */
    private $prix;

    /**
     * @ORM\ManyToOne(targetEntity=Tarif::class, inversedBy="prixDeclinaisons")
     */
    private $tarif;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups("produit:read")
     */
    private $actif;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $date;

    /**
     * @ORM\ManyToOne(targetEntity=HistoriqueTarif::class, inversedBy="prixDeclinaisons")
     */
    private $historique;

    /**
     * @ORM\ManyToOne(targetEntity=TypeDeClient::class, inversedBy="prixDeclinaisons")
     */
    private $typeDeClient;



    public function getId(): ?int
    {
        return $this->id;
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

    public function getPrix(): ?string
    {
        return $this->prix;
    }

    public function setPrix(string $prix): self
    {
        $this->prix = $prix;

        return $this;
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

    public function getActif(): ?bool
    {
        return $this->actif;
    }

    public function setActif(?bool $actif): self
    {
        $this->actif = $actif;

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

    public function getHistorique(): ?HistoriqueTarif
    {
        return $this->historique;
    }

    public function setHistorique(?HistoriqueTarif $historique): self
    {
        $this->historique = $historique;

        return $this;
    }

    public function getTypeDeClient(): ?TypeDeClient
    {
        return $this->typeDeClient;
    }

    public function setTypeDeClient(?TypeDeClient $typeDeClient): self
    {
        $this->typeDeClient = $typeDeClient;

        return $this;
    }

}
