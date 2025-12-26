<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
\Bitrix\Main\Localization\Loc::loadLanguageFile(__FILE__);
?>
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-center mh-75vh">
        <div class="mb-5 pb-5 tac">
            <div class="mb-3"><i class="fal fa-check-circle fa-3x"></i></div>
            <div class="fs-20 fw-400">Заказ успешно оформлен!</div>
            <div class="mt-4">Номер заказа: <?= $arResult['ACCOUNT_NUMBER'] ?>.</div>
            <div class="mt-2">Наш менеджер скоро свяжется с&nbsp;вами по&nbsp;указанному телефону.</div>
        </div>
    </div>
</div>
<?
if($arResult["ORDER"]['PAYED'] != 'Y' && in_array($arResult['PAY_SYSTEM']['ID'], $arParams['YANDEX_PAY_PAY_SYSTEMS'])){
    require __DIR__ . '/yandexpay.php';
    //LocalRedirect($GLOBALS["response"]["payment_link"]);
    $APPLICATION->RestartBuffer();
    http_response_code(302);
    header('Location: ' . $GLOBALS["response"]["payment_link"]);
    die();
}

if($arResult["ORDER"]['PAYED'] != 'Y' && in_array($arResult['PAY_SYSTEM']['ID'], $arParams['YOOKASSA_PAY_PAY_SYSTEMS'])){
    require __DIR__ . '/yookassa.php';
    //LocalRedirect($GLOBALS["response"]["payment_link"]);
    $APPLICATION->RestartBuffer();
    http_response_code(302);
    header('Location: ' . $GLOBALS["response"]["payment_link"]);
    die();
}
