<?php

namespace Gsv\Bitrix24\Bitrix;

use \Bitrix\Main\Web\HttpClient as Client;
use \Gsv\Bitrix24\Exception\Bitrix24NetworkException as Bitrix24Exception;

class RawBitrix24HttpClient implements \Gsv\Bitrix24\HttpClient
{
    /* @var array */
    protected $options;

    /** @var Client */
    protected $client;

    public function __construct(array $options = [])
    {
        $options += array(
            "redirect" => true, // true, если нужно выполнять редиректы
            "redirectMax" => 5, // Максимальное количество редиректов
            "waitResponse" => true, // true - ждать ответа, false - отключаться после запроса
            "socketTimeout" => 30, // Таймаут соединения, сек
            "streamTimeout" => 60, // Таймаут чтения ответа, сек, 0 - без таймаута
            "version" => Client::HTTP_1_1, // версия HTTP (HttpClient::HTTP_1_0 или HttpClient::HTTP_1_1)
            "proxyHost" => "", // адрес
            "proxyPort" => "", // порт
            "proxyUser" => "", // имя
            "proxyPassword" => "", // пароль
            "compress" => true, // true - принимать gzip (Accept-Encoding: gzip)
            "charset" => "", // Кодировка тела для POST и PUT
            "disableSslVerification" => false, // true - отключить проверку ssl (с 15.5.9)
        );

        $this->options = $options;

        if(!array_key_exists('useCurl', $this->options) && static::isPsrCompatible())
        {
            $this->options['useCurl'] = extension_loaded('curl');
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
        if(self::isPsrCompatible())
        {
            if($this->client === null)
                $this->client = new Client($this->options);
            return self::doCallImpl($params, $this->client, $uri);
        }
        $httpClient = new Client($this->options);
        return self::doCallImpl($params, $httpClient, $uri);
    }

    protected static function doCallImpl(?array $params, Client $httpClient, string $uri)
    {
        if (is_array($params))
            $response = $httpClient->post($uri, $params);
        else
            $response = $httpClient->get($uri);

        if ($response === false)
        {
            throw new Bitrix24Exception(implode(', ', $httpClient->getError()));
        }

        if ($httpClient->getStatus() < 300)
        {
            $result = json_decode($response, true);
            if (!is_array($result))
            {
                throw new Bitrix24Exception('Parse error. Received: ' . $response);
            }
            return $result;
        }

        throw new Bitrix24Exception('Error code: ' . $httpClient->getStatus() . " Received: " . $response, $httpClient->getStatus());
    }


    public static function isPsrCompatible()
    {
        static $isPsrCompatible;

        if($isPsrCompatible === null)
        {
            $isPsrCompatible = interface_exists(\Psr\Http\Client\ClientInterface::class, true)
                && in_array(
                    \Psr\Http\Client\ClientInterface::class,
                    class_implements(
                        \Bitrix\Main\Web\HttpClient::class,
                        true
                    )
                );
        }

        return $isPsrCompatible;
    }
}