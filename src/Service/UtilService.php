<?php

namespace App\Service;

class UtilService
{
    public function getClosestTo($val, $arr) 
    {
        $nearest = $arr[0];
        
        foreach ($arr as $elt) {
            $diff = abs($elt - $val);
            
            if ($diff < abs($nearest - $val)) {
                $nearest = $elt;
            }
        }

        return $nearest;
    }
}