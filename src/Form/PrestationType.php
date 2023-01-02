<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\Prestation;
use App\Entity\TypePrestation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class PrestationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('client',EntityType::class, [
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
            ->add('typePrestations', CollectionType::class, [
                'entry_type'   => TypePrestationType::class,
                'allow_add' => true,
                'prototype' => true,
                'allow_delete' => true,
                'label' => 'Type de prestation',
                'by_reference' => false,
                'error_bubbling' => false
            ])
            ->add('montant', MoneyType::class, [
                'attr' => [
                    'min' => 0,
                    'step' => '.01',
                ],
            ])
            ->add('tva', MoneyType::class, [
                  'attr' => [
                    'min' => 0,
                    'step' => '.01',
                ],
            ])
            ->add('totalTtc', MoneyType::class, [
                  'attr' => [
                    'min' => 0,
                    'step' => '.01',
                ],
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Prestation::class,
        ]);
    }
}
