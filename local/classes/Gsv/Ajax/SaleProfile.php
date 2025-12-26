<?php

namespace Gsv\Ajax;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;

\Bitrix\Main\Loader::requireModule('sale');

class SaleProfile extends \Gsv\Bitrix\JsonAjaxController
{
    public function getAllCitiesStartingWithAction($arParams)
    {
        $arResult = [];

        $cache = \Bitrix\Main\Data\Cache::createInstance();
        if ($cache->initCache(604800, 'selectAddrCity_'.LANGUAGE_ID.'_'.md5($arParams['country_id'].'|'.mb_strtoupper($arParams['q'])))) {
            $arResult = $cache->getVars();
        } elseif ($cache->startDataCache()) {

            $country_id = $arParams['country_id'];
            if($country_id > 0) {
                $city = $arParams['q'];
                $city = mb_strtoupper($city);

                $params = [
                    'select' => [
                        'LOCATION_ID',
                        'NAME',
                        'CODE' => 'LOCATION.CODE',
                    ],
                    'filter' => [
                        '%=NAME_UPPER' => $city . '%',
                        'LOCATION.TYPE.ID' => 5,
                        ['LOGIC' => 'OR',
                            'LOCATION.PARENT_ID' => $country_id,
                            'LOCATION.PARENT.PARENT_ID' => $country_id,
                            'LOCATION.PARENT.PARENT.PARENT_ID' => $country_id,
                            'LOCATION.PARENT.PARENT.PARENT.PARENT_ID' => $country_id,
                        ],
                        'LANGUAGE_ID' => LANGUAGE_ID,
                    ],
                    'order' => [
                        'NAME' => 'ASC',
                        'ID' => 'DESC',
                    ],
                ];

                $dbCities = \Bitrix\Sale\Location\Name\LocationTable::getList($params);
                while ($arCity = $dbCities->fetch()) {
                    $arResult[] = [
                        'id' => $arCity['LOCATION_ID'],
                        'text' => $arCity['NAME'],
                        'code' => $arCity['CODE'],
                    ];
                }
            }

            $cache->endDataCache($arResult);
        }

        return $arResult;
    }

    public function getAllAddressesStartingWithAction($arParams)
    {
        $arResult = [];

        $country = $arParams['country'];
        $city = $arParams['city'];
        $address = $arParams['q'];

        $DADATA_TOKEN = Option::get('gsv.dadata', 'dadata_token', '');
        $DADATA_SECRET = Option::get('gsv.dadata', 'dadata_secret', '');

        $dadata = new \Dadata\Dadata($DADATA_TOKEN, $DADATA_SECRET);
        $dadata->init();

        if(LANGUAGE_ID !== 'ru') {
            $cache = \Bitrix\Main\Data\Cache::createInstance();
            if ($cache->initCache(604800, 'selectAddrCountryCity_'.SITE_ID.'_'.md5(mb_strtoupper($country.'|'.$city)))) {
                [$country, $city] = $cache->getVars();
            } elseif ($cache->startDataCache()) {
                $_country = false;
                $_city = false;
                \Bitrix\Main\Loader::requireModule('sale');
                $dbLoc = \Bitrix\Sale\Location\Name\LocationTable::getList([
                    'filter' => [
                        'LOGIC' => 'OR',
                        [
                            'LOGIC' => 'AND',
                            'NAME_UPPER' => mb_strtoupper($country),
                            'LOCATION.TYPE_ID' => 1,
                            'LANGUAGE_ID' => LANGUAGE_ID,
                        ],
                        [
                            'LOGIC' => 'AND',
                            'NAME_UPPER' => mb_strtoupper($city),
                            'LOCATION.TYPE_ID' => 5,
                            'LANGUAGE_ID' => LANGUAGE_ID,
                        ],
                    ],
                    'select' => [
                        'LOCATION_ID',
                    ],
                ]);
                $arLocs = [];
                while($arLoc = $dbLoc->fetch()) {
                    $arLocs[$arLoc['LOCATION_ID']] = false;
                }
                $dbLoc = \Bitrix\Sale\Location\Name\LocationTable::getList([
                    'filter' => [
                        'LOCATION_ID' => array_keys($arLocs),
                        'LANGUAGE_ID' => 'ru',
                    ],
                    'select' => [
                        'LOCATION_ID',
                        'NAME',
                        'NAME_UPPER',
                        'LOCATION_TYPE' => 'LOCATION.TYPE_ID',
                    ],
                ]);
                while($arLoc = $dbLoc->fetch()) {
                    $arLocs[$arLoc['LOCATION_ID']] = $arLoc;
                    if(!$_city && $arLoc['LOCATION_TYPE'] == 5 && $arLoc['NAME_UPPER'] != mb_strtoupper($city)) {
                        $_city= $arLoc['NAME'];
                    } else if(!$_country && $arLoc['LOCATION_TYPE'] == 1) {
                        $_country = $arLoc['NAME'];
                    }
                }

                if($_country) $country = $_country;
                if($_city) $city = $_city;
                $cache->endDataCache([$country, $city]);
            }
        }

        $result = $dadata->suggest(
            "address", [
                "query" => addslashes($address),
                "language" => strtoupper(LANGUAGE_ID),
                "locations" => [
                    "country" => ($country),
                    "city" => ($city),
                ],
            ]
        );


        if($result && $result['suggestions']) {
            $arResult = $result['suggestions'];
        } else {
            $result = $dadata->suggest(
                "address", [
                    "query" => addslashes($city.', '.$address),
                    "language" => strtoupper(LANGUAGE_ID),
                    "locations" => [
                        "country" => ($country),
                    ],
                ]
            );
            if($result && $result['suggestions']) {
                $arResult = $result['suggestions'];
            }
        }

        return $arResult;
    }

    public function upsertProfileAction($arParams)
    {
        $ID = $arParams['ID'];

        $result = false;

        global $USER;
        $USER_ID = $USER->GetID();

        $SITE_ID = SITE_ID;
        $arPropValues = $arParams['profile'];

        $arResult = static::ensureAllProfiles($ID, $arPropValues, $USER_ID, $SITE_ID);

        $arProfile = $arResult['profile'];
        $errors = $arResult['errors'];

        if(empty($errors)) {
            $result = true;
        }

        $arPropValues = static::convertPropertyValuesToCodes($arPropValues);
        if($arPropValues['IS_MAIN_ADDRESS'] == 'Y') {
            $rsOtherAddresses = \Bitrix\Sale\Internals\UserPropsTable::getList([
                'filter' => [
                    '=USER_ID' => $USER_ID,
                    '!ID' => $arProfile['ID'],
                ],
            ]);
            $arProfs = [];
            while($arAnotherProfile = $rsOtherAddresses->fetch()) {
                $arProfs[] = $arAnotherProfile['ID'];
            }
            $rsOtherProps = \Bitrix\Sale\Internals\UserPropsValueTable::getList([
                'filter' => [
                    'USER_PROPS_ID' => $arProfs,
                    'PROPERTY.CODE' => 'IS_MAIN_ADDRESS',
                    'VALUE' => 'Y',
                ],
            ]);
            while($arAnotherProp = $rsOtherProps->fetch()) {
                \Bitrix\Sale\Internals\UserPropsValueTable::update(
                    $arAnotherProp['ID'],
                    ['VALUE' => 'N'],
                );
            }
        }

        return [
            'result' => $result,
            'errors' => $errors,
            'ID' => $arProfile['ID'],
        ];
    }

    public static function ensureAllProfiles($ID, $arPropValues, $USER_ID = false, $SITE_ID = false)
    {
        \Bitrix\Main\Loader::requireModule('sale');

        static::ensureParams($ID, $USER_ID, $SITE_ID);

        $errors = [];
        $dateUpdate = new \Bitrix\Main\Type\DateTime();

        $arUser = static::getUser($USER_ID, $errors);
        $arProfile = static::ensureProfile($ID, $arUser, $SITE_ID, $errors);

        if(empty($errors)) {

            $arProperties = [];
            $dbProps = \Bitrix\Sale\Internals\OrderPropsTable::getList([
                'filter' => [
                    'USER_PROPS' => true,
                    '=PERSON_TYPE_ID' => $arProfile['PERSON_TYPE_ID'],
                ],
                'select' => [
                    'ID', 'NAME'
                ],
            ]);
            while ($arProp = $dbProps->fetch()) {
                $id = $arProp['ID'];

                $arProperties[$id] = [
                    'ID' => $arProp['ID'],
                    'NAME' => $arProp['NAME'],
                ];
            }

            $arValues = [];
            $dbValues = \Bitrix\Sale\Internals\UserPropsValueTable::getList([
                'filter' => [
                    '=USER_PROPS_ID' => $arProfile['ID'],
                ],
                'select' => [
                    'ID',
                    'ORDER_PROPS_ID',
                    'VALUE',
                ]
            ]);
            while ($arValue = $dbValues->fetch()) {
                $arValues[$arValue['ORDER_PROPS_ID']] = $arValue;
            }

            $changed = false;
            foreach ($arProperties as $id => $arProperty) {
                if (!isset($arPropValues[$id])) {
                    continue;
                }
                $value = $arPropValues[$id];
                if (isset($arValues[$id])) {
                    if($arValues[$id]['VALUE'] == $value) {
                        unset($arValues[$id]);
                        continue;
                    }
                    $changed = true;
                    $r = \Bitrix\Sale\Internals\UserPropsValueTable::update($arValues[$id]['ID'], ['VALUE' => $value]);
                    unset($arValues[$id]);
                    if (!$r->isSuccess()) {
                        $errors = array_merge($errors, $r->getErrorMessages());
                    }
                } else {
                    $changed = true;
                    $arValue = [
                        'USER_PROPS_ID' => $arProfile['ID'],
                        'ORDER_PROPS_ID' => $id,
                        'NAME' => $arProperty['NAME'],
                        'VALUE' => $value,
                    ];
                    $r = \Bitrix\Sale\Internals\UserPropsValueTable::add($arValue);
                    if (!$r->isSuccess()) {
                        $errors = array_merge($errors, $r->getErrorMessages());
                    }
                }
            }

            foreach ($arValues as $arValue) {
                $changed = true;
                $r = \Bitrix\Sale\Internals\UserPropsValueTable::delete($arValue['ID']);
                if (!$r->isSuccess()) {
                    $errors = array_merge($errors, $r->getErrorMessages());
                }
            }

            if($changed) {
                \Bitrix\Sale\Internals\UserPropsTable::update($arProfile['ID'], [
                    'DATE_UPDATE' => $dateUpdate,
                ]);
            }
        }

        return [
            'status' => empty($errors),
            'profile' => $arProfile,
            'errors' => $errors,
        ];
    }

    protected static function ensureParams($ID, &$USER_ID, &$SITE_ID)
    {
        if(!empty($ID) && (empty($USER_ID) || empty($SITE_ID))) {
            $arProf = \Bitrix\Sale\Internals\UserPropsTable::getRow([
                'filter' => [
                    'ID' => $ID,
                ],
                'runtime' => [
                    new Reference(
                        'PERSON_TYPE',
                        \Bitrix\Sale\Internals\PersonTypeTable::class,
                        Join::on('this.PERSON_TYPE_ID', 'ref.ID'),
                        ['join_type' => Join::TYPE_LEFT_OUTER]
                    )
                ],
                'select' => [
                    'USER_ID',
                    'LID' => 'PERSON_TYPE.LID',
                ],
            ]);
            if($arProf) {
                //gsv_dump($arProf);
                if(empty($USER_ID)) {
                    $USER_ID = $arProf['USER_ID'];
                }
                if(empty($SITE_ID)) {
                    $SITE_ID = $arProf['LID'];
                }
            }
            unset($arProf);
        }

        if(empty($SITE_ID)) {
            $SITE_ID = SITE_ID;
        }

        if(empty($USER_ID)) {
            $USER_ID = $GLOBALS['USER']->GetID();
        }
    }

    public static function ensureProfile($ID, $arUser, $SITE_ID, &$errors)
    {
        $USER_ID = $arUser['ID'];

        if($ID) {
            $arProfile = \Bitrix\Sale\Internals\UserPropsTable::getRow([
                'filter' => [
                    '=USER_ID' => $USER_ID,
                    '=ID' => $ID,
                ],
                'select' => [
                    'ID', 'NAME', 'PERSON_TYPE_ID', 'USER_ID',
                    'LID' => 'PERSON_TYPE.LID',
                    'DATE_UPDATE',
                ],
                'runtime' => [
                    new Reference(
                        'PERSON_TYPE',
                        \Bitrix\Sale\Internals\PersonTypeTable::class,
                        Join::on('this.PERSON_TYPE_ID', 'ref.ID'),
                        ['join_type' => Join::TYPE_LEFT_OUTER]
                    )
                ],
            ]);

            if(empty($arProfile)) {
                $errors[] = 'Profile not found';
            }

            $arPersonType = [
                'ID' => $arProfile['PERSON_TYPE_ID'],
                'LID' => $arProfile['LID'],
            ];

        } else {

            $arPersonType = \Bitrix\Sale\Internals\PersonTypeTable::getRow([
                'filter' => [
                    '=LID' => $SITE_ID,
                ],
                'select' => ['ID'],
            ]);

            $arProfile = [
                'PERSON_TYPE_ID' => $arPersonType['ID'],
                'USER_ID' => $USER_ID,
                'NAME' => trim(trim($arUser['NAME'] . ' ' . $arUser['LAST_NAME'])
                    . ' ' . (new \Bitrix\Main\Type\DateTime())->format(\Bitrix\Main\Type\DateTime::getFormat())),
                //'DATE_UPDATE' => new \Bitrix\Main\Type\DateTime(),
            ];
            $r = \Bitrix\Sale\Internals\UserPropsTable::add($arProfile);
            if($r->isSuccess()) {
                $arProfile['ID'] = $r->getId();
            } else {
                $errors[] = $r->getErrorMessages();
            }

            $arProfile['DATE_UPDATE'] = null;
            $arProfile['LID'] = $SITE_ID;
        }

        return $arProfile;
    }

    public static function getUser($USER_ID, &$errors)
    {
        $arUser = \Bitrix\Main\UserTable::getRow([
            'filter' => ['ID' => $USER_ID],
            'select' => ['*'],
        ]);

        if(empty($arUser)) {
            $errors[] = 'User not found';
        }

        return $arUser;
    }

    public static function convertPropertiesToCodes($arIds)
    {
        if(empty($arIds)) {
            return [];
        }

        \Bitrix\Main\Loader::requireModule('sale');

        $arIds = array_fill_keys($arIds, '');

        $dbProps = \Bitrix\Sale\Internals\OrderPropsTable::getList([
            'filter' => [
                'ID' => array_keys($arIds),
            ],
            'select' => [
                'ID', 'CODE',
            ],
        ]);
        while($arProp = $dbProps->fetch()) {
            $arIds[$arProp['ID']] = $arProp['CODE'];
        }

        return $arIds;
    }

    public static function convertPropertiesFromCodes($personTypeId, $arCodes)
    {
        if(empty($arCodes)) {
            return [];
        }

        \Bitrix\Main\Loader::requireModule('sale');

        $arCodes = array_fill_keys($arCodes, 0);

        $dbProps = \Bitrix\Sale\Internals\OrderPropsTable::getList([
            'filter' => [
                '=CODE' => array_keys($arCodes),
                '=PERSON_TYPE_ID' => $personTypeId,
            ],
            'select' => [
                'ID', 'CODE',
            ],
        ]);
        while($arProp = $dbProps->fetch()) {
            $arCodes[$arProp['CODE']] = $arProp['ID'];
        }

        return $arCodes;
    }

    public static function convertPropertyValuesToCodes($arValues)
    {
        $result = [];
        $arMap = static::convertPropertiesToCodes(array_keys($arValues));
        foreach ($arMap as $k => $v) {
            $result[$v] = $arValues[$k];
        }
        return $result;
    }

    public static function convertPropertyValuesToIds($profile, $arValues)
    {
        $result = [];
        $arMap = static::convertPropertiesFromCodes($profile, array_keys($arValues));
        foreach ($arMap as $k => $v) {
            $result[$v] = $arValues[$k];
        }
        return $result;
    }

    public function removeProfileAction($arParams)
    {
        $ID = $arParams['ID'];

        $result = false;
        $errors = [];

        global $USER;
        $USER_ID = $USER->GetID();

        if (empty($USER_ID)) {
            $errors[] = 'User not found';
        }

        \Bitrix\Main\Loader::requireModule('sale');

        if ($ID) {
            $r = \Bitrix\Sale\Internals\UserPropsTable::delete($ID);
            if (!$r->isSuccess()) {
                $errors = array_merge($errors, $r->getErrorMessages());
            } else {
                $result = true;
            }
        }

        return [
            'result' => $result,
            'errors' => $errors,
        ];
    }

    public function convertErrors($arErrors)
    {
        $arAnswer = [];
        foreach ($arErrors as $obError) {
            $arAnswer[] = array(
                'code' => $obError->getCode(),
                'msg' => $obError->getMessage(),
                'more' => $obError->getMore()
            );
        }
        return $arAnswer;
    }
}