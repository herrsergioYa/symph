<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

\Bitrix\Main\Loader::requireModule('sale');

$locationProp = 'LOCATION';

$arProfileIds = [];
global $USER;
$USER_ID = $USER->GetID();
foreach ($arResult['PROFILES'] as &$arProfile) {
    $arProfile['PROPERTIES'] = [];
    $arProfileIds[$arProfile['ID']] = &$arProfile;
    $USER_ID = $arProfile['USER_ID'];
}
unset($arProfile);

$dbProps = \Bitrix\Sale\Internals\UserPropsValueTable::getList([
    'filter' => [
        '=USER_PROPS_ID' => array_keys($arProfileIds),
    ],
    'select' => [
        'ID', 'USER_PROPS_ID', /*'PROPERTY_' => */'PROPERTY.*', 'VALUE',
    ],
]);
while($obProp = $dbProps->fetchObject()) {
    $arProp = $obProp->collectValues();
    $arProp['PROPERTY'] = $obProp->getProperty()->collectValues();
    $code = $arProp['PROPERTY']['CODE'];
    $profileId = $arProp['USER_PROPS_ID'];
    if(!array_key_exists($profileId, $arProfileIds)) {
        //$arProfileProps[$profileId] = [];
    }
    $arProfileIds[$profileId]['PROPERTIES'][$code] = $arProp;
}
unset($arProfileIds);

$arPhones = \Bitrix\Main\UserTable::getRow([
    'filter' => [
        '=ID' => $USER_ID,
    ],
    'select' => [
        'PERSONAL_PHONE', 'PERSONAL_MOBILE', 'WORK_PHONE', 'AUTH_PHONE' => 'PHONE_AUTH.PHONE_NUMBER',
    ]
]);
$arResult['PHONE'] = '';
if($arPhones) {
    foreach ($arPhones as $phone) {
        if($phone) {
            $phone = NormalizePhone($phone);
            if($phone) {
                $phone = preg_replace('/^[+]?7(\d{3})(\d{3})(\d{2})(\d{2})$/', '+7 $1 $2 $3 $4', $phone);
                if($phone) {
                    $arResult['PHONE'] = $phone;
                    break;
                }
            }
        }
    }
}

$arResult['PERSON_TYPE'] = \Bitrix\Sale\Internals\PersonTypeTable::getRow([
    'filter' => [
       // '=LID' => SITE_ID,
    ],
    'select' => ['*'],
]);

$arResult['PROPERTIES'] = [];

if($arResult['PERSON_TYPE']) {
    $dbProps = \Bitrix\Sale\Internals\OrderPropsTable::getList([
        'filter' => [
            'USER_PROPS' => true,
            '=PERSON_TYPE_ID' => $arResult['PERSON_TYPE']['ID'],
        ],
        'select' => [
            '*',
        ],
    ]);
    while ($obProp = $dbProps->fetchObject()) {
        $arProp = $obProp->collectValues();
        $code = $arProp['CODE'];
        $arResult['PROPERTIES'][$code] = $arProp;
    }
}

/*$arProperties = [];
foreach ([
    'USER_NAME', 'USER_LAST_NAME',
     'COUNTRY', 'CITY', 'ADDR_DADATA',
     'ADDR_CITY', 'ADDR_STREET', 'ADDR_BUILDING', 'ADDR_HOUSING', 'ADDR_APARTAMENT',
     'ZIP', 'PHONE'
    ] as $propName) {
    if(isset($arResult['PROPERTIES'][$propName])) {
        $arProperties[$propName] = $arResult['PROPERTIES'][$propName];
    } else {
        $arProperties[$propName] = [
            'ID' => ~count($arProperties),
            'CODE' => $propName,
        ];
    }
}
$arResult['PROPERTIES'] = $arProperties;*/

$arLocations = [];

foreach($arResult['PROFILES'] as &$arProfile) {

    $arLocation = [
        //'LOCATION_ID' => 0,
        'CITY_ID' => 0,
        'CITY' => '',
        'COUNTRY_ID' => 0,
        'COUNTRY' => '',
    ];

    if($arProfile['PROPERTIES'][$locationProp] && $arProfile['PROPERTIES'][$locationProp]['VALUE']) {

        $dbCity = \Bitrix\Sale\Location\Name\LocationTable::getList([
            'filter' => [
                'LOCATION.TYPE_ID' => 5,
                //'LANGUAGE_ID' => LANGUAGE_ID,
                'LOCATION.CODE' => $arProfile['PROPERTIES'][$locationProp]['VALUE'],
            ],
            'select' => [
                'CITY_ID' => 'LOCATION.ID',
                'CITY' => 'NAME',
                'LEFT_MARGIN' => 'LOCATION.LEFT_MARGIN',
                'RIGHT_MARGIN' => 'LOCATION.RIGHT_MARGIN',
                'LANGUAGE_ID',
            ],
        ]);
        $LANGUAGE_ID = strtolower(LANGUAGE_ID);

        while($arCity = $dbCity->fetch()) {
            if($arLocation['CITY_ID'] && strtolower($arCity['LANGUAGE_ID']) != $LANGUAGE_ID) {
                continue;
            }
            $arLocation = array_merge($arLocation, $arCity);

            $dbCountry = \Bitrix\Sale\Location\LocationTable::getList([
                'filter' => [
                    'TYPE_ID' => 1,
                    //'NAME.LANGUAGE_ID' => LANGUAGE_ID,
                    '<LEFT_MARGIN' => $arCity['LEFT_MARGIN'],
                    '>RIGHT_MARGIN' => $arCity['RIGHT_MARGIN'],
                ],
                'select' => [
                    'COUNTRY_IDX' => 'ID',
                    'COUNTRY' => 'NAME.NAME',
                    'LANGUAGE_ID' => 'NAME.LANGUAGE_ID',
                ],
            ]);

            while($arCountry = $dbCountry->fetch()) {
                if($arLocation['COUNTRY_ID'] && strtolower($arCountry['LANGUAGE_ID']) != $LANGUAGE_ID) {
                    continue;
                }
                $arCountry['COUNTRY_ID'] = $arCountry['COUNTRY_IDX'];
                unset($arCountry['COUNTRY_IDX']);

                $arLocation = array_merge($arLocation, $arCountry);
            }
        }
        if(empty($arLocation['CITY_ID'])) {
            $dbCountry = \Bitrix\Sale\Location\Name\LocationTable::getList([
                'filter' => [
                    'LOCATION.TYPE_ID' => 1,
                    //'LANGUAGE_ID' => LANGUAGE_ID,
                    'LOCATION.ID' => $arProfile['PROPERTIES'][$locationProp]['VALUE'],
                ],
                'select' => [
                    'COUNTRY_ID' => 'LOCATION.ID',
                    'COUNTRY' => 'NAME',
                    'LANGUAGE_ID',
                ],
            ]);
            while($arCountry = $dbCountry->fetch()) {
                if($arLocation['COUNTRY_ID'] && strtolower($arCountry['LANGUAGE_ID']) != $LANGUAGE_ID) {
                    continue;
                }
                $arLocation = array_merge($arLocation, $arCountry);
            }
        }

    }

    $arProfile['LOCATION'] = $arLocation;
}
unset($arProfile);