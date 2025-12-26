<?php


namespace Gsv\Internals;

class GsvBasketSoaAjax extends \Gsv\Bitrix\JsonAjaxController
{
    protected static function exec($callable, $arParams)
    {
        \Bitrix\Main\Loader::requireModule('sale');
        return parent::exec($callable, $arParams);
    }

    public function listCountriesAction($arParams)
    {
        $countryPrefix = trim($arParams['q']);

        $arResult = [];

        $dbCountries =  \Bitrix\Sale\Location\Name\LocationTable::getList([
            'select' => [
                'LOCATION_ID',
                'NAME',
                'CODE' => 'LOCATION.CODE',

                'LEFT_MARGIN' => 'LOCATION.LEFT_MARGIN',
                'RIGHT_MARGIN' => 'LOCATION.RIGHT_MARGIN',
            ],
            'filter' => [
                'LOCATION.TYPE.ID' => 1, //Countries only
                'LANGUAGE_ID' => LANGUAGE_ID,
                '%=NAME_UPPER' => mb_strtoupper($countryPrefix) . '%',
            ],
            'order' => ['NAME' => 'ASC'],
        ]);
        while($arCountry = $dbCountries->fetch()) {
            $arResult[/*$arCountry['LOCATION_ID']*/] = [
                'id' => $arCountry['LOCATION_ID'],
                'text' => $arCountry['NAME'],
                'code' => $arCountry['CODE'],
            ];
        }

        return $arResult;
    }

    public function listCitiesAction($arParams)
    {
        $countryId = $arParams['countryId'];
        $cityPrefix = trim($arParams['q']);

        $arResult = [];

        $arCountry = \Bitrix\Sale\Location\Name\LocationTable::getRow([
            'select' => [
                'LOCATION_ID',
                'NAME',
                'CODE' => 'LOCATION.CODE',

                'LEFT_MARGIN' => 'LOCATION.LEFT_MARGIN',
                'RIGHT_MARGIN' => 'LOCATION.RIGHT_MARGIN',
            ],
            'filter' => [
                'LOCATION.TYPE.ID' => 1, //Country only
                'LANGUAGE_ID' => LANGUAGE_ID,
                'LOCATION_ID' => $countryId,
            ],
        ]);

        if($arCountry) {
            $dbCities = \Bitrix\Sale\Location\Name\LocationTable::getList([
                'select' => [
                    'LOCATION_ID',
                    'NAME',
                    'CODE' => 'LOCATION.CODE',

                    'LEFT_MARGIN' => 'LOCATION.LEFT_MARGIN',
                    'RIGHT_MARGIN' => 'LOCATION.RIGHT_MARGIN',
                ],
                'filter' => [
                    'LOCATION.TYPE.ID' => 5, //City only
                    'LANGUAGE_ID' => LANGUAGE_ID,
                    '%=NAME_UPPER' => mb_strtoupper($cityPrefix) . '%',

                    '>=LOCATION.LEFT_MARGIN' => $arCountry['LEFT_MARGIN'],
                    '<=LOCATION.RIGHT_MARGIN' => $arCountry['RIGHT_MARGIN'],
                ],
                'order' => ['NAME' => 'ASC'],
            ]);
            $arMap = [];
            while($arCity = $dbCities->fetch()) {
                $arResult[$arCity['LOCATION_ID']] = [
                    'id' => $arCity['LOCATION_ID'],
                    'text' => $arCity['NAME'],
                    'group' => [],
                    'code' => $arCity['CODE'],
                ];
                $arMap[$arCity['LOCATION_ID']] = [
                    '<=LOCATION.LEFT_MARGIN' => $arCity['LEFT_MARGIN'],
                    '>=LOCATION.RIGHT_MARGIN' => $arCity['RIGHT_MARGIN'],
                ];
            }

            $arFilter = array_values($arMap);
            $arFilter['LOGIC'] = 'OR';

            $dbRegions = \Bitrix\Sale\Location\Name\LocationTable::getList([
                'order' => ['LOCATION.LEFT_MARGIN' => 'DESC'],
                'select' => [
                    'LOCATION_ID',
                    'NAME',
                    'CODE' => 'LOCATION.CODE',

                    'LEFT_MARGIN' => 'LOCATION.LEFT_MARGIN',
                    'RIGHT_MARGIN' => 'LOCATION.RIGHT_MARGIN',
                ],
                'filter' => [
                    'LOCATION.TYPE.ID' => [2,3,4], //Regions only
                    'LANGUAGE_ID' => LANGUAGE_ID,
                    $arFilter,
                ],
            ]);
            while($arRegion = $dbRegions->fetch()) {
                foreach ($arMap as $id => $ar) {
                    if($arRegion['LEFT_MARGIN'] <= $ar['<=LOCATION.LEFT_MARGIN']
                        && $arRegion['RIGHT_MARGIN'] >= $ar['>=LOCATION.RIGHT_MARGIN']) {
                        $arResult[$id]['group'][] = $arRegion['NAME'];
                    }
                }
            }

            foreach ($arResult as &$arCity) {
                $arCity['group'] = array_reverse($arCity['group']);
                $arCity['group'] = implode(', ', $arCity['group']);
            }
            unset($arCity);
        }

        return $arResult;
    }

    public function listRuAddressesAction($arParams)
    {
        $countryId = $arParams['countryId'];
        $cityId = $arParams['cityId'];
        $query = trim($arParams['q']);

        $arResult = [];

        $arCountry = \Bitrix\Sale\Location\Name\LocationTable::getRow([
            'select' => [
                'LOCATION_ID',
                'NAME',
                'CODE' => 'LOCATION.CODE',

                'LEFT_MARGIN' => 'LOCATION.LEFT_MARGIN',
                'RIGHT_MARGIN' => 'LOCATION.RIGHT_MARGIN',
            ],
            'filter' => [
                'LOCATION.TYPE.ID' => 1, //Country only
                'LANGUAGE_ID' => LANGUAGE_ID,
                'LOCATION_ID' => $countryId,
            ],
        ]);

        if($arCountry) {
            $arCity = \Bitrix\Sale\Location\Name\LocationTable::getRow([
                'select' => [
                    'LOCATION_ID',
                    'NAME',
                    'CODE' => 'LOCATION.CODE',

                    'LEFT_MARGIN' => 'LOCATION.LEFT_MARGIN',
                    'RIGHT_MARGIN' => 'LOCATION.RIGHT_MARGIN',
                ],
                'filter' => [
                    'LOCATION.TYPE.ID' => 5, //City only
                    'LANGUAGE_ID' => LANGUAGE_ID,
                    'LOCATION_ID' => $cityId,

                    '>=LOCATION.LEFT_MARGIN' => $arCountry['LEFT_MARGIN'],
                    '<=LOCATION.RIGHT_MARGIN' => $arCountry['RIGHT_MARGIN'],
                ],
            ]);
            if($arCity) {
                $arRegion = \Bitrix\Sale\Location\Name\LocationTable::getRow([
                    'order' => ['LOCATION.LEFT_MARGIN' => 'DESC'],
                    'select' => [
                        'LOCATION_ID',
                        'NAME',
                        'CODE' => 'LOCATION.CODE',

                        'LEFT_MARGIN' => 'LOCATION.LEFT_MARGIN',
                        'RIGHT_MARGIN' => 'LOCATION.RIGHT_MARGIN',
                    ],
                    'filter' => [
                        'LOCATION.TYPE.ID' => [3], //Regions only
                        'LANGUAGE_ID' => LANGUAGE_ID,
                        [
                            '<=LOCATION.LEFT_MARGIN' => $arCity['LEFT_MARGIN'],
                            '>=LOCATION.RIGHT_MARGIN' => $arCity['RIGHT_MARGIN'],
                        ],
                    ],
                ]);

                $daDataToken = \Bitrix\Main\Config\Option::get('gsv.dadata', 'token', '');
                $daDataSecret = \Bitrix\Main\Config\Option::get('gsv.dadata', 'secret', '');
                $obData = new \Dadata\Dadata($daDataToken, $daDataSecret);
                $obData->init();
                $arLoc =  [
                    'country' => $arCountry['NAME'],
                    'city' => $arCity['NAME'],
                ];
                if($arRegion) {
                    //unset($arLoc['country']);
                    $arLoc['region'] = mb_strtolower($arRegion['NAME']);
                    $arLoc['region'] = str_replace('автономная область', '', $arLoc['region']);
                    $arLoc['region'] = str_replace('область', '', $arLoc['region']);
                    //$arLoc['region'] = str_replace('народная республика', '', $arLoc['region']);
                    $arLoc['region'] = str_replace('республика', '', $arLoc['region']);
                    $arLoc['region'] = str_replace('автономный округ', '', $arLoc['region']);
                    $arLoc['region'] = str_replace('округ', '', $arLoc['region']);//Just in case
                    $arLoc['region'] = str_replace('край', '', $arLoc['region']);
                    $arLoc['region'] = trim($arLoc['region']);
                    if ($arLoc['region'] == 'ханты-мансийский') {
                        $arLoc['region'] = 'Ханты-Мансийский Автономный округ - Югра';
                        //unset($arLoc['region']);
                    }
                }
                $result = $obData->suggest('address', [
                    'query' => $query,
                    'locations' => $arLoc,
                ]);
                /*$test = $obData->suggest('address', [
  "query"=> "д",
  "from_bound"=> [ "value"=> "region" ],
  "to_bound"=> [ "value"=> "region" ],
                ]);*/
                foreach ($result['suggestions'] as $suggestion) {
                    if(empty($suggestion['data']['postal_code'])) {
                        //continue;
                    }
                    $value = $suggestion['value'];
                    if(($pos = strpos($value, $suggestion['data']['city_with_type'])) !== false) {
                        $value = substr($value, $pos + strlen($suggestion['data']['city_with_type']));
                        $value = trim($value, ', ');
                        $value = trim($value);
                    }
                    $arResult[] = [
                        'id' => $arCountry['LOCATION_ID'] . '/'. $arCity['LOCATION_ID'] . '/'. $suggestion['unrestricted_value'],
                        'text' => $value,
                        'code' => $suggestion['data']['postal_code'],
                    ];
                }
                $obData->close();
            }
        }

        return $arResult;
    }

    public function updateBonusesAction($arParams)
    {
        global $USER;
        if($USER->IsAuthorized()) {

            if(empty($arParams['NO_1C']) || $arParams['NO_1C'] === 'false') {
                $obUserBonus = new \Gsv\Helpers\UserBonus();
                $obUserBonus->updateBonusOf($USER->getId());
            }

            $userId = $USER->GetID();
            $currency = $arParams['CURRENCY'];

            $arUserAccount = \CSaleUserAccount::GetByUserID(
                $userId,
                $currency
            );
            if(empty($arUserAccount)) {
                \CSaleUserAccount::Add([
                    'USER_ID' => $userId,
                    'CURRENCY' => $currency,
                    'CURRENT_BUDGET' => 0,
                ]);
                $arUserAccount = \CSaleUserAccount::GetByUserID(
                    $userId,
                    $currency
                );
            }

            if($arUserAccount) {
                $arUserAccount = [
                    'USER_ID' => +$arUserAccount['USER_ID'],
                    'CURRENT_BUDGET' => +$arUserAccount['CURRENT_BUDGET'],
                    'CURRENCY' => $arUserAccount['CURRENCY'],
                ];
            }

            return $arUserAccount;
        }

        return false;
    }
}