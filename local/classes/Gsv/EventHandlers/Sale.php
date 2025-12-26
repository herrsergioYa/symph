<?php

namespace Gsv\EventHandlers;

class Sale
{
    public static function OnGetCustomCashboxHandlersHandler()
    {
        return new \Bitrix\Main\EventResult(
            \Bitrix\Main\EventResult::SUCCESS,
            array(
                \Gsv\Sale\Cashbox\CashboxCloud::class => '/local/classes/Gsv/Sale/Cashbox/CashboxCloud.php',
            )
        );
    }

    public static function OnSaleDeliveryHandlersClassNamesBuildListHandler()
    {
        $arSaleDeliveries = [];

        $dir = __DIR__;
        if(strpos($dir, $_SERVER['DOCUMENT_ROOT'] . '/') === 0)
        {
            $dir = substr($dir, strlen($_SERVER['DOCUMENT_ROOT']));
        }
        else
        {
            $dir = '/local/classes/Gsv/EventsHandlers/Sale';
        }

        foreach (CUSTOM_SALE_DELIVERY_CLASSES as $name)
        {
            $clazz = '\\Gsv\Sale\Delivery\\' . str_replace('/', '\\', $name);
            $file = $dir .'/../../..' . str_replace('\\', '/', $clazz) . '.php';
            $arSaleDeliveries[$clazz] = $file;
        }

        return new \Bitrix\Main\EventResult(
            \Bitrix\Main\EventResult::SUCCESS,
            $arSaleDeliveries
        );
    }

    public static function onSaleDeliveryRestrictionsClassNamesBuildListHandler()
    {
        $arSaleDeliveryRestrictions = [];

        $dir = __DIR__;
        if(strpos($dir, $_SERVER['DOCUMENT_ROOT'] . '/') === 0)
        {
            $dir = substr($dir, strlen($_SERVER['DOCUMENT_ROOT']));
        }
        else
        {
            $dir = '/local/classes/Gsv/EventsHandlers/Sale';
        }

        foreach (CUSTOM_SALE_DELIVERY_RESTRICTIONS_CLASSES as $name)
        {
            $clazz = '\\Gsv\Sale\Delivery\Restrictions\\' . str_replace('/', '\\', $name);
            $file = $dir .'/../../..' . str_replace('\\', '/', $clazz) . '.php';
            $arSaleDeliveryRestrictions[$clazz] = $file;
        }

        return new \Bitrix\Main\EventResult(
            \Bitrix\Main\EventResult::SUCCESS,
            $arSaleDeliveryRestrictions
        );
    }

    public static function onSalePaySystemRestrictionsClassNamesBuildListHandler()
    {
        $arSalePaySystemRestrictions = [];

        $dir = __DIR__;
        if(strpos($dir, $_SERVER['DOCUMENT_ROOT'] . '/') === 0)
        {
            $dir = substr($dir, strlen($_SERVER['DOCUMENT_ROOT']));
        }
        else
        {
            $dir = '/local/classes/Gsv/EventsHandlers/Sale';
        }

        foreach (CUSTOM_SALE_PAYSYSTEM_RESTRICTIONS_CLASSES as $name)
        {
            $clazz = '\\Gsv\Sale\PaySystem\Restrictions\\' . str_replace('/', '\\', $name);
            $file = $dir .'/../../..' . str_replace('\\', '/', $clazz) . '.php';
            $arSalePaySystemRestrictions[$clazz] = $file;
        }

        return new \Bitrix\Main\EventResult(
            \Bitrix\Main\EventResult::SUCCESS,
            $arSalePaySystemRestrictions
        );
    }
}