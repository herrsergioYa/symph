<?php

namespace Gsv\Bitrix\ImportTraits;

trait StoreTrait
{
    use CommonTrait;

    /**
     * @var array $arStores - Amounts in the stores
     */
    protected $arStores = [];

    protected function initStores($MAPPING)
    {
        //$this->arStores = [];
        $this->fillStores(static::getStoreAmountCodes($MAPPING));
    }

    protected function fillStores($stores)
    {
        $dbStores = \Bitrix\Catalog\StoreTable::getList([
            'filter' => [
                '=ID' => array_unique($stores),
            ],
            'select' => [
                'XML_ID', 'ID'
            ],
        ]);
        while($arStore = $dbStores->fetch()) {
            $this->arStores[$arStore['ID']] = $arStore;
        }
    }

    protected function readCatalogStoreAmount(&$arOffers)
    {
        $arIds = [];
        foreach ($arOffers as $k => &$arOffer) {
            $arIds[$arOffer['ID']] = $k;
            $arOffer['STORES'] = [];
        }
        unset($arOffer);
        $arGrp = [];
        foreach ($this->arStores as $k => $arStore) {
            $arGrp[$arStore['ID']] = $k;
        }

        $dbAmounts = \Bitrix\Catalog\StoreProductTable::getList([
            'filter' => [
                '=PRODUCT_ID' => array_keys($arIds),
                '=STORE_ID' => array_keys($arGrp),
            ],
            'select' => [
                'ID', 'PRODUCT_ID', 'STORE_ID',
                'AMOUNT',
            ]
        ]);
        while($arAmount = $dbAmounts->fetch()) {
            if(static::DEBUG && array_key_exists($arGrp[$arAmount['STORE_ID']], $arOffers[$arIds[$arAmount['PRODUCT_ID']]]['STORES'])) {
                static ::dump("Excess STORE AMOUNT with ID = " . $arAmount['ID']);
                \Bitrix\Catalog\StoreProductTable::delete($arAmount['ID']);
                continue;
            }
            $arOffers[$arIds[$arAmount['PRODUCT_ID']]]['STORES'][$arGrp[$arAmount['STORE_ID']]] = $arAmount;
        }
    }


    protected function applyAmounts($PRODUCT_ID, $arExistingAmounts, $arAmounts)
    {
        foreach ($arAmounts as $id => $amount) {
            if(array_key_exists($id, $arExistingAmounts)) {
                $arExistingAmount = $arExistingAmounts[$id];
                if($arExistingAmount['AMOUNT'] == $amount) {
                    unset($arExistingAmounts[$id]);
                    continue;
                }
                $arFields = [
                    'AMOUNT' => $amount,
                ];
                $result = \Bitrix\Catalog\StoreProductTable::update($arExistingAmount['ID'], $arFields);
                unset($arExistingAmounts[$id]);
            } else {
                $arFields = [
                    'PRODUCT_ID' => intval($PRODUCT_ID),
                    'STORE_ID' => $this->arStores[$id]['ID'],
                    'AMOUNT' => $amount,
                ];
                $result = \Bitrix\Catalog\StoreProductTable::add($arFields);
            }
        }

        //А это все можно удалить...
        foreach ($arExistingAmounts as $arExistingAmount) {
            \Bitrix\Catalog\StoreProductTable::delete($arExistingAmount['ID']);
        }
    }

    public static function asStoreAmount($target)
    {
        $pos = strpos($target, 'STORE_');
        if($pos === 0) {
            return substr($target, strlen('STORE_'));
        } else {
            return false;
        }
    }

    protected static function getStoreAmountCodes($arMapping)
    {
        $arResult = [];
        foreach ($arMapping as $key => $arTargets) {
            foreach ($arTargets as $target) {
                if($store = static::asStoreAmount($target)) {
                    $arResult[] = $store;
                }
            }
        }
        return array_unique($arResult);
    }
}