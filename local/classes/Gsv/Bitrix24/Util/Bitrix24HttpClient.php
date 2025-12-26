<?php

namespace Gsv\Bitrix24\Util;

use \Gsv\Bitrix24\Exception\Bitrix24NetworkException as Bitrix24Exception;

class Bitrix24HttpClient implements \Gsv\Bitrix24\HttpClient
{
    /* @var \Gsv\Bitrix24\HttpClient $httpClient */
    protected $httpClient;

    public function __construct()
    {
        if(class_exists(\GuzzleHttp\Client::class, true))
        {
            $this->httpClient = static::createGuzzlePsr7Client();
        }
        elseif (self::canUseRawBitrixClient() && \Gsv\Bitrix24\Bitrix\RawBitrix24HttpClient::isPsrCompatible())
        {
            $this->httpClient = new \Gsv\Bitrix24\Bitrix\RawBitrix24HttpClient();
        }
        elseif(extension_loaded('curl'))
        {
            $this->httpClient = new CurlBitrix24HttpClient();
        }
        elseif(\ini_get('allow_url_fopen'))
        {
            $this->httpClient = new NativeBitrix24HttpClient();
        }
        elseif (static::canUseBitrixClient())
        {
            $this->httpClient = new \Gsv\Bitrix24\Bitrix\Bitrix24HttpClient();
        }
        elseif (static::canUseRawBitrixClient())
        {
            $this->httpClient = new \Gsv\Bitrix24\Bitrix\RawBitrix24HttpClient();
        }
        elseif(static::canUseSymfonyPsr7Client())
        {
            $this->httpClient = static::createSymfonyPsr7Client();
        }
        else
        {
            //throw new Bitrix24Exception("No HTTP Provider found!");
            $this->httpClient = new SocketBitrix24HttpClient();
        }
    }

    /**
     * Осуществляет запрос на низком уровне
     * @param string $uri Адрес вызываемого метода
     * @param array $params Параметры вызываемог омтеода
     * @return array Возвращаемое CRM значение
     * @throws Bitrix24Exception
     */
    public function doCall(string $uri, ?array $params = []) : array
    {
        return $this->httpClient->doCall($uri, $params);
    }

    /**
     * @param \GuzzleHttp\Client|null $client
     * @return Psr7Bitrix24HttpClient
     */
    public static function createGuzzlePsr7Client(?\GuzzleHttp\Client $client = null): Psr7Bitrix24HttpClient
    {
        if(empty($client))
        {
            $client = new \GuzzleHttp\Client();
        }
        return new Psr7Bitrix24HttpClient(
            $client,
            static::createGuzzlePsr7RequestFactory(),
            static::createGuzzlePsr7StreamFactory()
        );
    }

    public static function createGuzzlePsr7RequestFactory(): \Closure
    {
        return function ($method, $uri)
        {
            return new \GuzzleHttp\Psr7\Request($method, $uri);
        };
    }

    public static function createGuzzlePsr7StreamFactory(): \Closure
    {
        return function ($body)
        {
            return \GuzzleHttp\Psr7\Utils::streamFor($body);
        };
    }

    public static function createSymfonyPsr7Client(): Psr7Bitrix24HttpClient
    {
        $client = new \Symfony\Component\HttpClient\Psr18Client();
        return new Psr7Bitrix24HttpClient(
            $client, $client, $client
        );
    }

    public static function canUseSymfonyPsr7Client()
    {
        return class_exists('\\Symfony\\Component\\HttpClient\\HttpClient', true)
            && class_exists('\\Psr\\Http\\Message\\RequestFactoryInterface', true)
            && class_exists('\\Psr\\Http\\Message\\StreamFactoryInterface', true)
            && class_exists('\\Psr\\Http\\Message\\UriFactoryInterface', true)
            && class_exists('\\Psr\\Http\\Message\\StreamFactoryInterface', true)
            && (class_exists('\\Nyholm\\Psr7\\Factory\\Psr17Factory', true) || class_exists('\\Http\\Discovery\\Psr17FactoryDiscovery', true))
            && class_exists('\\Symfony\\Component\\HttpClient\\Psr18Client', true);
    }

    public static function canUseBitrixClient()
    {
        return class_exists('\\Bitrix\\Main\\Web\\HttpClient', true)
            && class_exists('\\Gsv\\Bitrix\\RestClient', true)
            && class_exists('\\Gsv\\Bitrix24\\Bitrix\\Bitrix24HttpClient', true);
    }

    public static function canUseRawBitrixClient()
    {
        return class_exists('\\Bitrix\\Main\\Web\\HttpClient', true)
            && class_exists('\\Gsv\\Bitrix24\\Bitrix\\RawBitrix24HttpClient', true);
    }
}