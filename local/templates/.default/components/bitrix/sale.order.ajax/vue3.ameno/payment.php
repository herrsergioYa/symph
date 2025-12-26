<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
global $APPLICATION;

// если заказ не оплачен, то сделать редирект на оплату
if($arResult['ORDER']['ID']) {
    $arPayment = reset($arResult["PAYMENT"]);
    $arPaySystem = $arResult["PAY_SYSTEM_LIST"][$arPayment["PAY_SYSTEM_ID"]];

    if($arPayment["PAID"] == "N") {
        $_SESSION["ORDER_RETURN_URL"] = 'https://'.$_SERVER['HTTP_HOST'].\Km20\Design::getSitePath('/cart/order/').'?ORDER_ID='.$arResult['ORDER']['ID'];

        $redirect = false;
        if ($arPaySystem['CODE'] == 'yandexpay') {
            \Bitrix\Main\Loader::includeModule('yandexpay.pay');
            $redirect = \KM20\Payment\YandexPay::getRedirect($arResult['ORDER']['ID']);
            if(!$redirect) {
                echo \KM20\Payment\YandexPay::getError();
            }
        } elseif ($arPaySystem['CODE'] == 'yookassa_to_order') {
            if($_SESSION["YOOKASSA_PAYMENT_TOKEN"]) {
                /** @var \Bitrix\Sale\Order $order */
                $order = \Bitrix\Sale\Order::load($arResult['ORDER']['ID']);
                $payment = $order->getPaymentCollection()->current();
                $service = \Bitrix\Sale\PaySystem\Manager::getObjectById($payment->getPaymentSystemId());
                if ($service) {
                    $getHandler = Closure::bind(function(Bitrix\Sale\PaySystem\Service $service) {
                        return $service->handler;
                    }, null, 'Bitrix\Sale\PaySystem\Service');

                    $result = $getHandler($service)->createPayment($payment, $arResult['ORDER']['ID'], $service->getParamsBusValue($payment), $_SESSION["YOOKASSA_PAYMENT_TOKEN"]);
                    if ($result['url']) {
                        $_SESSION["YOOKASSA_PAYMENT_TOKEN"] = false;
                        $redirect = $result['url'];
                    } else {
                        //echo 'Ошибка создания оплаты';
                    }
                }
            } else {
                $redirect = \Km20\Design::getSitePath('/personal/payment/').'?ORDER_ID='.$arResult['ORDER']['ID'];
            }
        } elseif ($arPaySystem['CODE'] == 'yookassa_custom') {
            if($arPaySystem["PAYMENT_URL"]) {
                $redirect = $arPaySystem["PAYMENT_URL"];
            } else {
                $redirect = \Km20\Design::getSitePath('/personal/payment/').'?ORDER_ID='.$arResult['ORDER']['ID'];
            }
        } else {
            $redirect = \Km20\Design::getSitePath('/personal/payment/').'?ORDER_ID='.$arResult['ORDER']['ID'];
        }

        if($redirect && !isset($_SESSION['INIT_PAYMENT_'.$arResult['ORDER']['ID']])) {
            $_SESSION['INIT_PAYMENT_'.$arResult['ORDER']['ID']] = true;
            $APPLICATION->RestartBuffer();
            header('Location: '.$redirect);
            die();
        } else {
            $GLOBALS["PAY_LINK"] = $redirect;
        }
    }
}