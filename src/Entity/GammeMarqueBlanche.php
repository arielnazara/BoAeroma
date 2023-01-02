<?php

namespace App\Entity;

use App\Repository\GammeMarqueBlancheRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=GammeMarqueBlancheRepository::class)
 */
class GammeMarqueBlanche
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $nom;


    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $couleur;


    /**
     * @ORM\ManyToOne(targetEntity=Client::class, inversedBy="gammeMarqueBlanches")
     */
    private $client;

    /**
     * @ORM\OneToMany(targetEntity=CatalogueClient::class, mappedBy="gammeClient")
     */
    private $catalogueClients;

    public function __construct()
    {
       
        $this->catalogueClients = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getCouleur(): ?string
    {
        return $this->couleur;
    }

    public function setCouleur(?string $couleur): self
    {
        $this->couleur = $couleur;

        return $this;
    }

    public function __toString()
    {
        return $this->getNom();   
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


    /**
     * @return Collection<int, CatalogueClient>
     */
    public function getCatalogueClients(): Collection
    {
        return $this->catalogueClients;
    }

    public function addCatalogueClient(CatalogueClient $catalogueClient): self
    {
        if (!$this->catalogueClients->contains($catalogueClient)) {
            $this->catalogueClients[] = $catalogueClient;
            $catalogueClient->setGammeClient($this);
        }

        return $this;
    }

    public function removeCatalogueClient(CatalogueClient $catalogueClient): self
    {
        if ($this->catalogueClients->removeElement($catalogueClient)) {
            // set the owning side to null (unless already changed)
            if ($catalogueClient->getGammeClient() === $this) {
                $catalogueClient->setGammeClient(null);
            }
        }

        return $this;
    }
}
