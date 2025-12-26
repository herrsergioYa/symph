<?php


namespace Gsv\Bitrix24\Util;

use \Gsv\Bitrix24\Exception\Bitrix24NetworkException as Bitrix24Exception;
use \Gsv\Bitrix24\HttpClient;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class Psr7Bitrix24HttpClient implements HttpClient
{
    /* @var ClientInterface */
    protected $client;

    protected $requestFactory;

    //private $responseFactory;
    protected $streamFactory;

    public function __construct(ClientInterface $client, /*RequestFactoryInterface callable */ $requestFactory, /*StreamFactoryInterface*/ $streamFactory = null)// = null, ResponseFactoryInterface $responseFactory = null, StreamFactoryInterface $streamFactory = null)
    {
        $this->client = $client;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
    }


    /**
     * Осуществляет запрос на низком уровне
     * @param string $uri Адрес вызываемого метода
     * @param array|null $params Параметры вызываемог омтеода
     * @return array Возвращаемое CRM значение
     * @throws Bitrix24Exception
     */
    function doCall(string $uri, ?array $params = []): array
    {
        $method = is_array($params) ? 'POST' : 'GET';

        if (interface_exists(RequestFactoryInterface::class) && ($this->requestFactory instanceof RequestFactoryInterface))
        {
            /* @var RequestInterface */
            $request = $this->requestFactory->createRequest($method, $uri);
        }
        elseif(is_callable($this->requestFactory))
        {
            /* @var RequestInterface */
            $request = ($this->requestFactory)($method, $uri);
        }
        else
        {
            throw new Bitrix24Exception('No RequestFactory found');
        }

        $request = $request->withHeader("Content-Type" ,"application/x-www-form-urlencoded");

        if($method == 'POST')
        {
            $body = http_build_query($params);

            if (interface_exists(StreamFactoryInterface::class) && ($this->streamFactory instanceof StreamFactoryInterface))
            {
                $body = $this->streamFactory->createStream($body);
            }
            elseif (is_callable($this->requestFactory))
            {
                $body = ($this->streamFactory)($body);
            }
            else
            {
                throw new Bitrix24Exception('No StreamFactory found');
            }

            $request = $request->withBody($body);
        }

        /* @var ResponseInterface */
        $response = $this->client->sendRequest($request);

        if ($response->getStatusCode() >= 300)
        {
            throw new Bitrix24Exception('Error code: ' . $response->getStatusCode() . " Received: " . $response->getBody()->getContents(), $response->getStatusCode());
        }

        $result = $response->getBody()->getContents();

        $jsonResult = json_decode($result, true);

        if (!is_array($jsonResult))
        {
            throw new Bitrix24Exception('Parse error. Received: ' . $result);
        }

        return $jsonResult;
    }
}