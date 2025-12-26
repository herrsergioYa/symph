<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
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
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

//$GLOBALS["IS_CATALOG"] = true;
//$APPLICATION->SetPageProperty("page-type", "catalog");

/*$APPLICATION->IncludeComponent(
    "bitrix:main.include",
    "",
    array(
        "SECTION_ID" => SIGNATURES_SECTION_ID_CATALOG,
        "AREA_FILE_SHOW" => "file",
        "AREA_FILE_SUFFIX" => "inc",
        "PATH" => SITE_DIR."content/signatures.php"
    )
);*/

$this->setFrameMode(false);

/*$arParams["ELEMENT_SORT_FIELD2"] = $arParams["ELEMENT_SORT_FIELD"];
$arParams["ELEMENT_SORT_ORDER2"] = $arParams["ELEMENT_SORT_ORDER"];
if ($_REQUEST["sort"] == "new" || (!isset($_REQUEST["sort"]) && $_SESSION["sort"] == "new")) {
    $arParams["ELEMENT_SORT_FIELD"]  = "ID";
    $arParams["ELEMENT_SORT_ORDER"]  = "DESC";
    $_SESSION["sort"] = "new";
} elseif ($_REQUEST["sort"] == "price" || (!isset($_REQUEST["sort"]) && $_SESSION["sort"] == "price")) {
    $arParams["ELEMENT_SORT_FIELD"]  = "PRICE_2";
    $arParams["ELEMENT_SORT_ORDER"]  = "ASC";
    $_SESSION["sort"] = "price";
} elseif ($_REQUEST["sort"] == "best" || (!isset($_REQUEST["sort"]) && $_SESSION["sort"] == "best")) {
    $arParams["ELEMENT_SORT_FIELD"]  = "PRICE_2";
    $arParams["ELEMENT_SORT_ORDER"]  = "DESC";
    $_SESSION["sort"] = "best";
} else {
    $sortCode = $GLOBALS["CUR_DIR_FULL"];
    $sortCode = substr($sortCode, strlen(SITE_DIR));
    $sortCode = substr($sortCode, 0, -1);
    $sortCode = str_replace(["/", "-"], "_", $sortCode);
    $sortCode = "PROPERTY_SORT_".strtoupper($sortCode);

    $arParams["ELEMENT_SORT_FIELD"]  = $sortCode;
    $arParams["ELEMENT_SORT_ORDER"]  = "ASC";

    $_SESSION["sort"] = "default";
}*/

$arElements = $APPLICATION->IncludeComponent(
    "bitrix:search.page",
    "and",
    [
        "RESTART" => !empty($arParams["SEARCH_RESTART"]) ? $arParams["SEARCH_RESTART"] : "N",
        "NO_WORD_LOGIC" => !empty($arParams["SEARCH_NO_WORD_LOGIC"]) ? $arParams["SEARCH_NO_WORD_LOGIC"] : "Y",
        "USE_LANGUAGE_GUESS" => !empty($arParams["SEARCH_USE_LANGUAGE_GUESS"]) ? $arParams["SEARCH_USE_LANGUAGE_GUESS"] : "Y",
        "CHECK_DATES" => !empty($arParams["SEARCH_CHECK_DATES"]) ? $arParams["SEARCH_CHECK_DATES"] : "Y",
        "arrFILTER" => [
            "iblock_".$arParams["IBLOCK_TYPE"],
        ],
        "arrFILTER_iblock_".$arParams["IBLOCK_TYPE"] => [
            $arParams["IBLOCK_ID"],
        ]	,
        "USE_TITLE_RANK" => $arParams['USE_TITLE_RANK'],
        "DEFAULT_SORT" => "rank",
        "FILTER_NAME" => "",
        "SHOW_WHERE" => "N",
        "arrWHERE" => [],
        "SHOW_WHEN" => "N",
        "PAGE_RESULT_COUNT" => !empty($arParams["SEARCH_PAGE_RESULT_COUNT"]) ? $arParams["SEARCH_PAGE_RESULT_COUNT"] : $arParams["PAGE_ELEMENT_COUNT"],
        "DISPLAY_TOP_PAGER" => $arParams["DISPLAY_TOP_PAGER"],
        "DISPLAY_BOTTOM_PAGER" => $arParams["DISPLAY_BOTTOM_PAGER"],
        "PAGER_TITLE" => $arParams["PAGER_TITLE"],
        "PAGER_SHOW_ALWAYS" => $arParams["PAGER_SHOW_ALWAYS"],
        "PAGER_TEMPLATE" => $arParams["PAGER_TEMPLATE"],
    ],
    $component,
    [
        'HIDE_ICONS' => 'Y',
    ]
);

$sTitle = str_replace('#QUERY#', $_REQUEST['q'], 'Поиск по запросу "#QUERY#"');
if($arParams['SET_TITLE'] == 'Y') {
    //We are NOT cached!! It is safe!
    $APPLICATION->SetTitle($sTitle);
}
if($arParams['SEARCH_BROWSER_TITLE'] == 'Y') {
    //We are NOT cached!! It is safe!
    $APPLICATION->SetPageProperty('title', $sTitle);
}

$sCatalogSearchFilterName = 'arCatalogSearchFilter';
if($arParams['FILTER_NAME']) {
    $sCatalogSearchFilterName = $arParams['FILTER_NAME'] . '_' . $sCatalogSearchFilterName;
}
$GLOBALS[$sCatalogSearchFilterName] = [
    'ID' => array_values(array_unique($arElements)),
];

if($arParams['FILTER_NAME']) {
    if(is_array($GLOBALS[$arParams['FILTER_NAME']])) {
        $GLOBALS[$sCatalogSearchFilterName][] = $GLOBALS[$arParams['FILTER_NAME']];
    }
}

$arParams['FILTER_NAME'] = $sCatalogSearchFilterName;
//$arResult["VARIABLES"] = [];
?>
<div class="container-fluid">
    <div class="d-flex align-items-center pr py-3 py-lg-4">
        <div class="d-none d-lg-flex">
            <ol itemscope itemtype="https://schema.org/BreadcrumbList" class="d-flex fs-13 breadcrumb">
                <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem" class="breadcrumb-item">
                    <a itemprop="item" href="/magazin/"><span itemprop="name">Каталог</span></a>
                    <meta itemprop="position" content="1">
                </li>
                <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem" class="breadcrumb-item active">
                    <span itemprop="name"><?=$sTitle?></span>
                    <meta itemprop="position" content="2">
                </li>
            </ol>
        </div>
        <h1 class="d-none d-lg-flex catalog-title"><?= $sTitle ?></h1>
        <div class="ms-auto filter-wrap">
            <?/*button class="fs-13 p-3 m-n3 btn btn-link btn-filter">
                <span class="d-none d-lg-flex">Фильтры</span>
                <svg width="15" height="13" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <mask id="a" fill="#fff">
                        <path d="M0 2.5h15v1H0v-1Z"/>
                    </mask>
                    <path d="M0 2.5h15v1H0v-1Z" fill="#020203"/>
                    <path d="M0 2.5v-1h-1v1h1Zm15 0h1v-1h-1v1Zm0 1v1h1v-1h-1Zm-15 0h-1v1h1v-1Zm0 0h15v-2H0v2Zm14-1v1h2v-1h-2Zm1 0H0v2h15v-2Zm-14 1v-1h-2v1h2Z" fill="#020203" mask="url(#a)"/>
                    <mask id="b" fill="#fff">
                        <path d="M0 9.5h15v1H0v-1Z"/>
                    </mask>
                    <path d="M0 9.5h15v1H0v-1Z" fill="#020203"/>
                    <path d="M0 9.5v-1h-1v1h1Zm15 0h1v-1h-1v1Zm0 1v1h1v-1h-1Zm-15 0h-1v1h1v-1Zm0 0h15v-2H0v2Zm14-1v1h2v-1h-2Zm1 0H0v2h15v-2Zm-14 1v-1h-2v1h2Z" fill="#020203" mask="url(#b)"/>
                    <circle cx="5" cy="10" r="2.5" fill="#fff" stroke="#020203"/>
                    <circle cx="10" cy="3" r="2.5" fill="#fff" stroke="#020203"/>
                </svg>
            </button*/?>
        </div>
    </div>
</div>
<?$APPLICATION->IncludeComponent(
    "bitrix:catalog.section",
    "category",
    array(
        "IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
        "IBLOCK_ID" => $arParams["IBLOCK_ID"],
        "ELEMENT_SORT_FIELD" => $arParams["ELEMENT_SORT_FIELD"],
        "ELEMENT_SORT_ORDER" => $arParams["ELEMENT_SORT_ORDER"],
        "ELEMENT_SORT_FIELD2" => $arParams["ELEMENT_SORT_FIELD2"],
        "ELEMENT_SORT_ORDER2" => $arParams["ELEMENT_SORT_ORDER2"],
        "PROPERTY_CODE" => (isset($arParams["LIST_PROPERTY_CODE"]) ? $arParams["LIST_PROPERTY_CODE"] : []),
        "PROPERTY_CODE_MOBILE" => $arParams["LIST_PROPERTY_CODE_MOBILE"],
        "META_KEYWORDS" => $arParams["LIST_META_KEYWORDS"],
        "META_DESCRIPTION" => $arParams["LIST_META_DESCRIPTION"],
        "BROWSER_TITLE" => $arParams["LIST_BROWSER_TITLE"],
        "SET_LAST_MODIFIED" => $arParams["SET_LAST_MODIFIED"],
        "INCLUDE_SUBSECTIONS" => $arParams["INCLUDE_SUBSECTIONS"],
        "BASKET_URL" => $arParams["BASKET_URL"],
        "ACTION_VARIABLE" => $arParams["ACTION_VARIABLE"],
        "PRODUCT_ID_VARIABLE" => $arParams["PRODUCT_ID_VARIABLE"],
        "SECTION_ID_VARIABLE" => $arParams["SECTION_ID_VARIABLE"],
        "PRODUCT_QUANTITY_VARIABLE" => $arParams["PRODUCT_QUANTITY_VARIABLE"],
        "PRODUCT_PROPS_VARIABLE" => $arParams["PRODUCT_PROPS_VARIABLE"],
        "FILTER_NAME" => $arParams["FILTER_NAME"],
        "CACHE_TYPE" => $arParams["CACHE_TYPE"],
        "CACHE_TIME" => $arParams["CACHE_TIME"],
        "CACHE_FILTER" => $arParams["CACHE_FILTER"],
        "CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
        "SET_TITLE" => $arParams["SET_TITLE"],
        "MESSAGE_404" => $arParams["~MESSAGE_404"],
        "SET_STATUS_404" => $arParams["SET_STATUS_404"],
        "SHOW_404" => $arParams["SHOW_404"],
        "FILE_404" => $arParams["FILE_404"],
        "DISPLAY_COMPARE" => $arParams["USE_COMPARE"],
        "PAGE_ELEMENT_COUNT" => $arParams["PAGE_ELEMENT_COUNT"],
        "LINE_ELEMENT_COUNT" => $arParams["LINE_ELEMENT_COUNT"],
        "PRICE_CODE" => $arParams["~PRICE_CODE"],
        "USE_PRICE_COUNT" => $arParams["USE_PRICE_COUNT"],
        "SHOW_PRICE_COUNT" => $arParams["SHOW_PRICE_COUNT"],

        "PRICE_VAT_INCLUDE" => $arParams["PRICE_VAT_INCLUDE"],
        "USE_PRODUCT_QUANTITY" => $arParams['USE_PRODUCT_QUANTITY'],
        "ADD_PROPERTIES_TO_BASKET" => (isset($arParams["ADD_PROPERTIES_TO_BASKET"]) ? $arParams["ADD_PROPERTIES_TO_BASKET"] : ''),
        "PARTIAL_PRODUCT_PROPERTIES" => (isset($arParams["PARTIAL_PRODUCT_PROPERTIES"]) ? $arParams["PARTIAL_PRODUCT_PROPERTIES"] : ''),
        "PRODUCT_PROPERTIES" => (isset($arParams["PRODUCT_PROPERTIES"]) ? $arParams["PRODUCT_PROPERTIES"] : []),

        "DISPLAY_TOP_PAGER" => $arParams["DISPLAY_TOP_PAGER"],
        "DISPLAY_BOTTOM_PAGER" => $arParams["DISPLAY_BOTTOM_PAGER"],
        "PAGER_TITLE" => $arParams["PAGER_TITLE"],
        "PAGER_SHOW_ALWAYS" => $arParams["PAGER_SHOW_ALWAYS"],
        "PAGER_TEMPLATE" => $arParams["PAGER_TEMPLATE"],
        "PAGER_DESC_NUMBERING" => $arParams["PAGER_DESC_NUMBERING"],
        "PAGER_DESC_NUMBERING_CACHE_TIME" => $arParams["PAGER_DESC_NUMBERING_CACHE_TIME"],
        "PAGER_SHOW_ALL" => $arParams["PAGER_SHOW_ALL"],
        "PAGER_BASE_LINK_ENABLE" => $arParams["PAGER_BASE_LINK_ENABLE"],
        "PAGER_BASE_LINK" => $arParams["PAGER_BASE_LINK"],
        "PAGER_PARAMS_NAME" => $arParams["PAGER_PARAMS_NAME"],
        "LAZY_LOAD" => $arParams["LAZY_LOAD"],
        "MESS_BTN_LAZY_LOAD" => $arParams["~MESS_BTN_LAZY_LOAD"],
        "LOAD_ON_SCROLL" => $arParams["LOAD_ON_SCROLL"],

        "OFFERS_CART_PROPERTIES" => (isset($arParams["OFFERS_CART_PROPERTIES"]) ? $arParams["OFFERS_CART_PROPERTIES"] : []),
        "OFFERS_FIELD_CODE" => $arParams["LIST_OFFERS_FIELD_CODE"],
        "OFFERS_PROPERTY_CODE" => (isset($arParams["LIST_OFFERS_PROPERTY_CODE"]) ? $arParams["LIST_OFFERS_PROPERTY_CODE"] : []),
        "OFFERS_SORT_FIELD" => $arParams["OFFERS_SORT_FIELD"],
        "OFFERS_SORT_ORDER" => $arParams["OFFERS_SORT_ORDER"],
        "OFFERS_SORT_FIELD2" => $arParams["OFFERS_SORT_FIELD2"],
        "OFFERS_SORT_ORDER2" => $arParams["OFFERS_SORT_ORDER2"],
        "OFFERS_LIMIT" => (isset($arParams["LIST_OFFERS_LIMIT"]) ? $arParams["LIST_OFFERS_LIMIT"] : 0),

        "SHOW_ALL_WO_SECTION" => "Y",
        "SECTION_ID" => $arResult["VARIABLES"]["SECTION_ID"],
        "SECTION_CODE" => $arResult["VARIABLES"]["SECTION_CODE"],
        "SECTION_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["section"],
        "DETAIL_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["element"],
        "USE_MAIN_ELEMENT_SECTION" => $arParams["USE_MAIN_ELEMENT_SECTION"],
        'CONVERT_CURRENCY' => $arParams['CONVERT_CURRENCY'],
        'CURRENCY_ID' => $arParams['CURRENCY_ID'],
        'HIDE_NOT_AVAILABLE' => $arParams["HIDE_NOT_AVAILABLE"],
        'HIDE_NOT_AVAILABLE_OFFERS' => $arParams["HIDE_NOT_AVAILABLE_OFFERS"],
        "SECTION_USER_FIELDS" => array("UF_NAME_EN", "UF_DESCRIPTION_EN", "UF_DESCRIPTION_RU"),

        'LABEL_PROP' => $arParams['LABEL_PROP'],
        'LABEL_PROP_MOBILE' => $arParams['LABEL_PROP_MOBILE'],
        'LABEL_PROP_POSITION' => $arParams['LABEL_PROP_POSITION'],
        'ADD_PICT_PROP' => $arParams['ADD_PICT_PROP'],
        'PRODUCT_DISPLAY_MODE' => $arParams['PRODUCT_DISPLAY_MODE'],
        'PRODUCT_BLOCKS_ORDER' => $arParams['LIST_PRODUCT_BLOCKS_ORDER'],
        'PRODUCT_ROW_VARIANTS' => $arParams['LIST_PRODUCT_ROW_VARIANTS'],
        'ENLARGE_PRODUCT' => $arParams['LIST_ENLARGE_PRODUCT'],
        'ENLARGE_PROP' => isset($arParams['LIST_ENLARGE_PROP']) ? $arParams['LIST_ENLARGE_PROP'] : '',
        'SHOW_SLIDER' => $arParams['LIST_SHOW_SLIDER'],
        'SLIDER_INTERVAL' => isset($arParams['LIST_SLIDER_INTERVAL']) ? $arParams['LIST_SLIDER_INTERVAL'] : '',
        'SLIDER_PROGRESS' => isset($arParams['LIST_SLIDER_PROGRESS']) ? $arParams['LIST_SLIDER_PROGRESS'] : '',

        'OFFER_ADD_PICT_PROP' => $arParams['OFFER_ADD_PICT_PROP'],
        'OFFER_TREE_PROPS' => (isset($arParams['OFFER_TREE_PROPS']) ? $arParams['OFFER_TREE_PROPS'] : []),
        'PRODUCT_SUBSCRIPTION' => $arParams['PRODUCT_SUBSCRIPTION'],
        'SHOW_DISCOUNT_PERCENT' => $arParams['SHOW_DISCOUNT_PERCENT'],
        'DISCOUNT_PERCENT_POSITION' => $arParams['DISCOUNT_PERCENT_POSITION'],
        'SHOW_OLD_PRICE' => $arParams['SHOW_OLD_PRICE'],
        'SHOW_MAX_QUANTITY' => $arParams['SHOW_MAX_QUANTITY'],
        'MESS_SHOW_MAX_QUANTITY' => (isset($arParams['~MESS_SHOW_MAX_QUANTITY']) ? $arParams['~MESS_SHOW_MAX_QUANTITY'] : ''),
        'RELATIVE_QUANTITY_FACTOR' => (isset($arParams['RELATIVE_QUANTITY_FACTOR']) ? $arParams['RELATIVE_QUANTITY_FACTOR'] : ''),
        'MESS_RELATIVE_QUANTITY_MANY' => (isset($arParams['~MESS_RELATIVE_QUANTITY_MANY']) ? $arParams['~MESS_RELATIVE_QUANTITY_MANY'] : ''),
        'MESS_RELATIVE_QUANTITY_FEW' => (isset($arParams['~MESS_RELATIVE_QUANTITY_FEW']) ? $arParams['~MESS_RELATIVE_QUANTITY_FEW'] : ''),
        'MESS_BTN_BUY' => (isset($arParams['~MESS_BTN_BUY']) ? $arParams['~MESS_BTN_BUY'] : ''),
        'MESS_BTN_ADD_TO_BASKET' => (isset($arParams['~MESS_BTN_ADD_TO_BASKET']) ? $arParams['~MESS_BTN_ADD_TO_BASKET'] : ''),
        'MESS_BTN_SUBSCRIBE' => (isset($arParams['~MESS_BTN_SUBSCRIBE']) ? $arParams['~MESS_BTN_SUBSCRIBE'] : ''),
        'MESS_BTN_DETAIL' => (isset($arParams['~MESS_BTN_DETAIL']) ? $arParams['~MESS_BTN_DETAIL'] : ''),
        'MESS_NOT_AVAILABLE' => (isset($arParams['~MESS_NOT_AVAILABLE']) ? $arParams['~MESS_NOT_AVAILABLE'] : ''),
        'MESS_BTN_COMPARE' => (isset($arParams['~MESS_BTN_COMPARE']) ? $arParams['~MESS_BTN_COMPARE'] : ''),

        'USE_ENHANCED_ECOMMERCE' => (isset($arParams['USE_ENHANCED_ECOMMERCE']) ? $arParams['USE_ENHANCED_ECOMMERCE'] : ''),
        'DATA_LAYER_NAME' => (isset($arParams['DATA_LAYER_NAME']) ? $arParams['DATA_LAYER_NAME'] : ''),
        'BRAND_PROPERTY' => (isset($arParams['BRAND_PROPERTY']) ? $arParams['BRAND_PROPERTY'] : ''),

        'TEMPLATE_THEME' => (isset($arParams['TEMPLATE_THEME']) ? $arParams['TEMPLATE_THEME'] : ''),
        "ADD_SECTIONS_CHAIN" => "N",
        'ADD_TO_BASKET_ACTION' => $basketAction,
        'SHOW_CLOSE_POPUP' => isset($arParams['COMMON_SHOW_CLOSE_POPUP']) ? $arParams['COMMON_SHOW_CLOSE_POPUP'] : '',
        'COMPARE_PATH' => $arResult['FOLDER'].$arResult['URL_TEMPLATES']['compare'],
        'COMPARE_NAME' => $arParams['COMPARE_NAME'],
        'BACKGROUND_IMAGE' => (isset($arParams['SECTION_BACKGROUND_IMAGE']) ? $arParams['SECTION_BACKGROUND_IMAGE'] : ''),
        'COMPATIBLE_MODE' => (isset($arParams['COMPATIBLE_MODE']) ? $arParams['COMPATIBLE_MODE'] : ''),
        'DISABLE_INIT_JS_IN_COMPONENT' => (isset($arParams['DISABLE_INIT_JS_IN_COMPONENT']) ? $arParams['DISABLE_INIT_JS_IN_COMPONENT'] : ''),

        'CUSTOM_SORT_ORDER' => $GLOBALS[$sCatalogSearchFilterName]['ID'],
        'DONT_SET_TITLE' => 'Y',
    ),
    $component
);?>