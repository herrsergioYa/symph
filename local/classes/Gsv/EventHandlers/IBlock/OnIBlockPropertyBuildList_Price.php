<?
namespace Gsv\EventHandlers\Iblock;

use Bitrix\Main\Localization\Loc,
    Bitrix\Main\Loader;

/**
 * Создает свойство iblock.
 * Новое свойство позволяется указывать привязку к цене
 */
class OnIBlockPropertyBuildList_Price
{
    public static function OnIBlockPropertyBuildListHandler()
    {
        return array(
            'PROPERTY_TYPE' => 'S',
            'USER_TYPE' => 'SGsvListPrices',
            //'USER_TYPE_ID' => 'SGsvListPrices',
            'DESCRIPTION' => 'Gsv: Список цен',
            'GetPropertyFieldHtml' => array(__CLASS__, 'GetPropertyFieldHtml'),
            'GetSettingsHTML' => array(__CLASS__, 'GetSettingsHTML'),
        );
    }

    public static function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
    {
        static $cache = array();
        $html = '';
        if(Loader::includeModule('catalog'))
        {
            $cache["PRICES"] = array();
            $rsComp = \Bitrix\Catalog\GroupTable::getList([
                'order' => [
                    array("NAME" => "ASC")
                ],
            ]);
            while($arComp = $rsComp->fetch())
            {
                if($arComp["NAME"])
                    $cache["PRICES"][$arComp["ID"]] = $arComp;
            }

            $varName = str_replace("VALUE", "DESCRIPTION", $strHTMLControlName["VALUE"]);
            $val = ($value["VALUE"] ? $value["VALUE"] : $arProperty["DEFAULT_VALUE"]);
            $html = '<select name="'.$strHTMLControlName["VALUE"].'" onchange="document.getElementById(\'DESCR_'.$varName.'\').value=this.options[this.selectedIndex].text">
			<option value="" >-</option>';
            foreach($cache["PRICES"] as $arCompany)
            {
                $html .= '<option value="'.$arCompany["ID"].'"';
                if($val == $arCompany["ID"])
                    $html .= ' selected';
                $html .= '>'.$arCompany["NAME"].'</option>';
            }
            $html .= '</select>';
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
}
