<?php

namespace Gsv\Bitrix\Http;

use Bitrix\Main\Web\HttpClient as Client;

class RawBitrixHttpClient extends \Gsv\Util\Http\AbstractHttpClient
{
    /* @var array */
    protected $options;

    protected static $isPsrCompatible;

    /**
     * @var Client
     */
    protected $httpClient;

    public function  __construct($options = [], $bodyDefaultsToJson = null, $autoParseResponse = true)
    {
        parent::__construct($bodyDefaultsToJson, $autoParseResponse);
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
        if(!array_key_exists('useCurl', $options) && static::isPsrCompatible())
        {
            $options['useCurl'] = extension_loaded('curl');
        }
        $this->options = $options;
    }

    protected function callInternal(string $method, string $url, array $headers, ?string $body): array
    {
        if(static::isPsrCompatible())
        {
            if($this->httpClient === null)
            {
                $this->httpClient = new Client($this->options);
            }
            else
            {
                $this->httpClient->clearHeaders();
            }
            return self::callImpl($headers, $this->httpClient, $body, $method, $url);
        }
        $restClient = new Client($this->options);
        return self::callImpl($headers, $restClient, $body, $method, $url);
    }

    public static function isPsrCompatible()
    {
        if(static::$isPsrCompatible === null)
        {
            static::$isPsrCompatible = interface_exists(\Psr\Http\Client\ClientInterface::class, true)
                && in_array(
                    \Psr\Http\Client\ClientInterface::class,
                        class_implements(
                    \Bitrix\Main\Web\HttpClient::class,
                    true
                        )
                );
        }
        return static::$isPsrCompatible;
    }

    protected static function callImpl(array $headers, Client $restClient, ?string $body, string $method, string $url): array
    {
        foreach ($headers as $arHeader)
        {
            if (!is_array($arHeader['value']))
            {
                $arHeader['value'] = array($arHeader['value']);
            }
            foreach ($arHeader['value'] as $value)
            {
                $restClient->setHeader($arHeader['name'], $value, false);
            }
        }
        if ($body !== null)
            $result = $restClient->query($method, $url, $body);
        else
            $result = $restClient->query($method, $url);
        if ($result)
        {
            $arResult = [
                'code' => $restClient->getStatus(),
                'data' => $restClient->getResult(),
                'headers' => [],
            ];
            foreach ($restClient->getHeaders()->toArray() as $header)
            {
                $arResult['headers'][$header['name']] = $header['values'];
            }
            return $arResult;
        }
        else
        {
            return [
                'errors' => $restClient->getError(),
            ];
        }
    }
}