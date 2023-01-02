<?php

namespace App\Entity;

use DateTime;
use App\Entity\Tarif;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\MarqueBlancheRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Entity(repositoryClass=MarqueBlancheRepository::class)
 * @ORM\HasLifecycleCallbacks()
 */
class MarqueBlanche
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups("mb:read")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups("mb:read")
     */
    private $nom;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups("mb:read")
     */
    private $reference;

    /**
     * @ORM\ManyToOne(targetEntity=Client::class, inversedBy="marqueBlanches")
     */
    private $client;

    /**
     * @ORM\ManyToOne(targetEntity=Produit::class, inversedBy="marqueBlanches")
     * @Groups("mb:read")
     * @Assert\NotBlank(
     *  message = "Ce champ ne peut pas être vides"
     * )
     */
    private $produit;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $memeEan13;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $codeEAN13 = [];

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $marque;

    /**
     * @ORM\ManyToOne(targetEntity=Tarif::class, inversedBy="marqueBlanches")
     * @Groups("mb:read")
     * @Assert\NotBlank(
     *  message = "Ce champ ne peut pas être vides"
     * )
     */
    private $tarif;

    /**
     * @ORM\ManyToOne(targetEntity=GammeMarqueBlanche::class, inversedBy="marqueBlanches")
     */
    private $gammeMarqueBlanche;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $position;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $greendot;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private $updatedAt;

    /**
     * @ORM\ManyToOne(targetEntity=CatalogueClient::class, inversedBy="marqueBlanches")
     */
    private $catalogueClient;

    /**
     * @ORM\ManyToOne(targetEntity=PositionGammeClient::class, inversedBy="marqueBlanches")
     */
    private $positionGammeClient;


    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function setClientMBs()
    {
        $client = $this->getPositionClient();

        if($this->getPositionGammeClient())
        {
            $this->setClient($client);
        }

        if( ! $this->getMarque() )
        {
            $this->setMarque($client->getRaisonSocial());
        }
    }

    public function getTarifPath()
    {
        return $this->tarif;
    }

     /**
     * @Assert\Callback(groups={"marqueBlanches"})
     */
    public function validate(ExecutionContextInterface $context, $payload)
    {
      
        $result = $this->checkError();

        $error = $result[0];
        $message = $result[1];

        if($error)
        {
            $context->buildViolation($message)
                ->addViolation();
        }
    }

    public function getPositionClient()
    {
        if($this->getPositionGammeClient())
        {
            return $this->getPositionGammeClient()->getClient();
        }
        else
        {
            return $this->getClient();
        }
    }

    public function getLabel()
    {

        if($this->getClient())
        {
            $label = $this->getProduit()->getNomMarqueBlanche($this->getClient());
        }
        else
        {
            $label = $this->getNom();
        }

        return $label;
    }

    public function checkError()
    {
        $error = false;
        $tarif = $this->getTarif();
        $client = $this->getPositionClient();
        $label = $this->getMarque() ? $this->getMarque() : $client->getFullName();
        $message = null;

        if($tarif)
        {

            if($tarif->getClient() == $client)
            {
                
                $error = $this->checkPrice($tarif);
                $message = " <span style='font-weight: bolder;'> $label :</span> cet tarif n'est pas compatible au configuration du produit";
                
            }
            else
            {
            
                $error = true;
                $message = " <span style='font-weight: bolder;'> $label :</span> Cet tarif n'apartient pas au client que vous avez choisis";
    
            }
        }

        return [$error, $message];
    }


    /**
     * @Assert\Callback(groups={"marqueBlanches_gammeClient"})
     */
    public function validationTarifGammeClient(ExecutionContextInterface $context, $payload)
    {
        $tarif = $this->getTarif();
        $produit = $this->getProduit();
        $label = $this->getLabel();
        $client = $this->getPositionClient();

        if($tarif)
        {
            
            if($tarif->getClient() != $client)
            {
                $context->buildViolation(" <span style='font-weight: bolder;'> $label :</span> Cet tarif n'apartient pas au client que vous avez choisis")
                        ->atPath('tarif')
                        ->addViolation();
            }
            else
            {
                if ( $tarif->getBase() != $produit->getBase() || $tarif->getCategorie() != $produit->getCategorie() || $tarif->getContenance() != $produit->getContenant() || $tarif->getDeclineAvec() != $produit->getPrincipeActif()) 
                {
                    $context->buildViolation(" <span style='font-weight: bolder;'> $label :</span> Le tarif de référence que vous avez choisi ne correspond pas au configuration de ce produit")
                        ->atPath('tarif')
                        ->addViolation();
                } 
            }

        }
    }

    public function checkPrice(Tarif $tarif): bool
    {
        $error = false;

        $produit = $this->getProduit();

        if ($tarif->getBase() == $produit->getBase() && $tarif->getCategorie() == $produit->getCategorie() && $tarif->getContenance() == $produit->getContenant() && $tarif->getDeclineAvec() == $produit->getPrincipeActif()) 
        {
            $error = false;
        }
        else
        {
            $error = true;
        } 
        
        return $error;
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

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): self
    {
        $this->reference = $reference;

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

    public function getProduit(): ?Produit
    {
        return $this->produit;
    }

    public function setProduit(?Produit $produit): self
    {
        $this->produit = $produit;

        return $this;
    }

    public function getMemeEan13(): ?bool
    {
        return $this->memeEan13;
    }

    public function setMemeEan13(?bool $memeEan13): self
    {
        $this->memeEan13 = $memeEan13;

        return $this;
    }

    public function getCodeEAN13(): ?array
    {
        return $this->codeEAN13;
    }

    public function setCodeEAN13(?array $codeEAN13): self
    {
        $this->codeEAN13 = $codeEAN13;

        return $this;
    }

    public function getMarque(): ?string
    {
        return $this->marque;
    }

    public function setMarque(?string $marque): self
    {
        $this->marque = $marque;

        return $this;
    }

    public function getTarif(): ?Tarif
    {
        return $this->tarif;
    }

    public function setTarif(?Tarif $tarif): self
    {
        $this->tarif = $tarif;

        return $this;
    }

    public function getGammeMarqueBlanche(): ?GammeMarqueBlanche
    {
        return $this->gammeMarqueBlanche;
    }

    public function setGammeMarqueBlanche(?GammeMarqueBlanche $gammeMarqueBlanche): self
    {
        $this->gammeMarqueBlanche = $gammeMarqueBlanche;

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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getCatalogueClient(): ?CatalogueClient
    {
        return $this->catalogueClient;
    }

    public function setCatalogueClient(?CatalogueClient $catalogueClient): self
    {
        $this->catalogueClient = $catalogueClient;

        return $this;
    }

    public function getPositionGammeClient(): ?PositionGammeClient
    {
        return $this->positionGammeClient;
    }

    public function setPositionGammeClient(?PositionGammeClient $positionGammeClient): self
    {
        $this->positionGammeClient = $positionGammeClient;

        return $this;
    }

}
