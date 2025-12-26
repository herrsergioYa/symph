<?php

Bitrix\Main\Loader::includeModule('yandexpay.pay');

if(!isset($orderObj)) {
    /** @var array $arResult */
    $orderObj = \Bitrix\Sale\Order::load($arResult["ORDER"]['ID']);
}
$apiKey = $testMode = null;

/** @var Bitrix\Sale\Order $orderObj */

/** @var \Bitrix\Sale\Payment $payment */
$paySystem = new YandexPay\Pay\Trading\Entity\Sale\PaySystem(YandexPay\Pay\Trading\Entity\Registry::getEnvironment());
foreach ($orderObj->getPaymentCollection() as $payment) {
    $handler = $paySystem->getHandler($payment->getPaymentSystemId());
    if (!($handler instanceof Sale\Handlers\PaySystem\YandexPayHandler)) {
        continue;
    }

    if (!$apiKey) {
        $apiKey = Bitrix\Sale\BusinessValue::get(
            'YANDEX_PAY_REST_API_KEY',
            Bitrix\Sale\PaySystem\Service::PAY_SYSTEM_PREFIX . $payment->getPaymentSystemId()
        );

        $testMode = Bitrix\Sale\BusinessValue::get(
            'YANDEX_PAY_TEST_MODE',
            Bitrix\Sale\PaySystem\Service::PAY_SYSTEM_PREFIX . $payment->getPaymentSystemId()
        );
    }
}


$requestOrder = new YandexPay\Pay\Trading\Action\Api\Order\Request();
$requestOrder->setApiKey($apiKey);
$requestOrder->setTestMode($testMode === 'Y');
$requestOrder->setOrderNumber($orderObj->getId());
$response = $requestOrder->send();

if($response['status'] == 'success') {
    $GLOBALS["response"]["payment_link"] = $response['data']['order']['paymentUrl'];
} else {
    $orderData = [
        'availablePaymentMethods' => [],
        'orderId' => (string)$orderObj->getId(),
        'cart' => [
            'items' => [],
            'total' => [
                'amount' => 0,
            ],
        ],
        'redirectUrls' => [
            'onError' => 'https://andthebrand.com/checkout/'.$GLOBALS["response"]["order_id"].'/',
            'onSuccess' => 'https://andthebrand.com/checkout/'.$GLOBALS["response"]["order_id"].'/',
        ],
        'currencyCode' => $orderObj->getCurrency(),
    ];


    /** @var \Bitrix\Sale\BasketItem $basketItem */
    foreach ($orderObj->getBasket() as $basketItem) {
        $product = [
            (string)$basketItem->getProductId(),
            (string)$basketItem->getBasketCode(),
        ];

        $orderData['cart']['items'][] = [
            'productId' => implode(':', $product),
            'unitPrice' => (float)$basketItem->getBasePriceWithVat(),
            'discountedUnitPrice' => (float)$basketItem->getPriceWithVat(),
            'subtotal' => (float)$basketItem->getBasePriceWithVat() * $basketItem->getQuantity(),
            'total' => (float)$basketItem->getFinalPrice(),
            'title' => (string)$basketItem->getField('NAME'),
            'quantity' => [
                'count' => (float)$basketItem->getQuantity(),
                'label' => (string)$basketItem->getField('MEASURE_NAME'),
            ],
            'receipt' => [
                'tax' => YandexPay\Pay\Data\Vat::convertForService($basketItem->getVatRate()),
                'measure' => YandexPay\Pay\Data\Measure::convertForService($basketItem->getField('MEASURE_CODE')),
                'productCode' => base64_encode($basketItem->getProductId()),
                'paymentMethodType' => 1,
                'paymentSubjectType' => 1,
            ],
        ];
    }


    $delivery = null;
    $shipmentCollection = $orderObj->getShipmentCollection();
    /** @var \Bitrix\Sale\Shipment $shipment */
    foreach ($shipmentCollection as $shipment) {
        if (!$shipment->isSystem()) {
            $delivery = $shipment->getDelivery();
            break;
        }
    }

    if ($delivery !== null) {
        $vatList = YandexPay\Pay\Data\Vat::getVatList();
        $vatRate = $vatList[$delivery->getVatId()] ?? 0;

        $orderData['cart']['items'][] = [
            'productId' => (string)$delivery->getId(),
            'unitPrice' => $orderObj->getDeliveryPrice(),
            'discountedUnitPrice' => $orderObj->getDeliveryPrice(),
            'subtotal' => $orderObj->getDeliveryPrice(),
            'total' => $orderObj->getDeliveryPrice(),
            'title' => $delivery->getNameWithParent(),
            'quantity' => [
                'count' => 1,
            ],
            'receipt' => [
                'tax' => YandexPay\Pay\Data\Vat::convertForService($vatRate),
                'productCode' => base64_encode((string)$delivery->getId()),
            ],
        ];
    }


    /** @var \Bitrix\Sale\Payment $payment */
    foreach ($orderObj->getPaymentCollection() as $payment) {
        $handler = $paySystem->getHandler($payment->getPaymentSystemId());
        if (!($handler instanceof Sale\Handlers\PaySystem\YandexPayHandler)) {
            continue;
        }

        $availablePaymentMethods = Bitrix\Sale\BusinessValue::get(
            'YANDEX_PAY_AVAILABLE_PAYMENT_METHODS',
            Bitrix\Sale\PaySystem\Service::PAY_SYSTEM_PREFIX . $payment->getPaymentSystemId()
        );

        $orderData['availablePaymentMethods'] = unserialize($availablePaymentMethods);

        $orderData['cart']['total']['amount'] += $payment->getSum();
    }


    $requestOrders = new YandexPay\Pay\Trading\Action\Api\Orders\Request();
    $requestOrders->setApiKey($apiKey);
    $requestOrders->setOrderNumber($orderObj->getId());
    $requestOrders->setLogger(new YandexPay\Pay\Logger\Logger());
    $requestOrders->setOrder($orderData);
    $requestOrders->setTestMode($testMode === 'Y');
    $response = $requestOrders->send();

    $GLOBALS["response"]["yandexpay"]["response"] = $response;

    if ($response['status'] == 'success') {
        $GLOBALS["response"]["payment_link"] = $response['data']['paymentUrl'];
    }
}