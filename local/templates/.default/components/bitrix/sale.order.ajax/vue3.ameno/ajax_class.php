<?php


namespace Gsv\Internals;

class GsvBasketSoaAjax extends \Gsv\Bitrix\JsonAjaxController
{
    protected static function exec($callable, $arParams)
    {
        \Bitrix\Main\Loader::requireModule('sale');
        return parent::exec($callable, $arParams);
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
}