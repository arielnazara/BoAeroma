<?php

namespace App\Form;

use App\Entity\FactureAvoir;
use App\Entity\FactureMaitre;
use App\Entity\FactureCommande;
use Symfony\Component\Form\AbstractType;
use App\Repository\FactureMaitreRepository;
use App\Repository\FactureCommandeRepository;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class FactureAvoirType extends AbstractType
{

    private $repository;
    private $factureMaitreRepository;

    public function __construct(FactureCommandeRepository $repository, FactureMaitreRepository $factureMaitreRepository)
    {
        $this->repository = $repository;
        $this->factureMaitreRepository = $factureMaitreRepository;
    }

    public function getAllIvoices()
    {
        $invoicesOrder = $this->repository->findAll();
        $invoicesMaster = $this->factureMaitreRepository->findAll();

        $invoices = array_merge($invoicesOrder, $invoicesMaster);

        
        for($i=0; $i<count($invoices)-1; $i++) {
            for($j=0; $j<(count($invoices)-1-$i); $j++) {

                $nombre1  = $invoices[$j];
                $nombre2  = $invoices[$j+1];

                 
                $nombre1 = explode("-", $nombre1->getNumero());
                $nombre1 = end($nombre1);

                $nombre2 = explode("-", $nombre2->getNumero());
                $nombre2 = end($nombre2);

                if ( intval($nombre1) < intval($nombre2) ) {
                    $temp = $invoices[$j+1];
                    $invoices[$j+1] = $invoices[$j];
                    $invoices[$j] = $temp;
                }
                
            }
        }

        $iFinal = [];

        foreach($invoices as $invoice)
        {
            $iFinal[$invoice->getNumero()] = $invoice;
        }

        return $iFinal;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('Description')
            ->add('totalHT', MoneyType::class, [
                'label' => 'Montant HT',
                'attr' => [
                    'class' => 'customize',
                    'min' => 0,
                    'step' => 0.01
                ]
            ])
            ->add('tva', MoneyType::class, [
                'label' => 'TVA',
                'attr' => [
                    'class' => 'customize',
                    'min' => 0,
                    'step' => 0.01
                ]
            ])
            ->add('totalTtc', MoneyType::class, [
                'label' => 'Montant TTC',
                'attr' => [
                    'class' => 'customize',
                    'min' => 0,
                    'step' => 0.01
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FactureAvoir::class,
        ]);
    }
}
