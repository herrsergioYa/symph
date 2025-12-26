<?php

/** @var Bitrix\Sale\Order $orderObj */
if(!isset($orderObj)) {
    /** @var array $arResult */
    $orderObj = \Bitrix\Sale\Order::load($arResult["ORDER"]['ID']);
}

$payment = null;

/* @var $_payment \Bitrix\Sale\Payment */
foreach ($orderObj->getPaymentCollection() as $_payment) {
    if(!$_payment->isInner()) {
        $payment = $_payment;
        break;
    }
}

$_SESSION['YANDEX_CHECKOUT_RETURN_URL'] = 'https://andthebrand.com/checkout/'.$GLOBALS["response"]["order_id"].'/';


$handler = new \Sale\Handlers\PaySystem\YandexCheckoutHandler('', $payment->getPaySystem());
$result = $handler->initiatePay($payment);

$GLOBALS["response"]["payment_link"] = $result->getPaymentUrl();