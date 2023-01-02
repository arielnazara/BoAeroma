<?php

namespace App\Entity;

use App\Repository\PrestationRepository;
use DateTime;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=PrestationRepository::class)
 * @ORM\HasLifecycleCallbacks()
 */
class Prestation
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Client::class, inversedBy="prestations")
     */
    private $client;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $montant;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $tva;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $totalTtc;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $montantPayer;

    /**
     * @ORM\OneToMany(targetEntity=TypePrestation::class, mappedBy="prestation", cascade={"persist"})
     * @Assert\Valid()
     */
    private $typePrestations;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $numero;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $increment;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $expedier;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private $datePaiement;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $estPayer;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $balance;

    public function __construct()
    {
        $this->typePrestations = new ArrayCollection();
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreatedAtValue()
    {
        $date = new DateTimeImmutable();
        $this->setCreatedAt($date);
    }

    public function getId(): ?int
    {
        return $this->id;
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
     * @return Collection<int, TypePrestation>
     */
    public function getType(): Collection
    {
        return $this->type;
    }


    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(?string $montant): self
    {
        $this->montant = $montant;

        return $this;
    }

    public function getTva(): ?string
    {
        return $this->tva;
    }

    public function setTva(?string $tva): self
    {
        $this->tva = $tva;

        return $this;
    }

    public function getTotalTtc(): ?string
    {
        return $this->totalTtc;
    }

    public function setTotalTtc(?string $totalTtc): self
    {
        $this->totalTtc = $totalTtc;

        return $this;
    }

    public function getMontantPayer(): ?string
    {
        return $this->montantPayer;
    }

    public function setMontantPayer(?string $montantPayer): self
    {
        $this->montantPayer = $montantPayer;

        return $this;
    }

    /**
     * @return Collection<int, TypePrestation>
     */
    public function getTypePrestations(): Collection
    {
        return $this->typePrestations;
    }

    public function addTypePrestation(TypePrestation $typePrestation): self
    {
        if (!$this->typePrestations->contains($typePrestation)) {
            $this->typePrestations[] = $typePrestation;
            $typePrestation->setPrestation($this);
        }

        return $this;
    }

    public function removeTypePrestation(TypePrestation $typePrestation): self
    {
        if ($this->typePrestations->removeElement($typePrestation)) {
            // set the owning side to null (unless already changed)
            if ($typePrestation->getPrestation() === $this) {
                $typePrestation->setPrestation(null);
            }
        }

        return $this;
    }

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(?string $numero): self
    {
        $this->numero = $numero;

        return $this;
    }

    public function getIncrement(): ?int
    {
        return $this->increment;
    }

    public function setIncrement(?int $increment): self
    {
        $this->increment = $increment;

        return $this;
    }

    public function getExpedier(): ?bool
    {
        return $this->expedier;
    }

    public function setExpedier(?bool $expedier): self
    {
        $this->expedier = $expedier;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getDatePaiement(): ?\DateTimeImmutable
    {
        return $this->datePaiement;
    }

    public function setDatePaiement(?\DateTimeImmutable $datePaiement): self
    {
        $this->datePaiement = $datePaiement;

        return $this;
    }

    public function getEstPayer(): ?bool
    {
        return $this->estPayer;
    }

    public function setEstPayer(?bool $estPayer): self
    {
        $this->estPayer = $estPayer;

        return $this;
    }

    public function getBalance(): ?string
    {
        return $this->balance;
    }

    public function setBalance(?string $balance): self
    {
        $this->balance = $balance;

        return $this;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function CalculBalance()
    {
        if($this->getMontantPayer())
        {
            $balance = $this->getTotalTtc() - $this->getMontantPayer();
    
            if($balance > 0)
            {
                $balance = - $balance;
            }
            else
            {
                $balance = abs($balance);
            }
            

            $this->setBalance($balance);
        }
    }

    function getInvoiceType(): string
    {
        return "prestation";
    }
}
