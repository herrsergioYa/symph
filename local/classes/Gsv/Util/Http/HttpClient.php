<?php

namespace Gsv\Util\Http;

class HttpClient extends IHttpClient
{
    /* @var IHttpClient */
    protected $httpClient;

    public function __construct($bodyDefaultsToJson = null, $autoParseResponse = true)
    {
        if (static::canUsePsrBitrixClient())
        {
            $this->httpClient = new \Gsv\Bitrix\Http\RawBitrixHttpClient([], $bodyDefaultsToJson, $autoParseResponse);
        }
        elseif(static::canUseCurl())
        {
            $this->httpClient = new CurlHttpClient([], $bodyDefaultsToJson, $autoParseResponse);
        }
        elseif(static::canUseNativeHttpClient())
        {
            $this->httpClient = new NativeHttpClient([], $bodyDefaultsToJson, $autoParseResponse);
        }
        elseif (static::canUseBitrixClient())
        {
            $this->httpClient = new \Gsv\Bitrix\Http\BitrixHttpClient([], $bodyDefaultsToJson, $autoParseResponse);
        }
        elseif (static::canUseRawBitrixClient())
        {
            $this->httpClient = new \Gsv\Bitrix\Http\RawBitrixHttpClient([], $bodyDefaultsToJson, $autoParseResponse);
        }
        elseif(static::canUseGuzzleHttpClient())
        {
            $this->httpClient = static::createGuzzlePsr7Client(null, $bodyDefaultsToJson, $autoParseResponse);
        }
        elseif(static::canUseSymfonyPsr7Client())
        {
            $this->httpClient = static::createSymfonyPsr7Client(null, $bodyDefaultsToJson, $autoParseResponse);
        }
        else
        {
            //throw new \Exception("No HTTP Provider found!");
            $this->httpClient = new \Gsv\Util\Http\SocketHttpClient([], $bodyDefaultsToJson, $autoParseResponse);
        }
    }


    /**
     * @param \GuzzleHttp\Client|null $client
     * @param $bodyDefaultsToJson
     * @param $autoParseResponse
     * @return Psr7HttpClient
     */
    public static function createGuzzlePsr7Client(?\GuzzleHttp\Client $client = null, $bodyDefaultsToJson = null, $autoParseResponse = true): Psr7HttpClient
    {
        if(empty($client))
        {
            $client = new \GuzzleHttp\Client();
        }
        return new Psr7HttpClient(
            $client,
            static::createGuzzlePsr7RequestFactory(),
            static::createGuzzlePsr7StreamFactory(),
            $bodyDefaultsToJson, $autoParseResponse
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

    public static function createSymfonyPsr7Client(?\Symfony\Component\HttpClient\Psr18Client $client = null, $bodyDefaultsToJson = null, $autoParseResponse = true): Psr7HttpClient
    {
        if(empty($client))
        {
            $client = new \Symfony\Component\HttpClient\Psr18Client();
        }
        return new Psr7HttpClient(
            $client, $client, $client,
            $bodyDefaultsToJson, $autoParseResponse
        );
    }

    public static function canUseGuzzleHttpClient(): bool
    {
        return class_exists(\GuzzleHttp\Client::class, true);
    }

    protected static function canUseCurl(): bool
    {
        return extension_loaded('curl');
    }

    protected static function canUseNativeHttpClient(): string
    {
        return \ini_get('allow_url_fopen');
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
        return class_exists(\Bitrix\Main\Web\HttpClient::class, true)
            && class_exists(\Gsv\Bitrix\Http\RestClient::class, true)
            && class_exists(\Gsv\Bitrix\Http\BitrixHttpClient::class, true);
    }

    public static function canUseRawBitrixClient()
    {
        return class_exists(\Bitrix\Main\Web\HttpClient::class, true)
            && class_exists(\Gsv\Bitrix\Http\RawBitrixHttpClient::class, true);
    }

    public static function canUsePsrBitrixClient()
    {
        return class_exists(\Bitrix\Main\Web\HttpClient::class, true)
            && class_exists(\Gsv\Bitrix\Http\RawBitrixHttpClient::class, true)
            && \Gsv\Bitrix\Http\RawBitrixHttpClient::isPsrCompatible();
    }

    public function call(string $method, string $url, $query, array $headers, $body): array
    {
        return $this->httpClient->call($method, $url, $query, $headers, $body);
    }

    public function getBodyDefaultsToJson() : ?bool
    {
        return $this->httpClient->getBodyDefaultsToJson();
    }

    public function setBodyDefaultsToJson(?bool $bodyDefaultsToJson)
    {
        $this->httpClient->setBodyDefaultsToJson($bodyDefaultsToJson);
    }

    public function getAutoParseResponse() : bool
    {
        return $this->httpClient->getAutoParseResponse();
    }

    public function setAutoParseResponse(bool $autoParseResponse)
    {
        $this->httpClient->setAutoParseResponse($autoParseResponse);
    }
}