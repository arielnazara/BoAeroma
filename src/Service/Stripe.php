<?php


namespace App\Service;

use App\Entity\Client;
use App\Entity\Devis;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Charge;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;


class Stripe {

    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /*public function createCustomer(User $user, $paymentToken)
    {
        $customer = \Stripe\Customer::create([
            'email' => $user->getEmail(),
            'source' => $paymentToken,
        ]);
        $user->setStripeCustomerId($customer->id);
        $this->em->persist($user);
        $this->em->flush($user);
        return $customer;
    }*/

    public function proceed(Devis $devis, $stripeToken, $secretKey) {
        \Stripe\Stripe::setApiKey($secretKey);

        $client = $devis->getClient();
    
        $customer = Customer::create([
            'email' => $client->getEmail(),
            'name' => ($client->getDenomination()) ? $client->getDenomination() : $client->getName() . ' ' . $client->getFirstName(),
            'phone' => $client->getTel(),
            'description' => ($client->getTypeDeClient()),
            'source' => $stripeToken
        ]);
        $devis->setStripeCustomerId($customer->id);

        $this->em->persist($client);
        $this->em->flush();

        Charge::create([
            'amount' => $devis->getTotalTtc() * 100,
            'currency' => 'eur',
            'description' => $client->getTypeDeClient(),
            'customer' => $devis->getStripeCustomerId()
        ]);

    }


    public function updateCustomerCard(Devis $devis, $paymentToken, $secretKey)
    {
       // $client = $devis->getClient();
        \Stripe\Stripe::setApiKey($secretKey);

        $customer = \Stripe\Customer::retrieve($devis->getStripeCustomerId());
        $customer->source = $paymentToken;
        $customer->save();
    }

 /*   public function createInvoiceItem($amount, User $user, $description, $secretKey)
    {
        \Stripe\Stripe::setApiKey($secretKey);
        return \Stripe\InvoiceItem::create(array(
            "amount" => $amount,
            "currency" => "usd",
            "customer" => $user->getStripeCustomerId(),
            "description" => $description
        ));
    }
/*
    public function proceedPaiementInvoice(Facture $facture, $stripeToken, $secretKey) {
        \Stripe\Stripe::setApiKey($secretKey);
        try {

            $charges = Charge::create([
                'amount' => $facture->getPrixTotal() * 100,
                'currency' => 'eur',
                'description' => 'Paiement facture F'.$facture->getNumero().' par ' . $facture->getCentre()->getRaisonSocial(),
                'source' => $stripeToken
            ]);

        } catch (ApiErrorException $e) {
            dump($e->getMessage());
        }

        return ($charges) ? $charges->paid : 'error';
    }
/*
    public function proceed(Devis $contrat, $token_post, $stripe_sk) {

        $prixT = $contrat->getTotalTtc() * 100;
        $clientName = $contrat->getClient()->getFirstName() . ' - ' . $contrat->getClient()->getName();

        \Stripe\Stripe::setApiKey($stripe_sk);
        $token = $token_post;

        try {
            $customer = \Stripe\Customer::create([
                "email" => $contrat->getClient()->getEmail(),
                "name" => $clientName,
                "phone" => $contrat->getClient()->getTel(),
                "description" => "Client Site Winklecard.com",
                'source' => $token
            ]);

            $charges = \Stripe\Charge::create([
                'amount' => $prixT,
                'currency' => 'eur',
                'description' => $contrat->getClient()->getDenomination(),
                'customer' => $customer->id,
                //"metadata" => ["No contrat" => $contrat->getNumero()]
            ]);

        } catch (\Stripe\Error\Card $e) {
            echo 'error';
        }

        if ($charges) {
            return [
                'customerId' => $customer->id,
                'paid' => $charges->paid
            ];
        } else {
            return 'error';
        }
    }*/
}