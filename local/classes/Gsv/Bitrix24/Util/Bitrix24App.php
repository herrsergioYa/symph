<?php


namespace Gsv\Bitrix24\Util;


use \Gsv\Bitrix24\Exception\Bitrix24AccessException;
use \Gsv\Bitrix24\Exception\Bitrix24ArgumentException;
use \Gsv\Bitrix24\HttpClient;
use \Bitrix\Main\Config\Option;

class Bitrix24App extends \Gsv\Bitrix24\Bitrix24App
{
    public function __construct($app = null, $requireAccess = true)
    {
        parent::__construct($app, $requireAccess);
    }

    protected function createHttpClient(): HttpClient
    {
        static $httpClient = null;
        if($httpClient === null) {
            $httpClient = new Bitrix24HttpClient();
        }
        return $httpClient;
    }

    public static function getInstance($app)
    {
        static $webHooks = [];

        if(is_string($app))
        {
            if(!in_array($app, $webHooks))
            {
                $webHooks[$app] = new static($app);
            }
            return $webHooks[$app];
        }
        else
        {
            return new self($app);
        }
    }
}