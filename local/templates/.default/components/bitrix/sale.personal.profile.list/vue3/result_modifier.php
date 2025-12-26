<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

require __DIR__ . '/include/profiles_modifier.php';

$rootId = $this->randString(18);
$rootId = 'vue-sale-profile-' . $rootId;

$ajaxUrl = '/local/ajax/sale_profile.php?';
$ajaxUrl .= \Gsv\Ajax\SaleProfile::buildQuery();

$arResult['VUE'] = [
    'ROOT_ID' => $rootId,
    'MAIN_AJAX_URL' => $ajaxUrl,
    'AJAX_KEY' => 'AJAX_' . $this->randString(12),
    'AJAX_URL' => POST_FORM_ACTION_URI,
    'MESSAGES' => \data::getAll(),
];

$arResult['COUNTRIES'] = [];
$rsCountries = \Bitrix\Sale\Location\LocationTable::getList([
    'filter' => [
        'TYPE_ID' => 1,
        'NAME.LANGUAGE_ID' => LANGUAGE_ID,
    ],
    'select' => [
        'COUNTRY_IDX' => 'ID',
        'COUNTRY' => 'NAME.NAME',
    ],
]);
while($arCountry = $rsCountries->fetch()) {
    $arCountry['COUNTRY_ID'] = $arCountry['COUNTRY_IDX'];
    unset($arCountry['COUNTRY_IDX']);

    $arResult['COUNTRIES'][$arCountry['COUNTRY_ID']] = $arCountry;
}