<?php

namespace App\Entity;

use App\Repository\UnitGameClientGameRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=UnitGameClientGameRepository::class)
 */
class UnitGameClientGame
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=GammeClient::class, inversedBy="unitGameClientGames")
     */
    private $gammeClient;

    /**
     * @ORM\ManyToOne(targetEntity=Gamme::class, inversedBy="unitGameClientGames")
     */
    private $gamme;

    /**
     * @ORM\ManyToOne(targetEntity=Marque::class, inversedBy="unitGameClientGames")
     */
    private $marque;

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

    public function getGamme(): ?Gamme
    {
        return $this->gamme;
    }

    public function setGamme(?Gamme $gamme): self
    {
        $this->gamme = $gamme;

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
}
