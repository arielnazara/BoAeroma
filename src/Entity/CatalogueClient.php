<?php

namespace App\Entity;

use App\Repository\CatalogueClientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CatalogueClientRepository::class)
 */
class CatalogueClient
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToMany(targetEntity=MarqueBlanche::class, mappedBy="catalogueClient", cascade={"persist"}))
     */
    private $marqueBlanches;

    /**
     * @ORM\ManyToOne(targetEntity=Gamme::class, inversedBy="catalogueClients")
     */
    private $gamme;

    /**
     * @ORM\ManyToOne(targetEntity=Client::class, inversedBy="catalogueClients")
     */
    private $client;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $position;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private $updatedAt;

    /**
     * @ORM\ManyToOne(targetEntity=GammeMarqueBlanche::class, inversedBy="catalogueClients")
     */
    private $gammeClient;

    public function __construct()
    {
        $this->marqueBlanches = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, MarqueBlanche>
     */
    public function getMarqueBlanches(): Collection
    {
        return $this->marqueBlanches;
    }

    public function addMarqueBlanch(MarqueBlanche $marqueBlanch): self
    {
        if (!$this->marqueBlanches->contains($marqueBlanch)) {
            $this->marqueBlanches[] = $marqueBlanch;
            $marqueBlanch->setCatalogueClient($this);
        }

        return $this;
    }

    public function removeMarqueBlanch(MarqueBlanche $marqueBlanch): self
    {
        if ($this->marqueBlanches->removeElement($marqueBlanch)) {
            // set the owning side to null (unless already changed)
            if ($marqueBlanch->getCatalogueClient() === $this) {
                $marqueBlanch->setCatalogueClient(null);
            }
        }

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

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        foreach($this->getMarqueBlanches() as $mb)
        {
            $mb->setUpdatedAt($updatedAt);
        }

        return $this;
    }

    public function getGammeClient(): ?GammeMarqueBlanche
    {
        return $this->gammeClient;
    }

    public function setGammeClient(?GammeMarqueBlanche $gammeClient): self
    {
        $this->gammeClient = $gammeClient;

        return $this;
    }
    
}
