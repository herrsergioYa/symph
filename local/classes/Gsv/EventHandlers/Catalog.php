<?php

namespace Gsv\EventHandlers;

class Catalog
{
    public static function OnGetOptimalPriceHandler($intProductID,
                                                    $quantity,
                                                    $arUserGroups,
                                                    $renewal,
                                                    $arPrices,
                                                    $siteID,
                                                    $arDiscountCoupons)
    {
        if (!is_array($arUserGroups) && (int)$arUserGroups.'|' == (string)$arUserGroups.'|')
            $arUserGroups = array((int)$arUserGroups);

        if (!is_array($arUserGroups))
            $arUserGroups = array();

        if (!in_array(2, $arUserGroups))
            $arUserGroups[] = 2;

        if ($siteID === false)
            $siteID = SITE_ID;

        $PRICE_TYPE_ID = SITES[$siteID]['PRICE_TYPE_ID'];

        $arPrice = \Bitrix\Catalog\PriceTable::getRow([
            'filter' => [
                'PRODUCT_ID' => $intProductID,
                'CATALOG_GROUP_ID' => $PRICE_TYPE_ID,
            ],
            'select' => [
                'ID',
                'CATALOG_GROUP_ID',
                'PRICE',
                'CURRENCY',
            ]
        ]);
        if(empty($arPrice)) {
            return false;
        }
        $arPrice['ELEMENT_IBLOCK_ID'] = $intProductID;
        if($arPrices && is_array($arPrices)) {
            $found = true;
            foreach ($arPrices as $price) {
                if($price['ID'] == $arPrice['ID']) {
                    $found = true;
                    break;
                }
            }
            if(!$found) {
                //return false;
            }
        }

        return [
            'PRICE' => $arPrice,
        ];
    }
}