<? use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;

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

$defaultCountryId = array_slice($arParams['COUNTRY_LIST'], 0, 1)[0];
$arLocation = \Gsv\Helpers\Usha::parseLocation($location, $defaultCountryId);
$arResult['JS_DATA'] = array_replace($arResult['JS_DATA'], $arLocation);
$arCountry = $arLocation['COUNTRY'];

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
        'LOCATION_ID' => $arParams['COUNTRY_LIST'],
    ],
    'order' => [
        'NAME' => 'ASC',
    ],
];
$arResult['JS_DATA']['COUNTRY_LIST'] = [];
$dbLocations = Bitrix\Sale\Location\Name\LocationTable::getList($params);
while ($arLocation = $dbLocations->fetch()) {
    $arResult['JS_DATA']['COUNTRY_LIST'][$arLocation['LOCATION_ID']] = [
        'ID' => $arLocation['LOCATION_ID'],
        'NAME' => $arLocation['NAME'],
        'CODE' => $arLocation['CODE'],
        //'SORT' => 100 * (1 + count($arResult['JS_DATA']['COUNTRY_LIST'])), //To restore the order after the JSON-form
    ];
}
$arMap = array_flip(array_values($arParams['COUNTRY_LIST']));
uksort($arResult['JS_DATA']['COUNTRY_LIST'], function (&$a, &$b) use(&$arMap) {
    return min(1, $arMap[$a]) <=> min(1, $arMap[$b]);
});
$arResult['JS_DATA']['COUNTRY_LIST'] = array_values($arResult['JS_DATA']['COUNTRY_LIST']);
/*$sort = 100;
foreach ($arResult['JS_DATA']['COUNTRY_LIST'] as $arCountry) {
    $arCountry['SORT'] = $sort;
    $sort += 100;
}*/

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

$arResult['JS_DATA']['SKU_PROPS'] = \Gsv\Helpers\Usha::getSkuPropValues(CATALOG_OFFERS_IBLOCK_ID, true);

$arColorProp = \CIBlockProperty::GetList(
    [],
    [
        'IBLOCK_ID' => CATALOG_IBLOCK_ID,
        'CODE' => 'COLOR',
    ]
)->Fetch();
if($arColorProp) {
    $dbColors = \CIBlockElement::GetList(
        ['SORT' => 'ASC'],
        [
            'IBLOCK_ID' => $arColorProp['LINK_IBLOCK_ID'],
        ],
        false,
        false,
        ['ID', 'IBLOCK_ID', 'NAME', 'SORT']
    );
    $arColorProp['VALUES'] = [];
    $arColorProp['VALUES_COUNT'] = 0;
    $arColorProp['VALUES_ASSOC'] = [];
    while($arColor = $dbColors->Fetch()) {
        $arValue = [
            'ID' => $arColor['ID'],
            'NAME' => $arColor['NAME'],
            'SORT' => $arColor['SORT'],
            'PICT' => false,
        ];
        $arColorProp['VALUES'][] = $arValue;
        $arColorProp['VALUES_COUNT'] ++;
        $arColorProp['VALUES_ASSOC'][$arValue['ID']] = $arValue;
    }
    $arColorProp['VALUES_ASSOC'][''] = [
        'ID' => '',
        'NAME' => 'N/A',
        'SORT' => 99999999,
        'PICT' => false,
    ];
    $arResult['JS_DATA']['SKU_PROPS'] = array_merge(
        [$arColorProp['CODE'] => $arColorProp],
        $arResult['JS_DATA']['SKU_PROPS'],
    );
}

$arAltSkuProps = \Gsv\Helpers\Usha::ALT_SKU_PROPS;
$arResult['JS_DATA']['SKU_PROPS'] = [
    'PROP_CODES' => array_keys($arResult['JS_DATA']['SKU_PROPS']),
    'PROP_VALS' => $arResult['JS_DATA']['SKU_PROPS'],
];

$arProducts = [];
foreach ($arResult['JS_DATA']['GRID']['ROWS'] as &$ROW) {
    $arProducts[] = &$ROW['data'];
}
unset($ROW);

\Gsv\Helpers\Usha::modifyBasketProducts($arProducts);

foreach ($arResult['JS_DATA']['GRID']['ROWS'] as &$ROW) {

    $arRow = &$ROW['data'];
    $arProduct = &$ROW['data']['PRODUCT'];
    if ($arRow['PHOTO']['SRC']) {
        $ROW['data']['DETAIL_PICTURE_SRC'] = $arRow['PHOTO']['SRC'];
    }
    if($ROW['data']['OFFER']) {
        $arOffer = &$ROW['data']['OFFER'];
        $ROW['data']['PROPS'] = $arOffer['PROPS'];
        foreach ($arAltSkuProps as $code) {
            $ROW['data']['PROPS'][$code] = ($arProduct['PROPERTIES'][$code]['~VALUE_ENUM_ID'] ?: $arProduct['PROPERTIES'][$code]['VALUE_ENUM_ID'])
                ?: ($arProduct['PROPERTIES'][$code]['~VALUE'] ?: $arProduct['PROPERTIES'][$code]['VALUE']);
        }
    }
    $ROW['data']['PRODUCT_NAME'] = $arProduct['NAME'];
    if($arProduct['OFFERS']) {
        $arRow['OFFERS'] = [];
        foreach ($arProduct['OFFERS'] as $OFFER) {
            if($OFFER['ACTIVE'] == 'Y' && $OFFER['AVAILABLE'] == 'Y' || $OFFER['ID'] == $arRow['PRODUCT_ID']) {
                foreach ($arAltSkuProps as $code) {
                    $OFFER['PROPS'][$code] = ($arProduct['PROPERTIES'][$code]['~VALUE_ENUM_ID'] ?: $arProduct['PROPERTIES'][$code]['VALUE_ENUM_ID'])
                        ?: ($arProduct['PROPERTIES'][$code]['~VALUE'] ?: $arProduct['PROPERTIES'][$code]['VALUE']);
                }
                $arRow['OFFERS'][] = [
                    'ID' => $OFFER['ID'],
                    'PROPS' => $OFFER['PROPS'],
                    'QTY' => (int)$OFFER['QUANTITY'] - (int)$arRow['QUANTITY_RESERVED'],
                ];
            }
        }
    }

    if($arProduct['PROPERTIES']['COLORS']['VALUE']) {

        $arAltProducts = [];
        $dbAltProducts = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => $arProduct['PROPERTIES']['COLORS']['LINK_IBLOCK_ID'],
                'ID' => $arProduct['PROPERTIES']['COLORS']['VALUE'],
                '!ID' => $arProduct['ID'],
            ],
            false,
            false,
            ['ID', 'PROPERTY_COLOR']
        );
        while ($arAltProduct = $dbAltProducts->Fetch()) {
            $arAltProducts[$arAltProduct['ID']] = [
                'ID' => $arAltProduct['ID'],
                'OFFERS' => [],
                'PROPS' => [
                    'COLOR' => $arAltProduct['PROPERTY_COLOR_VALUE']
                ],
            ];
        }

        if($arAltProducts) {
            $arOffers = [];
            $arAltCatalog = \CCatalogSku::GetInfoByProductIBlock($arProduct['PROPERTIES']['COLORS']['LINK_IBLOCK_ID']);
            $dbOffers = \CIBlockElement::GetList(
                [],
                [
                    'IBLOCK_ID' => $arAltCatalog['IBLOCK_ID'],
                    'PROPERTY_CML2_LINK' => array_keys($arAltProducts),
                ],
                false,
                false,
                [
                    'ID', 'IBLOCK_ID', 'IBLOCK_SECTION_ID', 'NAME', 'CODE', 'PROPERTY_CML2_LINK', 'TYPE',
                    'PREVIEW_PICTURE', 'DETAIL_PICTURE', 'DETAIL_PAGE_URL',
                    'ACTIVE', 'AVAILABLE', 'QUANTITY', 'QUANTITY_RESERVED',
                ]
            );
            while($arOffer = $dbOffers->GetNext()) {
                $arOffer['PROPERTIES'] = [];
                $arAltProducts[$arOffer['PROPERTY_CML2_LINK_VALUE']]['OFFERS'][$arOffer['ID']] = $arOffer;
                $arOffers[$arOffer['ID']] = &$arAltProducts[$arOffer['PROPERTY_CML2_LINK_VALUE']]['OFFERS'][$arOffer['ID']];
            }
            if($arOffers) {
                \CIBlockElement::GetPropertyValuesArray(
                    $arOffers,
                    $arAltCatalog['IBLOCK_ID'],
                    ['ID' => array_keys($arOffers)],
                    []
                );
            }
            unset($arOffers);
            foreach ($arAltProducts as $arAltProduct) {
                foreach ($arAltProduct['OFFERS'] as $OFFER) {
                    if ($OFFER['ACTIVE'] == 'Y' && $OFFER['AVAILABLE'] == 'Y') {
                        $OFFER['PROPS'] = [];
                        foreach ($arResult['JS_DATA']['SKU_PROPS']['PROP_CODES'] as $code) {
                            if(in_array($code, $arAltSkuProps)) {
                                continue;
                            }
                            $OFFER['PROPS'][$code] = ($OFFER['PROPERTIES'][$code]['~VALUE_ENUM_ID'] ?: $OFFER['PROPERTIES'][$code]['VALUE_ENUM_ID'])
                                ?: ($OFFER['PROPERTIES'][$code]['~VALUE'] ?: $OFFER['PROPERTIES'][$code]['VALUE']);
                        }
                        foreach ($arAltSkuProps as $code) {
                            $OFFER['PROPS'][$code] = $arAltProduct['PROPS'][$code];
                        }
                        $arRow['OFFERS'][] = [
                            'ID' => $OFFER['ID'],
                            'PROPS' => $OFFER['PROPS'],
                            'QTY' => (int)$OFFER['QUANTITY'] - (int)$arRow['QUANTITY_RESERVED'],
                        ];
                    }
                }
            }
        }
    }

    //For security
    unset($ROW['data']['PRODUCT']);
    unset($ROW['data']['OFFER']);

    unset($arRow, $arProduct, $arOffer);
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

$arPaySystems = [];
foreach ($arResult['JS_DATA']['PAY_SYSTEM'] as $arPaySystem) {
    $arPaySystems[] = $arPaySystem['ID'];
}
if($arPaySystems) {
    $arPaySystems = \Gsv\Helpers\Usha::getElementsOfIbockByXmlId($arPaySystems, SOA_HINTS_IBLOCK_ID);

    foreach ($arResult['JS_DATA']['PAY_SYSTEM'] as &$arPaySystem) {
        if($arPaySystems[$arPaySystem['ID']]) {
            $arPaySystem['HINT'] = $arPaySystems[$arPaySystem['ID']];
        }
    }
    unset($arPaySystem);
}

if($USER->IsAuthorized()) {
    $arResult['JS_DATA']['USER'] = \Bitrix\Main\UserTable::getRow([
        'filter' => [
            'ID' => $USER->GetID(),
        ],
        'select' => ['ID', 'LOGIN', 'NAME', 'LAST_NAME', 'SECOND_NAME'],
    ]) ?: false;
    /*
    if($arResult['JS_DATA']['USER']) {
        $arResult['JS_DATA']['USER']['ACCOUNT'] = \CSaleUserAccount::GetByUserID(
            $arResult['JS_DATA']['USER']['ID'],
            $arResult['BASE_LANG_CURRENCY']
        );
        if(empty($arResult['JS_DATA']['USER']['ACCOUNT'])) {
            \CSaleUserAccount::Add([
                'USER_ID' => $arResult['JS_DATA']['USER']['ID'],
                'CURRENCY' => $arResult['BASE_LANG_CURRENCY'],
                'CURRENT_BUDGET' => 0,
            ]);
            $arResult['JS_DATA']['USER']['ACCOUNT'] = \CSaleUserAccount::GetByUserID(
                $arResult['JS_DATA']['USER']['ID'],
                $arResult['BASE_LANG_CURRENCY']
            );
        }

        if($arResult['JS_DATA']['USER']['ACCOUNT']) {
            $arResult['JS_DATA']['USER']['ACCOUNT']['CURRENT_BUDGET'] = +$arResult['JS_DATA']['USER']['ACCOUNT']['CURRENT_BUDGET'];
        }
    }
    */
    $arResult['JS_DATA']['USER']['CURRENCY'] = $arResult['BASE_LANG_CURRENCY'];
} else {
    $arResult['JS_DATA']['USER'] = false;
}
