<?
namespace Gsv\EventHandlers\IBlock;

use Bitrix\Main\Localization\Loc,
    Bitrix\Main\Loader;

/**
 * Создает свойство iblock.
 * Новое свойство позволяется указывать привязку к маркетинговому правилу
 */
class OnIBlockPropertyBuildList_Discount extends OnIBlockPropertyBuildList
{
    public static function OnIBlockPropertyBuildListHandler()
    {
        return array(
            'PROPERTY_TYPE' => 'N',
            'USER_TYPE' => 'SGsvListDiscounts',
            //'USER_TYPE_ID' => 'SGsvListDiscounts',
            'DESCRIPTION' => 'Gsv: Список маркетинговых правил',
            'GetPropertyFieldHtml' => array(static::class, 'GetPropertyFieldHtml'),
            'GetSettingsHTML' => array(static::class, 'GetSettingsHTML'),
        );
    }

    public static function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
    {
        $cache = static::GetCache();

        if(empty($cache))
        {
            $cache["DISCOUNTS"] = array();
            if (Loader::includeModule('sale'))
            {
                $rsDiscounts = static::getDiscountList();
                while ($arDiscount = $rsDiscounts->fetch()) {
                    if ($arDiscount["NAME"])
                        $cache["DISCOUNTS"][$arDiscount["ID"]] = $arDiscount;
                }
            }
            static::SetCache($cache);
        }

        $html = '';
        $varName = str_replace("VALUE", "DESCRIPTION", $strHTMLControlName["VALUE"]);
        $val = ($value["VALUE"] ? $value["VALUE"] : $arProperty["DEFAULT_VALUE"]);
        $html = '<select name="' . $strHTMLControlName["VALUE"] . '" onchange="document.getElementById(\'DESCR_' . $varName . '\').value=this.options[this.selectedIndex].text">
        <option value="" >-</option>';
        foreach ($cache["DISCOUNTS"] as $arDiscount) {
            $html .= '<option value="' . $arDiscount["ID"] . '"';
            if ($val == $arDiscount["ID"])
                $html .= ' selected';
            $html .= '>[' . $arDiscount["ID"] . '] ' . $arDiscount["NAME"] . '</option>';
        }
        $html .= '</select>';
        if (
            $arProperty['WITH_DESCRIPTION'] === 'Y'
            && $strHTMLControlName['DESCRIPTION'] !== ''
        )
        {
            $html .= '&nbsp;<input type="text" size="20" name="' . $strHTMLControlName['DESCRIPTION'].'"'
                .' value="' . htmlspecialcharsbx($value['DESCRIPTION'] ?? '') . '">';
        }
        return $html;
    }

    public static function GetSettingsHTML($arProperty, $strHTMLControlName, &$arPropertyFields)
    {
        $arPropertyFields = array(
            'HIDE' => array(
                'SMART_FILTER',
                'SEARCHABLE',
                'COL_COUNT',
                'ROW_COUNT',
                'FILTER_HINT',
            ),
            'SET' => array(
                'SMART_FILTER' => 'N',
                'SEARCHABLE' => 'N',
                'ROW_COUNT' => '10',
            ),
        );
    }

    protected static function getDiscountList()
    {
        return \Bitrix\Sale\Internals\DiscountTable::getList([
            'order' => array("NAME" => "ASC"),
        ]);
    }
}
