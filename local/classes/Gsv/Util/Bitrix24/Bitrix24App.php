<?php


namespace Gsv\Util\Bitrix24;


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
        if($httpClient === null)
            $httpClient = new Bitrix24IHttpClient();
        return $httpClient;
    }

    public static function getInstance($app)
    {
        return static::getInstanceImpl($app, self::class);
    }

    protected static function getInstanceImpl($app, $className)
    {
        static $arrInstances = [];

        if(is_array($app))
        {
            ksort($app);
            $key = 'arr.' . md5(serialize($app));
        }
        else if(is_string($app))
        {
            $key = 'hook.' . $app;
        }
        else
        {
            throw new Bitrix24ArgumentException("Impossible name used !");
        }

        $className = '\\' . trim($className, '\\');
        $key .= '.' . strtolower($className);

        if(!array_key_exists($key, $arrInstances))
        {
            $arrInstances[$key] = new $className($app);
        }

        return $arrInstances[$key];
    }
}