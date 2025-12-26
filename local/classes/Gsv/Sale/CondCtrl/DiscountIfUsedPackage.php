<?php

namespace Gsv\Sale\CondCtrl;

use Bitrix\Main\Loader;
use Bitrix\Sale\Location;
use Bitrix\Main\GroupTable;


class DiscountIfUsedPackage extends \CSaleCondCtrlComplex
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
        return [
            "CondDiscountIfUsedPackage",
        ];
    }

    /**
     * @return array
     */
//    public static function GetControlDescr()
//    {
//        $description = parent::GetControlDescr();
//        $description['SORT'] = 1;
//        return $description;
//    }

    /**
     * @param $arControls
     * @return array
     */
    /*public static function GetShowIn($arControls)
    {
        if (!is_array($arControls))
            $arControls = array($arControls);
        return array_values(array_unique($arControls));
    }*/

    /**
     * Основная группа для стека кастомных правил
     * @param $arParams
     * @return array
     */
    public static function GetControlShow($arParams)
    {
        $arControls = static::GetControls();
        $arResult = array(
            'controlgroup' => true,
            'group' =>  true,
            'label' => 'Кастомизированные свойства',
            'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
            'children' => array()
        );
        foreach ($arControls as &$arOneControl)
        {
            $arResult['children'][] = array(
                'controlId' => $arOneControl['ID'],
                'group' => false,
                'label' => $arOneControl['LABEL'],
                'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
                'control' => array(
                    $arOneControl['PREFIX'],
                    static::GetLogicAtom($arOneControl['LOGIC']),
                    static::GetValueAtom($arOneControl['JS_VALUE'])
                )
            );
        }
        if (isset($arOneControl))
            unset($arOneControl);

        return $arResult;
    }

    /**
     * Создаем наши кастомные правила
     * @param bool $controlId
     * @return array|bool|mixed
     * @throws \Bitrix\Main\ArgumentException
     */
    public static function GetControls($controlId = false)
    {
        // формируем правила
        $controlList = array(
            'CondDiscountIfUsedPackage' => array(
                'ID' => 'CondDiscountIfUsedPackage',
                'FIELD' => 'DISCOUNT_AMOUNT',
                'FIELD_TYPE' => 'string',
                'MULTIPLE' => 'N',
                'GROUP' => 'N',
                'LABEL' => 'Использованная упаковка',
                'PREFIX' => 'Использованная упаковка?',
                'LOGIC' => static::getLogic(array(BT_COND_LOGIC_EQ)),
                'JS_VALUE' => array(
                    'type' => 'select',
                    'values' => static::getGroupList(),
                    'multiple' => 'N',
                ),
                'PHP_VALUE' => ''
            ),
        );

        foreach ($controlList as &$control)
        {
            if (!isset($control['PARENT']))
                $control['PARENT'] = true;

            $control['MULTIPLE'] = 'N';
            $control['GROUP'] = 'N';
        }
        unset($control);

        if (false === $controlId)
        {
            return $controlList;
        }
        elseif (isset($controlList[$controlId]))
        {
            return $controlList[$controlId];
        }
        else
        {
            return false;
        }
    }

    /**
     * Обработка логики правил
     * @param $oneCondition
     * @param $params
     * @param $control
     * @param bool $subs
     * @return bool|mixed|string
     */
    public static function Generate($oneCondition, $params, $control, $subs = false)
    {
        $mxResult = '';
        if (is_string($control))
        {
            $control = static::GetControls($control);
        }
        $boolError = !is_array($control);

        $values = array();
        if (!$boolError)
        {
            $values = static::check($oneCondition, $params, $control, false);
            $boolError = (false === $values);
        }

        if (!$boolError)
        {
            $type = $oneCondition['logic'];
            //$stringArray = 'array(' . implode(',', array_map('intval', $values['value'])) . ')';
            $stringArray = (string)($values['value']);

            if($control['ID'] === 'CondDiscountIfUsedPackage')
            {
                $mxResult = static::getClassName() . "::checkPackage({$params['ORDER']}, $stringArray, '{$type}')";
            }
        }

        return $mxResult;
    }

    /**
     * Получаем список групп пользователей
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     */
    protected static function getGroupList()
    {
        return [
            'Y' => 'Да',
            'N' => 'Нет',
        ];
    }


    /**
     * Проверка принадлежности пользователя к группе
     * @param array $order
     * @param int|float|string $value
     * @param string $type
     * @return bool
     */
    public static function checkPackage(array $order, $value, $type)
    {
        $applied = false;
        if($order["ORDER_PROP"]) {
            $arProp = \Bitrix\Sale\Internals\OrderPropsTable::getRow([
                'filter' => [
                    'PERSON_TYPE_ID' => $order['PERSON_TYPE_ID'],
                    'CODE' => 'PACKING',
                ],
                'select' => ['ID']
            ]);
            if($arProp) {
                if($order["ORDER_PROP"][$arProp['ID']]) {
                    if($type == "Equal") {
                        $applied = $order["ORDER_PROP"][$arProp['ID']] == $value;
                    } /*else if($type == "NotEqual") {
                        $applied = $order["ORDER_PROP"][$arProp['ID']] != $value;
                    }*/
                }
            }
        }
        return $applied;
    }
}