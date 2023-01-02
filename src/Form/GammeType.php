<?php

namespace App\Form;

use App\Entity\Gamme;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GammeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom')
            ->add('description')
            ->add('parDefaut', ChoiceType::class, 
                ['label' => false,
                'choices' => [
                        'Oui' => true,
                        'Non' => false
                ],
                'choice_attr' => [
                    'Oui' =>['class' => 'form-check-input'],
                    'Non' => ['class' => 'form-check-input']
                ],
                'expanded'=>true,
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Gamme::class,
        ]);
    }
}
