<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arResult['CHECKED'] = 0;
foreach ($arResult['ITEMS'] as &$arItem) {
    foreach ($arItem['VALUES'] as $id => &$arValue) {
        if($arValue['CHECKED']) {
            $arResult['CHECKED']++;
        } else if($arValue['DISABLED']) {
            //unset($arItem['VALUES'][$id]);
        }
    }
}
unset($arItem);

\Gsv\Helpers\Usha::modifySmartFilter($arResult);