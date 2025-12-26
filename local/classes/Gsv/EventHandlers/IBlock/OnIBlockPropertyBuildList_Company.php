<?
namespace Gsv\EventHandlers\Iblock;

use Bitrix\Main\Localization\Loc,
    Bitrix\Main\Loader;

/**
 * Создает свойство iblock.
 * Новое свойство позволяется указывать привязку к компании
 */
class OnIBlockPropertyBuildList_Company
{
    public static function OnIBlockPropertyBuildListHandler()
    {
        return array(
            'PROPERTY_TYPE' => 'S',
            'USER_TYPE' => 'SGsvListCompanies',
            //'USER_TYPE_ID' => 'SGsvListCompanies',
            'DESCRIPTION' => 'Gsv: Список компаний',
            'GetPropertyFieldHtml' => array(__CLASS__, 'GetPropertyFieldHtml'),
            'GetSettingsHTML' => array(__CLASS__, 'GetSettingsHTML'),
        );
    }

    public static function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
    {
        static $cache = array();
        $html = '';
        if(Loader::includeModule('sale'))
        {
            $cache["COMPANIES"] = array();
            $rsComp = \Bitrix\Sale\CompanyTable::getList([
                'order' => [
                    array("NAME" => "ASC")
                ],
            ]);
            while($arComp = $rsComp->fetch())
            {
                if($arComp["NAME"])
                    $cache["COMPANIES"][$arComp["ID"]] = $arComp;
            }

            $varName = str_replace("VALUE", "DESCRIPTION", $strHTMLControlName["VALUE"]);
            $val = ($value["VALUE"] ? $value["VALUE"] : $arProperty["DEFAULT_VALUE"]);
            $html = '<select name="'.$strHTMLControlName["VALUE"].'" onchange="document.getElementById(\'DESCR_'.$varName.'\').value=this.options[this.selectedIndex].text">
			<option value="" >-</option>';
            foreach($cache["COMPANIES"] as $arCompany)
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
