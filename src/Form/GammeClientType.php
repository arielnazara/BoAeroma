<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\GammeClient;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GammeClientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('client', EntityType::class, [
                'class' => Client::class,
                'required' => true,
                'placeholder' => ''
            ])
            ->add('toutLesProduits', ChoiceType::class,
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
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => GammeClient::class,
        ]);
    }
}
