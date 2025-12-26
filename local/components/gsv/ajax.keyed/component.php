<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/** @var CBitrixComponent $this */
/** @var array $arParams */
/** @var array $arResult */
/** @var string $componentPath */
/** @var string $componentName */
/** @var string $componentTemplate */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

$component = $this;

$arResult['AJAX_KEY'] =  $component->randString(48);
$component->SetResultCacheKeys(['AJAX_KEY']);

if($this->startResultCache(false, array(($arParams["CACHE_GROUPS"]==="N"? false: $USER->GetGroups()))))
{
    $this->includeComponentTemplate();
}

if($_POST['AJAX_KEY'] === $arResult['AJAX_KEY'])
{
    $result = ['success' => 'Y'];

    $APPLICATION->RestartBuffer();
    header("Content-Type: application/json");
    echo json_encode($result);
    die();
}