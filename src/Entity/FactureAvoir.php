<?php

namespace App\Entity;

use App\Repository\FactureAvoirRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=FactureAvoirRepository::class)
 * @ORM\HasLifecycleCallbacks()
 */
class FactureAvoir
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

   

   

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $totalHT;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $tva;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $totalTtc;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $Description;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $numero;

    /**
     * @ORM\Column(type="integer")
     */
    private $increment;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $payer;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $datePaiement;

    /**
     * @ORM\ManyToOne(targetEntity=FactureCommande::class, inversedBy="factureAvoirs")
     */
    private $facture;

    /**
     * @ORM\ManyToOne(targetEntity=FactureMaitre::class, inversedBy="factureAvoirs")
     */
    private $factureMaitre;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $expedier;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $montantPayer;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     */
    private $balance;

    /**
     * @ORM\ManyToOne(targetEntity=Prestation::class, inversedBy="factureAvoirs")
     */
    private $prestation;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreatedAtValue()
    {
        $this->createdAt = new \DateTime();
    }

    public function getFacture(): ?FactureCommande
    {
        return $this->facture;
    }

    public function setFacture(?FactureCommande $facture): self
    {
        $this->facture = $facture;

        return $this;
    }

    public function getFactureMaitre(): ?FactureMaitre
    {
        return $this->factureMaitre;
    }

    public function setFactureMaitre(?FactureMaitre $factureMaitre): self
    {
        $this->factureMaitre = $factureMaitre;

        return $this;
    }

    public function getTotalHT(): ?string
    {
        return $this->totalHT;
    }

    public function setTotalHT(?string $totalHT): self
    {
        $this->totalHT = $totalHT;

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

    public function getDescription(): ?string
    {
        return $this->Description;
    }

    public function setDescription(?string $Description): self
    {
        $this->Description = $Description;

        return $this;
    }

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(string $numero): self
    {
        $this->numero = $numero;

        return $this;
    }

    public function getIncrement(): ?int
    {
        return $this->increment;
    }

    public function setIncrement(int $increment): self
    {
        $this->increment = $increment;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getPayer(): ?bool
    {
        return $this->payer;
    }

    public function setPayer(?bool $payer): self
    {
        $this->payer = $payer;

        if($payer && !$this->getDatePaiement())
        {
            $this->setDatePaiement(new DateTime());
        }

        return $this;
    }

    public function getDatePaiement(): ?\DateTimeInterface
    {
        return $this->datePaiement;
    }

    public function setDatePaiement(?\DateTimeInterface $datePaiement): self
    {
        $this->datePaiement = $datePaiement;

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

    public function getMontantPayer(): ?string
    {
        return $this->montantPayer;
    }

    public function setMontantPayer(?string $montantPayer): self
    {
        $this->montantPayer = $montantPayer;

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
            
            $this->setBalance( $balance);
        }
    }

    public function getPrestation(): ?Prestation
    {
        return $this->prestation;
    }

    public function setPrestation(?Prestation $prestation): self
    {
        $this->prestation = $prestation;

        return $this;
    }
}
