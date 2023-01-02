<?php


namespace App\Service;


class CodeGenerate {

    public function code($lenght) {
        $charList = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $chain = "";
        $max = mb_strlen($charList, '8bit') - 1;
        for ($i = 0; $i < $lenght; ++$i) {
            $chain .= $charList[random_int(0, $max)];
        }
        return $chain;
    }
}