<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */

$arResult['AJAX_KEY'] = $this->randString(32);

if($_REQUEST['AJAX_KEY'] == $arResult['AJAX_KEY'])
{
    $APPLICATION->RestartBuffer();
    header('Content-Type; application/json');
    echo json_encode($arResult['DATA']);
    die();
}
