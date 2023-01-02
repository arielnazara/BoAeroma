<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\ProduitRepository;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\File\File;
use Doctrine\Common\Collections\ArrayCollection;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\Serializer\Annotation\Groups ;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Entity(repositoryClass=ProduitRepository::class)
 * @UniqueEntity(fields="reference", message="Référence existante")
 * @Vich\Uploadable
 */
class Produit
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"produit:read","samples", "mb:read"})
     */
    private $id;

     /**
     * NOTE: This is not a mapped field of entity metadata, just a simple property.
     * 
     * @Vich\UploadableField(mapping="products", fileNameProperty="imageName", size="imageSize")
     * 
     * @var File|null
     */
    private $imageFile;

     /**
     * @ORM\Column(type="string", nullable=true)
     * @Groups("produit:read")
     * @var string|null
     */
    private $imageName;

    
    /**
     * @ORM\Column(type="integer", nullable=true)
     *
     * @var int|null
     */
    private $imageSize;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $idPrestashop;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dateAdd;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dateUpdate;

    /**
     * @Groups("produit:read")
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"produit:read","samples", "mb:read"})
     */
    private $nom;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * 
     */
    private $faitParAeroma;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $marqueBlanche;

    /**
     * @ORM\ManyToOne(targetEntity=Contenant::class, inversedBy="produits")
     * 
     * 
     */
    private $contenant;


    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups("produit:read", "mb:read")
     * 
     */
    private $reference;

    /**
     * @ORM\ManyToOne(targetEntity=Marque::class, inversedBy="produits")
     * @ORM\JoinColumn( onDelete="SET NULL")
     * 
     */
    private $marque;

    /**
     * @ORM\ManyToOne(targetEntity=Gamme::class, inversedBy="produits")
     * 
     */
    private $gamme;

    /**
     * @ORM\ManyToOne(targetEntity=Categorie::class, inversedBy="produits")
     * 
     */
    private $categorie;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $quantite;

    /**
     * @ORM\OneToMany(targetEntity=ProduitArome::class, mappedBy="produit", cascade={"remove", "persist"})
     * 
     */
    private $produitAromes;

    /**
     * @ORM\ManyToOne(targetEntity=Fournisseur::class, inversedBy="produits")
     */
    private $fournisseur;

    /**
     * @ORM\ManyToOne(targetEntity=Base::class, inversedBy="produits")
     * 
     */
    private $base;

    /**
     * @ORM\ManyToOne(targetEntity=Tarif::class, inversedBy="produits")
     * @ORM\JoinColumn( onDelete="SET NULL")
     * @Groups("produit:read", "mb:read")
     * 
     */
    private $tarif;

    /**
     * @ORM\OneToMany(targetEntity=UnitGammeClientProduit::class, mappedBy="produit", cascade={"remove"})
     */
    private $unitGammeClientProduits;

    /**
     * @ORM\OneToMany(targetEntity=MarqueBlanche::class, mappedBy="produit", cascade={"remove", "persist"} )
     * @Assert\Valid()
     */
    private $marqueBlanches;

    /**
     * @ORM\OneToMany(targetEntity=DevisProduit::class, mappedBy="produit", cascade={"remove"})
     */
    private $devisProduits;

    /**
     * @ORM\OneToMany(targetEntity=Commandes::class, mappedBy="produit")
     */
    private $commandes;

    /**
     * @ORM\OneToMany(targetEntity=LotQuantite::class, mappedBy="produit")
     */
    private $lotQuantites;

    /**
     * @ORM\Column(type="json", nullable=true)
     * @Groups({"produit:read","samples", "mb:read"})
     */
    private $declinaison = [];

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $codeEAN13 = [];

    /**
     * @ORM\ManyToOne(targetEntity=PrincipeActif::class, inversedBy="produits")
     * @Groups("produit:read")
     */
    private $principeActif;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=3, nullable=true)
     * @Groups({"mb:read", "produit:read"})
     * 
     */
    private $poids;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $type;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $position;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $constellation;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $plaisir;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $greenLeaf;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $saltyDog;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $referenceDeclinaison = [];

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $sativap;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $cbdYzy;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $absolu;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $yzyCities;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $positionGreendot;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $coolex;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $coolexOil;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $origineHemp;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $origineEliquide;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $tech;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $spring;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $summer;

    /**
     * @ORM\OneToOne(targetEntity=OrdreCeres::class, mappedBy="produit", cascade={"persist", "remove"})
     */
    private $ordreCeres;

    /**
     * @ORM\OneToOne(targetEntity=OrdreExtravape::class, mappedBy="produit", cascade={"persist", "remove"})
     */
    private $ordreExtravape;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $offert;


    public function __construct()
    {
        $this->setDateAdd(new \DateTime());
        $this->produitAromes = new ArrayCollection();
        $this->unitGammeClientProduits = new ArrayCollection();
        $this->marqueBlanches = new ArrayCollection();
        $this->devisProduits = new ArrayCollection();
        $this->commandes = new ArrayCollection();
        $this->lotQuantites = new ArrayCollection();
        
    }

    /**
     * @Assert\Callback(groups={"marqueBlanches"})
     */
    public function checkPrice(ExecutionContextInterface $context): void
    {

        if($this->getTarif())
        {
            if ( $this->getTarif()->getBase() != $this->getBase() || $this->getTarif()->getCategorie() != $this->getTarif()->getCategorie() || $this->getTarif()->getContenance() != $this->getContenant() || $this->getTarif()->getDeclineAvec() != $this->getPrincipeActif()) 
            {
                $context->buildViolation('Le tarif de référence que vous avez choisi ne correspond pas au configuration de ce produit ')
                    ->atPath('tarif')
                    ->addViolation();
            } 
        }
    
    }


    public function getTarifMb(Client $client)
    {
        foreach($this->getMarqueBlanches() as $mb)
        {
            if($mb->getClient() == $client)
            {
                return $mb->getTarif();
            }
        }
        return $this->tarif;
    }


    public function getNomMarqueBlanche(Client $client)
    {
        foreach($this->getMarqueBlanches() as $mb)
        {
            if($mb->getClient() == $client && $mb->getProduit() == $this)
            {
                return $mb->getNom();
            }
        }

        return $this->getNom();
    }

    public function siClientMarqueBlanche(Client $client)
    {
        foreach($this->getMarqueBlanches() as $mb)
        {
            if($mb->getClient() == $client && $mb->getProduit() == $this)
            {
                return $mb;
            }
        }

        return null;
    }

    public function getAromes()
    {
        $aromes = $this->getProduitAromes();

        $str = [];

        foreach($aromes as $arome)
        {
            
            array_push($str, $arome->getArome() );

        }
       
        return implode(", ", $str);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdPrestashop(): ?int
    {
        return $this->idPrestashop;
    }

    public function setIdPrestashop(?int $idPrestashop): self
    {
        $this->idPrestashop = $idPrestashop;

        return $this;
    }

    public function getDateAdd(): ?\DateTimeInterface
    {
        return $this->dateAdd;
    }

    public function setDateAdd(?\DateTimeInterface $dateAdd): self
    {
        $this->dateAdd = $dateAdd;

        return $this;
    }

    public function getDateUpdate(): ?\DateTimeInterface
    {
        return $this->dateUpdate;
    }

    public function setDateUpdate(?\DateTimeInterface $dateUpdate): self
    {
        $this->dateUpdate = $dateUpdate;

        return $this;
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


    public function getFaitParAeroma(): ?bool
    {
        return $this->faitParAeroma;
    }

    public function setFaitParAeroma(?bool $faitParAeroma): self
    {
        $this->faitParAeroma = $faitParAeroma;

        return $this;
    }

    public function getMarqueBlanche(): ?bool
    {
        return $this->marqueBlanche;
    }

    public function setMarqueBlanche(?bool $marqueBlanche): self
    {
        $this->marqueBlanche = $marqueBlanche;

        return $this;
    }

    
    public function getContenant(): ?Contenant
    {
        return $this->contenant;
    }

    public function setContenant(?Contenant $contenant): self
    {
        $this->contenant = $contenant;

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

    public function getMarque(): ?Marque
    {
        return $this->marque;
    }

    public function setMarque(?Marque $marque): self
    {
        $this->marque = $marque;

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

    public function getCategorie(): ?Categorie
    {
        return $this->categorie;
    }

    public function setCategorie(?Categorie $categorie): self
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getQuantite(): ?string
    {
        return $this->quantite;
    }

    public function setQuantite(?string $quantite): self
    {
        $this->quantite = $quantite;

        return $this;
    }

    /**
     * @return Collection|ProduitArome[]
     */
    public function getProduitAromes(): Collection
    {
        return $this->produitAromes;
    }

    public function addProduitArome(ProduitArome $produitArome): self
    {
        
        if (!$this->produitAromes->contains($produitArome)) {
            $this->produitAromes[] = $produitArome;
            $produitArome->setProduit($this);
        }

        return $this;
    }

    public function removeProduitArome(ProduitArome $produitArome): self
    {
        if ($this->produitAromes->removeElement($produitArome)) {
            // set the owning side to null (unless already changed)
            if ($produitArome->getProduit() === $this) {
                $produitArome->setProduit(null);
            }
        }

        return $this;
    }

    public function getFournisseur(): ?Fournisseur
    {
        return $this->fournisseur;
    }

    public function setFournisseur(?Fournisseur $fournisseur): self
    {
        $this->fournisseur = $fournisseur;

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

    public function getTarif(): ?Tarif
    {
        return $this->tarif;
    }

    public function setTarif(?Tarif $tarif): self
    {
        $this->tarif = $tarif;

        return $this;
    }

    /**
     * @return Collection|UnitGammeClientProduit[]
     */
    public function getUnitGammeClientProduits(): Collection
    {
        return $this->unitGammeClientProduits;
    }

    public function addUnitGammeClientProduit(UnitGammeClientProduit $unitGammeClientProduit): self
    {
        if (!$this->unitGammeClientProduits->contains($unitGammeClientProduit)) {
            $this->unitGammeClientProduits[] = $unitGammeClientProduit;
            $unitGammeClientProduit->setProduit($this);
        }

        return $this;
    }

    public function removeUnitGammeClientProduit(UnitGammeClientProduit $unitGammeClientProduit): self
    {
        if ($this->unitGammeClientProduits->removeElement($unitGammeClientProduit)) {
            // set the owning side to null (unless already changed)
            if ($unitGammeClientProduit->getProduit() === $this) {
                $unitGammeClientProduit->setProduit(null);
            }
        }

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
            $marqueBlanch->setProduit($this);
        }

        return $this;
    }

    public function removeMarqueBlanch(MarqueBlanche $marqueBlanch): self
    {
        if ($this->marqueBlanches->removeElement($marqueBlanch)) {
            // set the owning side to null (unless already changed)
            if ($marqueBlanch->getProduit() === $this) {
                $marqueBlanch->setProduit(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|DevisProduit[]
     */
    public function getDevisProduits(): Collection
    {
        return $this->devisProduits;
    }

    public function addDevisProduit(DevisProduit $devisProduit): self
    {
        if (!$this->devisProduits->contains($devisProduit)) {
            $this->devisProduits[] = $devisProduit;
            $devisProduit->setProduit($this);
        }

        return $this;
    }

    public function removeDevisProduit(DevisProduit $devisProduit): self
    {
        if ($this->devisProduits->removeElement($devisProduit)) {
            // set the owning side to null (unless already changed)
            if ($devisProduit->getProduit() === $this) {
                $devisProduit->setProduit(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Commandes[]
     */
    public function getCommandes(): Collection
    {
        return $this->commandes;
    }

    public function addCommande(Commandes $commande): self
    {
        if (!$this->commandes->contains($commande)) {
            $this->commandes[] = $commande;
            $commande->setProduit($this);
        }

        return $this;
    }

    public function removeCommande(Commandes $commande): self
    {
        if ($this->commandes->removeElement($commande)) {
            // set the owning side to null (unless already changed)
            if ($commande->getProduit() === $this) {
                $commande->setProduit(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|LotQuantite[]
     */
    public function getLotQuantites(): Collection
    {
        return $this->lotQuantites;
    }

    public function addLotQuantite(LotQuantite $lotQuantite): self
    {
        if (!$this->lotQuantites->contains($lotQuantite)) {
            $this->lotQuantites[] = $lotQuantite;
            $lotQuantite->setProduit($this);
        }

        return $this;
    }

    public function removeLotQuantite(LotQuantite $lotQuantite): self
    {
        if ($this->lotQuantites->removeElement($lotQuantite)) {
            // set the owning side to null (unless already changed)
            if ($lotQuantite->getProduit() === $this) {
                $lotQuantite->setProduit(null);
            }
        }

        return $this;
    }

    public function getDeclinaison(): ?array
    {
        return $this->declinaison;
    }

    public function setDeclinaison(?array $declinaison): self
    {
        $this->declinaison = $declinaison;

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

    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;

    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageName(?string $imageName): void
    {
        $this->imageName = $imageName;
    }

    public function getImageName(): ?string
    {
        return $this->imageName;
    }
    
    public function setImageSize(?int $imageSize): void
    {
        $this->imageSize = $imageSize;
    }

    public function getImageSize(): ?int
    {
        return $this->imageSize;
    }

    public function getPrincipeActif(): ?PrincipeActif
    {
        return $this->principeActif;
    }

    public function setPrincipeActif(?PrincipeActif $principeActif): self
    {
        $this->principeActif = $principeActif;

        return $this;
    }

    public function getPoids(): ?string
    {
        return $this->poids;
    }

    public function setPoids(?string $poids): self
    {
        $this->poids = $poids;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

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

    public function getConstellation(): ?bool
    {
        return $this->constellation;
    }

    public function setConstellation(?bool $constellation): self
    {
        $this->constellation = $constellation;

        return $this;
    }

    public function getPlaisir(): ?bool
    {
        return $this->plaisir;
    }

    public function setPlaisir(?bool $plaisir): self
    {
        $this->plaisir = $plaisir;

        return $this;
    }

    public function getGreenLeaf(): ?bool
    {
        return $this->greenLeaf;
    }

    public function setGreenLeaf(?bool $greenLeaf): self
    {
        $this->greenLeaf = $greenLeaf;

        return $this;
    }

    public function getSaltyDog(): ?bool
    {
        return $this->saltyDog;
    }

    public function setSaltyDog(?bool $saltyDog): self
    {
        $this->saltyDog = $saltyDog;

        return $this;
    }

    public function getReferenceDeclinaison(): ?array
    {
        return $this->referenceDeclinaison;
    }

    public function setReferenceDeclinaison(?array $referenceDeclinaison): self
    {
        $this->referenceDeclinaison = $referenceDeclinaison;

        return $this;
    }

    public function getSativap(): ?bool
    {
        return $this->sativap;
    }

    public function setSativap(?bool $sativap): self
    {
        $this->sativap = $sativap;

        return $this;
    }

    public function getCbdYzy(): ?bool
    {
        return $this->cbdYzy;
    }

    public function setCbdYzy(?bool $cbdYzy): self
    {
        $this->cbdYzy = $cbdYzy;

        return $this;
    }

    public function getAbsolu(): ?bool
    {
        return $this->absolu;
    }

    public function setAbsolu(?bool $absolu): self
    {
        $this->absolu = $absolu;

        return $this;
    }

    public function getYzyCities(): ?bool
    {
        return $this->yzyCities;
    }

    public function setYzyCities(?bool $yzyCities): self
    {
        $this->yzyCities = $yzyCities;

        return $this;
    }

    public function getPositionGreendot(): ?int
    {
        return $this->positionGreendot;
    }

    public function setPositionGreendot(?int $positionGreendot): self
    {
        $this->positionGreendot = $positionGreendot;

        return $this;
    }

    public function getCoolex(): ?bool
    {
        return $this->coolex;
    }

    public function setCoolex(?bool $coolex): self
    {
        $this->coolex = $coolex;

        return $this;
    }

    public function getCoolexOil(): ?bool
    {
        return $this->coolexOil;
    }

    public function setCoolexOil(?bool $coolexOil): self
    {
        $this->coolexOil = $coolexOil;

        return $this;
    }

    public function getOrigineHemp(): ?bool
    {
        return $this->origineHemp;
    }

    public function setOrigineHemp(?bool $origineHemp): self
    {
        $this->origineHemp = $origineHemp;

        return $this;
    }

    public function getOrigineEliquide(): ?bool
    {
        return $this->origineEliquide;
    }

    public function setOrigineEliquide(?bool $origineEliquide): self
    {
        $this->origineEliquide = $origineEliquide;

        return $this;
    }

    public function getTech(): ?bool
    {
        return $this->tech;
    }

    public function setTech(?bool $tech): self
    {
        $this->tech = $tech;

        return $this;
    }

    public function getSpring(): ?bool
    {
        return $this->spring;
    }

    public function setSpring(?bool $spring): self
    {
        $this->spring = $spring;

        return $this;
    }

    public function getSummer(): ?bool
    {
        return $this->summer;
    }

    public function setSummer(?bool $summer): self
    {
        $this->summer = $summer;

        return $this;
    }

    public function getOrdreCeres(): ?OrdreCeres
    {
        return $this->ordreCeres;
    }

    public function setOrdreCeres(?OrdreCeres $ordreCeres): self
    {
        // unset the owning side of the relation if necessary
        if ($ordreCeres === null && $this->ordreCeres !== null) {
            $this->ordreCeres->setProduit(null);
        }

        // set the owning side of the relation if necessary
        if ($ordreCeres !== null && $ordreCeres->getProduit() !== $this) {
            $ordreCeres->setProduit($this);
        }

        $this->ordreCeres = $ordreCeres;

        return $this;
    }

    public function getOrdreExtravape(): ?OrdreExtravape
    {
        return $this->ordreExtravape;
    }

    public function setOrdreExtravape(?OrdreExtravape $ordreExtravape): self
    {
        // unset the owning side of the relation if necessary
        if ($ordreExtravape === null && $this->ordreExtravape !== null) {
            $this->ordreExtravape->setProduit(null);
        }

        // set the owning side of the relation if necessary
        if ($ordreExtravape !== null && $ordreExtravape->getProduit() !== $this) {
            $ordreExtravape->setProduit($this);
        }

        $this->ordreExtravape = $ordreExtravape;

        return $this;
    }

    public function __toString()
    {
        return $this->getNom();   
    }

    public function getOffert(): ?bool
    {
        return $this->offert;
    }

    public function setOffert(?bool $offert): self
    {
        $this->offert = $offert;

        return $this;
    }

}
