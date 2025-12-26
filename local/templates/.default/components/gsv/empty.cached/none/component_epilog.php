<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponent $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var array $templateData */
/** @var string $componentPath */
/** @var CBitrixComponent $component */

if(empty($arParams['GLOBAL_VARIABLE']))
{
    $arParams['GLOBAL_VARIABLE'] = 'arrComponentData';
}

$GLOBALS[$arParams['GLOBAL_VARIABLE']]['arParams'] = $arParams;
$GLOBALS[$arParams['GLOBAL_VARIABLE']]['arResult'] = $arResult;
$GLOBALS[$arParams['GLOBAL_VARIABLE']]['templateData'] = $templateData;
