<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */

$component = $this->getComponent();
$arParams = $component->applyTemplateModifications();

\Gsv\Helpers\Runa::modifyProduct($arResult);

if($arParams['USE_PRODUCT_QUANTITY'] == 'Y' && $arParams['PRODUCT_QUANTITY_VARIABLE']) {
    $arResult['JS_DATA']['QUANTITY_VAR'] = $arParams['PRODUCT_QUANTITY_VARIABLE'];
}

$arResult['JS_DATA']['ID'] = 'product-' . $this->randString(12);

$this->__component->SetResultCacheKeys(array('DETAIL_PAGE_URL', 'SECTION_PATH'));