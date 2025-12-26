<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

if($arParams["ELEMENT_ID"] > 0){
    $arResult["OFFER_ID_SELECTED"] = intval($arParams["ELEMENT_ID"]);
} else {
    $arResult["OFFER_ID_SELECTED"] = 0;
}

\Gsv\Helpers\Wos::modifyGiftCardSection($arResult);

$arResult['JS_DATA']['ROOT_ID'] = 'product-' . $this->randString(12);
///$arResult['JS_DATA']['ADD_TO_BASKET_URL'] = 'product-' . $this->randString(12);