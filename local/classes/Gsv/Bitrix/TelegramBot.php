<?php

namespace Gsv\Bitrix;

class TelegramBot extends \Gsv\Util\TelegramBot
{
    private $app;

    public function __construct(?string $app = 'auth_bot')
    {
        $this->app = $app;
        $this->loadBot($app);
    }

    public function getApp(): ?string
    {
        return $this->app;
    }

    public function setApp(?string $app): void
    {
        $this->app = $app;
    }

    /**
     * @param string|null $app
     * @return void
     */
    public function loadBot(?string $app): void
    {
        if ($app) {
            $this->setName(\Bitrix\Main\Config\Option::get('gsv.telegram', "${app}_name", ''));
            $this->setToken(\Bitrix\Main\Config\Option::get('gsv.telegram', "${app}_token", ''));
        }
    }
}