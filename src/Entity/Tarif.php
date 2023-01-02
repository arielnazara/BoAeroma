<?php

namespace App\Entity;

use App\Repository\TarifRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=TarifRepository::class)
 */
class Tarif
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"produit:read","tarif", "mb:read"})
     * 
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"produit:read","tarif"})
     */
    private $nom;

    /**
     * @ORM\ManyToOne(targetEntity=Categorie::class, inversedBy="tarifs")
     */
    private $categorie;

    /**
     * @ORM\ManyToOne(targetEntity=Contenant::class, inversedBy="tarifs")
     */
    private $contenance;

    /**
     * @ORM\ManyToOne(targetEntity=Base::class, inversedBy="tarifs")
     * @ORM\JoinColumn( onDelete="SET NULL")
     */
    private $base;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     * @Groups({"produit:read","tarif", "mb:read"})
     */
    private $prixDeReferenceDetaillant;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups({"produit:read","tarif", "mb:read"})
     * 
     */
    private $memeTarif;

    /**
     * @ORM\OneToMany(targetEntity=PrixDeclinaison::class, mappedBy="tarif", cascade={"remove"})
     * @Groups({"produit:read"})
     * 
     */
    private $prixDeclinaisons;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dateAjout;

    /**
     * @ORM\ManyToOne(targetEntity=PrincipeActif::class, inversedBy="tarifs")
     * @Groups({"produit:read"})
     * 
     */
    private $declineAvec;

    /**
     * @ORM\ManyToOne(targetEntity=Client::class, inversedBy="tarifs")
     * @Groups({"produit:read", "mb:read"})
     */
    private $client;

    /**
     * @ORM\ManyToOne(targetEntity=Marque::class, inversedBy="tarifs")
     * @ORM\JoinColumn( onDelete="SET NULL")
     */
    private $marque;

    /**
     * @ORM\OneToMany(targetEntity=HistoriqueTarif::class, mappedBy="tarif", cascade={"remove"})
     */
    private $historiqueTarifs;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     * @Groups({"produit:read","tarif", "mb:read"})
     * 
     */
    private $prixDeReferenceGrossiste;

    /**
     * @ORM\OneToMany(targetEntity=MarqueBlanche::class, mappedBy="tarif")
     * 
     */
    private $marqueBlanches;


    public function __construct()
    {
        $this->prixDeclinaisons = new ArrayCollection();
        $this->dateAjout = new DateTime();
        $this->historiqueTarifs = new ArrayCollection();
        $this->marqueBlanches = new ArrayCollection();
    }

    /**
     * @Groups({"produit:read","tarif", "mb:read"})
     *
     * @return array
     */
    public function getActivePrice(): array
    {
        $actives = $this->getPrixDeclinaisons();

        $prices['grossiste'] = [];
        $prices['détaillant'] = [];

        foreach($actives as $price)
        {
            if($price->getActif())
            {
                if( strtolower($price->getTypeDeClient()->getNom()) == "grossiste")
                {
                    $prices['grossiste'][$price->getDeclinaison()] = $price->getPrix();
                }
                else
                {
                    $prices['détaillant'][$price->getDeclinaison()] = $price->getPrix();
                }
            }
        }

        return $prices;
    }

    public function getAllDecl()
    {

        $decls = $this->getDeclineAvec()->getDeclinaisons();

        $prices = $this->getActivePrice();
    
        foreach($prices as $key => $prix)
        {
            $val = [];

            foreach($decls as $dec)
            {
                $dec = $dec->getDeclinaison();

                if(!array_key_exists($dec, $prix))
                {
                    $prix[$dec] = 0;
                }
                
            }

            $prices[$key] = $prix;
            
        }

        return $prices;
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

    public function getCategorie(): ?Categorie
    {
        return $this->categorie;
    }

    public function setCategorie(?Categorie $categorie): self
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getContenance(): ?Contenant
    {
        return $this->contenance;
    }

    public function setContenance(?Contenant $contenance): self
    {
        $this->contenance = $contenance;

        return $this;
    }

    public function getBase(): ?Base
    {
        return $this->base;
    }

    public function setBase(?Base $base): self
    {
        $this->base = $base;

        return $this;
    }


    public function getPrixDeReferenceDetaillant(): ?string
    {
        return $this->prixDeReferenceDetaillant;
    }

    public function setPrixDeReferenceDetaillant(?string $prixDeReferenceDetaillant): self
    {
        $this->prixDeReferenceDetaillant = $prixDeReferenceDetaillant;

        return $this;
    }

    public function getMemeTarif(): ?bool
    {
        return $this->memeTarif;
    }

    public function setMemeTarif(?bool $memeTarif): self
    {
        $this->memeTarif = $memeTarif;

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
            $prixDeclinaison->setTarif($this);
        }

        return $this;
    }

    public function removePrixDeclinaison(PrixDeclinaison $prixDeclinaison): self
    {
        if ($this->prixDeclinaisons->removeElement($prixDeclinaison)) {
            // set the owning side to null (unless already changed)
            if ($prixDeclinaison->getTarif() === $this) {
                $prixDeclinaison->setTarif(null);
            }
        }

        return $this;
    }

    public function getDateAjout(): ?\DateTimeInterface
    {
        return $this->dateAjout;
    }

    public function setDateAjout(?\DateTimeInterface $dateAjout): self
    {
        $this->dateAjout = $dateAjout;

        return $this;
    }

    public function __toString()
    {

        //if($this->getActif() == true){

        $value = '';

        if ($this->getClient()) {
            if ($this->getClient()->getRaisonSocial()) {

                $value = $this->getNom() . ' (client: ' . $this->getClient()->getRaisonSocial() . ')';
            } else {
                $value = $this->getNom() . ' (client: ' . $this->getClient()->getFirstName() . ' ' . $this->getClient()->getLastName() . ')';
            }
        } elseif ($this->getMarque()) {

            $value = $this->getNom() . ' (marque: ' . $this->getMarque()->getNom() . ')';
        } else {

            $value = $this->getNom();
        }

        return $value;
        //}

    }

    public function getDeclineAvec(): ?PrincipeActif
    {
        return $this->declineAvec;
    }

    public function setDeclineAvec(?PrincipeActif $declineAvec): self
    {
        $this->declineAvec = $declineAvec;

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

    public function getMarque(): ?Marque
    {
        return $this->marque;
    }

    public function setMarque(?Marque $marque): self
    {
        $this->marque = $marque;

        return $this;
    }

    /**
     * @return Collection|HistoriqueTarif[]
     */
    public function getHistoriqueTarifs(): Collection
    {
        return $this->historiqueTarifs;
    }

    public function addHistoriqueTarif(HistoriqueTarif $historiqueTarif): self
    {
        if (!$this->historiqueTarifs->contains($historiqueTarif)) {
            $this->historiqueTarifs[] = $historiqueTarif;
            $historiqueTarif->setTarif($this);
        }

        return $this;
    }

    public function removeHistoriqueTarif(HistoriqueTarif $historiqueTarif): self
    {
        if ($this->historiqueTarifs->removeElement($historiqueTarif)) {
            // set the owning side to null (unless already changed)
            if ($historiqueTarif->getTarif() === $this) {
                $historiqueTarif->setTarif(null);
            }
        }

        return $this;
    }

    public function getPrixDeReferenceGrossiste(): ?string
    {
        return $this->prixDeReferenceGrossiste;
    }

    public function setPrixDeReferenceGrossiste(?string $prixDeReferenceGrossiste): self
    {
        $this->prixDeReferenceGrossiste = $prixDeReferenceGrossiste;

        return $this;
    }

    /**
     * @return Collection|MarqueBlanche[]
     */
    public function getMarqueBlanches(): Collection
    {
        return $this->marqueBlanches;
    }

    public function addMarqueBlanch(MarqueBlanche $marqueBlanch): self
    {
        if (!$this->marqueBlanches->contains($marqueBlanch)) {
            $this->marqueBlanches[] = $marqueBlanch;
            $marqueBlanch->setTarif($this);
        }

        return $this;
    }

    public function removeMarqueBlanch(MarqueBlanche $marqueBlanch): self
    {
        if ($this->marqueBlanches->removeElement($marqueBlanch)) {
            // set the owning side to null (unless already changed)
            if ($marqueBlanch->getTarif() === $this) {
                $marqueBlanch->setTarif(null);
            }
        }

        return $this;
    }
}
