<?php

namespace App\Form;

use App\Entity\Gamme;
use App\Entity\Client;
use App\Entity\CatalogueClient;
use App\Entity\GammeMarqueBlanche;
use App\Entity\MarqueBlanche;
use App\Entity\PositionGammeClient;
use App\Form\MarqueBlancheInverseType;
use App\Repository\MarqueBlancheRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class CatalogueClientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('gammePrincipale', EntityType::class, [
                'class' => Gamme::class,
                'placeholder' => '',
                'label' => false
            ])
            ->add('customGamme', EntityType::class, [
                'class' => GammeMarqueBlanche::class,
                'placeholder' => '',
                'label' => false
            ])
            ->add('gammeParDefaut', ChoiceType::class, [
                'choices'  => [
                    'Oui' => true,
                    'Non' => false,
                ],
                'multiple' => false,
                'expanded' => true,
                'label' => false,
                'attr' => [
                    'class' => 'form-check form-check-inline'
                ]
            ])
            ->add('client', EntityType::class,  [
                'class' => Client::class,
                'choice_label' => function ($client) {
                    
                    if($client->getRaisonSocial())
                    {
                        return $client->getRaisonSocial();
                    }
                    
                    return $client->getFullName();
                },
                'placeholder' => 'Veuillez choisir un client',
            ])
            ->add('position')
            ->add('marqueBlanches', CollectionType::class, [
                'entry_type' => MarqueBlancheInverseType::class,
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
            'data_class' => PositionGammeClient::class,
            'validation_groups' => ['Default', 'marqueBlanches_gammeClient'],
        ]);
    }
}
