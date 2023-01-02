<?php

namespace App\Entity;

use App\Repository\UnitGameClientMarqueRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=UnitGameClientMarqueRepository::class)
 */
class UnitGameClientMarque
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=GammeClient::class, inversedBy="unitGameClientMarques")
     */
    private $gammeClient;

    /**
     * @ORM\ManyToOne(targetEntity=Marque::class, inversedBy="unitGameClientMarques")
     * 
     */
    private $marque;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $numGammeClient;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $configurer;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGammeClient(): ?GammeClient
    {
        return $this->gammeClient;
    }

    public function setGammeClient(?GammeClient $gammeClient): self
    {
        $this->gammeClient = $gammeClient;

        return $this;
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

    public function getNumGammeClient(): ?string
    {
        return $this->numGammeClient;
    }

    public function setNumGammeClient(?string $numGammeClient): self
    {
        $this->numGammeClient = $numGammeClient;

        return $this;
    }

    public function getConfigurer(): ?bool
    {
        return $this->configurer;
    }

    public function setConfigurer(?bool $configurer): self
    {
        $this->configurer = $configurer;

        return $this;
    }

}
