<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}
/** @var CBitrixComponent $this */
/** @var array $arParams */
/** @var array $arResult */
/** @var string $componentPath */
/** @var string $componentName */
/** @var string $componentTemplate */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

$arResult['LANG'] = [];
$url = $APPLICATION->GetCurUri();
if($arParams['LANG_CURRENT'] != $arParams['LANG_BASE']) {
    $prefixLen = 1 + strlen($arParams['LANG_CURRENT']) + 1;
    if(substr($url, 0, $prefixLen) == '/' . $arParams['LANG_CURRENT'] . '/') {
        $url = substr($url, $prefixLen - 1);
    } else {
        $url = '/';
    }
}

foreach ($arParams['LANG_LIST'] as $lang) {

    $arLang = [];
    $arLang['SELECTED'] = $lang == $arParams['LANG_CURRENT'];
    $arLang['CODE'] = $lang;
    $arLang['URL'] =  ($lang != $arParams['LANG_BASE'] ? '/' . $lang : '') . $url;

    $arResult['LANG'][$lang] = $arLang;
}

//if($this->startResultCache(false))
{

	$this->includeComponentTemplate();
}
