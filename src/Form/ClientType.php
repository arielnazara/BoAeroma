<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\TypeDeClient;
use PhpParser\Node\Stmt\Label;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('raisonSocial', TextType::class, [
                'label' => false
            ])
            ->add('lastName', TextType::class, [
                'label' => false
            ])
            ->add('firstName', TextType::class, [
                'label' => false
            ])
            ->add('email', EmailType::class, [
                'label' => false
            ])
            ->add('Siren', TextType::class, [
                'label' => false,
                'required' => false

            ])
            ->add('adresseFacturation', TextType::class, [
                'label' => false
            ])
            ->add('nomDestinataire', TextType::class, [
                'label' => false
            ])
            ->add('codePostalFacturation', TextType::class, [
                'label' => false
            ])
            ->add('villeFacturation', TextType::class, [
                'label' => false
            ])
            ->add('noTva', TextType::class, [
                'label' => false
            ])
            ->add('principaleActivite', TextType::class, [
                'label' => false
            ])
            ->add('formeJuridique', TextType::class, [
                'label' => false,
                'required' => false

            ])
            ->add('capital', TextType::class, [
                'label' => false,
                'required' => false

            ])
            ->add('codeNaf', TextType::class, [
                'label' => false,
                'required' => false

            ])
            ->add('adresseServiceAchats', TextType::class, [
                'label' => false,
                'required' => false
            ])
            ->add('codePostalServiceAchat', TextType::class, [
                'label' => false,
                'required' => false
            ])
            ->add('villeServiceAchat', TextType::class, [
                'label' => false,
                'required' => false
            ])
            ->add('horaireLivraison', ChoiceType::class, [
                'label' => false,
                'choices' => [
                    '' => '',
                    '6h à 8h' => '6h a 8h',
                    '8h à 10h' => '8h a 10h',
                    '10h à 12h' => '10h a 12h',
                    '12h à 14h' => '12h a 14h',
                    '14h à 16h' => '14h a 16h',
                    '16h à 18h' => '16h a 18h',
                    '18h à 20h' => '18h a 20h'
                ],
                'expanded' => false,
                'required' => false

            ])

            ->add('remarque', TextareaType::class, ['required' => false, 'label' => false])
            ->add('pays', ChoiceType::class, [
                'label' => false,
                'choices' => [
                    '' => '',
                    'Belgique' => 'Belgique',
                    'France' => 'France',
                ],
                'required' => false
            ])
            ->add('domiciliationBancaire', TextType::class, [
                'label' => false,
                'required' => false
            ])
            ->add('secondEmail', EmailType::class, [
                'required' => false
            ])
            ->add('iban', TextType::class, [
                'label' => false,
                'required' => false
            ])
            ->add('swift', TextType::class, [
                'label' => false,
                'required' => false
            ])
            ->add('regimeTva', TextType::class, [
                'label' => false,
                'required' => false
            ])
            ->add('telephone', TextType::class, [
                'label' => false
            ])

            ->add('jourLivraison', ChoiceType::class, [
                'label' => false,
                'choices' => [
                    '' => '',
                    'Lundi' => 'Lundi',
                    'Mardi' => 'Mardi',
                    'Mercredi' => 'Mercredi',
                    'Jeudi' => 'Jeudi',
                    'Vendredi' => 'Vendredi'
                ],
                'required' => false,
                'expanded' => false,
            ])
            ->add('typeDeClient', EntityType::class, [
                'class' => TypeDeClient::class,
                'label' => false,
                'placeholder' => ''
            ])
            ->add('communeFacturation', TextType::class, [
                'label' => false,
                'required' => false

            ])
            ->add('communeServiceAchat', TextType::class, [
                'label' => false,
                'required' => false
            ]);
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Client::class,
            'csrf_protection' => false
        ]);
    }
}
