<?php
namespace Bread\PayPal;

use Bread\REST;

class Item extends REST\Model
{
    const CURRENCY_AUSTRALIAN_DOLLAR = 'AUD';
    const CURRENCY_BRAZILIAN_REAL = 'BRL';
    const CURRENCY_CANADIAN_DOLLAR = 'CAD';
    const CURRENCY_CZECH_KORUNA = 'CZK';
    const CURRENCY_DANISH_KRONE = 'DKK';
    const CURRENCY_EURO = 'EUR';
    const CURRENCY_HONGKONG_DOLLAR = 'HKD';
    const CURRENCY_HUNGARIAN_FORINT = 'HUF';
    const CURRENCY_ISRAELI_NEW_SHEKEL = 'ILS';
    const CURRENCY_JAPANESE_YEN = 'JPY';
    const CURRENCY_MALAYSIAN_RINGGIT = 'MYR';
    const CURRENCY_MEXICAN_PESO = 'MXN';
    const CURRENCY_NEW_TAIWAN_DOLLAR = 'TWD';
    const CURRENCY_NEW_ZEALAND_DOLLAR = 'NZD';
    const CURRENCY_NORWEGIAN_KRONE = 'NOK';
    const CURRENCY_PHILIPPINE_PESO = 'PHP';
    const CURRENCY_POLISH_ZLOTY = 'PLN';
    const CURRENCY_POUND_STERLING = 'GBP';
    const CURRENCY_SINGAPORE_DOLLAR = 'SGD';
    const CURRENCY_SWEDISH_KRONA = 'SEK';
    const CURRENCY_SWISS_FRANC = 'CHF';
    const CURRENCY_THAI_BAHT = 'THB';
    const CURRENCY_TURKISH_LIRA = 'TRY';
    const CURRENCY_UNITED_STATES_DOLLAR = 'USD';

    protected $quantity; //Number of a particular item. 10 characters max. Required

    protected $name; //Item name. 127 characters max. Required

    protected $description; //Description of the item. Only supported when the payment_method is set to paypal. 127 characters max.

    protected $price; //Item cost. 10 characters max. Required

    protected $tax; //Tax of the item. Only supported when the payment_method is set to paypal.

    protected $currency; //3-letter currency code. Required

    protected $sku; //Stock keeping unit corresponding (SKU) to item. 50 characters max.

}