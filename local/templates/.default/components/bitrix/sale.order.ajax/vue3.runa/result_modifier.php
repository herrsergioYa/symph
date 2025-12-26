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

$location = '';
foreach ($arResult['JS_DATA']['ORDER_PROP']['properties'] as $arProperty) {
    if($arProperty['IS_LOCATION'] == 'Y') {
        //$location = $arProperty['VALUE'] ?: '';
        $location = $arProperty['VALUE'] ?: '';
    } else {

    }
}

$arCountry = $arCity = false;
if($location != '') {
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
            'LOCATION.CODE' => $location,
            //'LOCATION.ID' => $location,
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

$arResult['JS_DATA']['COUNTRY'] = $arCountry;
$arResult['JS_DATA']['CITY'] = $arCity;

// Список стран
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
$arResult['JS_DATA']['COUNTRY_LIST'] = [];
$dbLocations = Bitrix\Sale\Location\Name\LocationTable::getList($params);
while ($arLocation = $dbLocations->fetch()) {
    $arResult['JS_DATA']['COUNTRY_LIST'][/*$arLocation['LOCATION_ID']*/] = [
        'ID' => $arLocation['LOCATION_ID'],
        'NAME' => $arLocation['NAME'],
        'CODE' => $arLocation['CODE'],
    ];
}

$arResult['JS_DATA']['CITIES'] = [];
if($arCountry) {
    $ob = new \Gsv\Internals\GsvBasketSoaAjax();
    foreach ($ob->listCitiesAction([
            'countryId' => $arCountry['ID'],
            'q' => '',
        ]) as $arCity) {
        $arResult['JS_DATA']['CITIES'][] = [
            'ID' => $arCity['id'],
            'CODE' => $arCity['code'],
            'NAME' => $arCity['text'],
            'REGION' => $arCity['group'],
            'FULL_NAME' => $arCity['text'] . ($arCity['group'] ? ', ' . $arCity['group'] : ''),
        ];
    }
}

$arResult['JS_DATA']['SKU_PROPS'] = \Gsv\Helpers\Runa::getSkuProps(CATALOG_OFFERS_IBLOCK_ID);

$arProducts = [];
foreach ($arResult['JS_DATA']['GRID']['ROWS'] as &$ROW) {
    $arProducts[] = &$ROW['data'];
}
unset($ROW);

\Gsv\Helpers\Runa::modifyBasketProducts($arProducts);

foreach ($arResult['JS_DATA']['GRID']['ROWS'] as &$ROW) {
    $arProduct = $ROW['data']['PRODUCT'];
    if ($arProduct['PHOTO']['SRC']) {
        $ROW['data']['DETAIL_PICTURE_SRC'] = $arProduct['PHOTO']['SRC'];
    }
    $arOffer = $ROW['data']['OFFER'];
    $ROW['data']['PROP_VALS'] = $arOffer['PROPS'];
    $ROW['data']['PRODUCT_NAME'] = $ROW['data']['PRODUCT']['NAME'];
    //For security
    unset($ROW['data']['PRODUCT']);
    unset($ROW['data']['OFFER']);
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

$arResult['VUE'] = [];

$arResult['VUE']['MESSAGES'] = Loc::loadLanguageFile(__FILE__);

$arResult['VUE']['SITE_ID'] = SITE_ID;
$arResult['VUE']['SITE_DIR'] = SITE_DIR;
$arResult['VUE']['LANGUAGE_ID'] = LANGUAGE_ID;
$arResult['VUE']['ID'] = 'soa-' . $this->randString(16);

$ajaxUrl = $this->__component->getPath() . '/ajax.php';

$signer = new \Bitrix\Main\Security\Sign\Signer;
$signedParams = $signer->sign(base64_encode(serialize($arParams)), 'sale.order.ajax');
$arResult['VUE']['SOA_AJAX'] = [
    'url' => $ajaxUrl,
    'post' => [
        'signedParamsString' => $signedParams,
    ],
    'act_var' => $arParams['ACTION_VARIABLE'],
];

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

if(is_array($arParams['SELF_DELIVERIES']) && $arParams['SELF_DELIVERIES']) {
    foreach ($arResult['JS_DATA']['DELIVERY'] as &$arDelivery) {
        $arDelivery['IS_SELF_DELIVERY'] = in_array($arDelivery['ID'], $arParams['SELF_DELIVERIES']);
    }
    unset($arDelivery);
}
