<?php

namespace Gsv\Sale\Delivery;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Delivery\CalculationResult;
use Bitrix\Sale\Delivery\Services\Base;

//TODO: It is RAW!!
abstract class AigulDeliveryChild extends AigulDeliveryBase
{

    public function __construct(array $initParams)
    {
        parent::__construct($initParams);
        $this->parent = \Bitrix\Sale\Delivery\Services\Manager::getObjectById($this->parentId);
    }

    public static function isProfile()
    {
        return true;
    }

    public function getParentService()
    {
        return $this->parent;
    }

    public function isCompatible(\Bitrix\Sale\Shipment $shipment)
    {
        $calcResult = static::calculateConcrete($shipment);
        return $calcResult->isSuccess();
       //return true;
    }

    public function isCalculatePriceImmediately()
    {
        return true;
    }

    public static function whetherAdminExtraServicesShow()
    {
        return true;
    }

    protected function getConfigStructure()
    {
        return array(
            "MAIN" => array(
                "TITLE" => 'Настройка обработчика доставки Aigul',
                "DESCRIPTION" => 'Настройка обработчика доставки Aigul',
                "ITEMS" => array(
                    "SOME_VALUE" => array(
                        "TYPE" => "STRING",
                        "NAME" => "Test setting",
                        "READONLY" => true,
                        "DEFAULT" => "1234",
                    ),
                    "ANOTHER_VALUE" => array(
                        "TYPE" => "NUMBER",
                        "NAME" => "Test setting",
                        "READONLY" => false,
                        "DEFAULT" => 1234,
                    ),
                )
            )
        );
    }

    protected function calculateConcrete(\Bitrix\Sale\Shipment $shipment)
    {
        $result = new CalculationResult();
        $result->addError(new \Bitrix\Main\Error('Доставка не возможна'));
        return $result;
    }
}