<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
global $arCatalogSearchTitleFilter;

$arCatalogSearchTitleFilter = [
     'ID' => $arResult['ITEMS'] ?: false,
];
global $priceCode, $currency;
$arCatalogSearchTitleFilter["ACTIVE"] = "Y";
$arCatalogSearchTitleFilter["CATALOG_AVAILABLE"] = "Y";
$arCatalogSearchTitleFilter["PROPERTY_HIDE"] = false;
$arCatalogSearchTitleFilter["!PROPERTY_MORE_PHOTO"] = false;
//gsv_dump($arResult['SECTIONS']);
?>
<div class="row align-items-start">
    <ul class="col-sm-4 col-md-3 col-lg-2 py-md-3 lh-1_25">
        <?foreach ($arResult['SECTIONS'] as $arSection):?>
        <li><a class="dib py-1" href="<?= $arSection['URL'] ?>"><?= $arSection['NAME'] ?></a></li>
        <?endforeach;?>
    </ul>
    <?$intSectionID = $APPLICATION->IncludeComponent(
        "bitrix:catalog.section",
        "search",
        array(
            //"MERCH" => ($arResult["VARIABLES"]["SECTION_ID"] && isMerch()) ? "Y" : "N",
            /*"CUR_DIR_FULL" => $APPLICATION->GetCurDir(),
            "CUR_DIR_WO_FILTER" => $GLOBALS["CUR_DIR_FULL"],
            "CUR_DIR" => $GLOBALS["CUR_DIR"],
            "IS_MOBILE" => $GLOBALS["IS_MOBILE"] ? "Y" : "N",
            "IS_FILTER" => $GLOBALS["IS_FILTER"] ? "Y" : "N",
            "FILTER_CHECKED" => $GLOBALS["CHECKED"],*/
            "IBLOCK_ID" => CATALOG_IBLOCK_ID,
            "IBLOCK_TYPE" => IBLOCK_TYPE,
            /*"ELEMENT_SORT_FIELD" => $arParams["ELEMENT_SORT_FIELD"],
            "ELEMENT_SORT_ORDER" => $arParams["ELEMENT_SORT_ORDER"],
            "ELEMENT_SORT_FIELD2" => $arParams["ELEMENT_SORT_FIELD2"],
            "ELEMENT_SORT_ORDER2" => $arParams["ELEMENT_SORT_ORDER2"],*/
            "PROPERTY_CODE" => ['NAME_' . LANGUAGE_UPPER],//(isset($arParams["LIST_PROPERTY_CODE"]) ? $arParams["LIST_PROPERTY_CODE"] : []),
            "PROPERTY_CODE_MOBILE" => ['NAME_' . LANGUAGE_UPPER],//$arParams["LIST_PROPERTY_CODE_MOBILE"],
          //  "META_KEYWORDS" => $arParams["LIST_META_KEYWORDS"],
            //"META_DESCRIPTION" => $arParams["LIST_META_DESCRIPTION"],
            "BROWSER_TITLE" => '-',//$arParams["LIST_BROWSER_TITLE"],
            "SET_LAST_MODIFIED" => 'N', //$arParams["SET_LAST_MODIFIED"],
            "INCLUDE_SUBSECTIONS" =>'Y',// $arParams["INCLUDE_SUBSECTIONS"],
         //   "BASKET_URL" => $arParams["BASKET_URL"],
          //  "ACTION_VARIABLE" => $arParams["ACTION_VARIABLE"],
        //    "PRODUCT_ID_VARIABLE" => $arParams["PRODUCT_ID_VARIABLE"],
         //   "SECTION_ID_VARIABLE" => $arParams["SECTION_ID_VARIABLE"],
         //   "PRODUCT_QUANTITY_VARIABLE" => $arParams["PRODUCT_QUANTITY_VARIABLE"],
         //   "PRODUCT_PROPS_VARIABLE" => $arParams["PRODUCT_PROPS_VARIABLE"],
            "FILTER_NAME" => 'arCatalogSearchTitleFilter',$arParams["FILTER_NAME"],
            "CACHE_TYPE" => 'N',//$arParams["CACHE_TYPE"],
            "CACHE_TIME" => 0,//$arParams["CACHE_TIME"],
            "CACHE_FILTER" => 'N',//$arParams["CACHE_FILTER"],
            "CACHE_GROUPS" => 'Y',//$arParams["CACHE_GROUPS"],
            "SET_TITLE" => 'N',//$arParams["SET_TITLE"],
           // "MESSAGE_404" => $arParams["~MESSAGE_404"],
            "SET_STATUS_404" => 'N',//$arParams["SET_STATUS_404"],
            "SHOW_404" => 'N',//'$arParams["SHOW_404"],
            "FILE_404" => '',//$arParams["FILE_404"],
            "DISPLAY_COMPARE" => 'N',//'$arParams["USE_COMPARE"],
            //"PAGE_CUSTOM_NUMBER" => $arParams["PAGE_CUSTOM_NUMBER"],
            //"PAGE_DEFAULT_COUNT" => $arParams["PAGE_DEFAULT_COUNT"],
            "PAGE_ELEMENT_COUNT" => $arParams["TOP_COUNT"] >> 2,//$arParams["PAGE_ELEMENT_COUNT"],
            //"LINE_ELEMENT_COUNT" => $arParams["LINE_ELEMENT_COUNT"],
            "PRICE_CODE" => array($priceCode),
            "PRICE_VAT_INCLUDE" => "Y",
            "PRICE_VAT_SHOW_VALUE" => "N",
            "USE_PRICE_COUNT" => 'N',//$arParams["USE_PRICE_COUNT"],
            "SHOW_PRICE_COUNT" => 1,//$arParams["SHOW_PRICE_COUNT"],

            //"ADD_PROPERTIES_TO_BASKET" => (isset($arParams["ADD_PROPERTIES_TO_BASKET"]) ? $arParams["ADD_PROPERTIES_TO_BASKET"] : ''),
            //"PARTIAL_PRODUCT_PROPERTIES" => (isset($arParams["PARTIAL_PRODUCT_PROPERTIES"]) ? $arParams["PARTIAL_PRODUCT_PROPERTIES"] : ''),
            //"PRODUCT_PROPERTIES" => (isset($arParams["PRODUCT_PROPERTIES"]) ? $arParams["PRODUCT_PROPERTIES"] : []),

            "DISPLAY_TOP_PAGER" => 'N',//$arParams["DISPLAY_TOP_PAGER"],
            "DISPLAY_BOTTOM_PAGER" => 'N',//$arParams["DISPLAY_BOTTOM_PAGER"],
            "PAGER_TITLE" => '',//$arParams["PAGER_TITLE"],
            "PAGER_SHOW_ALWAYS" => 'N',//$arParams["PAGER_SHOW_ALWAYS"],
            "PAGER_TEMPLATE" => '',//$arParams["PAGER_TEMPLATE"],
            "PAGER_DESC_NUMBERING" => 'N',//$arParams["PAGER_DESC_NUMBERING"],
            "PAGER_DESC_NUMBERING_CACHE_TIME" => 0,//$arParams["PAGER_DESC_NUMBERING_CACHE_TIME"],
            "PAGER_SHOW_ALL" => 'N',//$arParams["PAGER_SHOW_ALL"],
           // "PAGER_BASE_LINK_ENABLE" => $arParams["PAGER_BASE_LINK_ENABLE"],
            //"PAGER_BASE_LINK" => $arParams["PAGER_BASE_LINK"],
            //"PAGER_PARAMS_NAME" => $arParams["PAGER_PARAMS_NAME"],
            "LAZY_LOAD" => 'N',//$arParams["LAZY_LOAD"],
           // "MESS_BTN_LAZY_LOAD" => $arParams["~MESS_BTN_LAZY_LOAD"],
           // "LOAD_ON_SCROLL" => $arParams["LOAD_ON_SCROLL"],

          //  "OFFERS_CART_PROPERTIES" => (isset($arParams["OFFERS_CART_PROPERTIES"]) ? $arParams["OFFERS_CART_PROPERTIES"] : []),
          //  "OFFERS_FIELD_CODE" => $arParams["LIST_OFFERS_FIELD_CODE"],
          //  "OFFERS_PROPERTY_CODE" => (isset($arParams["LIST_OFFERS_PROPERTY_CODE"]) ? $arParams["LIST_OFFERS_PROPERTY_CODE"] : []),
          //  "OFFERS_SORT_FIELD" => $arParams["OFFERS_SORT_FIELD"],
         //   "OFFERS_SORT_ORDER" => $arParams["OFFERS_SORT_ORDER"],
          //  "OFFERS_SORT_FIELD2" => $arParams["OFFERS_SORT_FIELD2"],
          //  "OFFERS_SORT_ORDER2" => $arParams["OFFERS_SORT_ORDER2"],
          //  "OFFERS_LIMIT" => (isset($arParams["LIST_OFFERS_LIMIT"]) ? $arParams["LIST_OFFERS_LIMIT"] : 0),

            "SECTION_ID" => 0,//$arResult["VARIABLES"]["SECTION_ID"],
            "SECTION_CODE" => '',//$arResult["VARIABLES"]["SECTION_CODE"],
            //"SECTION_URL" => $arResult["FOLDER"] . $arResult["URL_TEMPLATES"]["section"],
          //  "SECTION_USER_FIELDS" => array_merge($arParams['LIST_USER_FIELDS'], ['UF_CAMPAIGN', 'UF_NAME_' . LANGUAGE_UPPER,]),
            //"DETAIL_URL" => $arResult["FOLDER"] . $arResult["URL_TEMPLATES"]["element"],
           // "USE_MAIN_ELEMENT_SECTION" => $arParams["USE_MAIN_ELEMENT_SECTION"],
            'CONVERT_CURRENCY' => 'Y',//$arParams['CONVERT_CURRENCY'],
            'CURRENCY_ID' => $currency,
            'HIDE_NOT_AVAILABLE' => 'Y',//$arParams["HIDE_NOT_AVAILABLE"],
            'HIDE_NOT_AVAILABLE_OFFERS' => 'N',//$arParams["HIDE_NOT_AVAILABLE_OFFERS"],

            //'LABEL_PROP' => $arParams['LABEL_PROP'],
           // 'LABEL_PROP_MOBILE' => $arParams['LABEL_PROP_MOBILE'],
           // 'LABEL_PROP_POSITION' => $arParams['LABEL_PROP_POSITION'],
           // 'ADD_PICT_PROP' => $arParams['ADD_PICT_PROP'],
           // 'PRODUCT_DISPLAY_MODE' => $arParams['PRODUCT_DISPLAY_MODE'],
          //  'PRODUCT_BLOCKS_ORDER' => $arParams['LIST_PRODUCT_BLOCKS_ORDER'],
          //  'PRODUCT_ROW_VARIANTS' => $arParams['LIST_PRODUCT_ROW_VARIANTS'],
         //   'ENLARGE_PRODUCT' => $arParams['LIST_ENLARGE_PRODUCT'],
          //  'ENLARGE_PROP' => isset($arParams['LIST_ENLARGE_PROP']) ? $arParams['LIST_ENLARGE_PROP'] : '',
          //  'SHOW_SLIDER' => $arParams['LIST_SHOW_SLIDER'],
          //  'SLIDER_INTERVAL' => isset($arParams['LIST_SLIDER_INTERVAL']) ? $arParams['LIST_SLIDER_INTERVAL'] : '',
          //  'SLIDER_PROGRESS' => isset($arParams['LIST_SLIDER_PROGRESS']) ? $arParams['LIST_SLIDER_PROGRESS'] : '',

          //  'OFFER_ADD_PICT_PROP' => $arParams['OFFER_ADD_PICT_PROP'],
        //    'OFFER_TREE_PROPS' => (isset($arParams['OFFER_TREE_PROPS']) ? $arParams['OFFER_TREE_PROPS'] : []),
        //    'PRODUCT_SUBSCRIPTION' => $arParams['PRODUCT_SUBSCRIPTION'],
       //     'SHOW_DISCOUNT_PERCENT' => $arParams['SHOW_DISCOUNT_PERCENT'],
       //     'DISCOUNT_PERCENT_POSITION' => $arParams['DISCOUNT_PERCENT_POSITION'],
        //    'SHOW_OLD_PRICE' => $arParams['SHOW_OLD_PRICE'],
        //    'SHOW_MAX_QUANTITY' => $arParams['SHOW_MAX_QUANTITY'],
         //   'MESS_SHOW_MAX_QUANTITY' => (isset($arParams['~MESS_SHOW_MAX_QUANTITY']) ? $arParams['~MESS_SHOW_MAX_QUANTITY'] : ''),
         //   'RELATIVE_QUANTITY_FACTOR' => (isset($arParams['RELATIVE_QUANTITY_FACTOR']) ? $arParams['RELATIVE_QUANTITY_FACTOR'] : ''),
          // 'MESS_RELATIVE_QUANTITY_MANY' => (isset($arParams['~MESS_RELATIVE_QUANTITY_MANY']) ? $arParams['~MESS_RELATIVE_QUANTITY_MANY'] : ''),
        //    'MESS_RELATIVE_QUANTITY_FEW' => (isset($arParams['~MESS_RELATIVE_QUANTITY_FEW']) ? $arParams['~MESS_RELATIVE_QUANTITY_FEW'] : ''),
    //        'MESS_BTN_BUY' => (isset($arParams['~MESS_BTN_BUY']) ? $arParams['~MESS_BTN_BUY'] : ''),
    //        'MESS_BTN_ADD_TO_BASKET' => (isset($arParams['~MESS_BTN_ADD_TO_BASKET']) ? $arParams['~MESS_BTN_ADD_TO_BASKET'] : ''),
    //        'MESS_BTN_SUBSCRIBE' => (isset($arParams['~MESS_BTN_SUBSCRIBE']) ? $arParams['~MESS_BTN_SUBSCRIBE'] : ''),
    //        'MESS_BTN_DETAIL' => (isset($arParams['~MESS_BTN_DETAIL']) ? $arParams['~MESS_BTN_DETAIL'] : ''),
    //        'MESS_NOT_AVAILABLE' => $arParams['~MESS_NOT_AVAILABLE'] ?? '',
    //        'MESS_NOT_AVAILABLE_SERVICE' => $arParams['~MESS_NOT_AVAILABLE_SERVICE'] ?? '',
    //        'MESS_BTN_COMPARE' => (isset($arParams['~MESS_BTN_COMPARE']) ? $arParams['~MESS_BTN_COMPARE'] : ''),

    //        'USE_ENHANCED_ECOMMERCE' => (isset($arParams['USE_ENHANCED_ECOMMERCE']) ? $arParams['USE_ENHANCED_ECOMMERCE'] : ''),
    //        'DATA_LAYER_NAME' => (isset($arParams['DATA_LAYER_NAME']) ? $arParams['DATA_LAYER_NAME'] : ''),
    //        'BRAND_PROPERTY' => (isset($arParams['BRAND_PROPERTY']) ? $arParams['BRAND_PROPERTY'] : ''),

    //        'TEMPLATE_THEME' => (isset($arParams['TEMPLATE_THEME']) ? $arParams['TEMPLATE_THEME'] : ''),
    //        "ADD_SECTIONS_CHAIN" => "N",
    //        'ADD_TO_BASKET_ACTION' => $basketAction,
    //        'SHOW_CLOSE_POPUP' => isset($arParams['COMMON_SHOW_CLOSE_POPUP']) ? $arParams['COMMON_SHOW_CLOSE_POPUP'] : '',
    //        'COMPARE_PATH' => $arResult['FOLDER'] . $arResult['URL_TEMPLATES']['compare'],
    //        'COMPARE_NAME' => $arParams['COMPARE_NAME'],
    //        'USE_COMPARE_LIST' => 'Y',
    //        'BACKGROUND_IMAGE' => (isset($arParams['SECTION_BACKGROUND_IMAGE']) ? $arParams['SECTION_BACKGROUND_IMAGE'] : ''),
            'COMPATIBLE_MODE' => 'Y',//(isset($arParams['COMPATIBLE_MODE']) ? $arParams['COMPATIBLE_MODE'] : ''),
    //        'DISABLE_INIT_JS_IN_COMPONENT' => (isset($arParams['DISABLE_INIT_JS_IN_COMPONENT']) ? $arParams['DISABLE_INIT_JS_IN_COMPONENT'] : '')
            'CUSTOM_SORT_ORDER' => $arCatalogSearchTitleFilter['ID'],
            'DONT_SET_TITLE' => 'Y',
        ),
        $component
    ); ?>
    <?foreach ($arResult['CATEGORIES']['all']['ITEMS'] as $arItem):?>
    <div class="py-3 py-md-4">
        <a class="btn btn-arrow" href="<?= $arItem['URL'] ?>">
            <?= $arItem['NAME'] ?>
            <svg class="arrow" width="20" height="9" viewBox="0 0 22 11" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M1 5.5h20M14 10c1-3 7-4.5 7-4.5S15 4 14 1" stroke="#020203" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
        </a>
    </div>
    <?endforeach;?>
</div>