<?php
namespace Bread\PayPal;

use Bread\Configuration\Manager as Configuration;
use Bread\REST;

class Payment extends REST\Model
{

    const INTENT_SALE = 'sale';
    const INTENT_AUTHORIZE = 'authorize';
    const INTENT_ORDER = 'order';

    const STATE_CREATED = 'created';
    const STATE_APPROVED = 'approved';
    const STATE_FAILED = 'failed';
    const STATE_PENDING = 'pending';
    const STATE_CANCELED = 'canceled';
    const STATE_EXPIRED = 'expired';
    const STATE_IN_PROGRESS = 'in_progress';

    protected $id; //ID of the created payment

    protected $create; //Payment creation time as defined in RFC 3339 Section 5.6.

    protected $update; //Time that the resource was last updated.

    protected $description; //Description of transaction. 127 characters max.

    protected $intent; //Payment intent. Must be set to sale for immediate payment, authorize to authorize a payment for capture later, or order to create an order. Required.

    protected $payer; //Source of the funds for this payment represented by a PayPal account or a direct credit card. Required.

    protected $state; //Payment state. Must be one of the following: created; approved; failed; pending; canceled; expired, or in_progress.

    protected $cancelUrl; //Set of redirect URLs you provide only for PayPal-based payments. Returned only when the payment is in created state. Required for PayPal payments.

    protected $returnUrl; //Set of redirect URLs you provide only for PayPal-based payments. Returned only when the payment is in created state. Required for PayPal payments.

    protected $items; //Items and related shipping address within a transaction.

    protected $approvalUrl;

}

Configuration::defaults('Bread\PayPal\Payment', array(
    'create' => array(
        'type' => 'Bread\Types\DateTime'
    ),
    'update' => array(
        'type' => 'Bread\Types\DateTime'
    ),
    'payer' => array(
        'type' => 'Bread\PayPal\Payer'
    ),
    'items' => array(
        'type' => 'Bread\PayPal\Item',
        'multiple' => true
    )
));
