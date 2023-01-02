<?php

namespace App\Form;

use App\Entity\Base;
use App\Entity\Tarif;
use App\Entity\Client;
use App\Entity\Marque;
use App\Entity\Categorie;
use App\Entity\Contenant;
use App\Entity\TypeDeClient;
use App\Entity\PrincipeActif;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class TarifType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom', TextType::class, [
                'required' => false,
                'label' => false
            ])
            ->add('prixDeReferenceDetaillant', MoneyType::class, [
                'required' => false,
                
                'attr' => [
                    'min' => 0,
                    'step' => '.01',
                ],
            ])
            ->add('prixDeReferenceGrossiste', MoneyType::class, [
                'required' => false,
                
                'attr' => [
                    'min' => 0,
                    'step' => '.01',
                ],
            ])
            ->add('categorie', EntityType::class, [
                'class' => Categorie::class,
                'label' => false,
                'required' => false
            ])
            ->add('contenance', EntityType::class, [
                'class' => Contenant::class,
                'label' => false,
                'required' => false
            ])
            ->add('base', EntityType::class, [
                'class' => Base::class,
                'label' => false,
                'required' => false
            ])
            ->add(
                'memeTarif',
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
                    'placeholder' => false
                ]
            )
            ->add('declineAvec', EntityType::class, [
                'class' => PrincipeActif::class,
                'label' => false,
                'required' => false,
                'placeholder' => 'Sans déclinaison'
            ])
            ->add('client', EntityType::class, [
                'class' => Client::class,
                'label' => false,
                'required' => false,
                'choice_label' => function($client)
                {
                    return ($client->getRaisonSocial()) ? $client->getRaisonSocial() : $client->getFirstName() .' '. $client->getLastName();
                }
            ])
            ->add('marque', EntityType::class, [
                'label' => false,
                'class' => Marque::class,
                'label' => false,
                'required' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Tarif::class,
        ]);
    }
}
