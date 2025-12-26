<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arColors = [];
foreach ($arResult['ITEMS'] as $arItem) {
    if (in_array($arItem['CODE'], $arParams["LIST_COLORS"]) && !empty($arItem['VALUES'])) {
        foreach ($arItem['VALUES'] as $id => $arValue) {
            $arColors[] = $id;
        }
    }
}

$arColors = \Gsv\Helpers\Ameno::getColors($arColors);

foreach ($arResult['ITEMS'] as &$arItem) {
    foreach ($arItem['VALUES'] as $id => &$arValue) {
        if($arValue['DISABLED'] && !$arValue['CHECKED']) {
            unset($arItem['VALUES'][$id]);
        }
    }
}
unset($arItem);

$EXCESS_SIZES = -2;
$arSizes = [];

$arResult['CHECKED'] = 0;
foreach ($arResult['ITEMS'] as &$arItem) {
    if (in_array($arItem['CODE'], $arParams["LIST_COLORS"]) && !empty($arItem['VALUES'])) {
        foreach ($arItem['VALUES'] as $id => &$arValue) {
            $arValue['COLOR'] = $arColors[$id];
            if($arValue['CHECKED']) {
                $arResult['CHECKED']++;
            }
        }
        unset($arValue);
        $arItem['IS_COLOR'] = 'Y';
    } else {
        $arItem['IS_COLOR'] = 'N';
    }

    if (in_array($arItem['CODE'], $arParams["LIST_SIZES"]) && !empty($arItem['VALUES'])) {
        foreach ($arItem['VALUES'] as $id => $arValue) {
            if($arValue['CHECKED']) {
                $arResult['CHECKED']++;
            }
        }
        $arItem['IS_SIZE'] = 'Y';
        $EXCESS_SIZES++;
        if($EXCESS_SIZES <= 0) {
            $arSizes[] = &$arItem;
            $arItem['CHILDREN'] = [];
        } else {
            $arItem['IS_SIZE'] = 'N';
            $arSizes[($EXCESS_SIZES - 1) % count($arSizes)]['CHILDREN'][] = $arItem;
        }
    } else {
        $arItem['IS_SIZE'] = 'N';
    }

    if (in_array($arItem['CODE'], $arParams["LIST_CHECKBOXES"]) && !empty($arItem['VALUES'])) {
        foreach ($arItem['VALUES'] as $id => $arValue) {
            if($arValue['CHECKED']) {
                $arResult['CHECKED']++;
            }
        }
        $arItem['IS_CHECKBOX'] = 'Y';
    } else {
        $arItem['IS_CHECKBOX'] = 'N';
    }
}
unset($arItem);

unset($arSizes, $EXCESS_SIZES);//Just in case