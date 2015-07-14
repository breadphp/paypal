<?php
namespace Bread\PayPal;

use Bread\REST;

class CreditCard extends REST\Model
{

    const STATE_OK = 'ok';
    const STATE_EXPIRED = 'expired';

    protected $id; //ID of the credit card being saved for later use. Assigned in response.

    protected $number; //Card number. Required.

    protected $type; //Type of the Card (eg. Visa, Mastercard, etc.). Required.

    protected $expireMonth; //Two digit card expiry month, represented as 01 - 12. Required.

    protected $expireYear; //Four digit card expiry year, represented as YYYY format. Required.

    protected $cvv2; //Card validation code. Only supported when making a Payment, but not when saving a credit card for future use.

    protected $firstName; //Card holder’s first name.

    protected $lastName; //Card holder’s last name.

    protected $state; //State of the funding instrument. Assigned in response. Allowed values: expired, ok

    protected $validUntil; //Date/Time until this resource can be used fund a payment. Assigned in response.

}

Configuration::defaults('Bread\PayPal\CreditCard', array(
    'expireMonth' => array(
        'type' => 'integer'
    ),
    'expireYear' => array(
        'type' => 'integer'
    )
));
