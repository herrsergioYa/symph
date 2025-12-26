<?
/**
 * @global CMain $APPLICATION
 * @global CUser $USER
 * @var array $arParams
 * @var array $arResult
 */
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
    die();

if($_REQUEST['AJAX_KEY'] == $arResult['VUE']['AJAX_KEY'])
{
    $APPLICATION->RestartBuffer();
    header('Content-Type: application/json');
    echo json_encode($arResult);
    die();
}
