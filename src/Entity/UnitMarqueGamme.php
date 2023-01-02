<?php

namespace App\Entity;

use App\Repository\UnitMarqueGammeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=UnitMarqueGammeRepository::class)
 */
class UnitMarqueGamme
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Marque::class, inversedBy="unitMarqueGammes")
     */
    private $marque;

    /**
     * @ORM\ManyToOne(targetEntity=Gamme::class, inversedBy="unitMarqueGammes")
     */
    private $Gamme;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $num_gamme;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getGamme(): ?Gamme
    {
        return $this->Gamme;
    }

    public function setGamme(?Gamme $Gamme): self
    {
        $this->Gamme = $Gamme;

        return $this;
    }

    public function getNumGamme(): ?string
    {
        return $this->num_gamme;
    }

    public function setNumGamme(?string $num_gamme): self
    {
        $this->num_gamme = $num_gamme;

        return $this;
    }
}
