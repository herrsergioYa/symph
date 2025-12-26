<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */

$obj = new stdClass();
$obj->A = 45;
$obj->B = 54;

$arResult['ITEMS'] = [
    1 => [
        'NAME' => 'Test',
        'VALUE' => 'Text,'
    ],
    0 =>[
        'NAME' => 'Test2',
        'VALUE' => 'Text2'
    ],
];

$arResult['OBJ'] = $obj;
$arResult['JS_OBJ'] = json_encode($obj);