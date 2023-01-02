<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\Fournisseur;
use App\Entity\Marque;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MarqueType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom')
            ->add('description')
            ->add('proprietaire', ChoiceType::class,
                ['label' => false,
                'choices' => [
                        'Aeroma' => 'Aeroma',
                        'Client' => 'Client',
                        'Fournisseur' => 'Fournisseur'
                ],
                'choice_attr' => [
                    'Aeroma' =>['class' => 'form-check-input'],
                    'Client'=> ['class' => 'form-check-input'],
                    'Fournisseur' => ['class' => 'form-check-input'],
                ],
                'expanded'=>true,
            ])
            ->add('exclusivite', ChoiceType::class,
                ['label' => false,
                'choices' => [
                        "Pas d'exclusivité" => false,
                        'Client' => true,
                        
                ],
                'choice_attr' => [
                    "Pas d'exclusivité"  =>['class' => 'form-check-input'],
                    'Client'=> ['class' => 'form-check-input'],
                    
                ],
                'expanded'=>true,
            ])
            ->add('client', EntityType::class, [
                'class' => Client::class,
                'required' => false
            ])

            ->add('fournisseur', EntityType::class, [
                'class' => Fournisseur::class,
                'required' => false
            ])

            ->add('avecGamme', ChoiceType::class,
                ['label' => false,
                'choices' => [
                        "Oui" => true,
                        'Non' => false,
                        
                ],
                'choice_attr' => [
                    "Oui"  =>['class' => 'form-check-input'],
                    'Non'=> ['class' => 'form-check-input'],
                    
                ],
                'expanded'=>true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Marque::class,
        ]);
    }
}
