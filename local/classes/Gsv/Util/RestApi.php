<?php

namespace Gsv\Util;

use \Gsv\Util\Http\IHttpClient;
use \Gsv\Util\Http\HttpClient;

class RestApi
{
    /* @var IHttpClient */
    protected $httpClient;

    /* @var string */
    protected $url;

    public $arHeaders = [];

    public function __construct($url, ?IHttpClient $httpClient = null)
    {
        if($httpClient === null)
        {
            $httpClient = new HttpClient(true, true);
        }
        else
        {
            $httpClient->setAutoParseResponse(true);
        }

        $this->httpClient = $httpClient;
        $this->url = $url;
    }

    public function __call($name, $arguments)
    {
        //$method = 'POST';
        $query = [];
        $body = null;

        $nameLower = strtolower($name);
        if(substr($nameLower, -4) == 'post')
        {
            $method = 'POST';
            $name = substr($name, 0, -4);
            if(isset($arguments[0]))
                $body = $arguments[0];
            else
                $body = [];
            if(isset($arguments[1]))
                $query = $arguments[1];
        }
        else if(substr($nameLower, -3) == 'get')
        {
            $method = 'GET';
            $name = substr($name, 0, -3);
            if(isset($arguments[0]))
                $query = $arguments[0];
        }
        else if(isset($arguments[0]))
        {
            $method = 'POST';
            $body = $arguments[0];
            if(isset($arguments[1]))
                $query = $arguments[1];
        }
        else
        {
            $method = 'GET';
            //if(isset($arguments[0]))
            //    $query = $arguments[0];
        }

        $url = str_replace('#ACTION#', $name, $this->url);
        $arHeaders = $this->arHeaders;

        $result = $this->httpClient->call($method, $url, $query, $arHeaders, $body);
        if($result['code'] != 200)
        {
            throw new \Exception('Rest API error with the response: ' . json_encode($result));
        }
        return $result['data'];
    }

    public function __get($name)
    {
        $nameLower = strtolower($name);
        if($name == 'http')
        {
            return $this->httpClient;
        }
        else
        {
            return null;
        }
    }
}