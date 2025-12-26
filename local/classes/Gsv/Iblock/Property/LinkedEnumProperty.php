<?php

namespace Gsv\Iblock\Property;

use Bitrix\Main\Loader;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Iblock\PropertyEnumerationTable;

Loader::includeModule('iblock');

class LinkedEnumProperty
{
    public static function getUserTypeDescription(): array
    {
        return [
            'PROPERTY_TYPE' => 'S',
            'USER_TYPE' => 'linked_enum',
            'DESCRIPTION' => 'Список, привязанный к другому свойству (enum)',
            'GetPropertyFieldHtml' => [__CLASS__, 'getPropertyFieldHtml'],
            'GetSettingsHTML' => [__CLASS__, 'getSettingsHTML'],
            'PrepareSettings' => [__CLASS__, 'prepareSettings'],
        ];
    }

    public static function getSettingsHTML($arProperty, $strHTMLControlName, &$arPropertyFields): string
    {
        $val = htmlspecialcharsbx($arProperty['USER_TYPE_SETTINGS']['LINKED_PROPERTY'] ?? '');

        return '
            <tr>
                <td>Код связанного свойства (список):</td>
                <td><input type="text" name="' . $strHTMLControlName['NAME'] . '[LINKED_PROPERTY]" value="' . $val . '"></td>
            </tr>
        ';
    }

    public static function getPropertyFieldHtml($arProperty, $value, $strHTMLControlName): string
    {
        $linkedPropertyCode = $arProperty['USER_TYPE_SETTINGS']['LINKED_PROPERTY'] ?? '';

        if (!$linkedPropertyCode) {
            return '<em>Не указано связанное свойство</em>';
        }

        //$iblockId = $arProperty['IBLOCK_ID'];

        $linkedProperty = PropertyTable::getList([
            'filter' => [/*'IBLOCK_ID' => $iblockId, 'CODE'*/ 'ID' => $linkedPropertyCode],
            'select' => ['ID', 'NAME'],
        ])->fetch();

        if (!$linkedProperty) {
            return '<em>Свойство не найдено</em>';
        }

        $enumList = PropertyEnumerationTable::getList([
            'filter' => ['PROPERTY_ID' => $linkedProperty['ID']],
            'select' => ['ID', 'VALUE', 'XML_ID'],
        ])->fetchAll();

        $html = '<select name="' . $strHTMLControlName['VALUE'] . '">';
        $html .= '<option value="">(не выбрано)</option>';

        foreach ($enumList as $enum) {
            $selected = ($value['VALUE'] == $enum['ID']) ? 'selected' : '';
            $html .= '<option value="' . htmlspecialcharsbx($enum['ID']) . '" ' . $selected . '>'
                . htmlspecialcharsbx($enum['VALUE']) . '</option>';
        }

        $html .= '</select>';

        return $html;
    }

    public static function prepareSettings($arFields): array
    {
        return [
            'LINKED_PROPERTY' => trim($arFields['USER_TYPE_SETTINGS']['LINKED_PROPERTY'] ?? ''),
        ];
    }
}
