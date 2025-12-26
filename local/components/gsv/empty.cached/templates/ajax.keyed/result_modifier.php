<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */

$component = $this->__component;
$arResult['AJAX_KEY'] = $component->randString(32);
$component->SetResultCacheKeys(['AJAX_KEY']);