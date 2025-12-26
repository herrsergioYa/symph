<?php

namespace Gsv\Sale\Delivery;

use Bitrix\Sale\Delivery\Services\Base;

//TODO: It is RAW!!
abstract class AigulDeliveryBase extends Base
{
    public function __construct(array $initParams)
    {
        parent::__construct($initParams);
    }


    public function getLocationCode(\Bitrix\Sale\Shipment $shipment)
    {
        if($obOrder = $this->getOrder($shipment))
        {
            $obPropertyCollection = $obOrder->getPropertyCollection();
            foreach ($obPropertyCollection as $obProperty)
            {
                if($obProperty->getProperty()['IS_LOCATION'] == 'Y')
                {
                    if($obProperty->getField('VALUE'))
                    {
                        return $obProperty->getField('VALUE');
                    }
                    break;
                }
            }
        }
        return false;
    }

    public function getOrderPrice(\Bitrix\Sale\Shipment $shipment)
    {
        if($obOrder = $this->getOrder($shipment))
        {
            return $obOrder->getPrice();
        }
        return false;
    }

    protected function getOrder(\Bitrix\Sale\Shipment $shipment)
    {
        return $shipment->getOrder();
    }

    public static function distanceFromGps($pointA, $pointB)
    {
        if(isset($pointA['LATITUDE']))
            $pointA['Y'] = $pointA['LATITUDE'];
        if(isset($pointA['LONGITUDE']))
            $pointA['X'] = $pointA['LONGITUDE'];
        if(isset($pointB['LATITUDE']))
            $pointB['Y'] = $pointA['LATITUDE'];
        if(isset($pointB['LONGITUDE']))
            $pointB['X'] = $pointB['LONGITUDE'];
        $rad = pi() / 180; // константа перевода градусов в радианы
        $km = 2 * asin(sqrt(pow(sin(abs($pointB['Y'] * $rad - $pointA['Y'] * $rad) / 2), 2) + cos($pointB['Y'] * $rad) * cos($pointA['Y'] * $rad) * pow(sin(abs($pointB['X'] * $rad - $pointA['X'] * $rad) / 2), 2))) * 6373;
        return $km;
    }
}