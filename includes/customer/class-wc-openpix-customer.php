<?php

use JetBrains\PhpStorm\NoReturn;

define('__ROOT__', dirname(dirname(__FILE__)));

class WC_OpenPix_Customer
{
    public function getCustomerAddress($order)
    {
        $order_billing_address_1 = $order->get_billing_address_1();
        $order_billing_address_2 = $order->get_billing_address_2();
        $order_billing_city = $order->get_billing_city();
        $order_billing_state = $order->get_billing_state();
        $order_billing_postcode = $order->get_billing_postcode();
        $order_billing_country = $order->get_billing_country();
        $order_billing_neighborhood = $order->get_meta('_billing_neighborhood');
        if (empty($order_billing_neighborhood)) {
            $order_billing_neighborhood = $order->get_billing_address_2();
        }

        if (empty($order_billing_neighborhood)) {
            $order_billing_neighborhood = 'Bairro';
        }

        $order_billing_number = $order->get_meta('_billing_number');
        if (empty($order_billing_number)) {
            $order_billing_number = 'S/N';
        }

        $address = [
            'zipcode' => $order_billing_postcode,
            'street' => $order_billing_address_1,
            'number' => $order_billing_number,
            'neighborhood' => $order_billing_neighborhood,
            'city' => $order_billing_city,
            'state' => $order_billing_state,
            'complement' => $order_billing_address_2,
            'country' => $order_billing_country,
        ];

        return $address;
    }
}
