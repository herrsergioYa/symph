<?php

namespace Gsv\Bitrix24\Bitrix;

use \Gsv\Bitrix\Http\RestClient as Client;
use \Gsv\Bitrix24\Exception\Bitrix24NetworkException as Bitrix24Exception;

class Bitrix24HttpClient extends RawBitrix24HttpClient
{
    /* @var Client */
    protected $httpClient;

    public function __construct(array $options = [])
    {
        parent::__construct($options);
        if(!self::isPsrCompatible())
        {
            $this->httpClient = new Client($this->options);
        }
    }

    /**
     * Осуществляет запрос на низком уровне
     * @param string $uri Адрес вызываемого метода
     * @param array|null $params Параметры вызываемог омтеода
     * @return array Возвращаемое CRM значение
     * @throws Bitrix24Exception
     */
    public function doCall(string $uri, ?array $params = []) : array
    {
        if($this->httpClient)
        {
            return static::doCallImpl($params, $this->httpClient, $uri);
        }
        return parent::doCall($uri, $params);
    }
}