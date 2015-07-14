<?php
namespace Bread\PayPal;

use Bread\REST;

class Payer extends REST\Model
{

    const PAYMENT_PAYPAL = 'paypal';
    const PAYMENT_CREDIT_CARD = 'credit_card';

    const STATUS_VERIFIED = 'VERIFIED';
    const STATUS_UNVERIFIED = 'UNVERIFIED';

    protected $payerId; //PayPal assigned Payer ID.

    protected $salutation; //Salutation of the payer.

    protected $firstName; //First name of the payer.

    protected $middleName; //Middle name of the payer.

    protected $lastName; //Last name of the payer.

    protected $street; //Line 1 of the address (e.g., Number, street, etc). 100 characters max. Required.

    protected $city; //City name. 50 characters max. Required.

    protected $country; //2-letter country code. 2 characters max. Required.

    protected $state; //2-letter code for US states, and the equivalent for other countries. 100 characters max.

    protected $mail; //Email address representing the payer. 127 characters max.

    protected $phone; //Phone number representing the payer. 20 characters max.

    protected $paymentMethod; //Payment method used. Must be either credit_card or paypal. Required.

    protected $status; //Status of the payer’s PayPal account. Only supported when the payment_method is set to paypal. Allowed values: VERIFIED or UNVERIFIED.

    protected $creditCard; //A resource representing a credit card that can be used to fund a payment.

}

Configuration::defaults('Bread\PayPal\Payer', array(
    'creditCard' => array(
        'type' => 'Bread\PayPal\CreditCard'
    )
));
