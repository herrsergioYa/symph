<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */

$arResult['JS_DATA'] = [
    'NAME' => 'Sergey',
    'LAST_NAME' => 'Gaevoy',
];

$arResult['ROOT_ID'] = 'app-' . $this->randString();