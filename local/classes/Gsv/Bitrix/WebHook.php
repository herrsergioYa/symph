<?php

namespace Gsv\Bitrix;

abstract class WebHook extends \Gsv\Util\WebHook
{
    protected function send($code, $body, $contentType = null)
    {
        global $APPLICATION;
        $APPLICATION->RestartBuffer();
        parent::send($code, $body, $contentType);
    }
}