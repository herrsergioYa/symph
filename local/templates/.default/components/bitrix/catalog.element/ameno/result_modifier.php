<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/**
 * @var array $arParams
 * @var array $arResult
 */

/**
 * @var CBitrixComponentTemplate $this
 * @var CatalogElementComponent $component
 */

define('LANGUAGE_UPPER', mb_strtoupper(LANGUAGE_ID));

$component = $this->getComponent();
$arParams = $component->applyTemplateModifications();

$arResult['PRICE'] = false;
foreach ($arResult['OFFERS'] as &$arOffer) {
    $arOffer['PRICE'] = false;
    foreach ($arOffer['PRICES'] as $arPrice) {
        $arOffer['PRICE'] = $arPrice;
    }
    if($arOffer['CAN_BUY'] && empty($arResult['PRICE'])) {
        $arResult['PRICE'] = $arOffer['PRICE'];
    }
}
unset($arOffer);

if($arResult['PROPERTIES']['NAME_' . LANGUAGE_UPPER]['VALUE']) {
    $arResult['NAME'] = $arResult['PROPERTIES']['NAME_' . LANGUAGE_UPPER]['VALUE'];
}

if($arResult['PROPERTIES']['DESCRIPTION_' . LANGUAGE_UPPER]['VALUE']) {
    $arDescription = $arResult['PROPERTIES']['DESCRIPTION_' . LANGUAGE_UPPER]['VALUE'];
    $arResult['DETAIL_TEXT'] = $arDescription['TEXT'];
    $arResult['DETAIL_TEXT_TYPE'] = strtolower($arDescription['TYPE']);
}

if($arResult["DISPLAY_PROPERTIES"]["NAME_MODEL"]["VALUE"]){
    $arResult['NAME_MODEL'] = $arResult["DISPLAY_PROPERTIES"]["NAME_MODEL"]["VALUE"];
}

$arColors = [];//\Gsv\Helpers\Ameno::filterValues($arResult["PROPERTIES"]["COLOR"]["VALUE"]);
$arVariants = [];//\Gsv\Helpers\Ameno::filterValues($arResult["PROPERTIES"]["COLORS"]["VALUE"]);

$arVariants = [];//\Gsv\Helpers\Ameno::getVariants($arVariants, $arColors);
$arColors = [];//\Gsv\Helpers\Ameno::getColors($arColors);

$arResult['COLOR'] = [];//\Gsv\Helpers\Ameno::fillColor($arResult["DISPLAY_PROPERTIES"]["COLOR"]["VALUE"], $arColors);
$arResult['COLORS'] = [];/*\Gsv\Helpers\Ameno::fillColors(
    $arResult["PROPERTIES"]["COLORS"]["VALUE"],
    $arVariants,
    $arColors
);*/

$arPhotos = [];
foreach ($arResult['PROPERTIES']['PHOTO']['VALUE'] as $photoId) {
    if($photoId) {
        //$arPhoto = \CFile::ResizeImageGet($photoId, ['width' => 1000, 'height' => 1500]);
//        if($arPhoto) {
//            $arPhoto = array_change_key_case($arPhoto, CASE_UPPER);
//        } else {
//            continue;
//        }
        $arPhoto = \CFile::GetFileArray($photoId);
        if(empty($arPhoto)) {
            continue;
        }

        $arSlide = \CFile::ResizeImageGet($photoId, ['width' => 380, 'height' => 540]);
        if($arSlide) {
            $arSlide = array_change_key_case($arSlide, CASE_UPPER);
        } else {
            continue;
        }

        $arPhotos[] = [
            'PHOTO' => $arPhoto,
            'PREVIEW' => $arSlide,
            'IS_VIDEO' => false,
        ];
    }
}
$arVideos = [];
foreach ($arResult['PROPERTIES']['VIDEO']['VALUE'] as $videoId) {
    if($videoId) {
        $arVideo = \CFile::GetFileArray($videoId);
        if($arVideo) {
            $arVideos[] = [
                'VIDEO' => $arVideo,
                'IS_VIDEO' => true,
            ];
            break;
        }

    }
}
$arSlides = [];
if(count($arPhotos) <= 3) {
    $arSlides = $arPhotos;
} else {
    $arSlides = array_slice($arPhotos, 0, 3);
}
if($arVideos) {
    $arSlides = array_merge($arSlides, $arVideos);
}
if(count($arPhotos) > 3) {
    $arSlides = array_merge($arSlides, array_slice($arPhotos, 3));
}
$arResult['SLIDES'] = $arSlides;

//We are fixing the SKUProps
if(isset($arResult['SKU_PROPS']['SIZE'])) {
    if(empty($arResult['SKU_PROPS']['SIZE_CUP'])) {
        $arSkuPropAll = $component->getTemplateSkuPropList();
        $arSkuProp = $arSkuPropAll['SIZE_CUP'];
        $arSkuProp['VALUES'][0] = [
            'ID' => 0,
            'NAME' => '-',
        ];
        $arResult['SKU_PROPS']['SIZE_CUP'] = $arSkuProp;
        $PROP = 'PROP_' . $arSkuProp['ID'];
        unset($arSkuProp);
        foreach ($arResult['JS_OFFERS'] as &$jsOffer) {
            if(!array_key_exists($PROP, $jsOffer['TREE'])) {
                $jsOffer['TREE'][$PROP] = 0;
            }
        }
        unset($jsOffer);
        unset($PROP);
    }
    unset($arResult['SKU_PROPS']['SIZE_SKIRT']);
    unset($arResult['SKU_PROPS']['SIZE_TOP']);
} else if(isset($arResult['SKU_PROPS']['SIZE_TOP']) || isset($arResult['SKU_PROPS']['SIZE_SKIRT'])) {
    unset($arResult['SKU_PROPS']['SIZE']);
    unset($arResult['SKU_PROPS']['SIZE_CUP']);

    if(empty($arResult['SKU_PROPS']['SIZE_TOP']) || empty($arResult['SKU_PROPS']['SIZE_SKIRT'])) {
        $arSkuPropAll = $component->getTemplateSkuPropList();
        foreach (['SIZE_TOP', 'SIZE_SKIRT'] as $CODE) {
            if(isset($arResult['SKU_PROPS'][$CODE])) {
                continue;
            }
            $arSkuProp = $arSkuPropAll[$CODE];
            $arSkuProp['VALUES'][0] = [
                'ID' => 0,
                'NAME' => '-',
            ];
            $arResult['SKU_PROPS'][$CODE] = $arSkuProp;
            $PROP = 'PROP_' . $arSkuProp['ID'];
            unset($arSkuProp);
            foreach ($arResult['JS_OFFERS'] as &$jsOffer) {
                if (!array_key_exists($PROP, $jsOffer['TREE'])) {
                    $jsOffer['TREE'][$PROP] = 0;
                }
            }
            unset($jsOffer);
            unset($PROP);
        }
    }
}


foreach ($arResult['SKU_PROPS'] as &$arSkuProp) {
    foreach ($arSkuProp['VALUES'] as $id => &$arValue) {
        $arValue['OFFER_CNT'] = 0;
        $arValue['AVAILABLE_OFFER_CNT'] = 0;
        foreach ($arResult['JS_OFFERS'] as $jsOffer) {
            if($jsOffer['TREE']['PROP_' . $arSkuProp['ID']] == $id/* && $jsOffer['CAN_BUY']*/) {
                $arValue['OFFER_CNT']++;
                //continue 2;
                if($jsOffer['CAN_BUY']) {
                    $arValue['AVAILABLE_OFFER_CNT']++;
                }
            }
        }
    }
    unset($arValue);
}
unset($arSkuProp);

$arResult['JS_DATA'] = [
    'PRODUCT_ID' => $arResult['ID'],
    'OFFERS' => $arResult['JS_OFFERS'],
    'SKU_PROPS' => array_values($arResult['SKU_PROPS']), //We must ensure the order!
    'OFFER_ID_SELECTED' => $arResult['OFFER_ID_SELECTED'],
    //'ID' => $this->randString(),
    'SEL' => '.product-page',
    "BASKET_URL" => $arParams["BASKET_URL"],
];