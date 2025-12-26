<?php


namespace Gsv\Util\Http;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class Psr7HttpClient extends AbstractHttpClient
{
    /* @var ClientInterface */
    protected $client;

    protected $requestFactory;

    //private $responseFactory;
    protected $streamFactory;

    public function __construct(ClientInterface $client, /*RequestFactoryInterface | callable */ $requestFactory, /*StreamFactoryInterface | callable*/ $streamFactory = null, $bodyDefaultsToJson = null, $autoParseResponse = true)// = null, ResponseFactoryInterface $responseFactory = null, StreamFactoryInterface $streamFactory = null)
    {
        parent::__construct($bodyDefaultsToJson, $autoParseResponse);
        $this->client = $client;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
    }


    protected function callInternal(string $method, string $uri, array $headers, ?string $body): array
    {
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
            throw new \Exception('No RequestFactory found');
        }

        foreach ($headers as $header)
        {
            $request = $request->withHeader($header['name'], $header['value']);
        }

        if($body !== null)
        {
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
                throw new \Exception('No StreamFactory found');
            }

            $request = $request->withBody($body);
        }

        /* @var ResponseInterface */
        $response = $this->client->sendRequest($request);

        $return = [
            'code' => $response->getStatusCode(),
            'headers' => $response->getHeaders(),
            'data' => $response->getBody()->getContents(),
        ];
        return $return;
    }
}