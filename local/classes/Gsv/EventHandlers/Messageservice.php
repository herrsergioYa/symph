<?php

namespace Gsv\EventHandlers;

class Messageservice
{
    public static function onGetSmsSendersHandler()
    {
        return [
            new \Gsv\Sms\Mock(),
            new \Gsv\Sms\SmsRu(),
        ];
    }
}