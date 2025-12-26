<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */


foreach ($arResult['LANG'] as $lang => &$arLang)
{
    $country = $lang;
    if($country == 'en')
    {
        $country = 'gb';
    }
    $url = "https://flagcdn.com/$country.svg";

    $arLang['ICO'] = $url;
    $arLang['WIDTH'] = 30;
}
unset($arLang);
