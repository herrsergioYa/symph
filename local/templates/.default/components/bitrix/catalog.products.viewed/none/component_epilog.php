<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $templateData */
/** @var @global CMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */

if(empty($arParams['GLOBAL_VARIABLE']))
{
    $arParams['GLOBAL_VARIABLE'] = 'arrComponentData';
}

/*$GLOBALS[$arParams['GLOBAL_VARIABLE']]['arParams'] = $arParams;
$GLOBALS[$arParams['GLOBAL_VARIABLE']]['arResult'] = $arResult;
$GLOBALS[$arParams['GLOBAL_VARIABLE']]['templateData'] = $templateData;*/
$GLOBALS[$arParams['GLOBAL_VARIABLE']] = $arResult['ELEMENTS'];
