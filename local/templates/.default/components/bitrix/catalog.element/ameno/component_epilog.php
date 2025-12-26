<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
//$APPLICATION->SetPageProperty('page-type', "product-page");
/*
$arBasket = [];
if(\Bitrix\Main\Loader::includeModule('sale')) {
    $obBasket = \Bitrix\Sale\Basket::loadItemsForFUser(
        \Bitrix\Sale\Fuser::getId(),
        SITE_ID
    );
    /** @var \Bitrix\Sale\BasketItem $obBasketItem * /
    foreach ($obBasket->getBasketItems() as $obBasketItem) {
        if(isset($arBasket[$obBasketItem->getProductId()])) {
            $arBasket[$obBasketItem->getProductId()] += $obBasketItem->getQuantity();
        } else {
            $arBasket[$obBasketItem->getProductId()] = $obBasketItem->getQuantity();
        }
    }
}
?>
<script>
    window.BASKET = window.BASKET || {};
    Object.assign(window.BASKET, <?= json_encode($arBasket) ?>);
</script>
<?*/
    if($arParams['FILTER_NAME'] ) {
        $sFilterName = $arParams['FILTER_NAME'] . '_arrRecommendedFilter';
        $bAddMainFilter = !!is_array($GLOBALS[$arParams['FILTER_NAME']]);
    } else {
        $sFilterName = 'arrRecommendedFilter';
        $bAddMainFilter = false;
    }
    $GLOBALS[$sFilterName] = [
        '!ID' => $arResult['ID'],
    ];
    if($bAddMainFilter) {
        $GLOBALS[$sFilterName][] = $GLOBALS[$arParams['FILTER_NAME']];
    }
?>
<?$APPLICATION->IncludeComponent(
    "bitrix:catalog.section",
    "recommend",
    Array(
        'ACTION_VARIABLE' => $arParams['ACTION_VARIABLE'],
        'ADD_PROPERTIES_TO_BASKET' => (isset($arParams['ADD_PROPERTIES_TO_BASKET']) ? $arParams['ADD_PROPERTIES_TO_BASKET'] : ''),
        "ADD_SECTIONS_CHAIN" => "N",
        "ADD_TO_BASKET_ACTION" => "ADD",
        "AJAX_MODE" => "N",
        "AJAX_OPTION_ADDITIONAL" => "",
        "AJAX_OPTION_HISTORY" => "N",
        "AJAX_OPTION_JUMP" => "N",
        "AJAX_OPTION_STYLE" => "N",
        "BACKGROUND_IMAGE" => "-",
        "BASKET_URL" => $arParams['BASKET_URL'],
        "BROWSER_TITLE" => "-",
        "CACHE_FILTER" => "N",
        "CACHE_GROUPS" => "Y",
        "CACHE_TIME" => "36000000",
        "CACHE_TYPE" => "N",
        "COMPATIBLE_MODE" => $arParams['COMPATIBLE_MODE'],
        "CONVERT_CURRENCY" => $arParams['CONVERT_CURRENCY'],
        "CURRENCY_ID" => $arParams['CURRENCY_ID'],
        "CUSTOM_FILTER" => "{\"CLASS_ID\":\"CondGroup\",\"DATA\":{\"All\":\"AND\",\"True\":\"True\"},\"CHILDREN\":[]}",
        "DETAIL_URL" => $arParams['DETAIL_URL'],
        "DISABLE_INIT_JS_IN_COMPONENT" => "N",
        "DISPLAY_BOTTOM_PAGER" => "N",
        "DISPLAY_COMPARE" => "N",
        "DISPLAY_TOP_PAGER" => "N",
        "ELEMENT_SORT_FIELD" => "RAND",
        "ELEMENT_SORT_FIELD2" => "id",
        "ELEMENT_SORT_ORDER" => "asc",
        "ELEMENT_SORT_ORDER2" => "desc",
        "ENLARGE_PRODUCT" => "STRICT",
        "FILTER_NAME" => $sFilterName,
        'HIDE_NOT_AVAILABLE' => $arParams['HIDE_NOT_AVAILABLE'],
        'HIDE_NOT_AVAILABLE_OFFERS' => $arParams['HIDE_NOT_AVAILABLE_OFFERS'],
        'IBLOCK_TYPE' => $arParams['IBLOCK_TYPE'],
        'IBLOCK_ID' => $arParams['IBLOCK_ID'],
        "INCLUDE_SUBSECTIONS" => "Y",
        "LAZY_LOAD" => "N",
        "LINE_ELEMENT_COUNT" => "3",
        "LOAD_ON_SCROLL" => "N",
        "MESSAGE_404" => "",'MESS_BTN_BUY' => (isset($arParams['~MESS_BTN_BUY']) ? $arParams['~MESS_BTN_BUY'] : ''),
        'MESS_BTN_ADD_TO_BASKET' => (isset($arParams['~MESS_BTN_ADD_TO_BASKET']) ? $arParams['~MESS_BTN_ADD_TO_BASKET'] : ''),
        'MESS_BTN_SUBSCRIBE' => (isset($arParams['~MESS_BTN_SUBSCRIBE']) ? $arParams['~MESS_BTN_SUBSCRIBE'] : ''),
        'MESS_BTN_DETAIL' => (isset($arParams['~MESS_BTN_DETAIL']) ? $arParams['~MESS_BTN_DETAIL'] : ''),
        'MESS_NOT_AVAILABLE' => $arParams['~MESS_NOT_AVAILABLE'] ?? '',
        'MESS_NOT_AVAILABLE_SERVICE' => $arParams['~MESS_NOT_AVAILABLE_SERVICE'] ?? '',
        "MESS_BTN_LAZY_LOAD" => $arParams["~MESS_BTN_LAZY_LOAD"],
        "META_DESCRIPTION" => "-",
        "META_KEYWORDS" => "-",
        'OFFERS_CART_PROPERTIES' => (isset($arParams['OFFERS_CART_PROPERTIES']) ? $arParams['OFFERS_CART_PROPERTIES'] : []),
        'OFFERS_FIELD_CODE' => $arParams['DETAIL_OFFERS_FIELD_CODE'],
        'OFFERS_PROPERTY_CODE' => (isset($arParams['DETAIL_OFFERS_PROPERTY_CODE']) ? $arParams['DETAIL_OFFERS_PROPERTY_CODE'] : []),
        'OFFERS_SORT_FIELD' => $arParams['OFFERS_SORT_FIELD'],
        'OFFERS_SORT_ORDER' => $arParams['OFFERS_SORT_ORDER'],
        'OFFERS_SORT_FIELD2' => $arParams['OFFERS_SORT_FIELD2'],
        'OFFERS_SORT_ORDER2' => $arParams['OFFERS_SORT_ORDER2'],
        "OFFERS_LIMIT" => (isset($arParams["LIST_OFFERS_LIMIT"]) ? $arParams["LIST_OFFERS_LIMIT"] : 0),
        "PAGER_BASE_LINK_ENABLE" => "N",
        "PAGER_DESC_NUMBERING" => "N",
        "PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
        "PAGER_SHOW_ALL" => "N",
        "PAGER_SHOW_ALWAYS" => "N",
        "PAGER_TEMPLATE" => ".default",
        "PAGER_TITLE" => "Товары",
        "PAGE_ELEMENT_COUNT" => "18",
        "PARTIAL_PRODUCT_PROPERTIES" => "N",
        'PRICE_CODE' => $arParams['~PRICE_CODE'],
        'PRICE_VAT_INCLUDE' => $arParams['PRICE_VAT_INCLUDE'],
        "PRODUCT_BLOCKS_ORDER" => "price,props,sku,quantityLimit,quantity,buttons",
        'PRODUCT_ID_VARIABLE' => $arParams['PRODUCT_ID_VARIABLE'],
        "PRODUCT_PROPS_VARIABLE" => "prop",
        "PRODUCT_QUANTITY_VARIABLE" => "quantity",
        "PRODUCT_ROW_VARIANTS" => "[{'VARIANT':'2','BIG_DATA':false},{'VARIANT':'2','BIG_DATA':false},{'VARIANT':'2','BIG_DATA':false},{'VARIANT':'2','BIG_DATA':false},{'VARIANT':'2','BIG_DATA':false},{'VARIANT':'2','BIG_DATA':false}]",
        "PRODUCT_SUBSCRIPTION" => "Y",
        'SECTION_ID' => $arParams['SECTION_ID'],
        'SECTION_CODE' => $arParams['SECTION_CODE'],
        'SECTION_ID_VARIABLE' => $arParams['SECTION_ID_VARIABLE'],
        "SECTION_URL" => $arParams['SECTION_URL'],
        "SECTION_USER_FIELDS" => $arParams['SECTION_USER_FIELDS'],
        "SEF_MODE" => "N",
        "SET_BROWSER_TITLE" => "N",
        "SET_LAST_MODIFIED" => "N",
        "SET_META_DESCRIPTION" => "N",
        "SET_META_KEYWORDS" => "N",
        "SET_STATUS_404" => "N",
        "SET_TITLE" => "N",
        "SHOW_404" => "N",
        "SHOW_ALL_WO_SECTION" => "N",
        "SHOW_CLOSE_POPUP" => "N",
        "SHOW_DISCOUNT_PERCENT" => "N",
        "SHOW_MAX_QUANTITY" => "N",
        "SHOW_OLD_PRICE" => "N",
        "SHOW_PRICE_COUNT" => "1",
        "SHOW_SLIDER" => "Y",
        "TEMPLATE_THEME" => "blue",
        "USE_ENHANCED_ECOMMERCE" => "N",
        "USE_MAIN_ELEMENT_SECTION" => "N",
        "USE_PRICE_COUNT" => "N",
        "USE_PRODUCT_QUANTITY" => "N"
    )
);?>
<?

return;
$GLOBALS["ROOT"] = $arResult["ROOT"] > 0 ? $arResult["ROOT"] : "";

$APPLICATION->SetPageProperty("title", $arResult["SEO"]["TITLE"]);
$APPLICATION->SetPageProperty("description", $arResult["SEO"]["DESCRIPTION"]);

if ($arResult["CANONICAL"]) {
    $GLOBALS["IS_CANONICAL"] = true;
}
?>