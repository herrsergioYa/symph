<?php

namespace Gsv\Helpers;

class Common
{
    public static function fixDisplayProperties(&$arItem)
    {
        if (!(isset($arItem['DISPLAY_PROPERTIES']) && is_array($arItem['DISPLAY_PROPERTIES']))) {
            $arItem['DISPLAY_PROPERTIES'] = $arItem['PROPERTIES'];
        } else {
            foreach ($arItem['PROPERTIES'] as $code => $arPropertyValue) {
                if (!isset($arItem['DISPLAY_PROPERTIES'][$code])) {
                    $arItem['DISPLAY_PROPERTIES'][$code] = $arPropertyValue;
                }
            }
        }
    }

    public static function modifySectionName(&$arResult)
    {
        $nameUf = "UF_NAME_" . LANGUAGE_UPPER;
        if($arResult[$nameUf]) {
            $arResult['NAME'] = $arResult[$nameUf];
        }
        unset($arResult[$nameUf]);


        $nameUf = "~UF_NAME_" . LANGUAGE_UPPER;
        if($arResult[$nameUf]) {
            $arResult['~NAME'] = $arResult[$nameUf];
        }
        unset($arResult[$nameUf]);
    }

    public static function getSectionPath($IBLOCK_ID, $SECTION_ID)
    {
        $arResult = [];
        $dbSect = \CIBlockSection::GetNavChain($IBLOCK_ID, $SECTION_ID, [
            'NAME',
            'ID',
            'SECTION_PAGE_URL',
            'UF_NAME_' . LANGUAGE_UPPER,//FIXME: Isn't returned.
            'IBLOCK_SECTION_ID',
        ]);
        while ($arSect = $dbSect->GetNext()) {
            static::modifySectionName($arSect);
            $arResult[$arSect['ID']] = $arSect;
        }
        //HACK: Until it is fixed...
        $dbSect = \CIBlockSection::GetList(
            [],
            [
                'IBLOCK_ID' => $IBLOCK_ID,
                'ID' => array_keys($arResult),
            ],
            false,
            [
                'NAME',
                'ID',
                'SECTION_PAGE_URL',
                'UF_NAME_' . LANGUAGE_UPPER,
                'IBLOCK_SECTION_ID',
            ],
            ['nTopCount' => count($arResult)]
        );
        while ($arSect = $dbSect->GetNext()) {
            static::modifySectionName($arSect);
            $arResult[$arSect['ID']] = $arSect;
        }
        return $arResult;
    }

    public static function modifyProductName(&$arItem)
    {
        $nameUf = "NAME_" . LANGUAGE_UPPER;
        if($arItem['PROPERTIES'][$nameUf]['VALUE']) {
            $arItem['NAME'] = $arItem['PROPERTIES'][$nameUf]['VALUE'];
        }
        if($arItem['PROPERTIES'][$nameUf]['~VALUE']) {
            $arItem['~NAME'] = $arItem['PROPERTIES'][$nameUf]['~VALUE'];
        }
        unset($arItem['PROPERTIES'][$nameUf]);

        foreach (['PREVIEW_TEXT', 'DETAIL_TEXT'] as $field) {
            $nameUf = $field . '_' . LANGUAGE_UPPER;
            if (is_array($arItem['PROPERTIES'][$nameUf]['VALUE'])
                && $arItem['PROPERTIES'][$nameUf]['VALUE']['TYPE']
                && $arItem['PROPERTIES'][$nameUf]['VALUE']['TEXT']) {
                $arItem[$field . '_TYPE'] = strtolower($arItem['PROPERTIES'][$nameUf]['VALUE']['TYPE']);
                if($arItem[$field . '_TYPE'] == 'html') {
                    $arItem[$field] = $arItem['PROPERTIES'][$nameUf]['~VALUE']['TEXT'];
                } else {
                    $arItem[$field] = $arItem['PROPERTIES'][$nameUf]['VALUE']['TEXT'];
                }
                $arItem['~' . $field . '_TYPE'] = strtolower($arItem['PROPERTIES'][$nameUf]['~VALUE']['TYPE']);
                $arItem['~' . $field] = $arItem['PROPERTIES'][$nameUf]['~VALUE']['TEXT'];
            } else if(is_string($arItem['PROPERTIES'][$nameUf])
                && $arItem['PROPERTIES'][$nameUf]['VALUE']) {
                if($arItem[$field . '_TYPE'] == 'html') {
                    $arItem[$field] = $arItem['PROPERTIES'][$nameUf]['~VALUE'];
                } else {
                    $arItem[$field] = $arItem['PROPERTIES'][$nameUf]['VALUE'];
                }
                $arItem['~'. $field] = $arItem['PROPERTIES'][$nameUf]['~VALUE'];
            }
            unset($arItem['PROPERTIES'][$nameUf]);
        }
    }

    public static function fillImage($imageID, $arImgSize = false)
    {
        if(is_array($arImgSize)) {
            if($arImgSize) {
                $arImage = \CFile::ResizeImageGet($imageID, $arImgSize);
                if($arImage) {
                    $arImage = array_change_key_case($arImage, CASE_UPPER);
                }
                return $arImage;
            } else {
                return \CFile::GetFileArray($imageID);
            }
        } else if(is_numeric($arImgSize)) {
            return \pictures::picture($imageID, +$arImgSize);
        } else {
            return $imageID;
        }
    }

    public static function fillPhoto($imageID, $imgWidth = false)
    {
        //$imgWidth = $imgWidth ?: static::FANCYBOX_WIDTH;

        $arPhoto = \CFile::GetFileArray($imageID);
        if(empty($arPhoto)) {
            return $arPhoto;
        }
        if(empty($imgWidth)) {
            return $arPhoto;
        }
        if(empty($arPhoto['WIDTH']) || $arPhoto['WIDTH'] > $imgWidth) {
            if(empty($arPhoto['WIDTH']) || empty($arPhoto['HEIGHT'])) {
                $imgHeight = $imgWidth * 99;
            } else {
                $imgHeight = $arPhoto['HEIGHT'] / $arPhoto['WIDTH'] * $imgWidth;
                $imgHeight = ceil($imgHeight);
            }
            //gsv_dump(['width' => $imgWidth, 'height' => $imgHeight]);
            $arPhoto = \CFile::ResizeImageGet($arPhoto, ['width' => $imgWidth, 'height' => $imgHeight]);
            if($arPhoto) {
                $arPhoto = array_change_key_case($arPhoto, CASE_UPPER);
            }
        }

        return $arPhoto;
    }

    public static function sortItems($arSort, &$arItems)
    {
        if(isset($arSort) && is_array($arSort)) {
            $arSort = array_flip(array_values($arSort));
            usort($arItems, function (&$a, &$b) use(&$arSort) {
                return $arSort[$a['ID']] <=> $arSort[$b['ID']];
            });
        }
    }

    public static function getFavorites()
    {
        $arFavorites = [];

        if(\Bitrix\Main\Loader::includeModule('sale')) {
            $basketRes = \Bitrix\Sale\Internals\BasketTable::getList(array(
                "filter" => array(
                    "FUSER_ID" => \Bitrix\Sale\Fuser::getId(),
                    "ORDER_ID" => null,
                    "LID" => \Bitrix\Main\Context::getCurrent()->getSite(),
                    "DELAY" => 'Y',
                ),
                "select" => array('ID', "PRODUCT_ID")
            ));
            while($arFavorite = $basketRes->fetch()) {
                $arFavorites[] = $arFavorite['ID'];
            }

            $basketPropRes = \Bitrix\Sale\Internals\BasketPropertyTable::getList(array(
                "filter" => array(
                    "BASKET_ID" => $arFavorites,
                    "CODE" => "ELEMENT_ID"
                )
            ));
            $arFavorites = [];
            while ($property = $basketPropRes->fetch()) {
                $arFavorites[] = (string)$property["VALUE"];
            }
        }

        return $arFavorites;
    }

    public static function parseCoordinates($sCoordinates)
    {
        $arCoordinates = explode(',', $sCoordinates);
        return [
            'lat' => floatval($arCoordinates[0]),
            'lng' => floatval($arCoordinates[1]),
        ];
    }

    const EMAIL_REGEX = '/^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/';

    public static function subscribeUser($email, $IBLOCK_ID, $SITE_CODE)
    {
        $email = trim($email ?? '');

        if(!preg_match(static::EMAIL_REGEX, $email)) {
            return [
                'ERROR' => 'Неверный email: ' . $email,
            ];
        }

        if(\Bitrix\Main\Loader::includeModule('iblock')) {
            //$IBLOCK_ID = SUBSCRIBE_IBLOCK_ID;
            //$SITE_CODE = SITE_CODE[SITE_ID];
            $dbSubscription = \CIBlockElement::GetList(
                [],
                [
                    'IBLOCK_ID' => $IBLOCK_ID,
                    '=NAME' => $email,
                    '=PROPERTY_SITE_VALUE' => $SITE_CODE,
                ],
                false,
                ['nTopCount' => 1],
                ['ID', 'IBLOCK_ID', 'NAME', 'PROPERTY_SITE']
            );
            if($arSubscription = $dbSubscription->Fetch()) {
                return $arSubscription;
            }
            $dbEnum = \CIBlockPropertyEnum::GetList(
                [],
                [
                    'IBLOCK_ID' => $IBLOCK_ID,
                    'CODE' => 'SITE',
                    'VALUE' => $SITE_CODE,
                ]
            );
            if($arEnum = $dbEnum->Fetch()) {
                $arFields = [
                    'IBLOCK_ID' => $IBLOCK_ID,
                    'NAME' => $email,
                    //It is safe! It should be!!
                    'PROPERTY_VALUES' => [
                        'SITE' => $arEnum['ID'],
                    ],
                ];
                $obElement = new \CIBlockElement();
                $arFields['ID'] = $obElement->Add($arFields);
                $arFields['ERROR'] = $obElement->LAST_ERROR;
                return $arFields;
            } else {
                return [
                    'ERROR' => 'Неизвестный сайт',
                ];
            }
        } else {
            return [
                'ERROR' => 'Внутренняя ошибка',
            ];
        }

        return false;
    }

    public static function fillOfferPrices(&$arOffers)
    {
        $arResult = false;
        foreach ($arOffers as &$arOffer) {
            $arPrice = static::getOfferPrice($arOffer);
            $arOffer['PRICE'] = $arPrice;
            if(empty($arResult)
                ||!$arResult['AVAILABLE'] && $arPrice['AVAILABLE']
                || $arResult['AVAILABLE'] == $arPrice['AVAILABLE']
                && ($arResult['PRICE'] > $arPrice['PRICE']
                    || $arResult['PRICE'] == $arPrice['PRICE']
                        && $arResult['OFFER_ID'] > $arPrice['OFFER_ID'])
            ) {
                $arResult = $arPrice;
            }
        }
        unset($arOffer);
        return $arResult;
    }

    //"COMPATIBLE_MODE" => "Y",
    public static function getOfferPrice($arOffer)
    {
        if(empty($arOffer['MIN_PRICE'])) {
            return false;
        }
        $arPrice = array(
            'OFFER_ID' => $arOffer['ID'],
            "BASE_PRICE" => $arOffer["MIN_PRICE"]["VALUE"],
            "BASE_PRICE_FORMATED" => $arOffer["MIN_PRICE"]["PRINT_VALUE"],
            "PRICE" => $arOffer["MIN_PRICE"]["DISCOUNT_VALUE"],
            "PRICE_FORMATED" => $arOffer["MIN_PRICE"]["PRINT_DISCOUNT_VALUE"],
            "DISCOUNT" => $arOffer["MIN_PRICE"]["DISCOUNT_DIFF"],
            "DISCOUNT_PERCENT" => $arOffer["MIN_PRICE"]["DISCOUNT_DIFF_PERCENT"],

            'CURRENCY' => $arOffer["MIN_PRICE"]['CURRENCY'],

            'AVAILABLE' => $arOffer['CAN_BUY'],
        );
        return $arPrice;
    }

    public static function modifyProductSection(&$arResult)
    {
        static::modifyProducts($arResult['ITEMS']);
        static::modifySectionName($arResult);
        $arResult['SECTION_PATH'] = static::getSectionPath($arResult['IBLOCK_ID'], $arResult['ID']);
    }

    public static function modifySection(&$arResult)
    {
        static::modifyItems($arResult['ITEMS']);
        static::modifySectionName($arResult);
        //$arResult['SECTION_PATH'] = static::getSectionPath($arResult['IBLOCK_ID'], $arResult['ID']);
    }

    public static function modifySections(&$arSections)
    {
        foreach ($arSections as &$arSection) {
            static::modifySectionName($arSection);
        }
    }

    public static function modifyItems(&$arItems)
    {
        foreach ($arItems as &$arItem) {
            static::fixDisplayProperties($arItem);
            static::modifyProductName($arItem);
        }
    }

    public static function modifyItem(&$arResult)
    {
        static::fixDisplayProperties($arResult);
        static::modifyProductName($arResult);
    }

    public static function modifyProducts(&$arItems)
    {
        foreach ($arItems as &$arItem) {
            static::fixDisplayProperties($arItem);

            static::modifyProductName($arItem);

            static::modifyOffers($arItem);

            static::selectOffer($arItem);
            $arItem['JS_ITEM'] = static::getJsItem($arItem);
        }
        unset($arItem);
    }

    public static function modifyProduct(&$arResult)
    {
        //static::modifyItem($arResult);//- Can be overridden

        static::fixDisplayProperties($arResult);
        static::modifyProductName($arResult);

        static::modifyOffers($arResult);

        if($arResult['SKU_PROPS']) {
            static::localizeSkuProperties($arResult);
            static::disableSkuProps($arResult['SKU_PROPS'], $arResult['JS_OFFERS']);
            static::modifySkuPropsElementsProperties($arResult['SKU_PROPS']);
        }
        static::fillProductAttributes($arResult);
        static::selectOffer($arResult);
        if($arResult['SKU_PROPS']) {
            //TODO: Can|Should I do a little bit later?
            $arTree = null;
            foreach ($arResult['JS_OFFERS'] as $jsOffer) {
                if($jsOffer['ID'] == $arResult['OFFER_ID_SELECTED']) {
                    $arTree = $jsOffer['TREE'];
                    break;
                }
            }
            static::selectSkuProps($arResult['SKU_PROPS'], $arTree);
        }

        $arResult['JS_ITEM'] = static::getJsItem($arResult, $arResult);

        $arResult['JS_DATA'] = $arResult['JS_ITEM'];
        $arResult['JS_DATA']['ADD_URL_TEMPLATE'] = $arResult['ADD_URL_TEMPLATE'];

        if($arResult['SKU_PROPS']) {
            $arResult['JS_DATA']['SKU_PROPS'] = $arResult['SKU_PROPS'];
            //We should preserve the order
            $arResult['JS_DATA']['SKU_PROP_LIST'] = array_keys($arResult['SKU_PROPS']);
            foreach ($arResult['JS_DATA']['SKU_PROPS'] as &$arSkuProp) {
                $arSkuProp['VALUES'] = array_values($arSkuProp['VALUES']);
            }
            unset($arSkuProp);
        }

        static::modifySectionName($arResult['SECTION']);
        $arResult['SECTION_PATH'] = static::getSectionPath($arResult['IBLOCK_ID'], $arResult['SECTION']['ID']);
    }

    public static function modifyOffers(&$arItem)
    {
        if($arItem['OFFERS']) {
            $arItem['PRICE'] = static::fillOfferPrices($arItem['OFFERS']);
            if(empty($arItem['OFFER_ID_SELECTED']) && $arItem['PRICE']) {
                $arItem['OFFER_ID_SELECTED'] = $arItem['PRICE']['OFFER_ID'];
            }
        } else {
            //We should define the price first!
            $arItem['PRICE'] = static::getOfferPrice($arItem);
            $arOffer = $arItem;
            $arOffer['QUANTITY'] = $arItem['PRODUCT']['QUANTITY'];
            $arItem['OFFERS'] = [$arOffer];
            $arItem['OFFER_ID_SELECTED'] = $arItem['ID'];
        }
        unset($arOffer);
    }

    public static function selectOffer(&$arItem)
    {
        $arItem['OFFER_SELECTED'] = false;
        if($arItem['OFFER_ID_SELECTED']) {
            foreach ($arItem['OFFERS'] as &$arOffer) {
                if($arOffer['SELECTED'] = $arOffer['ID'] == $arItem['OFFER_ID_SELECTED']) {
                    $arItem['OFFER_SELECTED'] = $arOffer;
                }
            }
            unset($arOffer);
        } else {
            if(/*$arItem['SELECTED'] = */$arItem['ID'] == $arItem['OFFER_ID_SELECTED']) {
                $arItem['OFFER_SELECTED'] = $arItem;
            }
        }
        return $arItem['OFFER_SELECTED'];
    }

    public static function getJsItem($arItem, $js = [])
    {
        $arItem['JS_ITEM'] = [
            'NAME' => $arItem['NAME'],
            'ID' => $arItem['ID'],
            'PRICE' => $arItem['PRICE'],
            'QUANTITY' => $arItem['QUANTITY'],
            'OFFER_ID_SELECTED' => $arItem['OFFER_ID_SELECTED'],
            'OFFERS' => [],//$arItem['OFFERS'],
            'ADD_URL' => $arItem['ADD_URL'],

            'IS_INIT' => true,
        ];
        if(isset($js['CAN_BUY'])) {
            $arItem['JS_ITEM']['CAN_BUY'] = $js['CAN_BUY'];
        }

        $offers = [];
        foreach ($js['JS_OFFERS'] as $jsOffer) {
            $offers[$jsOffer['ID']] = $jsOffer;
        }
        //$offers = &$js['JS_OFFERS'];
        foreach ($arItem['OFFERS'] as $arOffer) {
            $jsOffer = [
                'NAME' => $arOffer['NAME'],
                'ID' => $arOffer['ID'],
                'SELECTED' => $arOffer['SELECTED'],
                'PRICE' => $arOffer['PRICE'],
                'QUANTITY' => $arOffer['QUANTITY'],
                'ADD_URL' => $arOffer['ADD_URL'],

                'IS_WISHLISTED' => null,
                'IN_CART_CNT' => null,
            ];
            if(isset($offers[$arOffer['ID']])) {
                //Do we need that one?
                //$jsOffer['DATA'] = $offers[$arOffer['ID']];
                //It can break json_encode
                $jsOffer['TREE'] = $offers[$arOffer['ID']]['TREE'];
                $jsOffer['CAN_BUY'] = $offers[$arOffer['ID']]['CAN_BUY'];
                static::modifyJsOffer($jsOffer, $offers[$arOffer['ID']], $arOffer);
            } else {
                $jsOffer['TREE'] = [];
                $jsOffer['CAN_BUY'] = $arOffer['CAN_BUY'];
                static::modifyJsOffer($jsOffer, NULL, $arOffer);
            }
            if($jsOffer['CAN_BUY'] || !isset($arItem['JS_ITEM']['CAN_BUY'])) {
                $arItem['JS_ITEM']['CAN_BUY'] = $jsOffer['CAN_BUY'];
            }
            $arItem['JS_ITEM']['OFFERS'][] = $jsOffer;
            if($arOffer['ID'] == $arItem['OFFER_ID_SELECTED']) {
                $arItem['JS_ITEM']['OFFER_SELECTED'] = $jsOffer;
            }
        }
        unset($offers);

        static::modifyJsItem($arItem['JS_ITEM'], $js);

        static::mergeJsAttributes($arItem['JS_ITEM']);

        return $arItem['JS_ITEM'];
    }

    public static function modifyJsItem(&$jsItem, $arItem)
    {
        if (isset($arItem['ATTRIBUTES'])) {
            $jsItem['ATTRIBUTES'] = $arItem['ATTRIBUTES'];
        }
    }

    public static function modifyJsOffer(&$jsOffer, $offer, $arOffer)
    {
        if(isset($offer['ATTRIBUTES'])) {
            $jsOffer['ATTRIBUTES'] = $offer['ATTRIBUTES'];
        }
    }

    public static function mergeJsAttributes(&$jsItem)
    {
        $jsItem['ALL_ATTRIBUTES'] = $jsItem['ATTRIBUTES'] ?: [];
        if($jsItem['OFFER_SELECTED']['ATTRIBUTES']) {
            $jsItem['ALL_ATTRIBUTES'] = array_merge(
                $jsItem['ALL_ATTRIBUTES'],
                $jsItem['OFFER_SELECTED']['ATTRIBUTES']
            );
        }
    }

    public static function fillProductAttributes(&$arResult)
    {
        $arResult['ATTRIBUTES'] = [];
        foreach ($arResult['JS_OFFERS'] as &$jsOffer) {
            $jsOffer['ATTRIBUTES'] = [];
        }
        unset($jsOffer);
    }

    public static function selectSkuProps(&$arSkuProps, $arTree)
    {
        foreach ($arSkuProps as &$arSkuProp) {
            $code = 'PROP_' . $arSkuProp['ID'];
            foreach ($arSkuProp['VALUES'] as &$arValue) {
                //$arValue['DELETED'] = true;
                $arValue['SELECTED'] = $arTree[$code] == $arValue['ID'];
            }
            unset($arValue);
        }
        unset($arSkuProp);
    }

    public static function disableSkuProps(&$arSkuProps, $jsOffers)
    {
        foreach ($arSkuProps as &$arSkuProp) {
            $code = 'PROP_' . $arSkuProp['ID'];
            $arSkuProp['ACTIVE_VALUE_CNT'] = 0;
            foreach ($arSkuProp['VALUES'] as &$arValue) {
                $arValue['DISABLED'] = true;
                foreach ($jsOffers as $jsOffer) {
                    $arValue['DISABLED'] &= !($jsOffer['TREE'][$code] == $arValue['ID'] && $jsOffer['CAN_BUY']);
                }
                if(!$arValue['DISABLED']) {
                    $arSkuProp['ACTIVE_VALUE_CNT']++;
                }
            }
            unset($arValue);
        }
        unset($arSkuProp);
    }

    public static function getLinkIdsFromElements(&$arItems, $PROP, &$IBLOCK_ID)
    {
        $arResult = [];
        $IBLOCK_ID = 0;
        foreach ($arItems as &$arItem) {
            if($arItem['PROPERTIES'][$PROP]) {
                if ($arItem['PROPERTIES'][$PROP]['VALUE']) {
                    foreach ((array)$arItem['PROPERTIES'][$PROP]['VALUE'] as $labelID) {
                        if ($labelID) {
                            $arResult[$labelID] = $labelID;
                        }
                    }
                }
                $IBLOCK_ID = $arItem['PROPERTIES'][$PROP]['LINK_IBLOCK_ID'];
            }
        }
        unset($arItem);
        return $arResult;
    }

    public static function getLinkIdsFromElement(&$arItem, $PROP, &$IBLOCK_ID)
    {
        $arResult = [];
        $IBLOCK_ID = 0;
        if($arItem['PROPERTIES'][$PROP]) {
            if ($arItem['PROPERTIES'][$PROP]['VALUE']) {
                foreach ((array)$arItem['PROPERTIES'][$PROP]['VALUE'] as $labelID) {
                    if ($labelID) {
                        $arResult[$labelID] = false;
                    }
                }
            }
            $IBLOCK_ID = $arItem['PROPERTIES'][$PROP]['LINK_IBLOCK_ID'];
        }
        unset($arItem);
        return $arResult;
    }

    public static function fillLinkElements(&$arItems, $PROP, $arValues = false)
    {
        if($arValues  === false || $arValues === null) {
            $IBLOCK_ID = 0;
            $arValues = static::getLinkIdsFromElements($arItems, $PROP, $IBLOCK_ID);
            if($arValues) {
                $arValues = static::getElementsOfIbock($arValues, $IBLOCK_ID);
            }
        }
        foreach ($arItems as &$arItem) {
            $arItem['PROPERTIES'][$PROP]['LINK_ELEMENT'] = static::getLinkElement($arItem, $PROP, $arValues);
        }
        unset($arItem);
    }

    public static function getLinkElement(&$arItem, $PROP, $arValues = null)
    {
        if($arValues  === false || $arValues === null) {
            $IBLOCK_ID = 0;
            $arValues = static::getLinkIdsFromElement($arItem, $PROP, $IBLOCK_ID);
            if($arValues) {
                $arValues = static::getElementsOfIbock($arValues, $IBLOCK_ID);
            }
        }
        if(is_array($arItem['PROPERTIES'][$PROP]['VALUE'])) {
            $arResult = [];
            foreach ($arItem['PROPERTIES'][$PROP]['VALUE'] as $k => $labelID) {
                if ($labelID) {
                    $arResult[$k] = $arValues[$labelID];
                }
            }
        } else {
            $arResult = false;
            if ($labelID = $arItem['PROPERTIES'][$PROP]['VALUE']) {
                $arResult = $arValues[$labelID];
            }
        }
        return $arResult;
    }

    public static function fillAllLinkElements(&$arItems)
    {
        $arProps = [];

        foreach ($arItems as &$arItem) {
            foreach ($arItem['PROPERTIES'] as &$arProperty) {
                if ($arProperty['PROPERTY_TYPE'] == 'E' && $arProperty['VALUE'] && $arProperty['CODE'] != 'CML2_LINK') {
                    $arProps[$arProperty['CODE']] = $arProperty['CODE'];
                }
            }
            unset($arProperty);
        }
        unset($arItem);

        foreach ($arProps as $code => $arProp) {
            static::fillLinkElements($arItems, $code);
        }
    }

    //TODO: Propose a better name
    public static function fillAllLinkElement(&$arItem)
    {
        foreach ($arItem['PROPERTIES'] as &$arProperty) {
            if ($arProperty['PROPERTY_TYPE'] == 'E' && $arProperty['VALUE'] && $arProperty['CODE'] != 'CML2_LINK') {
                $arProperty['LINK_ELEMENT'] = static::getLinkElement($arItem, $arProperty['CODE']);
            }
        }
        unset($arProperty);
    }

    public static function getElementsOfIbock($arIds, $IBLOCK_ID, $arProps = [])
    {
        $arItems = [];
        if($arIds) {
            $dbItems = \CIBlockElement::GetList(
                [
                    'SORT' => 'ASC',
                    'ID' => 'DESC',
                ],
                [
                    'IBLOCK_ID' => $IBLOCK_ID,
                    'ID' => $arIds,
                ],
                false,
                ['nTopCount' => count($arIds)],
                ['ID', 'NAME', 'IBLOCK_ID', 'DETAIL_PAGE_URL']
            );
            while($arItem = $dbItems->GetNext()) {
                $arItem['PROPERTIES'] = [];
                $arItems[$arItem['ID']] = $arItem;
            }
            if($arItems) {
                if(is_array($arProps)) {
                    $arProps = array_merge(['NAME_' . LANGUAGE_UPPER], $arProps);
                } else {
                    $arProps = [];
                }
                \CIBlockElement::GetPropertyValuesArray(
                    $arItems,
                    $IBLOCK_ID,
                    ['ID' => array_keys($arItems)],
                    ['CODE' => $arProps]
                );
                foreach ($arItems as &$arItem) {
                    static::modifyProductName($arItem);
                }
                unset($arItem);
            }
        }
        return $arItems;
    }

    public static function getElementsOfIbockByXmlId($arXmlIds, $IBLOCK_ID, $arProps = [])
    {
        $arItems = [];
        if($arXmlIds) {
            $dbItems = \CIBlockElement::GetList(
                [
                    'SORT' => 'ASC',
                    'ID' => 'DESC',
                ],
                [
                    'IBLOCK_ID' => $IBLOCK_ID,
                    'XML_ID' => $arXmlIds,
                ],
                false,
                ['nTopCount' => count($arXmlIds)],
                ['ID', 'NAME', 'IBLOCK_ID', 'XML_ID', 'DETAIL_TEXT']
            );
            while($arItem = $dbItems->Fetch()) {
                $arItem['PROPERTIES'] = [];
                $arItems[$arItem['ID']] = $arItem;
            }
            if($arItems) {
                if(is_array($arProps)) {
                    $arProps = array_merge(['NAME_' . LANGUAGE_UPPER, 'DETAIL_TEXT_' . LANGUAGE_UPPER], $arProps);
                } else {
                    $arProps = [];
                }
                \CIBlockElement::GetPropertyValuesArray(
                    $arItems,
                    $IBLOCK_ID,
                    ['ID' => array_keys($arItems)],
                    ['CODE' => $arProps]
                );
                foreach ($arItems as &$arItem) {
                    static::modifyProductName($arItem);
                }
                unset($arItem);
            }
        }

        return static::getBy($arItems, 'XML_ID');
    }

    public static function getAllElementsOfIbock($IBLOCK_ID, $arProps = [])
    {
        $arItems = [];
        $dbItems = \CIBlockElement::GetList(
            [
                'SORT' => 'ASC',
                'ID' => 'DESC',
            ],
            [
                'IBLOCK_ID' => $IBLOCK_ID,
            ],
            false,
            false,
            ['ID', 'NAME', 'IBLOCK_ID']
        );
        while($arItem = $dbItems->Fetch()) {
            $arItem['PROPERTIES'] = [];
            $arItems[$arItem['ID']] = $arItem;
        }
        if($arItems) {
            if(is_array($arProps)) {
                $arProps = array_merge(['NAME_' . LANGUAGE_UPPER], $arProps);
            } else {
                $arProps = [];
            }
            \CIBlockElement::GetPropertyValuesArray(
                $arItems,
                $IBLOCK_ID,
                ['ID' => array_keys($arItems)],
                ['CODE' => $arProps]
            );
            foreach ($arItems as &$arItem) {
                static::modifyProductName($arItem);
            }
            unset($arItem);
        }
        return $arItems;
    }

    public static function getElements($arElements)
    {
        if($arElements) {
            $dbElements = \CIBlockElement::GetList(
                [],
                [
                    'ID' => array_values($arElements),
                ],
                false,
                false,
                ['ID', 'IBLOCK_ID', 'NAME', 'CODE',]
            );
            $arElements = [];
            while ($arElement = $dbElements->GetNext()) {
                $arElement['PROPERTIES'] = [];
                $arElements[$arElement['IBLOCK_ID']][$arElement['ID']] = $arElement;
            }
        } else {
            return [];
        }

        foreach ($arElements as $IBLOCK_ID => &$arElement) {
            \CIBlockElement::GetPropertyValuesArray(
                $arElement,
                $IBLOCK_ID,
                ['ID' => array_keys($arElement)],
                []
            );
        }
        unset($arElement);

        $arElements = array_replace(...$arElements);

        foreach ($arElements as &$arElement) {
            static::modifyProductName($arElement);
        }
        unset($arElement);

        return $arElements;
    }

    public static function modifySmartFilter(&$arResult)
    {
        $arElements = [];

        foreach ($arResult['ITEMS'] as &$arItem) {
            if($arItem['PRICE']) {
                foreach ($arItem['VALUES'] as $type => &$arValue) {
                    if ($arValue['FILTERED_VALUE'] == '') {
                        $arValue['LIMIT_VALUE'] = $arValue['VALUE'];
                    } else {
                        $arValue['LIMIT_VALUE'] = $arValue['FILTERED_VALUE'];
                    }
                    if ($arValue['HTML_VALUE'] == ''
                        || $type == 'MIN' && $arValue['HTML_VALUE'] < $arValue['LIMIT_VALUE']
                        || $type == 'MAX' && $arValue['HTML_VALUE'] > $arValue['LIMIT_VALUE']
                    ) {
                        $arValue['RESULT_VALUE'] = $arValue['LIMIT_VALUE'];
                    } else {
                        $arValue['RESULT_VALUE'] = $arValue['HTML_VALUE'];
                    }
                }
                unset($arValue);
            } else if($arItem['PROPERTY_TYPE'] == 'E') {
                foreach ($arItem['VALUES'] as $id => $arValue) {
                    $arElements[] = $id;
                }
            }
        }

        $arElements = static::getSmartFilterPropElements($arElements);

        foreach ($arResult['ITEMS'] as &$arItem) {
            if ($arItem['PRICE']) {

            } else if ($arItem['PROPERTY_TYPE'] == 'E') {
                foreach ($arItem['VALUES'] as $id => &$arValue) {
                    $arValue['LINK_ELEMENT'] = $arElements[$id];

                    if($arValue['LINK_ELEMENT']['NAME']) {
                        $arValue['VALUE~'] = $arValue['VALUE'];
                        $arValue['UPPER~'] = $arValue['UPPER'];

                        $arValue['VALUE'] = $arValue['LINK_ELEMENT']['NAME'];
                        $arValue['UPPER'] = mb_strtoupper($arValue['LINK_ELEMENT']['NAME']);
                    }
                }
                unset($arValue);
            }

            $arItem['VALUE_CNT'] = 0;
            foreach ($arItem['VALUES'] as $arValue) {
                if($arValue['CHECKED'] || !$arValue['DISABLED']) {
                    $arItem['VALUE_CNT']++;
                }
            }
        }
        unset($arItem);
    }

    public static function getSmartFilterPropElements(/*array*/ $arElements)
    {
        return static::getElements($arElements);
    }

    public static function modifyBasketProducts(&$arItems)
    {
        $arOfferIds = [];
        $arOfferXmlIds = [];
        $arOfferIblockIds = [];
        $arOfferToProductIds = [];
        $arProductIds = [];

        $arProducts = [];

        foreach ($arItems as $i => $arItem) {
            if($arItem['PRODUCT_ID']) {
                $arOfferIds[$arItem['PRODUCT_ID']] = $arItem['PRODUCT_ID'];
            } else if($arItem['PRODUCT_XML_ID']) {
                $arOfferXmlIds[$arItem['PRODUCT_XML_ID']][] = $i;
            }
        }

        if($arOfferIds) {
            $dbOffers = \CIBlockElement::GetList(
                [],
                [
                    'ID' => array_values($arOfferIds),
                ],
                false,
                ['nTopCount' => count($arOfferIds)],
                [
                    'ID', 'IBLOCK_ID', 'IBLOCK_SECTION_ID', 'NAME', 'CODE', /*'PROPERTY_CML2_LINK',*/ 'TYPE',
                    'PREVIEW_PICTURE', 'DETAIL_PICTURE', 'DETAIL_PAGE_URL',
                    'ACTIVE', 'AVAILABLE', 'QUANTITY', 'QUANTITY_RESERVED',
                ]
            );
            while($arOffer = $dbOffers->GetNext()) {
                $arOffer['PROPERTIES'] = [];
                if ($arOffer['TYPE'] == \Bitrix\Catalog\ProductTable::TYPE_OFFER) {
                    $arOfferIblockIds[$arOffer['IBLOCK_ID']] = $arOffer['IBLOCK_ID'];
                    $arOfferToProductIds[$arOffer['ID']] = $arOffer['ID'];
                    $arOffer['PRODUCT'] = false;
                } elseif($arOffer['PROPERTY_TYPE'] == \Bitrix\Catalog\ProductTable::TYPE_SKU) {
                    //Add support for products in the cart?
                    $arProductIds[$arOffer['ID']] = $arOffer['ID'];
                    $arOffer['OFFERS'] = [];
                }
                $arProducts[$arOffer["IBLOCK_ID"]][$arOffer['ID']] = $arOffer;
            }
        }

        if($arOfferXmlIds) {
            $dbOffers = \CIBlockElement::GetList(
                [],
                [
                    '=XML_ID' => array_keys($arOfferXmlIds),
                    'IBLOCK_ID' => [CATALOG_IBLOCK_ID, CATALOG_OFFERS_IBLOCK_ID],
                ],
                false,
                ['nTopCount' => count($arOfferXmlIds)],
                [
                    'ID', 'IBLOCK_ID', 'IBLOCK_SECTION_ID', 'NAME', 'CODE', /*'PROPERTY_CML2_LINK',*/ 'TYPE',
                    'PREVIEW_PICTURE', 'DETAIL_PICTURE', 'DETAIL_PAGE_URL', 'XML_ID',
                    'ACTIVE', 'AVAILABLE', 'QUANTITY', 'QUANTITY_RESERVED',
                ]
            );
            while ($arOffer = $dbOffers->GetNext()) {
                foreach ($arOfferXmlIds[$arOffer['XML_ID']] as $i) {
                    $arItems[$i]['PRODUCT_ID'] = $arOffer['ID'];
                    $arItems[$i]['DETAIL_PAGE_URL'] = $arOffer['DETAIL_PAGE_URL'];
                    $arOfferIds[$arItems[$i]['PRODUCT_ID']] = $arItems[$i]['PRODUCT_ID'];
                }
                $arOffer['PROPERTIES'] = [];
                if ($arOffer['TYPE'] == \Bitrix\Catalog\ProductTable::TYPE_OFFER) {
                    $arOfferIblockIds[$arOffer['IBLOCK_ID']] = $arOffer['IBLOCK_ID'];
                    $arOfferToProductIds[$arOffer['ID']] = $arOffer['ID'];
                    $arOffer['PRODUCT'] = false;
                } elseif($arOffer['PROPERTY_TYPE'] == \Bitrix\Catalog\ProductTable::TYPE_SKU) {
                    //Add support for products in the cart?
                    $arProductIds[$arOffer['ID']] = $arOffer['ID'];
                    $arOffer['OFFERS'] = [];
                }
                $arProducts[$arOffer["IBLOCK_ID"]][$arOffer['ID']] = $arOffer;
            }
        }

        if ($arOfferToProductIds) {
            $arOfferToProductIds = \CCatalogSku::getProductList(array_keys($arOfferToProductIds));
            if ($arOfferToProductIds) {
                foreach ($arOfferToProductIds as $offerId => $arProduct) {
                    $arProducts[$arProduct["OFFER_IBLOCK_ID"]][$offerId]['PROPERTY_CML2_LINK_VALUE'] = $arProduct['ID'];
                    $arOfferToProductIds[$offerId] = $arProduct['ID'];
                    $arProductIds[$arProduct['ID']] = $arProduct['ID'];
                }
                unset($arProduct);
            } else {
                $arOfferToProductIds = [];
            }
        }

        if($arOfferIds || $arOfferXmlIds) {

            if($arOfferToProductIds) {
                $dbProducts = \CIBlockElement::GetList(
                    [],
                    [
                        'ID' => array_values($arOfferToProductIds),
                    ],
                    false,
                    ['nTopCount' => count($arOfferToProductIds)],
                    [
                        'ID', 'IBLOCK_ID', 'IBLOCK_SECTION_ID', 'NAME', 'CODE', /*'PROPERTY_CML2_LINK',*/ 'TYPE',
                        'PREVIEW_PICTURE', 'DETAIL_PICTURE', 'DETAIL_PAGE_URL',
                        'ACTIVE', 'AVAILABLE', 'QUANTITY', 'QUANTITY_RESERVED',
                    ]
                );
                while($arProduct = $dbProducts->GetNext()) {
                    $arProduct['PROPERTIES'] = [];
                    $arProduct['OFFERS'] = [];
                    $arProducts[$arProduct["IBLOCK_ID"]][$arProduct['ID']] = $arProduct;
                    $arOfferIds[$arProduct['ID']] = $arProduct['ID'];
                }
            }

            //Should we request the remaining offers of the products?
            if($arProductIds) {
                $arOtherOfferToProductIds = [];
                foreach(\CCatalogSKU::getOffersList(array_values($arProductIds)) as $productId => $arOffers) {
                    foreach($arOffers as $offerId => $arOffer) {
                        if(!array_key_exists($offerId, $arOfferIds)) {
                            $arOtherOfferToProductIds[$offerId] = $productId;
                        }
                    }
                }
                if($arOtherOfferToProductIds) {
                    $dbOffers = \CIBlockElement::GetList(
                        [],
                        [
                            //'IBLOCK_ID' => array_values($arOfferIblockIds),
                            'ID' => array_keys($arOtherOfferToProductIds),
                        ],
                        false,
                        false,
                        [
                            'ID', 'IBLOCK_ID', 'IBLOCK_SECTION_ID', 'NAME', 'CODE', /*'PROPERTY_CML2_LINK',*/ 'TYPE',
                            'PREVIEW_PICTURE', 'DETAIL_PICTURE', 'DETAIL_PAGE_URL',
                            'ACTIVE', 'AVAILABLE', 'QUANTITY', 'QUANTITY_RESERVED',
                        ]
                    );
                    while ($arOffer = $dbOffers->GetNext()) {
                        $arOffer['PROPERTIES'] = [];
                        $arOffer['PROPERTY_CML2_LINK_VALUE'] = $arOtherOfferToProductIds[$arOffer['ID']];
                        $arProductIds[$arOffer['PROPERTY_CML2_LINK_VALUE']] = $arOffer['PROPERTY_CML2_LINK_VALUE'];
                        $arOffer['PRODUCT'] = false;
                        $arProducts[$arOffer["IBLOCK_ID"]][$arOffer['ID']] = $arOffer;
                        $arOfferIds[$arOffer['ID']] = $arOffer['ID'];
                    }
                }
                unset($arOtherOfferToProductIds);
            }

            foreach ($arProducts as $IBLOCK_ID => &$arElements) {
                \CIBlockElement::GetPropertyValuesArray(
                    $arElements,
                    $IBLOCK_ID,
                    ['ID' => array_keys($arElements)],
                    []
                );
            }
            unset($arElements);

            $arProducts = static::modifyBasketProductsImpl($arProducts);

            $arSkuProps = [];

            foreach ($arProducts as &$arProduct) {
                static::modifyProductName($arProduct);
                if(isset($arProduct['PRODUCT'])) {
                    $arProduct["PRODUCT"] = &$arProducts[$arProduct['PROPERTY_CML2_LINK_VALUE']];
                    $arProduct["PRODUCT"]['OFFERS'][$arProduct['ID']] = &$arProduct;

                    $arProduct['PROPS'] = [];
                    if(!isset($arSkuProps[$arProduct['IBLOCK_ID']])) {
                        $arSkuProps[$arProduct['IBLOCK_ID']] = static::getSkuProps($arProduct['IBLOCK_ID']);
                    }
                    foreach ($arSkuProps[$arProduct['IBLOCK_ID']] as $code) {
                        $arProduct['PROPS'][$code] =
                            ($arProduct['PROPERTIES'][$code]['~VALUE_ENUM_ID'] ?: $arProduct['PROPERTIES'][$code]['VALUE_ENUM_ID'])
                                ?: ($arProduct['PROPERTIES'][$code]['~VALUE'] ?: $arProduct['PROPERTIES'][$code]['VALUE']);
                    }
                }
            }
            unset($arProduct);

            foreach ($arItems as &$arItem) {
                $arProduct = &$arProducts[$arItem['PRODUCT_ID']];
                if(isset($arProduct['PRODUCT'])) {
                    $arItem['OFFER'] = $arProduct;
                    unset($arItem['OFFER']['PRODUCT']);
                    $arItem['PRODUCT'] = $arProduct['PRODUCT'];
                    //If Not all the offers are there so we shouldn't preserve it.
                    //unset($arItem['PRODUCT']['OFFERS']);
                } else {
                    //Simple product.
                    $arItem['PRODUCT'] = $arProduct;
                    //It may still have an empty OFFERS array that we need in no way?
                    //unset($arItem['PRODUCT']['OFFERS']);
                }
                unset($arProduct);
            }
            unset($arItem);

            return $arProducts;
        }
    }

    public static function modifyBasketProductsImpl($arProducts)
    {
        if(empty($arProducts)) {
            return [];
        }
        $arProducts = array_replace(...$arProducts);
        return $arProducts;
    }

    public static function modifyBasket(&$arResult)
    {
        $arProducts = [];
        foreach ($arResult["ITEMS"] as $type => &$arItems) {
            foreach ($arItems as &$arItem) {
                $arProducts[] = &$arItem;
            }
            unset($arItem);
        }
        unset($arItems);
        $arResult['PRODUCTS'] = static::modifyBasketProducts($arProducts);
        unset($arProducts);
    }

    public static function modifyLinkElementsProperties(&$arItems)
    {
        $arElements = [];

        foreach ($arItems as $arItem) {
            foreach ($arItem['PROPERTIES'] as $arProp) {
                if($arProp['PROPERTY_TYPE'] == 'E' && $arProp['CODE'] != 'CML2_LINK' && $arProp['VALUE']) {
                    if($arProp['MULTIPLE'] == 'Y') {
                        foreach ($arProp['VALUE'] as $val) {
                            if($val) {
                                $arElements[$val] = $val;
                            }
                        }
                    } else {
                        $arElements[$arProp['VALUE']] = $arProp['VALUE'];
                    }
                }
            }
        }

        $arElements = static::getLinkElements($arElements);

        foreach ($arItems as &$arItem) {
            foreach ($arItem['PROPERTIES'] as &$arProp) {
                if ($arProp['PROPERTY_TYPE'] == 'E' && $arProp['CODE'] != 'CML2_LINK') {
                    if ($arProp['MULTIPLE'] == 'Y') {
                        $arProp['LINK_ELEMENT'] = [];
                        if($arProp['VALUE']) {
                            foreach ($arProp['VALUE'] as $val) {
                                if($val) {
                                    $arProp['LINK_ELEMENT'][] = $arElements[$val];
                                }
                            }
                        }
                    } else {
                        if($arProp['VALUE']) {
                            $arProp['LINK_ELEMENT'] = $arElements[$arProp['VALUE']];
                        } else {
                            $arProp['LINK_ELEMENT'] = false;
                        }
                    }
                }
            }
            unset($arProp);
        }
        unset($arItem);
    }

    public static function getLinkElements($arElements)
    {
        return static::getElements($arElements);
    }

    public static function localizeSkuProperties(&$arItem)
    {
//        foreach ($arItem['SKU_PROPS'] as $CODE => &$arSkuProp) {
//            if($arSkuProp['PROPERTY_TYPE'] != 'L') {
//                continue;
//            }
//            if (substr($arSkuProp['CODE'], -3) == '_RU') {
//                if (LANGUAGE_UPPER == 'RU') {
//                    continue;
//                }
//                $code = substr($arSkuProp['CODE'], 0, -3);
//            } else {
//                $code = $arSkuProp['CODE'];
//            }
//            $code .= '_' . LANGUAGE_UPPER;
//
//            $dbProperties = \CIBlockElement::GetList(
//                [],
//                [
//                    'PROPERTY_CML2_LINK' => $arItem['ID'],
//                ],
//                [
//                    'PROPERTY_' . $arSkuProp['CODE'],
//                    'PROPERTY_' . $code,
//                ]
//            );
//            $arMap = [];
//            $src = 'PROPERTY_' . $arSkuProp['CODE'] . '_ENUM_ID';
//            $dest = 'PROPERTY_' . $code . '_VALUE';
//            while ($arProperty = $dbProperties->Fetch()) {
//                $arMap[$arProperty[$src]] = $arProperty[$dest];
//            }
//
//            foreach ($arSkuProp['VALUES'] as $ID => &$arValue) {
//                if ($arValue['ID'] && $arMap[$arValue['ID']]) {
//                    $arValue['NAME'] = $arMap[$arValue['ID']];
//                }
//            }
//            unset($arValue);
//        }
    }

    public static function modifySkuPropsElementsProperties(&$arSkupProps)
    {
        $arElements = [];

        foreach ($arSkupProps as $arProp) {
            if($arProp['PROPERTY_TYPE'] == 'E' && $arProp['CODE'] != 'CML2_LINK' && $arProp['VALUES']) {
                foreach ($arProp['VALUES'] as $valId => $val) {
                    if($val) {
                        $arElements[$valId] = $valId;
                    }
                }
            }
        }

        $arElements = static::getSkuPropElements($arElements);

        foreach ($arSkupProps as &$arProp) {
            if ($arProp['PROPERTY_TYPE'] == 'E' && $arProp['CODE'] != 'CML2_LINK') {
                foreach ($arProp['VALUES'] as $valId => &$val) {
                    if($val) {
                        $val['LINK_ELEMENT'] = $arElements[$valId];
                    }
                }
                unset($val);
            }
        }
        unset($arProp);
    }

    public static function getSkuPropElements($arElements)
    {
        return static::getElements($arElements);
    }

    public static function getSkuProps($IBLOCK_ID)
    {
        $codeList = \Bitrix\Catalog\Product\PropertyCatalogFeature::getOfferTreePropertyCodes(
            $IBLOCK_ID,
            ['CODE' => 'Y']
        );
        return $codeList;
    }

    public static function getSkuPropValues($IBLOCK_ID, $bNoDefault, $isProductIblock_Hint = true)
    {
        $arCatalog = static::getCatalogDesc($IBLOCK_ID, $isProductIblock_Hint);
        if(empty($arCatalog)) {
            return false;
        }

        $codeList = static::getSkuProps($arCatalog['IBLOCK_ID']);
        $arProps = \CIBlockPriceTools::getTreeProperties($arCatalog['PRODUCT_IBLOCK_ID'], $codeList);
        $needValues = [];
        \CIBlockPriceTools::getTreePropertyValues($arProps, $needValues);

        foreach ($arProps as &$arProp) {
            $arProp['VALUES_ASSOC'] = $arProp['VALUES'];
            if($bNoDefault) {
                unset($arProp['VALUES'][0]);
                $arProp['VALUES_COUNT'] = count($arProp['VALUES']);
            }
            $arProp['VALUES'] = array_values($arProp['VALUES']);
        }
        unset($arProp);

        return $arProps;
    }

    public static function getCatalogDesc($IBLOCK_ID, $isProductIblock_Hint = true)
    {
        if(is_array($IBLOCK_ID) && isset($IBLOCK_ID['IBLOCK_ID']) && isset($IBLOCK_ID['PRODUCT_IBLOCK_ID'])) {
            return $IBLOCK_ID;
        }

        if($isProductIblock_Hint) {
            $arCatalog = \CCatalogSku::GetInfoByProductIBlock($IBLOCK_ID);
            if (empty($arCatalog)) {
                $arCatalog = \CCatalogSku::GetInfoByOfferIBlock($IBLOCK_ID);
            }
        } else {
            $arCatalog = \CCatalogSku::GetInfoByOfferIBlock($IBLOCK_ID);
            if (empty($arCatalog)) {
                $arCatalog = \CCatalogSku::GetInfoByProductIBlock($IBLOCK_ID);
            }
        }

        if(empty($arCatalog)) {
            $arCatalog = false;
        }

        return $arCatalog;
    }

    public static function getProductsOffers($arProductIds, $iblockId)
    {
        $codeList = static::getSkuProps($iblockId);
        $offers = \CCatalogSku::getOffersList(
            $arProductIds,
            $iblockId,
            [
                'ACTIVE' => 'Y',
                'ACTIVE_DATE' => 'Y',
                'CATALOG_AVAILABLE' => 'Y',
                'CHECK_PERMISSIONS' => 'Y',
                'MIN_PERMISSION' => 'R',
            ],
            ['ID', 'IBLOCK_ID', 'XML_ID', 'NAME', 'AVAILABLE', 'QUANTITY'],
            ['CODE' => $codeList]
        );
        return $offers;
    }

    public static function getBy(&$arElements, $field)
    {
        $arResult = [];

        foreach ($arElements as &$element) {
            $arResult[$element[$field]] = &$element;
        }
        unset($element);

        return $arResult;
    }

    public static function parseLocation($location, $defaultCountryId = 0, $isLocationCode = true)
    {
        $arCountry = $arCity = false;
        $locationField = $isLocationCode ? 'CODE' : 'ID';
        if($location != '') {
            $arLocation = false;
            $dbLocation = \Bitrix\Sale\Location\Name\LocationTable::getList([
                'select' => [
                    'LOCATION_ID',
                    'NAME',
                    'CODE' => 'LOCATION.CODE',
                    'LOCATION_TYPE' => 'LOCATION.TYPE_ID',

                    'LEFT_MARGIN' => 'LOCATION.LEFT_MARGIN',
                    'RIGHT_MARGIN' => 'LOCATION.RIGHT_MARGIN',

                    'LANGUAGE_ID',
                ],
                'filter' => [
                    "LOCATION.$locationField" => $location,
                    'LOCATION.TYPE_ID' => [1, 5], //Countries & cities
                    //'LANGUAGE_ID' => LANGUAGE_ID,
                ]
            ]);
            while($_arLocation = $dbLocation->fetch()) {
                if(empty($arLocation) || $_arLocation['LANGUAGE_ID'] == LANGUAGE_ID) {
                    $arLocation = $_arLocation;
                }
            }
            if($arLocation) {
                $arLocation['ID'] = $arLocation['LOCATION_ID'];
                unset($arLocation['LOCATION_ID']);
                if($arLocation['LOCATION_TYPE'] == 1) {
                    //Country
                    $arCountry = $arLocation;
                    $arCity = false;
                } else {
                    //City
                    $arCity = $arLocation;
                    $arCountry = false;
                    $dbCountry = \Bitrix\Sale\Location\Name\LocationTable::getList([
                        'select' => [
                            'LOCATION_ID',
                            'NAME',
                            'CODE' => 'LOCATION.CODE',
                            'LOCATION_TYPE' => 'LOCATION.TYPE_ID',

                            'LANGUAGE_ID',
                        ],
                        'filter' => [
                            '<=LOCATION.LEFT_MARGIN' => $arCity['LEFT_MARGIN'],
                            '>=LOCATION.RIGHT_MARGIN' => $arCity['RIGHT_MARGIN'],
                            'LOCATION.TYPE_ID' => 1, //Country
                            //'LANGUAGE_ID' => LANGUAGE_ID,
                        ]
                    ]);
                    while($_arCountry = $dbCountry->fetch()) {
                        if(empty($arCountry) || $_arCountry['LANGUAGE_ID'] == LANGUAGE_ID) {
                            $arCountry = $_arCountry;
                        }
                    }
                    if($arCountry) {
                        $arCountry['ID'] = $arCountry['LOCATION_ID'];
                        unset($arCountry['LOCATION_ID']);
                    }
                }
            } else if($defaultCountryId) {
                $arCountry = false;
                $dbCountry = \Bitrix\Sale\Location\Name\LocationTable::getList([
                    'select' => [
                        'LOCATION_ID',
                        'NAME',
                        'CODE' => 'LOCATION.CODE',
                        'LOCATION_TYPE' => 'LOCATION.TYPE_ID',

                        'LANGUAGE_ID',
                    ],
                    'filter' => [
                        'LOCATION.ID' => $defaultCountryId,
                        'LOCATION.TYPE_ID' => 1, //Countries only
                        //'LANGUAGE_ID' => LANGUAGE_ID,
                    ]
                ]);
                while($_arCountry = $dbCountry->fetch()) {
                    if(empty($arCountry) || $_arCountry['LANGUAGE_ID'] == LANGUAGE_ID) {
                        $arCountry = $_arCountry;
                    }
                }
                if($arCountry) {
                    $arCountry['ID'] = $arCountry['LOCATION_ID'];
                    unset($arCountry['LOCATION_ID']);
                } else {
                    $arCountry = false;
                }
            }
        }

        $arResult = [];
        $arResult['COUNTRY'] = $arCountry;
        $arResult['CITY'] = $arCity;
        return $arResult;
    }

    public static function getSkuPropertyValue($arProperty)
    {
        if(is_array($arProperty['VALUE'])) {
            $arSkuValues = [];
            foreach ($arProperty['VALUE'] as $index => $value) {
                $arSkuValues[] = $arProperty['LINK_ELEMENT'][$index]['NAME'] ?:
                    $arProperty['VALUE'][$index];
            }
            return $arSkuValues;
        } else if($arProperty['VALUE']) {
            return $arProperty['LINK_ELEMENT']['NAME'] ?: $arProperty['VALUE'];
        } else {
            return $arProperty['MULTIPLE'] == 'Y' ? [] : false;
        }
    }

    public static function getSkuPropertyValueId($arProperty)
    {
        if(is_array($arProperty['VALUE'])) {
            $arSkuValueIds = [];
            if($arProperty['VALUE_ENUM_ID']) {
                foreach ($arProperty['VALUE'] as $index => $value) {
                    $arSkuValueIds[] = $arProperty['VALUE_ENUM_ID'][$index] ?: $value;
                }
            } else {
                $arSkuValueIds = array_values($arProperty['VALUE']);
            }
            return $arSkuValueIds;
//        } else if($arProperty['VALUE'] || $arProperty['VALUE_ENUM_ID']) {
//            return $arProperty['VALUE_ENUM_ID'] ?: $arProperty['VALUE'] ?: false;
        } else if($arProperty['VALUE_ENUM_ID']) {
            return $arProperty['VALUE_ENUM_ID'];
        } else if($arProperty['VALUE']) {
            return $arProperty['VALUE'];
        } else {
            return $arProperty['MULTIPLE'] == 'Y' ? [] : false;
        }
    }

    public static function getItemOptimalPrice($ID, $CATALOG_CURRENCY = false)
    {
        $arPrice = \CCatalogProduct::GetOptimalPrice($ID);
        if($arPrice) {
            $arPrice = static::prepareOptimalPrice($arPrice, $CATALOG_CURRENCY);
        } else {
            $arPrice = false;
        }
        return $arPrice;
    }

    public static function prepareOptimalPrice($arPrice, $CATALOG_CURRENCY = false)
    {
        if ($arPrice) {
            if($arPrice['RESULT_PRICE']) {
                $arPrice = $arPrice['RESULT_PRICE'];
            }
            $arPrice = [
                'BASE_PRICE' => $arPrice['BASE_PRICE'],
                'PRICE' => $arPrice['DISCOUNT_PRICE'],
                'DISCOUNT' => $arPrice['DISCOUNT'],
                'DISCOUNT_PERCENT' => $arPrice['DISCOUNT_PERCENT'],
                'CURRENCY' => $arPrice['CURRENCY'],
            ];
            if ($CATALOG_CURRENCY && $arPrice['CURRENCY'] != $CATALOG_CURRENCY) {
                foreach (['BASE_PRICE', 'PRICE', 'DISCOUNT'] as $field) {
                    $arPrice[$field] = \CCurrencyRates::ConvertCurrency($arPrice[$field], $arPrice['CURRENCY'], $CATALOG_CURRENCY);
                }
                $arPrice['CURRENCY'] = $CATALOG_CURRENCY;
            }
            $arPrice['BASE_PRICE_FORMATED'] = \CCurrencyLang::CurrencyFormat($arPrice['BASE_PRICE'], $arPrice['CURRENCY']);
            $arPrice['PRICE_FORMATED'] = \CCurrencyLang::CurrencyFormat($arPrice['PRICE'], $arPrice['CURRENCY']);
        } else {
            $arPrice = false;
        }
        return $arPrice;
    }

    public static function modifyGiftCardSection(&$arResult)
    {
        static::modifyProductSection($arResult);

        $bFound = false;
        if($arResult['OFFER_ID_SELECTED']) {
            foreach ($arResult['ITEMS'] as &$arItem) {
                if ($arItem['ID'] == $arResult['OFFER_ID_SELECTED']) {
                    $bFound = true;
                    break;
                }
            }
        }
        if(!$bFound) {
            foreach ($arResult['ITEMS'] as $arItem) {
                if ($arItem['PRODUCT']['AVAILABLE'] === 'Y' || $arItem['PRODUCT']['AVAILABLE'] === true) {
                    $bFound = true;
                    $arResult['OFFER_ID_SELECTED'] = $arItem['ID'];
                    break;
                }
            }
        }
        if(!$bFound) {
            foreach ($arResult['ITEMS'] as $arItem) {
                $bFound = true;
                $arResult['OFFER_ID_SELECTED'] = $arItem['ID'];
                break;
            }
        }

        $arResult['JS_DATA'] = [
            'OFFER_ID_SELECTED' => $arResult['OFFER_ID_SELECTED'],
            'OFFERS' => [],
        ];
        foreach ($arResult['ITEMS'] as $arItem) {
            $jsOffer = [
                'ID' => $arItem['ID'],
                'PRICE' => $arItem['PRICE'],
                'CAN_BUY' => $arItem['PRICE']['AVAILABLE']
                    && ($arItem['PRODUCT']['AVAILABLE'] === 'Y' || $arItem['PRODUCT']['AVAILABLE'] === true),
            ];
            $arResult['JS_DATA']['OFFERS'][] = $jsOffer;
        }
    }
}

if(!defined('LANGUAGE_UPPER') && defined('LANGUAGE_ID')) {
    define('LANGUAGE_UPPER', mb_strtoupper(LANGUAGE_ID));
}