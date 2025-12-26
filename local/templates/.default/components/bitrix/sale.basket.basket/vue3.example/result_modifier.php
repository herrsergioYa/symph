<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$arResult['VUE']['AJAX_KEY'] = $this->randString(12);
$arResult['VUE']['AJAX_URL'] = $this->__component->GetPath() . '/ajax.php';
$arResult['VUE']['ROOT_ID'] = 'basket-' . $arResult['VUE']['AJAX_KEY'];

$templateName = basename(__DIR__);
$signer = new \Bitrix\Main\Security\Sign\Signer;
$signedTemplate = $signer->sign($templateName, 'sale.basket.basket');
$signedParams = $signer->sign(base64_encode(serialize($arParams)), 'sale.basket.basket');

$arResult['VUE']['ACTION_VARIABLE'] = $arParams['ACTION_VARIABLE'];
$arResult['VUE']['siteId'] = SITE_ID;
$arResult['VUE']['siteTemplateId'] = SITE_TEMPLATE_ID;
$arResult['VUE']['template'] = $signedTemplate;
$arResult['VUE']['signedParamsString'] = $signedParams;

$arResult['JS_DATA'] = [
    'BASKET_ITEM_RENDER_DATA' => $arResult['BASKET_ITEM_RENDER_DATA'],
    'TOTAL_RENDER_DATA' => $arResult['TOTAL_RENDER_DATA'],
    'EMPTY_BASKET' => $arResult['EMPTY_BASKET'],
];
