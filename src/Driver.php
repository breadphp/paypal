<?php
namespace Bread\PayPal;

use Bread\Configuration\Manager as Configuration;
use Bread\Promises\Deferred;
use Bread\Promises\When;
use Bread\Storage\Collection;
use Bread\Types\DateTime;

use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Address;
use PayPal\Api\Amount;
use PayPal\Api\BillingInfo;
use PayPal\Api\Cost;
use PayPal\Api\CreditCard as PayPalCreditCard;
use PayPal\Api\CreditCardToken;
use PayPal\Api\Currency;
use PayPal\Api\Details;
use PayPal\Api\FundingInstrument;
use PayPal\Api\Invoice;
use PayPal\Api\InvoiceAddress;
use PayPal\Api\InvoiceItem;
use PayPal\Api\Item as PayPalItem;
use PayPal\Api\ItemList;
use PayPal\Api\MerchantInfo;
use PayPal\Api\OpenIdTokeninfo;
use PayPal\Api\OpenIdUserinfo;
use PayPal\Api\Payer as PayPalPayer;
use PayPal\Api\Payment as PayPalPayment;
use PayPal\Api\PaymentExecution;
use PayPal\Api\PaymentTerm;
use PayPal\Api\Phone;
use PayPal\Api\ShippingInfo;
use PayPal\Api\Transaction;
use PayPal\Api\RedirectUrls;
use PayPal\Exception\PayPalConnectionException;
use PayPal\Exception\PayPalInvalidCredentialException;

class Driver
{

    public static function getPayment($id, $domain)
    {
        $deferred = new Deferred();
        try {
            $paypalPayment = PayPalPayment::get($id, static::getApiContext($domain));
            $payment = new Payment( array(
                'id' => $paypalPayment->getId(),
                'create' => new DateTime($paypalPayment->getCreateTime()),
                'update' => new DateTime($paypalPayment->getUpdateTime()),
                'intent' => $paypalPayment->getIntent(),
                'state' => $paypalPayment->getState(),
                'items' => new Collection()
            ));
            $paypalPayer = $paypalPayment->getPayer();
            $payment->payer = new Payer(array(
                'payerId' => $paypalPayer->getPayerInfo()->getPayerId(),
                'salutation' => $paypalPayer->getPayerInfo()->getSalutation(),
                'firstName' => $paypalPayer->getPayerInfo()->getFirstName(),
                'middleName' => $paypalPayer->getPayerInfo()->getMiddleName(),
                'lastName' => $paypalPayer->getPayerInfo()->getLastName(),
                'status' => $paypalPayer->getStatus(),
                'paymentMethod' => $paypalPayer->getPaymentMethod(),
                'mail' => $paypalPayer->getPayerInfo()->getEmail(),
                'phone' => $paypalPayer->getPayerInfo()->getPhone()
            ));
            foreach ($paypalPayment->getTransactions() as $transaction) {
                foreach ($transaction->getItemList()->getItems() as $item) {
                    $payment->items->append(new Item(array(
                        'quantity' => $item->getQuantity(),
                        'name' => $item->getName(),
                        'description' => $item->getDescription(),
                        'price' => $item->getPrice(),
                        'tax' => $item->getTax(),
                        'currency' => $item->getCurrency(),
                        'sku' => $item->getSku()
                    )));
                }
            }
            return $deferred->resolve($payment);
        } catch (PayPalConnectionException $exception) {
            return $deferred->reject($exception->getMessage());
        } catch (PayPalInvalidCredentialException $exception) {
            return $deferred->reject($exception->getMessage());
        }
    }

    public static function preparePayment($payment, $domain)
    {
        $items = array();
        $currency = null;
        $total = 0;
        foreach ($payment->items as $item) {
            $tmpItem = new PayPalItem();
            $tmpItem->setName($item->description)
                ->setCurrency($item->currency)
                ->setQuantity($item->quantity)
                ->setSku($item->sku)
                ->setPrice($item->price);
            $items[] = $tmpItem;
            //
            $total += (float) $item->price;
            $currency = $item->currency;
        }
        $itemList = new ItemList();
        $itemList->setItems($items);

        $payer = new PayPalPayer();
        switch ($payment->payer->paymentMethod) {
            case Payer::PAYMENT_CREDIT_CARD:
                $payer->setPaymentMethod($payment->payer->paymentMethod);
                $card = new PayPalCreditCard();
                $card->setType($payment->payer->creditCard->type)
                    ->setNumber($payment->payer->creditCard->number)
                    ->setExpireMonth($payment->payer->creditCard->expireMonth)
                    ->setExpireYear($payment->payer->creditCard->expireYear)
                    ->setCvv2($payment->payer->creditCard->cvv2)
                    ->setFirstName($payment->payer->creditCard->firstName)
                    ->setLastName($payment->payer->creditCard->lastName);
                $fi = new FundingInstrument();
                $fi->setCreditCard($card);
                $payer->setFundingInstruments(array($fi));
                break;
            case Payer::PAYMENT_PAYPAL:
                $payer->setPaymentMethod($payment->payer->paymentMethod);
                break;
        }

        $amount = new Amount();
        $amount->setCurrency($currency);
        $amount->setTotal($total);

        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setItemList($itemList)
            ->setDescription($payment->description);

        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl($payment->returnUrl);
        $redirectUrls->setCancelUrl($payment->cancelUrl);

        $paypalPayment = new PayPalPayment();
        $paypalPayment->setRedirectUrls($redirectUrls);
        $paypalPayment->setIntent($payment->intent);
        $paypalPayment->setPayer($payer);
        $paypalPayment->setTransactions(array($transaction));

        try {
            $paypalPayment->create(static::getApiContext($domain));
            return static::getPayment($paypalPayment->getId(), $domain)->then(function ($payment) use($paypalPayment) {
                $payment->approvalUrl = static::getLink($paypalPayment->getLinks(), "approval_url");
                return $payment;
            });
        } catch (PayPalConnectionException $e) {
            return When::reject($e);
        } catch (PayPalInvalidCredentialException $e) {
            return When::reject($e);
        }
    }

    public static function executePayment($paymentId, $payerId, $domain)
    {
        if (!$paymentId || !$payerId) {
            return When::reject("PaymentID or PayerID invalid");
        }
        $payment = static::getPaymentDetails($paymentId, $domain);
        $paymentExecution = new PaymentExecution();
        $paymentExecution->setPayerId($payerId);
        try {
            $payment->execute($paymentExecution, static::getApiContext($domain));
            return static::getPayment($payment->getId(), $domain);
        } catch (PayPalConnectionException $exception) {
            return When::reject($exception);
        }
    }

    /*
    public static function createInvoice($payment, $domain)
    {
        $invoice = new Invoice();
        $invoice
            ->setMerchantInfo(new MerchantInfo())
            ->setBillingInfo(array(new BillingInfo()))
            ->setPaymentTerm(new PaymentTerm())
            ->setShippingInfo(new ShippingInfo());
        $invoice->getMerchantInfo()
            ->setEmail('alex.tomasello@saiv.it')
            ->setFirstName('SAIV')
            ->setLastName('BEST Hotspot')
            ->setbusinessName('SAIV S.p.A.');
//             ->setPhone(new Phone());
//         $invoice->getMerchantInfo()->getPhone()
//             ->setNationalNumber($payment->payer->phone);
        $billing = $invoice->getBillingInfo();
        $billing[0]
            ->setEmail($payment->payer->mail);
        $billing[0]
            ->setBusinessName($payment->payer->firstName);
        $items = array();
        foreach ($payment->items as $item) {
            $tmpItem = new InvoiceItem();
            $tmpItem->setName($item->name)
                ->setQuantity((int) $item->quantity)
                ->setUnitPrice(new Currency());
            $tmpItem->getUnitPrice()
                ->setCurrency($item->currency)
                ->setValue($item->price);
            $items[] = $tmpItem;
        }
        $invoice->setItems($items);
        $invoice->getShippingInfo()
            ->setFirstName($payment->payer->firstName)
            ->setLastName($payment->payer->lastName)
            ->setbusinessName($payment->payer->company);
//             ->setPhone(new Phone());
//         $invoice->getShippingInfo()->getPhone()
//             ->setNationalNumber($payment->payer->phone);
        $invoice->getPaymentTerm()
            ->setTermType("NET_45");
//         $invoice->setLogoUrl('https://saiv.best.saiv.it:3443/images/SAIV-logo+sphere-40px.png');
        try {
        $invoice->create(static::getApiContext($domain));
        return $invoice->send(static::getApiContext($domain));
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            var_dump($e->getData());
        }
    }
    */

    protected function getLink(array $links, $type)
    {
        foreach($links as $link) {
            if($link->getRel() == $type) {
                return $link->getHref();
            }
        }
        return null;
    }

    /**
     * Retrieves the payment information based on PaymentID from Paypal APIs
     *
     * @param $paymentId
     *
     * @return Payment
     */
    protected static function getPaymentDetails($paymentId, $domain)
    {
        return PayPalPayment::get($paymentId, static::getApiContext($domain));
    }

    protected static function getApiContext($domain)
    {
        //{live, $sandbox}
        $mode = Configuration::get(Payment::class, 'api.mode', $domain);
        $clientID = Configuration::get(Payment::class, "api.{$mode}.clientId", $domain);
        $clientSecret = Configuration::get(Payment::class, "api.{$mode}.clientSecret", $domain);
        $api = new ApiContext(new OAuthTokenCredential($clientID, $clientSecret));
        $api->setConfig(array(
            'mode' => $mode,
//             'log.LogEnabled' => true,
//             'log.FileName' => '../PayPal.log',
//             'log.LogLevel' => 'DEBUG', // PLEASE USE `FINE` LEVEL FOR LOGGING IN LIVE ENVIRONMENTS
//             'validation.level' => 'log',
            'cache.enabled' => true
        ));
        return $api;
    }

}