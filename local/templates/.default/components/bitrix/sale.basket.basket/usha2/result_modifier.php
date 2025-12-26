<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arResult['ITEMS_COUNT'] = sprintf("%02d", intval($arResult['ORDERABLE_BASKET_ITEMS_COUNT']));

$arProducts = [];
foreach ($arResult["ITEMS"] as $type => &$arItems) {
    foreach ($arItems as &$arItem) {
        $arProducts[] = &$arItem;
    }
    unset($arItem);
}
unset($arItems);

\Gsv\Helpers\Usha::modifyBasketProducts($arProducts);

foreach ($arResult["ITEMS"] as $type => &$arItems) {
    foreach ($arItems as &$arItem) {

    }
    unset($arItem);
}
unset($arItems);