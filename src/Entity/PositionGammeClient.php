<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OrderBy;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Repository\PositionGammeClientRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Entity(repositoryClass=PositionGammeClientRepository::class)
 * @ORM\HasLifecycleCallbacks()
 */
class PositionGammeClient
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Client::class, inversedBy="positionGammeClients")
     * @Assert\NotBlank(
     *  message = "Ce champ ne peut pas être vides"
     * )
     */
    private $client;

    /**
     * @ORM\ManyToOne(targetEntity=Gamme::class, inversedBy="positionGammeClients")
     */
    private $GammePrincipale;

    /**
     * @ORM\ManyToOne(targetEntity=GammeMarqueBlanche::class, inversedBy="positionGammeClients")
     */
    private $CustomGamme;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\NotBlank
     */
    private $position;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $greendot;

    /**
     * @ORM\OneToMany(targetEntity=MarqueBlanche::class, mappedBy="positionGammeClient" ,cascade={"persist", "remove"})
     * @Assert\Valid()
     * @OrderBy({"position" = "ASC"})
     * @Assert\NotBlank
     */
    private $marqueBlanches;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $gammeParDefaut;

    public function __construct()
    {
        $this->marqueBlanches = new ArrayCollection();
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     * 
     */
    public function checkGamme()
    {
        if($this->getGammeParDefaut())
        {
            $this->setCustomGamme(null);
        }
        else
        {
            $this->setGammePrincipale(null);
        }
    }

    /**
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context)
    {
        $client = $this->getClient();
        $position = $this->getPosition();
        $gamme = false;

        if($client)
        {
            $gammeTab = $client->getPositionGammeClients();
            $gammeTabValue = $gammeTab->getValues();
            
            $dernierNumero = null;
            if(count($gammeTabValue) > 0)
            {
                $dernierNumero = end($gammeTabValue)->getPosition() + 1;
            }

            foreach($client->getPositionGammeClients() as $p)
            {
    
                if( $p->getGammeParDefaut() )
                {
                    $gamme = $p->getGammePrincipale();
                    
                    if($p->getPosition() == $position && $this->getGammePrincipale() != $gamme && $dernierNumero)
                    {
                        $message = "Cette position est déjà prise par la gamme {$gamme->getNom()} <br> <span style='font-weight: bolder;'> Numéro disponnible: à partir de la numéro $dernierNumero </span>";
                        $context->buildViolation($message)
                            ->atPath('position')
                            ->addViolation();
    
                        break;
                    }
                   
                }
                else
                {
            
                    $gamme = $p->getCustomGamme();
    
                    if($p->getPosition() == $position && $this->getCustomGamme() != $gamme)
                    {
                        $message = "Cette position est déjà prise par la gamme {$gamme->getNom()}";
                        $context->buildViolation($message)
                            ->atPath('position')
                            ->addViolation();
        
                        break;
                    }
    
                }
            }
        }
    }

    /**
     * @Assert\Callback
     */
    public function validateGamme(ExecutionContextInterface $context)
    {
        $client = $this->getClient();

        $gamme = null;
        $defaut = false;

        if($this->getGammeParDefaut())
        {
            $gamme = $this->getGammePrincipale();
            $defaut = true;
        }
        else
        {
            $gamme = $this->getCustomGamme();
        }
        if( $client )
        {
            foreach($client->getPositionGammeClients() as $position)
            {
                
                if($this->getId() != $position->getId())
                {
                    if($defaut && $position->getGammePrincipale() == $gamme )
                    {
                        
                        $message = "Cette gamme est déjà disponible à la position {$position->getPosition()}";
                        $context->buildViolation($message)
                            ->atPath('gammePrincipale')
                            ->addViolation();
                        
                        break;
        
                    }
                    elseif($position->getCustomGamme() == $gamme)
                    {
                        $message = "Cette gamme est déjà disponible à la position {$position->getPosition()}";
                        $context->buildViolation($message)
                            ->atPath('customGamme')
                            ->addViolation();
        
                        break;
                    }

                }
            }
        }


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

    public function getGammePrincipale(): ?Gamme
    {
        return $this->GammePrincipale;
    }

    public function setGammePrincipale(?Gamme $GammePrincipale): self
    {
        $this->GammePrincipale = $GammePrincipale;

        return $this;
    }

    public function getCustomGamme(): ?GammeMarqueBlanche
    {
        return $this->CustomGamme;
    }

    public function setCustomGamme(?GammeMarqueBlanche $CustomGamme): self
    {
        $this->CustomGamme = $CustomGamme;

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

    public function getGreendot(): ?bool
    {
        return $this->greendot;
    }

    public function setGreendot(?bool $greendot): self
    {
        $this->greendot = $greendot;

        return $this;
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
            $marqueBlanch->setPositionGammeClient($this);
        }

        return $this;
    }

    public function removeMarqueBlanch(MarqueBlanche $marqueBlanch): self
    {
        if ($this->marqueBlanches->removeElement($marqueBlanch)) {
            // set the owning side to null (unless already changed)
            if ($marqueBlanch->getPositionGammeClient() === $this) {
                $marqueBlanch->setPositionGammeClient(null);
            }
        }

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

    public function getGammeParDefaut(): ?bool
    {
        return $this->gammeParDefaut;
    }

    public function setGammeParDefaut(?bool $gammeParDefaut): self
    {
        $this->gammeParDefaut = $gammeParDefaut;

        return $this;
    }
}
