<?php

namespace Gsv\Sale\ActionCtrl;

use \Bitrix\Main\Loader;

class HiddenSaleDiscount extends \CSaleActionCtrlAction
{
    /**
     * Получение имени класса
     * @return string
     */
    public static function GetClassName()
    {
        return __CLASS__;
    }

    /**
     * Получение ID условия
     * @return array|string
     */
    public static function GetControlID()
    {
        return "HiddenSaleDiscount";
    }

    /**
     * Добавление пункта в список условий с указанием отдельной группы
     * @param $arParams
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function GetControlShow($arParams)
    {
        $arControls = static::GetAtomsEx();
        $arResult = array(
            'controlgroup' => true,
            'group' =>  false,
            'label' => 'Кастомные правила',
            'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
            'children' =>  [array(
                'controlId' => static::GetControlID(),
                'group' => false,
                'label' => "Если имеет доступ к скрытой распродаже",
                'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
                'control' => array(
                    "Если имеет доступ к скрытой распродаже",
                    $arControls["PT"]
                )
            )]
        );

        return $arResult;
    }

    /**
     * Формирование данных для визуального представления условия
     * @param bool $strControlID
     * @param bool $boolEx
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function GetAtomsEx($strControlID = false, $boolEx = false)
    {
        $boolEx = (true === $boolEx ? true : false);
        $priceList = [];
        /*if (Loader::includeModule('main')) {
            // Получение типов цен
            $arGroupPrice = \Bitrix\Catalog\GroupTable::getList([
                'select' => ['ID', 'NAME'],
                'cache' => [
                    'ttl' => 60,
                    'cache_joins' => true,
                ]
            ]);

            while ($el = $arGroupPrice->fetch()) {
                $priceList[$el['ID']] = $el['NAME'] . " [" . $el['ID'] . "]";
            }
        }*/
        $priceList[1] = 'да';

        $arAtomList = [
            "PT" => [
                "JS" => [
                    "id" => "PT",
                    "name" => "extra",
                    "type" => "select",
                    "values" => $priceList,
                    "defaultText" => "...",
                    "defaultValue" => "",
                    "first_option" => "..."
                ],
                "ATOM" => [
                    "ID" => "PT",
                    "FIELD_TYPE" => "string",
                    "FIELD_LENGTH" => 255,
                    "MULTIPLE" => "N",
                    "VALIDATE" => "list"
                ]
            ],
        ];
        if (!$boolEx) {
            foreach ($arAtomList as &$arOneAtom)
            {
                $arOneAtom = $arOneAtom["JS"];
            }
            if (isset($arOneAtom))
            {
                unset($arOneAtom);
            }
        }

        return $arAtomList;
    }

    /**
     * Функция должна вернуть колбэк того, что должно быть выполнено при наступлении условий
     * @param $arOneCondition
     * @param $arParams
     * @param $arControl
     * @param bool $arSubs
     * @return string
     */
    public static function Generate($arOneCondition, $arParams, $arControl, $arSubs = false)
    {
        return __CLASS__ . '::applyProductDiscount($row, "' . $arOneCondition["PT"] . '")';
    }

    /**
     * Логика кастомного условия
     * @param $row
     * @param $priceType
     * @return bool
     */
    public static function applyProductDiscount($row, $priceType)
    {
        return $_COOKIE['HIDDEN_SALE'] === 'Y';
    }
}