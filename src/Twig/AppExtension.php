<?php

namespace App\Twig;

use App\Entity\Produit;
use App\Service\DevisServices;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigTest;

class AppExtension extends AbstractExtension
{
    private $devisServices;

    public function __construct(DevisServices $devisServices)
    {
        $this->devisServices = $devisServices;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('customInvoiceNumber', [$this, 'customInvoiceNumber']),
            new TwigFilter('deleteSpecialChar', [$this, 'deleteSpecialChar']),
        ];
    }

    public function getTests()
    {
        return [
            new TwigTest('isInstanceOfProduct', [$this, 'isInstanceOfProduct'])
        ];
    }


    public function customInvoiceNumber($number)
    {
        $tab = explode("-", $number);

        array_pop($tab);
                
        $factNumber = implode('-', $tab);

        return $factNumber;
    }

    public function deleteSpecialChar($value)
    {
        return $this->devisServices->deleteSpecialChar($value);
    }

    public function isInstanceOfProduct($var) {
        return  $var instanceof Produit;
    }
}