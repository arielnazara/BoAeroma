<?php

namespace App\Form;

use App\Entity\TypePrestation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;

class TypePrestationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Description',
                'attr' => [
                    'placeholder' => 'Description',
                ]
            ])
            ->add('quantite', TextType::class, [
                'label' => 'Quantite',
                'attr' => [
                    'placeholder' => 'Quantite',
                ]
            ])
            ->add('prixUnitaire', MoneyType::class, [
                'label' => 'Prix Unitaire',
                'attr' => [
                    'min' => 0,
                    'step' => '.01',
                ],
            ])
            ->add('prix', MoneyType::class, [
                'label' => 'Total',
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
            'data_class' => TypePrestation::class,
        ]);
    }
}
