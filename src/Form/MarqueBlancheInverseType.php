<?php

namespace App\Form;

use App\Entity\Tarif;
use App\Entity\MarqueBlanche;
use App\Entity\Produit;
use App\Repository\ProduitRepository;
use App\Repository\TarifRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MarqueBlancheInverseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('reference')
           
            ->add('marque')
            ->add('position')
            ->add('greendot')
            
            ->add('produit', EntityType::class, [
                'class' => Produit::class,
                'choice_label' => function ($produit) {
                    return $produit->getReference()." - ".$produit->getNom();
                },
                'placeholder' => 'Veuillez choisir un produit'
            ])
            ->add('tarif', EntityType::class, [
                'class' => Tarif::class,
                'query_builder' => function(TarifRepository $tarif)
                {
                    return $tarif->createQueryBuilder('t')
                        ->where('t.client IS NOT null');
                },
                'placeholder' => 'Veuillez choisir un tarif'
            ])
            
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MarqueBlanche::class,
        ]);
    }
}
