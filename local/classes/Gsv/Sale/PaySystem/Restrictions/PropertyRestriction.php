<?php

namespace Gsv\Sale\PaySystem\Restrictions;

use Gsv\Bitrix\Utility;
use Bitrix\Sale\Services\Base;//<----Restriction
use Bitrix\Sale\Internals\CollectableEntity;
use Bitrix\Sale\Internals\Entity;
use Bitrix\Sale\Order;
use Bitrix\Sale\Shipment;

//TODO: It is RAW!!
class PropertyRestriction extends Base\Restriction
{
    public static function getClassTitle()
    {
        return 'по свойствам товара';
    }

    public static function getClassDescription()
    {
        return 'Доставка будет выводится только, если она разрешена для всех товаров заказа';
    }

    public static function check($params, array $restrictionParams, $serviceId = 0)
    {
        $PROP = $restrictionParams['PROPERTY_NAME'];
        $Y = $restrictionParams['ALLOW_VALUE'];
        $N = $restrictionParams['DISALLOW_VALUE'];

        $sku = [];

        /*if($result)
        {
            $arProducts = Utility::cachedGetList(
                [
                    'METHOD' => 'GetNextElement'
                ],
                [],
                [
                    '=ID' => $result,
                    // 'IBLOCK_ID' => 42,
                ],
                false,
                false,
                [
                    'ID', 'IBLOCK_ID', 'PROPERTY_*'
                ]
            );

            foreach ($arProducts as $arProduct)
            {
                $arProduct['PROPERTIES'][$PROP]['VALUE'] = (array)$arProduct['PROPERTIES'][$PROP]['VALUE'];

             //   if ($arProduct['PROPERTIES'][$PROP]['VALUE'])
              //  {
                    if (strlen($N) && in_array($N, $arProduct['PROPERTIES'][$PROP]['VALUE']))
                    {
                        return false;
                    }
                    elseif (strlen($Y) && in_array($Y, $arProduct['PROPERTIES'][$PROP]['VALUE']))
                    {
                        while (($i = array_search($arProduct['ID'], $result)) !== false)
                        {
                            unset($result[$i]);
                        }
                    }
                    elseif ($arProduct['PROPERTIES']['CML2_LINK']['VALUE'])
                    {
                        while (($i = array_search($arProduct['ID'], $result)) !== false)
                        {
                            unset($result[$i]);
                        }
                        $sku[] = $arProduct['PROPERTIES']['CML2_LINK']['VALUE'];
                    }
//                }
//                elseif ($arProduct['PROPERTIES']['CML2_LINK']['VALUE'])
//                {
//                    while (($i = array_search($arProduct['ID'], $result)) !== false)
//                    {
//                        unset($result[$i]);
//                    }
//                    $sku[] = $arProduct['PROPERTIES']['CML2_LINK']['VALUE'];
//                }
            }
        }


        if($sku)
        {
            $arProducts = Utility::cachedGetList(
                [
                    'METHOD' => 'GetNextElement'
                ],
                [],
                [
                    '=ID' => $sku,
                    // 'IBLOCK_ID' => 42,
                ],
                false,
                false,
                [
                    'ID', 'IBLOCK_ID', 'PROPERTY_*'
                ]
            );

            foreach ($arProducts as $arProduct)
            {
                $arProduct['PROPERTIES'][$PROP]['VALUE'] = (array)$arProduct['PROPERTIES'][$PROP]['VALUE'];

                //if ($arProduct['PROPERTIES'][$PROP]['VALUE'])
                //{
                    if (strlen($N) && in_array($N, $arProduct['PROPERTIES'][$PROP]['VALUE']))
                    {
                        return false;
                    }
                    elseif (strlen($Y) && in_array($Y, $arProduct['PROPERTIES'][$PROP]['VALUE']))
                    {
                        while (($i = array_search($arProduct['ID'], $sku)) !== false)
                        {
                            unset($sku[$i]);
                        }
                    }
               // }
            }
        }

        if(count($result) + count($sku) > 0)
        {
            return $restrictionParams['DEFAULT_BEHAVIOR'] == 'Y';
        }*/

        return true;
    }

    protected static function extractParams(Entity $entity)
    {
        $result = array();

        if ($entity instanceof Shipment)
        {
            foreach($entity->getShipmentItemCollection() as $shipmentItem)
            {
                $basketItem = $shipmentItem->getBasketItem();

                if(!$basketItem)
                    continue;

                $PRODUCT_ID = intval($basketItem->getField("PRODUCT_ID"));

                if($PRODUCT_ID > 0)
                {
                    $result[] = $PRODUCT_ID;
                }
            }
        }
        elseif ($entity instanceof CollectableEntity)
        {
//            /** @var \Bitrix\Sale\ShipmentCollection $collection */
//            $collection = $entity->getCollection();
//
//            /** @var \Bitrix\Sale\Order $order */
//            $order = $collection->getOrder();
        }
        elseif ($entity instanceof Order)
        {
//            /** @var \Bitrix\Sale\Order $order */
//            $order = $entity;
        }

//        if (!$order)
//            return false;
//
//        $personTypeId = $order->getPersonTypeId();
        return $result;
    }

    public static function getParamsStructure($entityId = 0)
    {
        return array(
            "PROPERTY_NAME" => array(
                'TYPE' => 'STRING',
                'DEFAULT' => "",
                'LABEL' => 'Название свойства',
            ),
            "ALLOW_VALUE" => array(
                'TYPE' => 'STRING',
                'DEFAULT' => "Y",
                'LABEL' => 'Разрешающее значение',
            ),
            "DISALLOW_VALUE" => array(
                'TYPE' => 'STRING',
                'DEFAULT' => "Y",
                'LABEL' => 'Запрещающее значение',
            ),
            "DEFAULT_BEHAVIOR" => array(
                'TYPE' => 'Y/N',
                'DEFAULT' => "Y",
                'LABEL' => 'Поведение по умолчанию',
            )
        );
    }

    protected static function getEntityTypeId()
    {
        return parent::getEntityTypeId();
    }
}