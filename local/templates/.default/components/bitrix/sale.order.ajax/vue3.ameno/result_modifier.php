<? use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;
use KM20\Helper\OrderDelivery;
use KM20\Helper\Tools;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/**
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponentTemplate $this
 * @global CMain $APPLICATION
 * @global CUser $USER
 * @global CDatabase $DB
 * */

$component = $this->__component;
$component::scaleImages($arResult['JS_DATA'], $arParams['SERVICES_IMAGES_SCALING']);

require_once __DIR__ . '/ajax_class.php';

$locationId = '';
foreach ($arResult['ORDER_PROP']['USER_PROPS_Y'] as $arProperty) {
    if($arProperty['IS_LOCATION'] == 'Y') {
        $locationId = $arProperty['VALUE'] ?: '';
    } else {

    }
}

$arCountry = $arCity = false;
if($locationId != '') {
    $arLocation = \Bitrix\Sale\Location\Name\LocationTable::getRow([
        'select' => [
            'LOCATION_ID',
            'NAME',
            'CODE' => 'LOCATION.CODE',
            'LOCATION_TYPE' => 'LOCATION.TYPE_ID',

            'LEFT_MARGIN' => 'LOCATION.LEFT_MARGIN',
            'RIGHT_MARGIN' => 'LOCATION.RIGHT_MARGIN',
        ],
        'filter' => [
            //'LOCATION.CODE' => $locationCode,
            'LOCATION.ID' => $locationId,
            'LOCATION.TYPE_ID' => [1, 5], //Countries & cities
            'LANGUAGE_ID' => LANGUAGE_ID,
        ]
    ]);
    if($arLocation) {
        $arLocation['ID'] = $arLocation['LOCATION_ID'];
        unset($arLocation['LOCATION_ID']);
        if($arLocation['LOCATION_TYPE'] == 1) {
            //Country
            $arCountry = $arLocation;
            $arCity = false;
        } else {
            //City
            $arCity = $arLocation;
            $arCountry = \Bitrix\Sale\Location\Name\LocationTable::getRow([
                'select' => [
                    'LOCATION_ID',
                    'NAME',
                    'CODE' => 'LOCATION.CODE',
                    'LOCATION_TYPE' => 'LOCATION.TYPE_ID',
                ],
                'filter' => [
                    '<=LOCATION.LEFT_MARGIN' => $arCity['LEFT_MARGIN'],
                    '>=LOCATION.RIGHT_MARGIN' => $arCity['RIGHT_MARGIN'],
                    'LOCATION.TYPE_ID' => 1, //Country
                    'LANGUAGE_ID' => LANGUAGE_ID,
                ]
            ]);
            if($arCountry) {
                $arCountry['ID'] = $arCountry['LOCATION_ID'];
                unset($arCountry['LOCATION_ID']);
            }
        }
    }
}

// Страны по умолчанию
$params = [
    'select' => [
        'LOCATION_ID',
        'NAME',
        'CODE' => 'LOCATION.CODE',
    ],
    'filter' => [
        'LOCATION.TYPE.ID' => 1,
        'LANGUAGE_ID' => LANGUAGE_ID,
    ],
    'order' => [
        'NAME' => 'ASC',
    ],
];

$arResult['JS_DATA']['COUNTRY'] = $arCountry;
$arResult['JS_DATA']['CITY'] = $arCity;

// Страны по умолчанию
$arResult['JS_DATA']['COUNTRY_LIST_DEFAULT'] = [];
//$arResult['JS_DATA']['COUNTRY_LIST_DEFAULT'][$arResult['JS_DATA']['LOCATION']['COUNTRY']['id']] = $arResult['JS_DATA']['LOCATION']['COUNTRY'];
$dbLocations = Bitrix\Sale\Location\Name\LocationTable::getList($params);
while ($arLocation = $dbLocations->fetch()) {
    //if ($arLocation['LOCATION_ID'] != $arResult['JS_DATA']['LOCATION']['COUNTRY']['id']) {
        $arResult['JS_DATA']['COUNTRY_LIST_DEFAULT'][$arLocation['LOCATION_ID']] = [
            'ID' => $arLocation['LOCATION_ID'],
            'NAME' => $arLocation['NAME'],
            'CODE' => $arLocation['CODE'],
        ];
    //}
}

$arProducts = [];
$arOffers = [];
foreach ($arResult['JS_DATA']['GRID']['ROWS'] as $ROW) {
    /*if(empty($ROW['data']['DETAIL_PICTURE_SRC']))*/ {
        $arProducts[] = $ROW['data']['PRODUCT_ID'];
    }
}

$PRODUCT_IBLOCK_ID = 0;
$OFFER_IBLOCK_ID = 0;

$arImgSizes = ['width'=>180, 'height'=>200];

if($arProducts) {
    $nameProp = 'NAME_' . LANGUAGE_UPPER;
    $dbProducts = \CIBlockElement::GetList(
        [],
        [
            'ID' => $arProducts,
        ],
        false,
        false,
        ['ID', 'IBLOCK_ID', 'NAME',]
    );
    $arProducts = [];
    while($arProduct = $dbProducts->GetNext()) {
        $arProduct['PROPERTIES'] = [];
        $OFFER_IBLOCK_ID = $arProduct['IBLOCK_ID'];
        $arProducts[$OFFER_IBLOCK_ID][$arProduct['ID']] = $arProduct;
    }

    foreach ($arProducts as $IBLOCK_ID => &$arList) {
        $codeList = \Bitrix\Catalog\Product\PropertyCatalogFeature::getOfferTreePropertyCodes(
            $IBLOCK_ID,
            ['CODE' => 'Y']
        );
        \CIBlockElement::GetPropertyValuesArray(
            $arList,
            $IBLOCK_ID,
            ['ID' => array_keys($arList)],
            ['CODE' => ['PHOTO', 'CML2_LINK', ...$codeList, $nameProp,]]
        );
    }
    unset($arList);

    $arProducts = array_replace(...$arProducts);

    foreach ($arResult['JS_DATA']['GRID']['ROWS'] as &$ROW) {
        $arProduct = $arProducts[$ROW['data']['PRODUCT_ID']];

        /*if(empty($ROW['data']['DETAIL_PICTURE_SRC']))*/ {
            if($arProduct['PROPERTIES']['PHOTO']['VALUE']) {
                $pictureId = $arProduct['PROPERTIES']['PHOTO']['VALUE'][0];
                if($pictureId) {
                    //$arPicture = \CFile::GetFileArray($pictureId);
                    //$ROW['data']['DETAIL_PICTURE_SRC'] = $arPicture['SRC'];
                    $arPicture = \CFile::ResizeImageGet($pictureId, $arImgSizes);
                    $ROW['data']['DETAIL_PICTURE_SRC'] = $arPicture['src'];
                }
            } else {
                $arOffers[$ROW['data']['PRODUCT_ID']] = $arProduct['PROPERTIES']['CML2_LINK']['VALUE'];
            }
        }

        if($arProduct['PROPERTIES'][$nameProp]['VALUE']) {
            $ROW['data']['NAME'] = $arProduct['PROPERTIES'][$nameProp]['VALUE'];
        } else if($arProduct['NAME']) {
            $ROW['data']['NAME'] = $arProduct['NAME'];
        }

        foreach ($ROW['data']['PROPS'] as $i => $arProp) {
            if(isset($arProduct['PROPERTIES'][$arProp['CODE']])) {
                $ROW['data']['PROPS'][$i]['VALUE_XML_ID'] = $arProduct['PROPERTIES'][$arProp['CODE']]['VALUE_XML_ID'];
            }
        }
    }
    unset($ROW);

    if($arOffers) {
        $dbProducts = \CIBlockElement::GetList(
            [],
            [
                'ID' => $arOffers,
            ],
            false,
            false,
            ['ID', 'IBLOCK_ID', 'NAME',]
        );
        $arProducts = [];
        while($arProduct = $dbProducts->GetNext()) {
            $arProduct['PROPERTIES'] = [];
            $PRODUCT_IBLOCK_ID = $arProduct['IBLOCK_ID'];
            $arProducts[$PRODUCT_IBLOCK_ID][$arProduct['ID']] = $arProduct;
        }

        foreach ($arProducts as $IBLOCK_ID => &$arList) {
            \CIBlockElement::GetPropertyValuesArray(
                $arList,
                $IBLOCK_ID,
                ['ID' => array_keys($arList)],
                ['CODE' => ['PHOTO', $nameProp,]]
            );
        }
        unset($arList);

        $arProducts = array_replace(...$arProducts);

        foreach ($arResult['JS_DATA']['GRID']['ROWS'] as &$ROW) {
            $arProduct = $arProducts[$arOffers[$ROW['data']['PRODUCT_ID']]];

            if(empty($ROW['data']['DETAIL_PICTURE_SRC'])) {
                if($arProduct['PROPERTIES']['PHOTO']['VALUE']) {
                    $pictureId = $arProduct['PROPERTIES']['PHOTO']['VALUE'][0];
                    if($pictureId) {
                        //$arPicture = \CFile::GetFileArray($pictureId);
                        //$ROW['data']['DETAIL_PICTURE_SRC'] = $arPicture['SRC'];
                        $arPicture = \CFile::ResizeImageGet($pictureId, $arImgSizes);
                        $ROW['data']['DETAIL_PICTURE_SRC'] = $arPicture['src'];
                    }
                }
            }

            if($arProduct['PROPERTIES'][$nameProp]['VALUE']) {
                $ROW['data']['NAME'] = $arProduct['PROPERTIES'][$nameProp]['VALUE'];
            } else if($arProduct['NAME']) {
                $ROW['data']['NAME'] = $arProduct['NAME'];
            }
        }
        unset($ROW);
    }
}

// заполнить имя и фамилию из данных пользователя
//foreach ($arResult['JS_DATA']['ORDER_PROP']['properties'] as $id=>$property) {
//    if($property['CODE'] == 'FIRST_NAME' && !$property['VALUE'][0]) {
//        $arResult['JS_DATA']['ORDER_PROP']['properties'][$id]['VALUE'][0] = $GLOBALS['USER']->GetFirstName();
//    }
//    if($property['CODE'] == 'LAST_NAME' && !$property['VALUE'][0]) {
//        $arResult['JS_DATA']['ORDER_PROP']['properties'][$id]['VALUE'][0] = $GLOBALS['USER']->GetLastName();
//    }
//}

$messages = Loc::loadLanguageFile(__FILE__);

$arResult['VUE'] = [];

$arResult['VUE']['SITE_ID'] = SITE_ID;
$arResult['VUE']['SITE_DIR'] = SITE_DIR;
$arResult['VUE']['LANGUAGE_ID'] = LANGUAGE_ID;
$arResult['VUE']['ID'] = 'soa-' . $this->randString(16);

$ajaxUrl = $this->__component->getPath() . '/ajax.php';
//$ajaxUrl .= '?SITE_ID=' . urlencode(SITE_ID);//In the _POST
//This one isn't used
//$ajaxUrl .= '&SITE_TEMPLATE_ID=' . urlencode(SITE_TEMPLATE_ID);
//$ajaxUrl .= '&' . $arParams['ACTION_VARIABLE'] . '=#action#'; //In the _POST

$signer = new \Bitrix\Main\Security\Sign\Signer;
$signedParams = $signer->sign(base64_encode(serialize($arParams)), 'sale.order.ajax');
$arResult['VUE']['SOA_AJAX'] = [
    'url' => $ajaxUrl,
    'post' => [
        'signedParamsString' => $signedParams,
    ],
    'act_var' => $arParams['ACTION_VARIABLE'],
];
$arResult['VUE']['MESSAGES'] = $messages;

$ajaxUrl = $this->GetFolder() . '/ajax.php';
$ajaxUrl .= '?site_id=' . urlencode(SITE_ID);
$ajaxUrl .= '&site_template_id=' . urlencode(SITE_TEMPLATE_ID);
$ajaxUrl .= '&' . bitrix_sessid_get();
$ajaxUrl .= '&' . \Gsv\Internals\GsvBasketSoaAjax::ACTION_NAME . '=#action#';

$component = $this->__component->GetName();
if(strpos($component, 'bitrix:') === 0) {
    $salt = substr($component, 7);
    $salt = str_replace(':', '', $salt);
} else {
    $salt = str_replace(':', '', $component);
}
$signedParams = $signer->sign(base64_encode(serialize($arParams)), $salt);
$arResult['VUE']['AJAX'] = [
    'url' => $ajaxUrl,
    'post' => [
        'signedParamsString' => $signedParams,
    ],
];

$arResult['JS_DATA']['VARIANTS'] = [];
if($OFFER_IBLOCK_ID) {
    $arVariants = [];
    $iblockId = $PRODUCT_IBLOCK_ID;
    $codeList = ['SIZE', 'SIZE_CUP',];
    $codeList = \Bitrix\Catalog\Product\PropertyCatalogFeature::getOfferTreePropertyCodes(
        $OFFER_IBLOCK_ID,
        ['CODE' => 'Y']
    ) ?: $codeList;
    $offers = \CCatalogSku::getOffersList(
        array_keys($arProducts),
        $iblockId,
        [
            'ACTIVE' => 'Y',
            'ACTIVE_DATE' => 'Y',
            'CATALOG_AVAILABLE' => 'Y',
            'CHECK_PERMISSIONS' => 'Y',
            'MIN_PERMISSION' => 'R',
        ],
        ['ID', 'IBLOCK_ID', 'XML_ID', 'NAME', 'AVAILABLE', 'QUANTITY'],
        ['CODE' => $codeList]
    );
    foreach (array_unique($arOffers) as $productId) {
        $variants = [];
        foreach ($offers[$productId] as $offer) {
            $variants[$offer['ID']] = [
                'ID' => $offer['ID'],
                'AVAILABLE' => $offer['AVAILABLE'],
                'QUANTITY' => $offer['QUANTITY'],
                'PROPS' => [],
            ];
            foreach ($codeList as $code) {
                if($offer['PROPERTIES'][$code]['VALUE']) {
                    $variants[$offer['ID']]['PROPS'][$code] = [
                        'VALUE' => $offer['PROPERTIES'][$code]['VALUE'],
                        'VALUE_XML_ID' => $offer['PROPERTIES'][$code]['VALUE_XML_ID'],
                        'CODE' => $code,
                    ];
                }
            }
        }
        $arVariants[$productId] = $variants;
    }
    $arResult['JS_DATA']['SKU_PROPS'] = $codeList;

    foreach ($arResult['JS_DATA']['GRID']['ROWS'] as &$ROW) {
        $variants = $arVariants[$arOffers[$ROW['data']['PRODUCT_ID']]];
        $ROW['data']['VARIANTS'] = $variants;
    }
    unset($ROW);
}
if(is_array($arParams['SELF_DELIVERIES']) && $arParams['SELF_DELIVERIES']) {
    foreach ($arResult['JS_DATA']['DELIVERY'] as &$arDelivery) {
        $arDelivery['IS_SELF_DELIVERY'] = in_array($arDelivery['ID'], $arParams['SELF_DELIVERIES']);
    }
    unset($arDelivery);
}
