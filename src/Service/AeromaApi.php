<?php


namespace App\Service;


class AeromaApi
{

    public function getOrders($proStore, array $yzy , $greendot)
    {

        if ($proStore) {
            $apiKey = "IIUC5ZMTMI7Q7IZ3CSGA9F9E61JDSPWV";
            $apiSecret = "";
            $api_url = "https://www.aeroma-prostore.fr/api/orders/&output_format=JSON";
            $url = "https://www.aeroma-prostore.fr/api/orders/";
        }

        if(!empty($yzy)){

            if ($yzy[0] == true and $yzy[1] == false ) {
                $apiKey = "PI9JNEN7I8NPBB5RDKA5KFJLVU13ITBQ";
                $apiSecret = "";
                $api_url = "https://yzyvape.store/api/orders/&output_format=JSON";
                $url = "https://yzyvape.store/api/orders/";
            }
    
            if ($yzy[0] == false and $yzy[1] == true ) {
                $apiKey = "PI9JNEN7I8NPBB5RDKA5KFJLVU13ITBQ";
                $apiSecret = "";
                $api_url = "https://yzyvapestore.fr/api/orders/&output_format=JSON";
                $url = "https://yzyvapestore.fr/api/orders/";
            }
            
        }

        if ($greendot) {
            $apiKey = "JW67837MIAVEVVR6YYA9CIQWY4DRHYYW";
            $apiSecret = "";
            $api_url = "https://grossiste.greendot.fr/api/orders/&output_format=JSON";
            $url = "https://grossiste.greendot.fr/api/orders/";
        }

        $headers = array(
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($apiKey . ":" . $apiSecret)
        );
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_URL, $api_url);
        $response = curl_exec($ch);
        curl_close($ch);
        $datas = json_decode($response);
        //$len = count($datas->orders);
        $orders = [];

        foreach ($datas as $dt) {
            foreach ($dt as $ord) {
                $api_url = $url . (int)$ord->id . "/&output_format=JSON";
                $headers = array(
                    'Content-Type: application/json',
                    'Authorization: Basic ' . base64_encode($apiKey . ":" . $apiSecret)
                );
                $ch = curl_init($api_url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_URL, $api_url);
                $response = curl_exec($ch);
                curl_close($ch);
                $orders[] = json_decode($response);
            }
        }

        return $orders;
    }

    public function fetchOrderHistory($proStore, array $yzy , $greendot)
    {
        if ($proStore) {
            $apiKey = "IIUC5ZMTMI7Q7IZ3CSGA9F9E61JDSPWV";
            $apiSecret = "";
            $api_url = "https://www.aeroma-prostore.fr/api/order_histories/&output_format=JSON";
            $url = "https://www.aeroma-prostore.fr/api/order_histories/";
        }

        if(!empty($yzy)){

            if ($yzy[0] == true and $yzy[1] == false ) {
                $apiKey = "PI9JNEN7I8NPBB5RDKA5KFJLVU13ITBQ";
                $apiSecret = "";
                $api_url = "https://yzyvape.store/api/order_histories/&output_format=JSON";
                $url = "https://yzyvape.store/api/order_histories/";
            }
    
            if ($yzy[0] == false and $yzy[1] == true ) {
                $apiKey = "PI9JNEN7I8NPBB5RDKA5KFJLVU13ITBQ";
                $apiSecret = "";
                $api_url = "https://yzyvapestore.fr/api/order_histories/&output_format=JSON";
                $url = "https://yzyvapestore.fr/api/order_histories/";
            }
            
        }

        if ($greendot) {
            $apiKey = "JW67837MIAVEVVR6YYA9CIQWY4DRHYYW";
            $apiSecret = "";
            $api_url = "https://grossiste.greendot.fr/api/order_histories/&output_format=JSON";
            $url = "https://grossiste.greendot.fr/api/order_histories/";
        }

        $headers = array(
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($apiKey . ":" . $apiSecret)
        );
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_URL, $api_url);
        $response = curl_exec($ch);
        curl_close($ch);

        $datas = json_decode($response);

        $orderHistories = [];

        foreach ($datas as $dt) {
            foreach ($dt as $ord) {
                $api_url = $url . (int)$ord->id . "/&output_format=JSON";
                $headers = array(
                    'Content-Type: application/json',
                    'Authorization: Basic ' . base64_encode($apiKey . ":" . $apiSecret)
                );
                $ch = curl_init($api_url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_URL, $api_url);
                $response = curl_exec($ch);
                curl_close($ch);

                $response = json_decode($response);

                if($response)
                {

                    $key = $response->order_history->id_order;
    
                   
                    $orderHistories[$key][]= $response->order_history->id_order_state;
                }
               
               
            }
        }

        return $orderHistories;
    }

    public function findOrder($id, $proStore, array $yzy, $greendot)
    {
        if ($proStore) {
            $apiKey = "IIUC5ZMTMI7Q7IZ3CSGA9F9E61JDSPWV";
            $apiSecret = "";
            $api_url = "https://www.aeroma-prostore.fr/api/orders/" . $id . "/&output_format=JSON";
            $url = "https://www.aeroma-prostore.fr/api/orders/";
        }

        if(!empty($yzy)){

            if ($yzy[0] == true and $yzy[1] == false ) {
                $apiKey = "PI9JNEN7I8NPBB5RDKA5KFJLVU13ITBQ";
                $apiSecret = "";
                $api_url = "https://yzyvape.store/api/orders/" . $id . "/&output_format=JSON";
            }

            if ($yzy[0] == false and $yzy[1] == true ) {
                $apiKey = "PI9JNEN7I8NPBB5RDKA5KFJLVU13ITBQ";
                $apiSecret = "";
                $api_url = "https://yzyvapestore.fr/api/orders/" . $id . "/&output_format=JSON";
            }
        }

        if ($greendot) {
            $apiKey = "JW67837MIAVEVVR6YYA9CIQWY4DRHYYW";
            $apiSecret = "";
            $api_url = "https://grossiste.greendot.fr/api/orders/" . $id . "/&output_format=JSON";
            $url = "https://grossiste.greendot.fr/api/orders/";
        }


        $headers = array(
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($apiKey . ":" . $apiSecret)
        );

        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_URL, $api_url);
        $response = curl_exec($ch);
        curl_close($ch);
        $order = json_decode($response);

        return $order;
    }

    public function findProduct($id, $proStore, $yzy, $greendot)
    {
        if ($proStore) {
            $apiKey = "IIUC5ZMTMI7Q7IZ3CSGA9F9E61JDSPWV";
            $apiSecret = "";
            $api_url = "https://www.aeroma-prostore.fr/api/products/" . $id . "/&output_format=JSON";
            $url = "https://www.aeroma-prostore.fr/api/orders/";
        }

        if ($yzy) {
            $apiKey = "PI9JNEN7I8NPBB5RDKA5KFJLVU13ITBQ";
            $apiSecret = "";
            $api_url = "https://yzyvapestore.fr/api/products/" . $id . "/&output_format=JSON";

        }

        if ($greendot) {
            $apiKey = "JW67837MIAVEVVR6YYA9CIQWY4DRHYYW";
            $apiSecret = "";
            $api_url = "https://grossiste.greendot.fr/api/products/" . $id . "/&output_format=JSON";
            $url = "https://grossiste.greendot.fr/api/orders/";
        }


        $headers = array(
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($apiKey . ":" . $apiSecret)
        );

       
        $produit = $this->tryGetProduct($api_url, $headers);

        
        if(!$produit)
        {
            $i = 0;

            while($i <= 5 or !$produit)
            {
                $produit = $this->tryGetProduct($api_url, $headers);
                $i++;
            }

        }

        return $produit;
    }

    public function tryGetProduct($api_url, $headers)
    {
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_URL, $api_url);
        $response = curl_exec($ch);
        
        curl_close($ch);
        $produit = json_decode($response);

        return $produit;

    }

    public function findClient($id,array $yzy, $greendot = false)
    {
        if($greendot){
            $apiKey = "JW67837MIAVEVVR6YYA9CIQWY4DRHYYW";
            $apiSecret = "";
            $api_url = "https://grossiste.greendot.fr/api/customers/" . $id . "/&output_format=JSON";
        }
        elseif(!empty($yzy))
        {

            if ($yzy[0] == true and $yzy[1] == false ) {
                $apiKey = "PI9JNEN7I8NPBB5RDKA5KFJLVU13ITBQ";
                $apiSecret = "";
                $api_url = "https://yzyvape.store/api/customers/" . $id . "/&output_format=JSON";
            }

            if ($yzy[0] == false and $yzy[1] == true ) {
                $apiKey = "PI9JNEN7I8NPBB5RDKA5KFJLVU13ITBQ";
                $apiSecret = "";
                $api_url = "https://yzyvapestore.fr/api/customers/" . $id . "/&output_format=JSON";
            }
        }
        else
        {

            $apiKey = "IIUC5ZMTMI7Q7IZ3CSGA9F9E61JDSPWV";
            $apiSecret = "";
            $api_url = "https://www.aeroma-prostore.fr/api/customers/" . $id . "/&output_format=JSON";
        }

        $headers = array(
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($apiKey . ":" . $apiSecret)
        );

        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_URL, $api_url);
        $response = curl_exec($ch);
        curl_close($ch);
        $client = json_decode($response);

        return $client;
    }

    public function getClient($proStore, $yzy, $greendot)
    {
        if ($proStore) {
            $apiKey = "IIUC5ZMTMI7Q7IZ3CSGA9F9E61JDSPWV";
            $apiSecret = "";
            $api_url = "https://www.aeroma-prostore.fr/api/customers/&output_format=JSON";
            $url = "https://www.aeroma-prostore.fr/api/customers/";
        }

        if ($yzy) {
            $apiKey = "PI9JNEN7I8NPBB5RDKA5KFJLVU13ITBQ";
            $apiSecret = "";
            $api_url = "https://yzyvape.store/api/customers/&output_format=JSON";
            $url = "https://yzyvape.store/api/customers/";
        }

        if ($greendot) {
            $apiKey = "JW67837MIAVEVVR6YYA9CIQWY4DRHYYW";
            $apiSecret = "";
            $api_url = "https://grossiste.greendot.fr/api/customers/&output_format=JSON";
            $url = "https://grossiste.greendot.fr/api/customers/";
        }

        $headers = array(
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($apiKey . ":" . $apiSecret)
        );
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_URL, $api_url);
        $response = curl_exec($ch);
        curl_close($ch);
        $datas = json_decode($response);
        $len = count($datas->customers);
        $customers = [];

        foreach ($datas as $dt) {
            foreach ($dt as $ord) {
                $api_url = $url . (int)$ord->id . "/&output_format=JSON";
                $headers = array(
                    'Content-Type: application/json',
                    'Authorization: Basic ' . base64_encode($apiKey . ":" . $apiSecret)
                );
                $ch = curl_init($api_url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_URL, $api_url);
                $response = curl_exec($ch);
                curl_close($ch);
                $customers[] = json_decode($response);
            }
        }

        return $customers;
    }

    public function getProducts($proStore, $yzy, $greendot)
    {

        if ($proStore) {
            $apiKey = "IIUC5ZMTMI7Q7IZ3CSGA9F9E61JDSPWV";
            $apiSecret = "";
            $api_url = "https://www.aeroma-prostore.fr/api/products/&output_format=JSON";
            $url = "https://www.aeroma-prostore.fr/api/products/";
        }

        if ($yzy) {
            $apiKey = "PI9JNEN7I8NPBB5RDKA5KFJLVU13ITBQ";
            $apiSecret = "";
            $api_url = "https://yzyvape.store/api/products/&output_format=JSON";
            $url = "https://yzyvape.store/api/products/";
        }

        if ($greendot) {
            $apiKey = "JW67837MIAVEVVR6YYA9CIQWY4DRHYYW";
            $apiSecret = "";
            $api_url = "https://grossiste.greendot.fr/api/products/&output_format=JSON";
            $url = "https://grossiste.greendot.fr/api/products/";
        }

        $headers = array(
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($apiKey . ":" . $apiSecret)
        );
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_URL, $api_url);
        $response = curl_exec($ch);
        curl_close($ch);
        $datas = json_decode($response);
        $len = count($datas->products);
        $products = [];

        //dd($datas);
        foreach ($datas as $dt) {
            foreach ($dt as $ord) {
                $api_url = $url . (int)$ord->id . "/&output_format=JSON";
                $headers = array(
                    'Content-Type: application/json',
                    'Authorization: Basic ' . base64_encode($apiKey . ":" . $apiSecret)
                );
                $ch = curl_init($api_url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_URL, $api_url);
                $response = curl_exec($ch);
                curl_close($ch);
                $products[] = json_decode($response);
            }
        }

        return $products;
    }
}
