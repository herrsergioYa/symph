<?php

namespace Gsv\Bitrix24\Util;

use \Gsv\Bitrix24\Exception\Bitrix24NetworkException as Bitrix24Exception;

class NativeBitrix24HttpClient implements \Gsv\Bitrix24\HttpClient
{
    protected $config = [];

    public function __construct(array $options = [])
    {
//        $options += array(
//            CURLOPT_RETURNTRANSFER => true,
//            CURLINFO_HEADER_OUT => true,
//            CURLOPT_VERBOSE => true,
//            CURLOPT_CONNECTTIMEOUT => 5,
//            CURLOPT_TIMEOUT        => 5,
//            CURLOPT_USERAGENT => strtolower(__CLASS__.'-PHP-SDK/Gsv'),
//        );

        $this->config = $options;
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
        if(is_array($params))
        {
            $param = array(
                'http' => array(
                    'method' => 'POST',
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'content' => http_build_query($params)
                ) + $this->config,
            );
        }
        else
        {
            $param = array(
                'http' => array(
                    'method' => 'GET',
                ) + $this->config,
            );
        }

        $context = @stream_context_create($param);
        $fp = @fopen($uri, 'rb', false, $context);
        if (empty($fp))
        {
            $errorMsg = 'fopen error';
            throw new Bitrix24Exception($errorMsg);
        }
        try
        {
            $result = @stream_get_contents($fp);
        }
        finally
        {
            @fclose($fp);
        }

        $jsonResult = json_decode($result, true);

        if (!is_array($jsonResult))
        {
            throw new Bitrix24Exception('Parse error. Received: ' . $result);
        }

        return $jsonResult;
    }
}