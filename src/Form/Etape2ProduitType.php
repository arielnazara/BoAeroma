<?php

namespace App\Form;

use App\Entity\Tarif;
use App\Entity\Marque;
use App\Entity\Produit;
use App\Entity\Fournisseur;
use Doctrine\ORM\EntityRepository;
use App\Repository\TarifRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class Etape2ProduitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('nom', TextType::class, [
            'label' => false,
            'required' => false
        ])
        ->add('marque', EntityType::class, [
            'label' => false,
            'class' => Marque::class,
            'placeholder' => '',
            'required' => false
        ])
        ->add(
            'faitParAeroma',
            ChoiceType::class,
            [
                'label' => false,
                'choices' => [
                    'Oui' => true,
                    'Non' => false
                ],
                'choice_attr' => [
                    'Oui' => ['class' => 'form-check-input'],
                    'Non' => ['class' => 'form-check-input']
                ],
                'expanded' => true,
                'required' => false,
                'placeholder' => false,
            ]
        )

        ->add(
            'marqueBlanche',
            ChoiceType::class,
            [
                'label' => false,
                'choices' => [
                    'Oui' => true,
                    'Non' => false
                ],
                'choice_attr' => [
                    'Oui' => ['class' => 'form-check-input'],
                    'Non' => ['class' => 'form-check-input']
                ],
                'expanded' => true,
                'required' => false,
                'placeholder' => false,
            ]
        )

        
        ->add('quantite', IntegerType::class, [
            'label' => false,
            'required' => false
        ])
        ->add('fournisseur', EntityType::class, [
            'label' => false,
            'class' => Fournisseur::class,
            'placeholder' => '',
            'required' => false,
        ])
      
        ->add('tarif', EntityType::class, [
            'label' => false,
            'class' => Tarif::class,
            'placeholder' => '',
            'required' => false,
            'query_builder' => function(TarifRepository $tarif)
            {
                return $tarif->createQueryBuilder('t')
                    ->where('t.client IS null');
            },

        ])
      
        ->add('poids', NumberType::class, [
            'label' => false,
            'required' => false,
            'scale' => 3,
            'attr' => [
                'min' => 0,
                'step' => '.001',
            ],
        ])

        ->add('marqueBlanches', CollectionType::class, [
                'entry_type'   => MarqueBlancheType::class,
                'allow_add' => true,
                'prototype' => true,
                'allow_delete' => true,
                'label' => false,
                'by_reference' => false,
                'error_bubbling' => false
            ])
        ;

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Produit::class,
            'validation_groups' => ['Default', 'marqueBlanches'],
        ]);
    }
}
