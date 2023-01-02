<?php

namespace App\Form;

use App\Entity\Base;
use App\Entity\Arome;
use App\Entity\Gamme;
use App\Entity\Tarif;
use App\Entity\Marque;
use App\Entity\Produit;
use App\Entity\Categorie;
use App\Entity\Contenant;
use App\Entity\Reference;
use App\Entity\Fournisseur;
use App\Entity\PrincipeActif;
use App\Form\ProduitAromeType;
use App\Form\MarqueBlancheType;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Vich\UploaderBundle\Form\Type\VichFileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\DBAL\Types\DecimalType as TypesDecimalType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\DecimalType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class ProduitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('gamme', EntityType::class, [
                'label' => false,
                'class' => Gamme::class,
                'placeholder' => '',
                'required' => false
            ])
            ->add('contenant', EntityType::class, [
                'class' => Contenant::class,
                'label' => false,
                'placeholder' => '',
                'required' => false
            ])
            ->add('reference', TextType::class, [
                'label' => false,
                'required' => false
            ])
            ->add('categorie', EntityType::class, [
                'label' => false,
                'class' => Categorie::class,
                'placeholder' => '',
                'required' => false

            ])
            ->add('imageFile', VichFileType::class, [
                'label' => false,
                'required' => false,
                'allow_delete' => true,
                'delete_label' => 'Cliquez ici pour supprimer ou remplacer l\'image',
                //'download_uri' => 'Telecharger',
                //'download_label' => 'Telecharger',
                'asset_helper' => true,
            ])
            ->add('base', EntityType::class, [
                'label' => false,
                'class' => Base::class,
                'placeholder' => '',
                'required' => false,

            ])
            ->add('principeActif', EntityType::class, [
                'class' => PrincipeActif::class,
                'attr' => ['class' => 'form-check-inline'],
                'choice_attr' => function( $key) {
                    $key = str_replace('é', 'e', $key);
                    $key = str_replace('è', 'e', $key);
                    $key = str_replace('ê', 'e', $key);
                    $key = str_replace('ç', 'c', $key);
                    $key = str_replace('à', 'a', $key);
                    $key = str_replace(" ", '', $key);
                    $key = str_replace("/", '_', $key);
                    $key = str_replace("\\", '_', $key);
                    $key = str_replace("%", '_', $key);
                    $key = str_replace(",", '_', $key);
                    return ['data-val' => $key];
                },
                'expanded' => true,
                'required' => false,
                'placeholder' => 'Sans',
                'label' => false
            ])
            ->add('produitAromes', CollectionType::class, [
                'entry_type'   => ProduitAromeType::class,
                'allow_add' => true,
                'prototype' => true,
                'allow_delete' => true,
                'label' => false,
                'by_reference' => false
            ])
            ->add(
                'Type',
                ChoiceType::class,
                [
                    'label' => false,
                    'choices' => [
                        '' => '',
                        'Concentré' => 'Concentré',
                        'Chubby' => 'Chubby'
                    ],
                    'expanded' => false,
                    'required' => false
                ]
            )
          
        ;

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Produit::class,
            'validation_groups' => ['Default']
        ]);
    }
}
