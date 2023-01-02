<?php


namespace App\Service;


class CodeNumber
{

    function Genere_number($size)
    {
        $characters = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9,);
        $number = "";
        for ($i = 0; $i < $size; $i++) {
            $number .=  $characters[array_rand($characters)];
        }

        return $number;
    }

    function generatePassword()
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890@_~?!%$+-/';
        $pass = array();
        $alphaLength = strlen($alphabet) - 1;
        for ($i = 0; $i < 10; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass);
    }

    public function getNumber($nbCaractere)
    {

        for ($i = 0; $i <= $nbCaractere; $i++) {
            $number = rand(97, 122);
        }

        return $number . random_int(1, 99);
    }
}
