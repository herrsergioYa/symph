<?php

namespace Gsv\Util;

class GoogleDoc
{
    /*public*/ const API_KEY = "AIzaSyBSqlZET5FY5uHraCxlbRwdQ4AX7ILaoe8";

    public static function getSpreadsheetAsJson($sheetKey, $sheet = null)
    {
        $apiKey = static::API_KEY;
        $sheet = $sheet ?? "Лист1";

        $sheetData = json_decode(file_get_contents("https://sheets.googleapis.com/v4/spreadsheets/$sheetKey/values/".urlencode($sheet)."?key=$apiKey"), true);
        return $sheetData;
    }

    public static function getSpreadsheetAsAssoc($sheetKey, $sheet = null)
    {
        $sheetData = static::getSpreadsheetAsJson($sheetKey, $sheet);
        if(empty($sheetData) || empty($sheetData["values"])) {
            return false;
        }
        $arLines = $sheetData["values"];
        $arHeader = array_shift($arLines);
        $arRows = [];
        if($arHeader) {
            while($arLine = array_shift($arLines)) {
                $arRow = [];
                foreach ($arHeader as $k => $v) {
                    $arRow[$v] = $arLine[$k];
                }
                $arRows[] = $arRow;
            }
        }
        return $arRows;
    }
}