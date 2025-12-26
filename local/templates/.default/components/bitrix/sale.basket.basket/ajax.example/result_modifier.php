<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$arResult['OPT']['AJAX_KEY'] = $this->randString(12);
$arResult['OPT']['AJAX_URL'] = $this->__component->GetPath() . '/ajax.php';
$arResult['OPT']['ROOT_ID'] = 'basket-' . $arResult['OPT']['AJAX_KEY'];

$templateName = basename(__DIR__);
$signer = new \Bitrix\Main\Security\Sign\Signer;
$signedTemplate = $signer->sign($templateName, 'sale.basket.basket');
$signedParams = $signer->sign(base64_encode(serialize($arParams)), 'sale.basket.basket');

$arResult['OPT']['ACTION_VARIABLE'] = $arParams['ACTION_VARIABLE'];
$arResult['OPT']['siteId'] = SITE_ID;
$arResult['OPT']['siteTemplateId'] = SITE_TEMPLATE_ID;
$arResult['OPT']['template'] = $signedTemplate;
$arResult['OPT']['signedParamsString'] = $signedParams;

$arResult['JS_DATA'] = [
    'BASKET_ITEM_RENDER_DATA' => $arResult['BASKET_ITEM_RENDER_DATA'],
    'TOTAL_RENDER_DATA' => $arResult['TOTAL_RENDER_DATA'],
    'EMPTY_BASKET' => $arResult['EMPTY_BASKET'],
];
