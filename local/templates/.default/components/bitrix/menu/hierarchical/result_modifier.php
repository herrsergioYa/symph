<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
$this->setFrameMode(true);

$arResult = [
    'MENU' => $arResult,
    'ITEMS' => [],
];

if($arResult['MENU'])
{
    $arTree = [
        'CHILDREN' => [],
        'DEPTH_LEVEL' => 0,
    ];
    $arLastItem = [&$arTree];
    foreach ($arResult['MENU'] as $arItem)
    {
        if($arItem['IS_PARENT'])
            $arItem['CHILDREN'] = [];
        $arLastItem[$arItem['DEPTH_LEVEL'] - 1]['CHILDREN'][] = &$arItem;
        $arLastItem[$arItem['DEPTH_LEVEL']] = &$arItem;
        unset($arItem);
    }
    $arResult['ITEMS'] = $arTree['CHILDREN'];
}
