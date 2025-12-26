<?php

namespace Gsv\Helpers;

class Ecommerce
{
    public static function getProducts($arProducts)
    {
        \Bitrix\Main\Loader::requireModule('iblock');
        \Bitrix\Main\Loader::requireModule('catalog');

        return static::getProductList($arProducts);
    }

    public static function getBasketItems($arProducts)
    {
        \Bitrix\Main\Loader::requireModule('sale');

        foreach ($arProducts as &$arProduct) {
            if(empty($arProduct['quantity']) || $arProduct['quantity'] <= 0) {
                $arProduct['quantity'] = 1;
            }
        }

        return static::getProductList($arProducts);
    }

    public static function getOrderItems($arProducts)
    {
        \Bitrix\Main\Loader::requireModule('sale');

        return array_values(static::getProductList($arProducts));
    }

    public static function getProductList($arList, $bUseOfferId = false)
    {
        \Bitrix\Main\Loader::requireModule('iblock');
        \Bitrix\Main\Loader::requireModule('catalog');

        $arResult = [];

        if($arList) {

            foreach ($arList as $item) {
                $arResult[$item['id']] = $item['id'];
            }

            $arOfferProducts = [];
            $dbProducts = \CIBlockElement::GetList(
                [],
                [
                    'ID' => array_keys($arResult),
                    //'IBLOCK_ID' => '',
                ],
                false,
                false,
                ['NAME', 'IBLOCK_ID', 'ID', 'TYPE', /*'PROPERTY_CML2_LINK'*/]
            );
            $arProducts = [];
            while ($arProduct = $dbProducts->Fetch()) {
                $arProduct['PROPERTIES'] = [];
                if ($arProduct['TYPE'] == \Bitrix\Catalog\ProductTable::TYPE_OFFER) {
                    $arOfferProducts[$arProduct['ID']] = '';
                }
                $arProducts[$arProduct['IBLOCK_ID']][$arProduct['ID']] = $arProduct;
            }
            if ($arOfferProducts) {
                $arOfferProducts = \CCatalogSku::getProductList(array_keys($arOfferProducts));
                if ($arOfferProducts) {
                    foreach ($arOfferProducts as &$arProduct) {
                        $arProduct = $arProduct['ID'];
                    }
                    unset($arProduct);
                } else {
                    $arOfferProducts = [];
                }
            }
            if ($arOfferProducts) {
                $dbProducts = \CIBlockElement::GetList(
                    [],
                    [
                        'ID' => array_values(array_unique($arOfferProducts)),
                        //'IBLOCK_ID' => '',
                    ],
                    false,
                    false,
                    ['NAME', 'IBLOCK_ID', 'ID', 'TYPE',]
                );
                while ($arProduct = $dbProducts->Fetch()) {
                    $arProduct['PROPERTIES'] = [];
                    $arProducts[$arProduct['IBLOCK_ID']][$arProduct['ID']] = $arProduct;
                }
            }

            foreach ($arProducts as $IBLOCK_ID => &$arProductList) {
                \CIBlockElement::GetPropertyValuesArray(
                    $arProductList,
                    $IBLOCK_ID,
                    ['ID' => array_keys($arProductList)],
                    ['CODE' => ['COLOR_1C', 'NAME_' . LANGUAGE_UPPER, 'SIZE_' . LANGUAGE_UPPER,]],
                    ['GET_RAW_DATA' => 'Y']
                );
            }
            unset($arProductList);

            $arProducts = $arProducts ? array_replace(...$arProducts) : [];

            /*$arCategories = [];

            foreach ($arProducts as $arProduct) {
                if (isset($arOfferProducts[$arProduct['ID']])) {
                    continue;
                }
                if ($arProduct['PROPERTIES']['CATEGORY_ID']['VALUE']) {
                    foreach ($arProduct['PROPERTIES']['CATEGORY_ID']['VALUE'] as $categoryId) {
                        $arCategories[] = $categoryId;
                    }
                }
            }

            $arCategories = AndBr::fillCategories($arCategories, CATALOG_CATEGORY_IBLOCK_ID);*/

            foreach ($arProducts as &$arProduct) {
                $canonicalName = $arProduct['NAME'];
                if ($arProduct['PROPERTIES']['NAME_' . LANGUAGE_UPPER]['VALUE']) {
                    $arProduct['NAME'] = $arProduct['PROPERTIES']['NAME_' . LANGUAGE_UPPER]['VALUE'];
                }
                if($arOfferProducts[$arProduct['ID']]) {
                    //Offer
                    $arProduct['SIZE'] = $arProduct['PROPERTIES']['SIZE_' . LANGUAGE_UPPER]['VALUE'];
                } else {
                    //SKU, Product
                    $arProduct['COLOR'] = $arProduct['PROPERTIES']['COLOR_1C']['VALUE'];
                }
                /*if (isset($arOfferProducts[$arProduct['ID']])) {
                    $arProduct['SIZE'] = $arProduct['PROPERTIES']['SIZE']['VALUE'];
                } else {
                    $arCategory = false;
                    foreach ($arProduct['PROPERTIES']['CATEGORY_ID']['VALUE'] as $categoryId) {
                        if ($arCategories[$categoryId]['FILTERABLE']) {
                            $arCategory = $arCategories[$categoryId];
                            break;
                        }
                    }
                    $arProduct['CATEGORY'] = $arCategory;
                }*/
                unset($arProduct['PROPERTIES']);
            }
            unset($arProduct);

            $arResult = [];

            foreach ($arList as $i => $item) {
                if(isset($arOfferProducts[$item['id']])) {
                    //SKU
                    $arProduct = $arProducts[$arOfferProducts[$item['id']]];
                    //Offer
                    $arProduct['OFFER'] = $arProducts[$item['id']];
                } else {
                    //Simple product
                    $arProduct = $arProducts[$item['id']];
                }
                $arCategory = $arProduct['CATEGORY'];
                if(!isset($item['price'])) {
                    //$arPrice = \CCatalogProduct::GetOptimalPrice($arProduct['ID']);
                }
                $arItem = [
                    'id' => $arProduct['ID'],
                    'name' => $arProduct['NAME'],
                    'brand' => 'Ushatava',
                    'category' => $arCategory ? $arCategory['NAME'] : '',
                    'variant' => $arProduct['COLOR'] ?: '',
                    'position' => count($arResult) + 1,
                    ///'price' => $item['price'],
                ];
                if($arProduct['OFFER']) {
                    if($bUseOfferId) {
                        $arItem['id'] = $arProduct['OFFER']['ID'];
                    }
                    if(strlen($arItem['variant'])) {
                        $arItem['variant'] .= ' + ';
                    }
                    $arItem['variant'] .= $arProduct['OFFER']['SIZE'];
                }
                unset($item['id']);//Unnecessary to be precise.
                $arItem += $item;
                $arResult[$i] = $arItem;
            }

        }

        return $arResult;
    }

    public static function getDetailEvent($arProduct, $currency)
    {
        return [                     // Наименование события
            'ecommerce' => [
                'currencyCode' => $currency,               // Код валюты
                'detail' => [
                    //"actionField": '',
                    'products' => array_values(static::getProductList([$arProduct])),
                ],
            ],
        ];
    }

    public static function getBasketEvent($arProducts, $currency, $remove = false)
    {
        if($remove) {
            $event = 'remove';
            $key = 'remove';
        } else {
            $event = 'addToCart';
            $key = 'add';
        }

        return [
            'ecommerce' => [
                'currencyCode' => $currency,               // Код валюты
                $key => [
                    'products' => array_values(static::getProductList($arProducts)),
                ],
            ],
        ];
    }

    public static function getBasketEvents($arBasket, $currency)
    {
        $arAdditions = [];
        $arRemovals = [];
        $arResult = [];

        foreach ($arBasket as $id => $arEvent) {
            if($arEvent['quantity'] > 0) {
                $arAdditions[$id] = $arEvent;
            } else if($arEvent['quantity'] < 0) {
                $arEvent['quantity'] = -$arEvent['quantity'];
                $arRemovals[$id] = $arEvent;
            }
        }

        if($arAdditions) {
            $arResult[] = static::getBasketEvent($arAdditions, $currency, false);
        }
        if($arRemovals) {
            $arResult[] = static::getBasketEvent($arRemovals, $currency, true);
        }

        return $arResult;
    }

    public static function getCheckoutEvent($arProducts, $currency, $step = 1)
    {
        $arEvent = [
            'event' => 'checkout_step_' . $step,            // Наименование события
            'ecommerce' => [
                'currencyCode' => $currency,               // Код валюты
                'checkout' => [
                    'actionField' => [
                        'step' => $step,                         // Шаг оформления заказа
                        'option' => 'View cart',              // Наименование шага
                    ],
                ],
            ],
        ];

        if(is_array($arProducts)) {
            $arEvent['ecommerce']['checkout']['products'] = array_values(static::getBasketItems($arProducts));
        }

        if($step == 2) {
            $arEvent['ecommerce']['checkout']['actionField']['option'] = 'Start order';
        } else {
            unset($arEvent['ecommerce']['checkout']['actionField']);
        }

        return $arEvent;
    }

    public static function getPurchaseEvent(\Bitrix\Sale\Order $order)
    {
        $siteId = $order->getSiteId();
        $sum = $order->getPrice();
        $currency = $order->getCurrency();

        /** @var \Bitrix\Sale\Basket $basket */
        $basket = $order->getBasket();
        $arProducts = static::getBasket($basket);

        $shippingCost = 0;
        /** @var \Bitrix\Sale\Shipment $obShipment */
        foreach ($order->getShipmentCollection() as $obShipment) {
            if($obShipment->isSystem()) {
                continue;
            }
            $shippingCost += $obShipment->getPrice();
        }

        $arCoupon = \Bitrix\Sale\Internals\OrderCouponsTable::getRow([
            'filter' => [
                'ORDER_ID' => $order->getId(),
            ],
            'select' => ['COUPON'],
        ]);
        if($arCoupon) {
            $coupon = $arCoupon['COUPON'];
        } else {
            $coupon = '';
        }

        $arEvent = [
            'ecommerce'=> [
                'currencyCode'=> $currency,               // Код валюты
                'purchase'=> [
                    'actionField'=> [
                        'id'=> $order->getField('ACCOUNT_NUMBER'),                    // ID транзакции
                        //'affiliation'=> 'AndTheBrand',     // Наименование магазина
                        'revenue'=> $sum,    // Стоимость транзакции
                        //'tax'=> $order->getField('TAX_VALUE'),                     // Налог
                        'shipping'=> $shippingCost,                // Стоимость доставки
                        'coupon'=> $coupon,            // Промокод
                    ],
                    'products'=> static::getOrderItems($arProducts),
                ],
            ],
        ];

        return $arEvent;
    }

    public static function getBasket(\Bitrix\Sale\Basket $basket = null, $isDelay = false)
    {
        if(empty($basket)) {
            $basket = \Bitrix\Sale\Basket::loadItemsForFUser(
                \Bitrix\Sale\Fuser::getId(),
                \Bitrix\Main\Context::getCurrent()->getSite(),
            );
        }

        $arBasket = [];

        /** @var \Bitrix\Sale\BasketItem $basketItem */
        foreach ($basket as $basketItem) {
            if($basketItem->isDelay() != $isDelay) {
                continue;
            }
            if(isset($arBasket[$basketItem->getProductId()])) {
                $arBasket[$basketItem->getProductId()]['quantity'] += $basketItem->getQuantity();
            } else {
                $arBasket[$basketItem->getProductId()] = [
                    'id' => $basketItem->getProductId(),
                    'price' => $basketItem->getPrice(),
                    'quantity' => $basketItem->getQuantity(),
                ];
            }
        }

        return $arBasket;
    }

    public static function jsonEncode($ecomm)
    {
        return json_encode($ecomm, JSON_UNESCAPED_UNICODE);
    }

    public static function htmlEncode($ecomm)
    {
        return htmlspecialchars($ecomm);
    }

    public static function htmlJsonEncode($ecomm)
    {
        return static::htmlEncode(static::jsonEncode($ecomm));
    }

    public static function scheduleEcommerceEvent($arEvent)
    {
        if(!is_array($_SESSION['GSV_ECOMMERCE'])) {
            $_SESSION['GSV_ECOMMERCE'] = [];
        }
        $_SESSION['GSV_ECOMMERCE'][] = $arEvent;
    }

    public static function executeEcommerceEvent($bConsume = true)
    {
        if(is_array($_SESSION['GSV_ECOMMERCE'])) {
            $arResult = $_SESSION['GSV_ECOMMERCE'];
        } else {
            $arResult = [];
        }
        if($bConsume) {
            unset($_SESSION['GSV_ECOMMERCE']);
        }
        return $arResult;
    }

    protected static $arBasket = null;

    public static function OnSaleBasketBeforeSavedHandler(\Bitrix\Main\Event $event)
    {
        /** @var \Bitrix\Sale\Basket $basket */
        $basket = $event->getParameter("ENTITY");

        static::$arBasket = static::getBasket();
    }

    public static function OnSaleBasketSavedHandler(\Bitrix\Main\Event $event)
    {
        /** @var \Bitrix\Sale\Basket $basket */
        $basket = $event->getParameter("ENTITY");

        $arBasket = static::getBasket($basket);

        $currency = static::getBasketCurrency($basket);

        if(static::$arBasket) {
            foreach (static::$arBasket as $id => $arItem) {
                if(isset($arBasket[$id])) {
                    $arBasket[$id]['quantity'] -= $arItem['quantity'];
                } else {
                    $arItem['quantity'] = - $arItem['quantity'];
                    $arBasket[$id] = $arItem;
                }
            }
        }

        static::$arBasket = null;

        foreach (static::getBasketEvents($arBasket, $currency) as $arBasketEvent) {
            static::scheduleEcommerceEvent($arBasketEvent);
        }
    }

    public static function OnSaleOrderSavedHandler(\Bitrix\Main\Event $event)
    {
        /** @var \Bitrix\Sale\Order $order */
        $order = $event->getParameter("ENTITY");
        $oldValues = $event->getParameter("VALUES");
        $isNew = $event->getParameter("IS_NEW");
        if ($isNew)
        {
            $arEvent = static::getPurchaseEvent($order);
            static::scheduleEcommerceEvent($arEvent);
        }
    }

    public static function addEventHandler(\Bitrix\Main\EventManager $eventManager = null)
    {
        if(empty($eventManager))
            $eventManager = \Bitrix\Main\EventManager::getInstance();

        $eventManager->addEventHandler('sale', 'OnSaleBasketBeforeSaved', [
            static::class,
            'OnSaleBasketBeforeSavedHandler',
        ]);
        $eventManager->addEventHandler('sale', 'OnSaleBasketSaved', [
            static::class,
            'OnSaleBasketSavedHandler',
        ]);

        $eventManager->addEventHandler('sale', 'OnSaleOrderSaved', [
            static::class,
            'OnSaleOrderSavedHandler',
        ]);
    }

    public static function getSiteCurrency($siteId = false)
    {
        $arCurrency = \Bitrix\Sale\SiteCurrencyTable::getCurrency($siteId ?: SITE_ID);
        return is_array($arCurrency) ? $arCurrency['CURRENCY'] : false;
    }

    public static function getBasketCurrency(\Bitrix\Sale\Basket $basket)
    {
        $currency = null;
        /** @var \Bitrix\Sale\BasketItem $basketItem */
        foreach ($basket->getBasketItems() as $basketItem) {
            if($currency = $basketItem->getCurrency()) {
                break;
            }
        }

        if($currency === null) {
            $currency = static::getSiteCurrency($basket->getSiteId());
        }

        return $currency;
    }


    public static function escape($arDataLayer)
    {
        if(is_array($arDataLayer)) {
            return static::htmlJsonEncode($arDataLayer);
        } else if(is_numeric($arDataLayer)) {
            return $arDataLayer;
        } else if(is_string($arDataLayer)) {
            return static::htmlEncode($arDataLayer);
        } else {
            return '';
        }
    }
}