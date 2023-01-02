<?php

namespace App\Entity;

use App\Repository\ComposantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ComposantRepository::class)
 */
class Composant
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
     * @ORM\OneToMany(targetEntity=Base::class, mappedBy="composant")
     */
    private $bases;

    /**
     * @ORM\OneToMany(targetEntity=BaseComposant::class, mappedBy="composant")
     */
    private $baseComposants;

    public function __construct()
    {
        $this->bases = new ArrayCollection();
        $this->baseComposants = new ArrayCollection();
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

    public function __toString()
    {
        return $this->getNom();
    }

    /**
     * @return Collection|Base[]
     */
    public function getBases(): Collection
    {
        return $this->bases;
    }

    /**
     * @return Collection|BaseComposant[]
     */
    public function getBaseComposants(): Collection
    {
        return $this->baseComposants;
    }

    public function addBaseComposant(BaseComposant $baseComposant): self
    {
        if (!$this->baseComposants->contains($baseComposant)) {
            $this->baseComposants[] = $baseComposant;
            $baseComposant->setComposant($this);
        }

        return $this;
    }

    public function removeBaseComposant(BaseComposant $baseComposant): self
    {
        if ($this->baseComposants->removeElement($baseComposant)) {
            // set the owning side to null (unless already changed)
            if ($baseComposant->getComposant() === $this) {
                $baseComposant->setComposant(null);
            }
        }

        return $this;
    }
}
