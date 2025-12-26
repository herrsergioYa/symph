<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @var \CBitrixComponent $component */

$arElements = $arParams['ID'];//List of integer

(function (/*&$arElements*/) use($arElements) {
    require __DIR__ . '/include/elements_modifier.php';
})(/*$arElements*/);

$arResult['BASKET'] = $arElements['BASKET'];

$this->__component->SetResultCacheKeys(['BASKET']);
