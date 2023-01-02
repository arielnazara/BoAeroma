<?php

namespace App\Entity;

use App\Repository\BaseComposantRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=BaseComposantRepository::class)
 */
class BaseComposant
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

  

    /**
     * @ORM\ManyToOne(targetEntity=Composant::class, inversedBy="baseComposants")
     * @ORM\JoinColumn( onDelete="SET NULL")
     */
    private $composant;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $numComposant;

    /**
     * @ORM\ManyToOne(targetEntity=Base::class, inversedBy="baseComposants")
     */
    private $base;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $ratio;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getComposant(): ?Composant
    {
        return $this->composant;
    }

    public function setComposant(?Composant $composant): self
    {
        $this->composant = $composant;

        return $this;
    }

    public function getNumComposant(): ?string
    {
        return $this->numComposant;
    }

    public function setNumComposant(?string $numComposant): self
    {
        $this->numComposant = $numComposant;

        return $this;
    }

    public function getBase(): ?Base
    {
        return $this->base;
    }

    public function setBase(?Base $base): self
    {
        $this->base = $base;

        return $this;
    }

    public function getRatio(): ?string
    {
        return $this->ratio;
    }

    public function setRatio(?string $ratio): self
    {
        $this->ratio = $ratio;

        return $this;
    }
}
