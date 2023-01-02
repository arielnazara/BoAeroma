<?php

namespace App\Form;

use App\Entity\LotIsolat;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class LotIsolatType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('numero', TextType::class)
            ->add('dateReception', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('dateDebutUtilisation', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('active', CheckboxType::class, [
                'label'    => 'Activer',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => LotIsolat::class,
        ]);
    }
}
