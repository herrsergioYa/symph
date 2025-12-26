<? use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;
use Gsv\Helpers\AndBr;

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

if($locationId == '' || empty($arCountry)
    || $arCountry['NAME'] != 'Россия' && $arCountry['NAME'] != 'Russian Federation') {
    $arCountry = \Bitrix\Sale\Location\Name\LocationTable::getRow([
        'select' => [
            'LOCATION_ID',
            'NAME',
            'CODE' => 'LOCATION.CODE',
            'LOCATION_TYPE' => 'LOCATION.TYPE_ID',
        ],
        'filter' => [
            'NAME' => ['Россия', 'Russian Federation',],
            'LOCATION.TYPE_ID' => 1, //Country
            'LANGUAGE_ID' => LANGUAGE_ID,
        ]
    ]);
    if($arCountry) {
        $locationId = $arCountry['LOCATION_ID'];
        $arCountry['ID'] = $arCountry['LOCATION_ID'];
        unset($arCountry['LOCATION_ID']);
    } else {
        $locationId = '';
        $arCountry = false;
    }
    $arCity = false;
    foreach ($arResult['ORDER_PROP']['USER_PROPS_Y'] as &$arProperty) {
        if($arProperty['IS_LOCATION'] == 'Y') {
            $arProperty['VALUE'] = $locationId;
        }
    }
    unset($arProperty);
}

$arResult['JS_DATA']['COUNTRY'] = $arCountry;
$arResult['JS_DATA']['CITY'] = $arCity;

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
        'LOCATION_ID' => $arCountry['ID'],
    ],
    'order' => [
        'NAME' => 'ASC',
    ],
];

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
$arOfferToProduct = [];
foreach ($arResult['JS_DATA']['GRID']['ROWS'] as $ROW) {
    /*if(empty($ROW['data']['DETAIL_PICTURE_SRC']))*/ {
        $arOffers[] = $ROW['data']['PRODUCT_ID'];
    }
}

$PRODUCT_IBLOCK_ID = 0;
$OFFER_IBLOCK_ID = 0;
$nameProp = 'NAME_' . LANGUAGE_UPPER;

$arImgSizes = ['width'=>180, 'height'=>200];

if($arOffers) {
    $dbOffers = \CIBlockElement::GetList(
        [],
        [
            'ID' => $arOffers,
        ],
        false,
        false,
        ['ID', 'IBLOCK_ID', 'NAME', 'TYPE', 'DETAIL_PAGE_URL',]
    );
    $arOffers = [];
    while($arOffer = $dbOffers->GetNext()) {
        $arOffer['PROPERTIES'] = [];
        $OFFER_IBLOCK_ID = $arOffer['IBLOCK_ID'];
        $arOffers[$OFFER_IBLOCK_ID][$arOffer['ID']] = $arOffer;
    }

    foreach ($arOffers as $IBLOCK_ID => &$arList) {
        $codeList = \Bitrix\Catalog\Product\PropertyCatalogFeature::getOfferTreePropertyCodes(
            $IBLOCK_ID,
            ['CODE' => 'Y']
        ) ?: [];
        \CIBlockElement::GetPropertyValuesArray(
            $arList,
            $IBLOCK_ID,
            ['ID' => array_keys($arList)]//,
            //['CODE' => [/*'PHOTO', 'MORE_PHOTO', */'CML2_LINK', ...$codeList, $nameProp,]]
        );
    }
    unset($arList);

    $arOffers = array_replace(...$arOffers);

    $list = [];

    foreach ($arResult['JS_DATA']['GRID']['ROWS'] as &$ROW) {
        $arOffer = $arOffers[$ROW['data']['PRODUCT_ID']];

        if($arOffer['TYPE'] != \Bitrix\Catalog\ProductTable::TYPE_OFFER) {
            $arProducts[$ROW['data']['PRODUCT_ID']] = $arOffer;
            unset($arOffers[$ROW['data']['PRODUCT_ID']]);
        } else if($arOffer['PROPERTIES']['CML2_LINK']['VALUE']) {
            $arOfferToProduct[$ROW['data']['PRODUCT_ID']] = $arOffer['PROPERTIES']['CML2_LINK']['VALUE'];
        } else {
            unset($arOffers[$ROW['data']['PRODUCT_ID']]);
        }

        if($arOffer['PROPERTIES'][$nameProp]['VALUE']) {
            $ROW['data']['NAME'] = $arOffer['PROPERTIES'][$nameProp]['VALUE'];
        } else if($arOffer['NAME']) {
            $ROW['data']['NAME'] = $arOffer['NAME'];
        }

        foreach ($ROW['data']['PROPS'] as $i => $arProp) {
            if(isset($arOffer['PROPERTIES'][$arProp['CODE']])) {
                $ROW['data']['PROPS'][$i]['VALUE_XML_ID'] = $arOffer['PROPERTIES'][$arProp['CODE']]['VALUE_XML_ID'];
            }
        }

        $list[] = [
            'id' => $ROW['data']['PRODUCT_ID'],
            'price' => $ROW['data']['PRICE'],
            'quantity' => $ROW['data']['QUANTITY'],
        ];
    }
    unset($ROW);

    if($arOfferToProduct) {
        $dbProducts = \CIBlockElement::GetList(
            [],
            [
                'ID' => array_unique($arOfferToProduct),
            ],
            false,
            false,
            ['ID', 'IBLOCK_ID', 'NAME','DETAIL_PAGE_URL',]
        );
        //$arProducts = [];//May be not-empty.
        while($arProduct = $dbProducts->GetNext()) {
            $arProduct['PROPERTIES'] = [];
            $PRODUCT_IBLOCK_ID = $arProduct['IBLOCK_ID'];
            $arProducts[$PRODUCT_IBLOCK_ID][$arProduct['ID']] = $arProduct;
        }

        foreach ($arProducts as $IBLOCK_ID => &$arList) {
            \CIBlockElement::GetPropertyValuesArray(
                $arList,
                $IBLOCK_ID,
                ['ID' => array_keys($arList)]//,
               // ['CODE' => ['PHOTO', 'MORE_PHOTO', $nameProp, 'COLOR',]]
            );
        }
        unset($arList);

        $arProducts = array_replace(...$arProducts);

        foreach ($arResult['JS_DATA']['GRID']['ROWS'] as &$ROW) {
            $arProduct = $arProducts[$arOfferToProduct[$ROW['data']['PRODUCT_ID']]];

            /*if(empty($ROW['data']['DETAIL_PICTURE_SRC'])) {
                if($arProduct['PROPERTIES']['PHOTO']['VALUE']) {
                    $pictureId = $arProduct['PROPERTIES']['PHOTO']['VALUE'][0];
                    if($pictureId) {
                        //$arPicture = \CFile::GetFileArray($pictureId);
                        //$ROW['data']['DETAIL_PICTURE_SRC'] = $arPicture['SRC'];
                        $arPicture = \CFile::ResizeImageGet($pictureId, $arImgSizes);
                        $ROW['data']['DETAIL_PICTURE_SRC'] = $arPicture['src'];
                    }
                }
            }*/

            if($arProduct['PROPERTIES'][$nameProp]['VALUE']) {
                $ROW['data']['NAME'] = $arProduct['PROPERTIES'][$nameProp]['VALUE'];
            } else if($arProduct['NAME']) {
                $ROW['data']['NAME'] = $arProduct['NAME'];
            }
        }
        unset($ROW);

        foreach ($arOffers as $arOffer) {
            $arProducts[$arOfferToProduct[$arOffer['ID']]]['OFFERS'][$arOffer['ID']] = $arOffer;
        }

        $arImgSizes = ['width' => 90, 'height' => 122];
        \Gsv\Helpers\AndBr::modifyProducts($arProducts, $arImgSizes);

        foreach ($arResult['JS_DATA']['GRID']['ROWS'] as &$ROW) {
            $offerId = $ROW['data']['PRODUCT_ID'];
            if(isset($arOfferToProduct[$offerId])) { //OFFER
                $productId = $arOfferToProduct[$offerId];
                $arProduct = $arProducts[$productId];
                $ROW['data']['PRODUCT'] = $arProduct;
                unset($ROW['data']['PRODUCT']['OFFERS']);
                $ROW['data']['OFFER'] = $arProduct['OFFERS'][$offerId];
            } else { //PRODUCT
                $productId = $arOfferToProduct[$offerId];
                $arProduct = $arProducts[$productId];
                $ROW['data']['PRODUCT'] = $arProduct;
                unset($ROW['data']['PRODUCT']['OFFERS']);//Just in case. It shouldn't be there.
            }
            $ROW['data']['NAME'] = $arProduct['NAME'];
            $ROW['data']['DETAIL_PAGE_URL'] = $arProduct['DETAIL_PAGE_URL'];
            $ROW['data']['DETAIL_PICTURE_SRC'] = $arProduct['PICTURE']['SRC'];
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
    'sessid' => bitrix_sessid(),
];

if(is_array($arParams['SELF_DELIVERIES']) && $arParams['SELF_DELIVERIES']) {
    foreach ($arResult['JS_DATA']['DELIVERY'] as &$arDelivery) {
        $arDelivery['IS_SELF_DELIVERY'] = in_array($arDelivery['ID'], $arParams['SELF_DELIVERIES']);
    }
    unset($arDelivery);
}

if(is_array($arParams['BLACKLISTED_DELIVERIES']) && $arParams['BLACKLISTED_DELIVERIES']) {
    $reCheck = false;
    foreach ($arResult['JS_DATA']['DELIVERY'] as $i => $arDelivery) {
        if(in_array($arDelivery['ID'], $arParams['BLACKLISTED_DELIVERIES'])) {
            if($arDelivery['CHECKED'] == 'Y') {
                $reCheck = true;
            }
            unset($arResult['JS_DATA']['DELIVERY'][$i]);
        }
    }
    $arResult['JS_DATA']['DELIVERY'] = array_values($arResult['JS_DATA']['DELIVERY']);
    if($reCheck && $arResult['JS_DATA']['DELIVERY']) {
        $arResult['JS_DATA']['DELIVERY'][0]['CHECKED'] = 'Y';
    }
}

if(is_array($arParams['BLACKLISTED_PAY_SYSTEMS']) && $arParams['BLACKLISTED_PAY_SYSTEMS']) {
    $reCheck = false;
    foreach ($arResult['JS_DATA']['PAY_SYSTEM'] as $i => $arPaySystem) {
        if(in_array($arPaySystem['ID'], $arParams['BLACKLISTED_PAY_SYSTEMS'])) {
            if($arPaySystem['CHECKED'] == 'Y') {
                $reCheck = true;
            }
            unset($arResult['JS_DATA']['PAY_SYSTEM'][$i]);
        }
    }
    $arResult['JS_DATA']['PAY_SYSTEM'] = array_values($arResult['JS_DATA']['PAY_SYSTEM']);
    if($reCheck && $arResult['JS_DATA']['PAY_SYSTEM']) {
        $arResult['JS_DATA']['PAY_SYSTEM'][0]['CHECKED'] = 'Y';
    }
}

$arResult['ECOMM'] = \Gsv\Helpers\Ecommerce::getCheckoutEvent(NULL, 2);
