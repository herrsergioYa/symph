<?php

namespace Gsv\Sale\Delivery;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Delivery\CalculationResult;
use Bitrix\Sale\Delivery\Services\Base;

//TODO: It is RAW!!
abstract class DeliveryChild extends DeliveryBase
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
                "TITLE" => 'Настройка обработчика доставки',
                "DESCRIPTION" => 'Настройка обработчика доставки',
                "ITEMS" => array(

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

    public static function getClassTitle()
    {
        return 'Профиль новой службы доставки';
    }

    public static function getClassDescription()
    {
        return 'Профиль новой службы доставки';
    }
}