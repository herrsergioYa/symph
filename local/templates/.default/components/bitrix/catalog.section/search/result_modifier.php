<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

//require __DIR__ . '/../category/result_modifier.php';

//$arImgSize = ['width' => 90];

\Gsv\Helpers\AndBr::modifyProducts($arResult['ITEMS'], 8);
//\Gsv\Helpers\AndBr::modifySectionName($arResult);

\Gsv\Helpers\AndBr::sortItems($arParams['CUSTOM_SORT_ORDER'] ?? false, $arResult['ITEMS']);

