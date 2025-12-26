<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
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

if($_REQUEST['AJAX_KEY'] === $arResult['AJAX_KEY'])
{
    $result = ['success' => 'Y',];

    $APPLICATION->RestartBuffer();
    header("Content-Type: application/json");
    echo json_encode($result);
    die();
}