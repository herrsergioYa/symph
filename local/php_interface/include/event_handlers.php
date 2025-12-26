<?php

$eventManager = \Bitrix\Main\EventManager::getInstance();

$eventManager->addEventHandler(
    'messageservice', 'onGetSmsSenders',
    [\Gsv\EventHandlers\Messageservice::class, 'onGetSmsSendersHandler']
);

if(!empty(CUSTOM_SALE_DELIVERY_CLASSES))
{
    $eventManager->addEventHandler(
        'sale', 'onSaleDeliveryHandlersClassNamesBuildList', [
            \Gsv\EventHandlers\Sale::class,
            'OnSaleDeliveryHandlersClassNamesBuildListHandler'
        ]
    );
}

if(!empty(CUSTOM_SALE_DELIVERY_RESTRICTIONS_CLASSES))
{
    $eventManager->addEventHandler(
        'sale', 'onSaleDeliveryRestrictionsClassNamesBuildList', [
            \Gsv\EventHandlers\Sale::class,
            'onSaleDeliveryRestrictionsClassNamesBuildListHandler'
        ]
    );
}

if(!empty(CUSTOM_SALE_PAYSYSTEM_RESTRICTIONS_CLASSES))
{
    $eventManager->addEventHandler(
        'sale', 'onSalePaySystemRestrictionsClassNamesBuildList', [
            \Gsv\EventHandlers\Sale::class,
            'onSalePaySystemRestrictionsClassNamesBuildListHandler'
        ]
    );
}

\Gsv\Iblock\PropertyBuildList::addAllEventHandlers($eventManager);

//$eventManager->addEventHandler(
//    "sale",
//    "OnCondSaleControlBuildList",
//    [\Gsv\Sale\CondCtrl\DiscountIfSumAfterBonuses::class, "GetControlDescr"]
//);

//$eventManager->addEventHandler(
//    "sale",
//    "OnCondSaleControlBuildList",
//    [\Gsv\Sale\CondCtrl\DiscountIfUsedPackage::class, "GetControlDescr"]
//);

//$eventManager->addEventHandlerCompatible(
//    "sale",
//    "OnCondSaleActionsControlBuildList",
//    [\Gsv\Sale\ActionCtrl\GifteryCoupon::class, "GetControlDescr"]
//);

//$eventManager->addEventHandlerCompatible(
//    "sale",
//    "OnCondSaleActionsControlBuildList",
//    [\Gsv\Sale\ActionCtrl\HiddenSaleDiscount::class, "GetControlDescr"]
//);

//$eventManager->addEventHandler(
//    'sale',
//    'OnSaleOrderSaved',
//    [\Gsv\EventHandlers\Sale\ActionCtrl\GifteryCoupon::class, "onSaleOrderSavedHandler"]
//);

//$eventManager->addEventHandler(
//    "sale", "OnGetCustomCashboxHandlers",
//    [\Gsv\EventHandlers\Sale::class, 'OnGetCustomCashboxHandlersHandler']
//);

//$eventManager->addEventHandler(
//    'catalog', 'OnGetOptimalPrice',
//    [\Gsv\EventHandlers\Catalog::class, 'OnGetOptimalPriceHandler']
//);

//$eventManager->addEventHandler(
//    'main', 'OnProlog',
//    [\Gsv\Helpers\CsvRedirector::class, 'onPrologHandler']
//);

$eventManager->addEventHandler(
    'iblock',
    'OnIBlockPropertyBuildList',
    [\Gsv\Iblock\Property\LinkedEnumProperty::class, 'getUserTypeDescription']
);