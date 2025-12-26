<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $templateData */
/** @var @global CMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */

if($arParams['GLOBAL_VARIABLE'])
{
    $GLOBALS[$arParams['GLOBAL_VARIABLE']] = $arResult['BASKET'];
}
