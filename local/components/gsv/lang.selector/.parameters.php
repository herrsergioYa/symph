<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}
/** @var array $arCurrentValues */

use Bitrix\Main\Loader;

$arLang = [];
$langs = explode(',', $arParams['LANG_LIST']);
if($langs) {
    foreach ($langs as $lang) {
        $lang = trim($lang);
        $arLang[$lang] = $lang;
    }
}

$arComponentParameters = [
	"GROUPS" => [],
	"PARAMETERS" => [
		"LANG_LIST" => [
			"PARENT" => "BASE",
			"NAME" => GetMessage("T_GSV_LANG_SELECTOR_LIST"),
			"TYPE" => "STRING",
			"DEFAULT" => 'ru,en',
			"REFRESH" => "Y",
		],
        "LANG_CURRENT" => [
            "PARENT" => "BASE",
            "NAME" => GetMessage("T_GSV_LANG_SELECTOR_CURRENT"),
            "TYPE" => "LIST",
            "VALUES" => $arLang,
            "REFRESH" => "N",
        ],
        "LANG_BASE" => [
            "PARENT" => "BASE",
            "NAME" => GetMessage("T_GSV_LANG_SELECTOR_BASE"),
            "TYPE" => "LIST",
            "VALUES" => $arLang,
            "REFRESH" => "N",
        ],
    ],
];