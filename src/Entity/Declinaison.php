<?php

namespace App\Entity;

use App\Repository\DeclinaisonRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups ;

/**
 * @ORM\Entity(repositoryClass=DeclinaisonRepository::class)
 */
class Declinaison
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * 
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=PrincipeActif::class, inversedBy="declinaisons")
     */
    private $principeActif;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * 
     */
    private $declinaison;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrincipeActif(): ?PrincipeActif
    {
        return $this->principeActif;
    }

    public function setPrincipeActif(?PrincipeActif $principeActif): self
    {
        $this->principeActif = $principeActif;

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
}
