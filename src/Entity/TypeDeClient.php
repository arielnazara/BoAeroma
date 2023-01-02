<?php

namespace App\Entity;

use App\Repository\TypeDeClientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TypeDeClientRepository::class)
 */
class TypeDeClient
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
     * @ORM\OneToMany(targetEntity=Client::class, mappedBy="typeDeClient")
     */
    private $clients;

    /**
     * @ORM\OneToMany(targetEntity=Tarif::class, mappedBy="typeDeClient")
     */
    private $tarifs;

    /**
     * @ORM\OneToMany(targetEntity=PrixDeclinaison::class, mappedBy="typeDeClient")
     */
    private $prixDeclinaisons;

    public function __construct()
    {
        $this->clients = new ArrayCollection();
        $this->tarifs = new ArrayCollection();
        $this->prixDeclinaisons = new ArrayCollection();
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

    /**
     * @return Collection|Client[]
     */
    public function getClients(): Collection
    {
        return $this->clients;
    }

    public function addClient(Client $client): self
    {
        if (!$this->clients->contains($client)) {
            $this->clients[] = $client;
            $client->setTypeDeClient($this);
        }

        return $this;
    }

    public function removeClient(Client $client): self
    {
        if ($this->clients->removeElement($client)) {
            // set the owning side to null (unless already changed)
            if ($client->getTypeDeClient() === $this) {
                $client->setTypeDeClient(null);
            }
        }

        return $this;
    }

    public function __toString()
    {
        return $this->getNom();
    }

    /**
     * @return Collection|Tarif[]
     */
    public function getTarifs(): Collection
    {
        return $this->tarifs;
    }

    public function addTarif(Tarif $tarif): self
    {
        if (!$this->tarifs->contains($tarif)) {
            $this->tarifs[] = $tarif;
            $tarif->setTypeDeClient($this);
        }

        return $this;
    }

    public function removeTarif(Tarif $tarif): self
    {
        if ($this->tarifs->removeElement($tarif)) {
            // set the owning side to null (unless already changed)
            if ($tarif->getTypeDeClient() === $this) {
                $tarif->setTypeDeClient(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|PrixDeclinaison[]
     */
    public function getPrixDeclinaisons(): Collection
    {
        return $this->prixDeclinaisons;
    }

    public function addPrixDeclinaison(PrixDeclinaison $prixDeclinaison): self
    {
        if (!$this->prixDeclinaisons->contains($prixDeclinaison)) {
            $this->prixDeclinaisons[] = $prixDeclinaison;
            $prixDeclinaison->setTypeDeClient($this);
        }

        return $this;
    }

    public function removePrixDeclinaison(PrixDeclinaison $prixDeclinaison): self
    {
        if ($this->prixDeclinaisons->removeElement($prixDeclinaison)) {
            // set the owning side to null (unless already changed)
            if ($prixDeclinaison->getTypeDeClient() === $this) {
                $prixDeclinaison->setTypeDeClient(null);
            }
        }

        return $this;
    }
}
