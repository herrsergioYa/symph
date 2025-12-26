<?php

namespace Gsv\Util\Bitrix24;

use \Gsv\Bitrix24\Exception\Bitrix24NetworkException as Bitrix24Exception;
use \Gsv\Util\Http\IHttpClient as IHttpClient;
use \Gsv\Util\Http\HttpClient as HttpClient;

class Bitrix24IHttpClient implements \Gsv\Bitrix24\HttpClient
{
    /* @var IHttpClient */
    protected $httpClient;

    public function __construct(?IHttpClient $httpClient = null)
    {
        if($httpClient === null)
        {
            $httpClient = new HttpClient();
        }
        $this->httpClient = $httpClient;
    }

    /**
     * Осуществляет запрос на низком уровне
     * @param string $uri Адрес вызываемого метода
     * @param array $params Параметры вызываемого мтеода
     * @return array Возвращаемое CRM значение
     * @throws Bitrix24Exception
     */
    public function doCall(string $uri, ?array $params = []) : array
    {
        $bOldBodyDefaultsToJson = $this->httpClient->getBodyDefaultsToJson();
        if($bOldBodyDefaultsToJson !== false)
        {
            $this->httpClient->setBodyDefaultsToJson(false);
        }
        try
        {
            return $this->doCallImpl($uri, $params);
        }
        finally
        {
            if($bOldBodyDefaultsToJson !== false)
            {
                $this->httpClient->setBodyDefaultsToJson($bOldBodyDefaultsToJson);
            }
        }
    }

    protected function doCallImpl(string $uri, ?array $params)
    {
        if (is_array($params))
        {
            $arResult = $this->httpClient->post($uri, $params);
        }
        else
        {
            $arResult = $this->httpClient->get($uri);
        }

        if (empty($arResult))
        {
            throw new Bitrix24Exception('Error: ' . "unknown");
        }
        elseif (is_array($arResult['errors']))
        {
            throw new Bitrix24Exception('Error: ' . implode(', ', $arResult['errors']));
        }
        elseif (is_string($arResult['error']))
        {
            throw new Bitrix24Exception('Error: ' . $arResult['error']);
        }

        if ($this->httpClient->getAutoParseResponse())
            $jsonResult = $arResult['data'];
        elseif (is_string($arResult['data']))
            $jsonResult = json_decode($arResult['data'], true);
        else
            throw new Bitrix24Exception('Parse error. Unknown answer');

        if (!is_array($jsonResult))
        {
            throw new Bitrix24Exception('Parse error. Received: ' . $arResult['data']);
        }

        return $jsonResult;
    }
}