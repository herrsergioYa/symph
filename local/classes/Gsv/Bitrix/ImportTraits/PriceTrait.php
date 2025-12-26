<?php

namespace Gsv\Bitrix\ImportTraits;

trait PriceTrait
{
    use CommonTrait;

    /**
     * @var array $arPrices - Prices
     */
    protected $arPrices = [];

    protected function initPrices($MAPPING)
    {
        //$this->arPrices = [];
        $this->fillPrices(static::getPriceCodes($MAPPING));
    }

    protected function fillPrices($prices)
    {
        $dbPrices = \Bitrix\Catalog\GroupTable::getList([
            'filter' => [
                '=NAME' => array_unique($prices),
            ],
            'select' => [
                'NAME', 'ID'
            ],
        ]);
        while($arPrice = $dbPrices->fetch()) {
            $this->arPrices[$arPrice['NAME']] = $arPrice;
        }
    }

    protected function readCatalogPrices(&$arOffers)
    {
        $arIds = [];
        foreach ($arOffers as $k => &$arOffer) {
            $arIds[$arOffer['ID']] = $k;
            $arOffer['PRICES'] = [];
        }
        unset($arOffer);
        $arGrp = [];
        foreach ($this->arPrices as $k => $arPrice) {
            $arGrp[$arPrice['ID']] = $k;
        }

        $dbPrices = \Bitrix\Catalog\Model\Price::getList([
            'filter' => [
                '=PRODUCT_ID' => array_keys($arIds),
                '=CATALOG_GROUP_ID' => array_keys($arGrp),
            ],
            'select' => [
                'ID', 'PRODUCT_ID', 'CATALOG_GROUP_ID',
                'PRICE', 'CURRENCY', 'PRICE_SCALE',
            ]
        ]);
        while($arPrice = $dbPrices->fetch()) {
            if(static::DEBUG && array_key_exists($arGrp[$arPrice['CATALOG_GROUP_ID']], $arOffers[$arIds[$arPrice['PRODUCT_ID']]]['PRICES'])) {
                static ::dump("Excess PRICE with ID = " . $arPrice['ID']);
                \Bitrix\Catalog\Model\Price::delete($arPrice['ID']);
                continue;
            }
            $arOffers[$arIds[$arPrice['PRODUCT_ID']]]['PRICES'][$arGrp[$arPrice['CATALOG_GROUP_ID']]] = $arPrice;
        }
    }

    protected function applyPrices($PRODUCT_ID, $arExistingPrices, $arPrices)
    {
        foreach ($arPrices as $name => $arPrice) {
            list('PRICE' => $price, 'CURRENCY' => $currency) = $arPrice;
            if(array_key_exists($name, $arExistingPrices)) {
                $arExistingPrice = $arExistingPrices[$name];
                if(abs(floatval($arExistingPrice['PRICE']) - $price) < 0.001 && $arExistingPrice['CURRENCY'] == $currency) {
                    unset($arExistingPrices[$name]);
                    continue;
                }
                $arFields = [
                    'PRICE' => $price,
                    'CURRENCY' => $currency,
                ];
                $result = \Bitrix\Catalog\Model\Price::update($arExistingPrice['ID'], $arFields);
                unset($arExistingPrices[$name]);
            } else {
                $arFields = [
                    'PRODUCT_ID' => intval($PRODUCT_ID),
                    'CATALOG_GROUP_ID' => $this->arPrices[$name]['ID'],
                    'PRICE' => $price,
                    'CURRENCY' => $currency,
                ];
                $result = \Bitrix\Catalog\Model\Price::add($arFields);
            }
        }

        //А это все можно удалить...
        foreach ($arExistingPrices as $arExistingPrice) {
            \Bitrix\Catalog\Model\Price::delete($arExistingPrices['ID']);
        }
    }

    public static function asPrice($target)
    {
        $pos = strpos($target, 'PRICE_');
        if($pos === 0) {
            return substr($target, strlen('PRICE_'));
        } else {
            return false;
        }
    }

    protected static function getPriceCodes($arMapping)
    {
        $arResult = [];
        foreach ($arMapping as $key => $arTargets) {
            foreach ($arTargets as $target) {
                if($price = static::asPrice($target)) {
                    $arResult[] = $price;
                }
            }
        }
        return array_unique($arResult);
    }
}