<?php

namespace Gsv\Bitrix\ImportTraits;

trait ProductTrait
{
    use CommonTrait;

    protected function readCatalogCatalog(&$arProducts, $PRODUCT_CODES, &$arOffers, $OFFER_CODES)
    {
        $arIds = [];
        foreach ($arProducts as $key => $arProduct) {
            $arIds[$arProduct['ID']] = $key;
        }
        foreach ($arOffers as $key => $arOffer) {
            $arIds[$arOffer['ID']] = $key;
        }

        $arSelect = ['ID', 'TYPE'];
        $arSelect = array_merge($arSelect, $PRODUCT_CODES);
        $arSelect = array_merge($arSelect, $OFFER_CODES);
        $arSelect = array_values(array_unique($arSelect));

        $dbProd = \Bitrix\Catalog\Model\Product::getList([
            'filter' => [
                'ID' => array_keys($arIds),
            ],
            'select' => $arSelect
        ]);
        while($arProd = $dbProd->fetch()) {
            $key = $arIds[$arProd['ID']];
            if(array_key_exists($key, $arProducts)) {
                $arProducts[$key]['PRODUCT'] = $arProd;
            } else if(array_key_exists($key,  $arOffers)) {
                $arOffers[$key]['PRODUCT'] = $arProd;
            } else {
                //Невозможно!
            }
        }
    }

    protected function adjustProduct($arProduct)
    {
        $this->adjustSections($arProduct);
        $arProduct['OFFERS'] = $this->createOffers($arProduct);

        $arProduct['PRODUCT']['TYPE'] = \Bitrix\Catalog\ProductTable::TYPE_SKU;
        return $arProduct;
    }

    protected function adjustOffer($arOffer)
    {
        $arOffer['PRODUCT']['TYPE'] = \Bitrix\Catalog\ProductTable::TYPE_OFFER;
        return $arOffer;
    }

    protected function createOffers($arProduct)
    {
        return $arProduct['OFFERS'];
    }

    protected function adjustCatalog()
    {

    }

    public static function asProd($target)
    {
        $pos = strpos($target, 'PRODUCT_');
        if($pos === 0) {
            return substr($target, strlen('PRODUCT_'));
        } else {
            return false;
        }
    }

    protected static function getProductCodes($arMapping)
    {
        $arResult = [];
        foreach ($arMapping as $key => $arTargets) {
            foreach ($arTargets as $target) {
                if($prod = static::asProd($target)) {
                    $arResult[] = $prod;
                }
            }
        }
        return array_unique($arResult);
    }
}