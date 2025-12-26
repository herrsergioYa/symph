<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */

/** @var CBitrixComponent $component */
$component = $this->__component;
/** @var CBitrixComponentTemplate $template */
$template = $this;

$nameField = LANGUAGE_UPPER == 'RU' ? 'NAME' : 'PROPERTY_NAME_' . LANGUAGE_UPPER;
$arSorting = [
    'CURRENT' => '',
    'ITEMS' => [
        '' => ["", "", 'NAME' => \data::get('SORT_NONE')],

        'price_asc' => ["SCALED_PRICE_" . CATALOG_PRICE_ID, 'NAME' => \data::get('SORT_PRICE_ASC')],
        'price_desc' => ["SCALED_PRICE_" . CATALOG_PRICE_ID, "DESC", 'NAME' => \data::get('SORT_PRICE_DESC')],

        'name_asc' => [$nameField, "ASC", 'NAME' => \data::get('SORT_NAME_ASC')],
        'name_desc' => [$nameField, "DESC", 'NAME' => \data::get('SORT_NAME_DESC')],

        'new' => ["ID", "DESC", 'NAME' => \data::get('SORT_NEW')],
        'old' => ["ID", "ASC", 'NAME' => \data::get('SORT_OLD')],
    ],
];

if($arParams['SORT_NAME']) {
    $arSortParam = &$GLOBALS[$arParams['SORT_NAME']];
} else {
    $arSortParam = [];
}
if (empty($arSortParam)) {
    $arSortParam = [
        "ELEMENT_SORT_FIELD" => "sort",
        "ELEMENT_SORT_FIELD2" => "id",
        "ELEMENT_SORT_ORDER" => "asc",
        "ELEMENT_SORT_ORDER2" => "desc",
    ];
}

unset($_SESSION["sort"], $_SESSION["sale_only"]);
foreach ($arSorting['ITEMS'] as $sort => $arSort) {
    if ($_REQUEST["sort"] == $sort || (empty($_REQUEST["sort"]) && $_SESSION["sort"] == $sort)) {
        if($arSort[0]) {
            $arSortParam["ELEMENT_SORT_FIELD2"] = $arSortParam["ELEMENT_SORT_FIELD"];
            $arSortParam["ELEMENT_SORT_ORDER2"] = $arSortParam["ELEMENT_SORT_ORDER"];
            $arSortParam["ELEMENT_SORT_FIELD"] = $arSort[0];
            $arSortParam["ELEMENT_SORT_ORDER"] = $arSort[1] ?: 'ASC';
        }
        //$_SESSION["sort"] = $sort;

        $arSorting['ITEMS'][$sort]['SELECTED'] = true;
        $arSorting['CURRENT'] = $sort;
    } else {
        $arSorting['ITEMS'][$sort]['SELECTED'] = false;
    }
}

unset($arSortParam);

$arResult = $arSorting;