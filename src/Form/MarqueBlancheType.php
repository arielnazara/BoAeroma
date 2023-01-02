<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\GammeMarqueBlanche;
use App\Entity\MarqueBlanche;
use App\Entity\Tarif;
use App\Repository\ClientRepository;
use App\Repository\TarifRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MarqueBlancheType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('reference')
            ->add('marque')
            ->add('position')
            ->add('client', EntityType::class, [
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
            ->add('tarif', EntityType::class, [
                'class' => Tarif::class,
                'query_builder' => function(TarifRepository $tarif)
                {
                    return $tarif->createQueryBuilder('t')
                        ->where('t.client IS NOT null');
                },
                'placeholder' => 'Veuillez choisir un tarif',
            ])
            ->add('gammeMarqueBlanche', EntityType::class, [
                'class' => GammeMarqueBlanche::class,
                'placeholder' => 'Veuillez choisir une gamme',
                'label' => 'Gamme',
                'required' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MarqueBlanche::class,
            
        ]);
    }
}
