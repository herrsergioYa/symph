<?php

namespace Gsv\Ajax;

class EcommerceAjax extends \Gsv\Bitrix\JsonAjaxController
{
    protected static function exec($callable, $arParams)
    {
        return parent::exec($callable, $arParams);
    }

    public function getPendingEventsAction($arParams = [])
    {
        $arEvents = \Gsv\Helpers\Ecommerce::executeEcommerceEvent();
        return $arEvents;
    }
}