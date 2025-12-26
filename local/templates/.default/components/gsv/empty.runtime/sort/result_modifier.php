<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */


if (empty($arParams['SORT_NAME'])) {
    $arParams['SORT_NAME'] = 'arrSort';
}

if(is_array($arParams['SORT_LIST'])) {
    $arResult['ITEMS'] = $arParams['SORT_LIST'];
} else {
    $arResult['ITEMS'] = $GLOBALS[$arParams['SORT_LIST']];
}

$arResult['SORT'] = false;
$GLOBALS[$arParams['SORT_NAME']] = [];
foreach($arResult['ITEMS'] as $k => &$arItem) {
    if($k == $arParams['SORT']) {
        $arResult['SORT'] = $k;
        $arItem['SELECTED'] = true;
        $GLOBALS[$arParams['SORT_NAME']] = [
            'FIELD' => $arItem['FIELD'],
            'ORDER' => $arItem['ORDER'],
        ];
    } else {
        $arItem['SELECTED'] = false;
    }
    $arItem['ID'] = $k;
}
unset($arItem);