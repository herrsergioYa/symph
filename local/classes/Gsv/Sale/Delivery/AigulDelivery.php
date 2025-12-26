<?
namespace Gsv\Sale\Delivery;

use Bitrix\Main\Config\Option;
use Bitrix\Sale\Delivery\CalculationResult;
use Bitrix\Sale\Delivery\Services\Base;

//TODO: It is RAW!!
class AigulDelivery extends AigulDeliveryChild
{
    public function __construct(array $initParams)
    {
        parent::__construct($initParams);

    }

    public static function getClassTitle()
    {
        return parent::getClassTitle();
    }

    public static function getClassDescription()
    {
        return parent::getClassDescription();
    }

    protected function calculateConcrete(\Bitrix\Sale\Shipment $shipment)
    {
        $result = new CalculationResult();
        //$result->addError(new \Bitrix\Main\Error('Доставка не возможна'));

        $location = $this->getLocationCode($shipment);

        if(empty($location))
        {
            return parent::calculateConcrete($shipment);
        }

        $arFreeLocations = explode(',', $this->config['MAIN']["FORCED_FREE_LOCATIONS"]);
        $arPaidLocations = explode(',', $this->config['MAIN']["FORCED_PAID_LOCATIONS"]);

        $arTarget = \Bitrix\Sale\Location\LocationTable::getByCode($location)->fetch();
        $arCenter = \Bitrix\Sale\Location\LocationTable::getByCode($this->config['MAIN']["CENTER_LOCATION"])->fetch();

        if(empty($arTarget) || empty($arCenter))
        {
            return parent::calculateConcrete($shipment);
        }

        $distance = static::distanceFromGps($arCenter, $arTarget);

        if($distance > $this->config['MAIN']["MAX_DISTANCE"])
        {
            return parent::calculateConcrete($shipment);
        }

        $price = $this->getOrderPrice($shipment);

        if($price < $this->config['MAIN']["MIN_PRICE"])
        {
            $bFree = false;
        }
        elseif (in_array($location, $arFreeLocations))
        {
            $bFree = true;
        }
        elseif (in_array($location, $arPaidLocations))
        {
            $bFree = false;
        }
        else
        {
            $bFree = false;
        }

        if($bFree)
        {
            $result->setDeliveryPrice(0);
        }
        else
        {
            $weight = $shipment->getWeight() / 1000.0;
            $price = $weight * $this->config['MAIN']["PRICE_FOR_WEIGHT"];
            $result->setDeliveryPrice($price);
        }

        return $result;
    }

    protected function getConfigStructure()
    {
        return array(
            "MAIN" => array(
                "TITLE" => 'Настройка обработчика доставки Aigul',
                "DESCRIPTION" => 'Настройка обработчика доставки Aigul',
                "ITEMS" => array(

                    "CENTER_LOCATION" => array(
                        "TYPE" => "STRING",
                        "NAME" => 'CODE центральной локации',
                        'DEFAULT' => '0001050889',
                    ),
                    "FORCED_FREE_LOCATIONS" => array(
                        "TYPE" => "STRING",
                        "NAME" => 'CODE бесплатных локаций',
                        'DEFAULT' => '0001050889',
                    ),
                    "FORCED_PAID_LOCATIONS" => array(
                        "TYPE" => "STRING",
                        "NAME" => 'CODE платных локаций',
                        'DEFAULT' => '0001050544',
                    ),
                    "MIN_PRICE" => array(
                        "TYPE" => "STRING",
                        "NAME" => 'Минимальная стоимость заказа на бесплатную доставку, руб',
                        'DEFAULT' => '100000',
                    ),
                    "MAX_DISTANCE" => array(
                        "TYPE" => "STRING",
                        "NAME" => 'Максимальное расстояние доставки, км',
                        'DEFAULT' => '300',
                    ),
                    "PRICE_FOR_WEIGHT" => array(
                        "TYPE" => "NUMBER",
                        "MIN" => 0,
                        "NAME" => 'Стоимость доставки за кг',
                        'DEFAULT' => 6,
                    )
                )
            )
        );
    }
}
?>