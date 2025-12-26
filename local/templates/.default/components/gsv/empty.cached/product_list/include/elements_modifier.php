<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arElements */

$arAdditionalProductProps = array("CML2_MANUFACTURER");
$arAdditionalOfferProps = ["CML2_ATTRIBUTES"];
$brandPropCode = false;//'CML2_MANUFACTURER';
$arAdditionalBrandProps = [];
$arImageSizes = ['width' => 100, 'height' => 150];
$bPrefferDetailPicture = false;

if($arElements) {
    $dbElements = \CIBlockElement::GetList(
        [],
        [
            'ID' => array_unique($arElements),
        ],
        false,
        false,
        ['ID', 'IBLOCK_ID', 'NAME', 'TYPE', 'PREVIEW_PICTURE', 'DETAIL_PICTURE', 'DETAIL_PAGE_URL']
    );
    $arElements = [];
    $arOffers = [];
    while ($arElement = $dbElements->GetNext()) {
        if (!array_key_exists($arElement['IBLOCK_ID'], $arElements)) {
            $arElements[$arElement['IBLOCK_ID']] = [];
        }
        $arElement['PROPERTIES'] = [];
        $arElements[$arElement['IBLOCK_ID']][$arElement['ID']] = $arElement;
        if ($arElement['TYPE'] == \Bitrix\Catalog\ProductTable::TYPE_OFFER) {
            $arOffers[$arElement['IBLOCK_ID']] = $arElement['IBLOCK_ID'];
        }
    }
    $arSkus = [];
    $arOfferProps = ['CML2_LINK'];
    if(is_array($arAdditionalOfferProps)) {
        $arOfferProps = array_merge($arOfferProps, $arAdditionalOfferProps);
    }
    if(in_array('*', $arOfferProps)) {
        $arOfferPropFilter = [];
    } else {
        $arOfferPropFilter = ['CODE' => $arOfferProps];
    }
    foreach ($arOffers as $offerIblockId) {
        \CIBlockElement::GetPropertyValuesArray(
            $arElements[$offerIblockId],
            $offerIblockId,
            ['ID' => array_keys($arElements[$offerIblockId])],
            $arOfferPropFilter
        );
        foreach ($arElements[$offerIblockId] as $arOffer) {
            $skuId = $arOffer['PROPERTIES']['CML2_LINK']['VALUE'];
            if ($skuId > 0) {
                $arSkus[$skuId] = $skuId;
            }
        }
    }
    if ($arSkus) {
        $dbSkus = \CIBlockElement::GetList(
            [],
            [
                'ID' => array_keys($arSkus),
            ],
            false,
            false,
            ['ID', 'IBLOCK_ID', 'NAME', 'TYPE', 'PREVIEW_PICTURE', 'DETAIL_PICTURE', 'DETAIL_PAGE_URL']
        );
        while ($arElement = $dbSkus->GetNext()) {
            if (!array_key_exists($arElement['IBLOCK_ID'], $arElements)) {
                $arElements[$arElement['IBLOCK_ID']] = [];
            }
            $arElement['PROPERTIES'] = [];
            $arElements[$arElement['IBLOCK_ID']][$arElement['ID']] = $arElement;
        }
    }
    $arBrands = [];
    $BRAND_IBLOCK_ID = 0;
    $arProductProps = [];
    if($brandPropCode) {
        $arProductProps[] = $brandPropCode;
    }
    if(is_array($arAdditionalOfferProps)) {
        $arProductProps = array_merge($arProductProps, $arAdditionalOfferProps);
    }
    if(in_array('*', $arProductProps)) {
        $arProductPropFilter = [];
    } else {
        $arProductPropFilter = ['CODE' => $arProductProps];
    }
    foreach ($arElements as $iblockId => $elements) {
        if(empty($arProductProps)) {
            break;
        }
        if (!isset($arOffers[$iblockId])) {
            \CIBlockElement::GetPropertyValuesArray(
                $arElements[$iblockId],
                $iblockId,
                ['ID' => array_keys($elements)],
                $arProductPropFilter
            );
            foreach ($arElements[$iblockId] as &$arProduct) {
                if(empty($brandPropCode)) {
                    break;
                }
                $brandId = $arProduct['PROPERTIES'][$brandPropCode]['VALUE'];
                if ($brandId > 0) {
                    $arBrands[$brandId] = $brandId;
                    $BRAND_IBLOCK_ID = $arProduct['PROPERTIES'][$brandPropCode]['LINK_IBLOCK_ID'];
                }
            }
            unset($arProduct);
        }
    }
    if ($arBrands && $BRAND_IBLOCK_ID) {
        $dbBrands = \CIBlockElement::GetList(
            [],
            [
                'ID' => array_keys($arBrands),
                //'IBLOCK_ID' => $BRAND_IBLOCK_ID,
            ],
            false,
            false,
            ['ID', 'IBLOCK_ID', 'NAME', 'PREVIEW_PICTURE', 'DETAIL_PICTURE', 'PREVIEW_TEXT', 'DETAIL_TEXT',]
        );
        $arBrands = [];
        while ($arBrand = $dbBrands->GetNext()) {
            $arBrand['PROPERTIES'] = [];
            $BRAND_IBLOCK_ID = $arBrand['IBLOCK_ID'];
            $arBrands[$arBrand['ID']] = $arBrand;
        }
        $arBrandProps = [];
        if(is_array($arAdditionalBrandProps)) {
            $arBrandProps = array_merge($arBrandProps, $arAdditionalBrandProps);
        }
        if(in_array('*', $arBrandProps)) {
            $arBrandPropFilter = [];
        } else {
            $arBrandPropFilter = ['CODE' => $arBrandProps];
        }
        \CIBlockElement::GetPropertyValuesArray(
            $arBrands,
            $BRAND_IBLOCK_ID,
            ['ID' => array_keys($arBrands)],
            $arBrandPropFilter
        );
        foreach ($arBrands as &$arBrand) {
            break;//For now!
        }
        unset($arBrand);
    }

    foreach ($arOffers as $offerIblockId) {
        foreach ($arElements[$offerIblockId] as $i => &$arOffer) {
            //Обработка торговых предложений
            break;
        }
        unset($arOffer);
    }

    foreach ($arElements as $iblockId => $elements) {
        if (!isset($arOffers[$iblockId])) {
            //Обработка товаров
            foreach ($arElements[$iblockId] as &$arProduct) {
                if ($brandPropCode && array_key_exists($arProduct['PROPERTIES'][$brandPropCode]['VALUE'], $arBrands)) {
                    $arProduct['BRAND'] = $arBrands[$arProduct['PROPERTIES'][$brandPropCode]['VALUE']];
                } else {
                    $arProduct['BRAND'] = false;
                }
            }
            unset($arProduct);
        }
    }

    $arElements = array_replace(...$arElements);

    foreach ($arElements as &$arProduct) {
        //Обработка всех подряд
        if($bPrefferDetailPicture) {
            $arPicture = $arProduct['DETAIL_PICTURE'] ?: $arProduct["PREVIEW_PICTURE"];
        } else {
            $arPicture = $arProduct['PREVIEW_PICTURE'] ?: $arProduct["DETAIL_PICTURE"];
        }
        if ($arPicture) {
            $arPicture = \CFile::GetFileArray($arPicture);
            if($arPicture && $arImageSizes) {
                $arTempPicture = \CFile::ResizeImageGet($arPicture['ID'], $arImageSizes);
                $arTempPicture = array_change_key_case($arTempPicture, CASE_UPPER);
                $arPicture = $arTempPicture + $arPicture;
            }
        }
        if(empty($arPicture)) {
            $arPicture = [
                'SRC' => "https://placehold.co/${arImageSizes['width']}x${arImageSizes['height']}",
                'empty' => true,
            ];
        }
        $arProduct['PICTURE'] = $arPicture;
    }
    unset($arProduct);

    $arElements['BASKET'] = [];
    foreach ($arElements as $id => $arProduct) {
        $arBasketItem = [
            'PRODUCT_ID' => $id,
        ];
        if($arProduct['TYPE'] == \Bitrix\Catalog\ProductTable::TYPE_OFFER) {
            $arBasketItem['OFFER'] = $arProduct;
            $productId = $arProduct['PROPERTIES']['CML2_LINK']['VALUE'];
            if(array_key_exists($productId, $arElements)) {
                $arProduct = $arElements[$productId];
            } else {
                $arProduct = false;
            }
        }
        $arBasketItem['PRODUCT'] = $arProduct;
        $arElements['BASKET'][$id] = $arBasketItem;
    }
}
