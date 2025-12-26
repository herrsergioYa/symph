<?php
namespace Gsv\EventHandlers\IBlock;

class OnIBlockPropertyBuildList_Location
{
    public static function OnIBlockPropertyBuildListHandler()
    {
        return array(
            "PROPERTY_TYPE" => "S",
            "USER_TYPE" => "SGsvListLocations",
            //"USER_TYPE_ID" => "SGsvListLocations",
            "DESCRIPTION" => 'Gsv: Привязка к местоположению',
            "GetPropertyFieldHtml" => Array(__CLASS__, "GetPropertyFieldHtml"),
            "ConvertToDB" => Array(__CLASS__, "ConvertToDB"),
            "ConvertFromDB" => Array(__CLASS__, "ConvertFromDB"),
            "GetAdminListViewHTML" => array(__CLASS__, "GetAdminListViewHTML")
        );
    }



    function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
    {
        // если значение установлено, делаем выборку местоположения, чтобы вывести название города
        if($value["VALUE"])
        {
            \CModule::IncludeModule("sale");
            $arFilter = Array(
                "ID" => $value["VALUE"],
                "COUNTRY_LID" => "ru",
                "CITY_LID" => "ru",
            );
            $rsData = \CSaleLocation::GetList(
                array(
                    "SORT" => "ASC",
                    "COUNTRY_NAME_LANG" => "DESC",
                    "CITY_NAME_LANG" => "ASC"
                ),
                $arFilter,
                false,
                false,
                array("CITY_NAME", "COUNTRY_NAME")
            );
            $location = $rsData->GetNext();
        }

        /*
           дальше для каждого поля ввода выводим функцию которая будет открывать окно с нашей формой построенной в файле location.php, функция заимствована у товарища Битрикса
        */
        $function_name = str_replace('[','xx',$strHTMLControlName['VALUE']); //заменяем квадратные скобочки, чтобы их небыло в имени функции
        $function_name = str_replace(']','xx',$function_name);
        $html = "<script>function open_win_".$function_name."(){ window.open('/bitrix/admin/location_search.php?lang=ru&FN=". $strHTMLControlName['FORM_NAME']."&FC=".$strHTMLControlName['VALUE']."', '', 'scrollbars=yes,resizable=yes,width=760,height=500,top='+Math.floor((screen.height - 560)/2-14)+',left='+Math.floor((screen.width - 760)/2-5));}</script>";

        // выводим input с id местоположения и рядом подпись в виде названия города
        $html .= '<input type="text" name="'.$strHTMLControlName['VALUE'].'" id="'.$strHTMLControlName['VALUE'].'" value="'.$value["VALUE"].'" /><input class="tablebodybutton" type="button" OnClick="open_win_'.$function_name.'()" value="..."> <span id="div_'.$strHTMLControlName['VALUE'].'">';

        // проверяем если названия города нет - пишем название страны
        if($location["CITY_NAME"])
            $html .= $location["CITY_NAME"];
        else
            $html .= $location["COUNTRY_NAME"];

        $html .= '</span>';
        return  $html;
    }



    function GetAdminListViewHTML($arProperty, $value, $strHTMLControlName)
    {
        // вывод местоположений в удобочитаемом виде в списке элементов. со ссылкой на редактирование местоположения
        if($value["VALUE"])
        {
            \CModule::IncludeModule("sale");
            $arFilter = Array(
                "ID" => $value["VALUE"],
                "COUNTRY_LID" => "ru",
                "CITY_LID" => "ru",
            );
            $rsData = \CSaleLocation::GetList(
                array(
                    "SORT" => "ASC",
                    "COUNTRY_NAME_LANG" => "DESC",
                    "CITY_NAME_LANG" => "ASC"
                ),
                $arFilter,
                false,
                false,
                array("CITY_NAME", "COUNTRY_NAME", "ID")
            );
            $location = $rsData->GetNext();

            return $location["CITY_NAME"]."[<a href='/bitrix/admin/sale_location_edit.php?ID=".$location["ID"]."'>".$location["ID"]."</a>]";
        }

        return;

    }



    function ConvertToDB($arProperty, $value)
    {
        return $value;
    }



    function ConvertFromDB($arProperty, $value)
    {
        return $value;
    }


}
