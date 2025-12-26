<?php

namespace Gsv\Bitrix24\Util;

use \Gsv\Bitrix24\Exception\Bitrix24NetworkException as Bitrix24Exception;

class CurlBitrix24HttpClient implements \Gsv\Bitrix24\HttpClient
{
    protected $config = [];

    public function __construct(array $options = [])
    {
        $options += array(
            CURLOPT_RETURNTRANSFER => true,
           // CURLINFO_HEADER_OUT => true,
          //  CURLOPT_VERBOSE => true,
            //CURLOPT_CONNECTTIMEOUT => 5,
            //CURLOPT_TIMEOUT        => 5,
            CURLOPT_USERAGENT => strtolower(__CLASS__.'-PHP-SDK/Gsv'),
        );

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
            $curlOptions = array(
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($params),
                CURLOPT_URL => $uri
            );
        }
        else
        {
            $curlOptions = array(
                CURLOPT_POST => false,
                CURLOPT_URL => $uri
            );
        }

        $curlOptions += $this->config;

        //sleep(1);

        $curl = curl_init();
        try
        {
            curl_setopt_array($curl, $curlOptions);

            $curlResult = curl_exec($curl);
            $requestInfo = curl_getinfo($curl);
            $curlErrorNumber = curl_errno($curl);

            if ($curlErrorNumber > 0)
            {
                $errorMsg = curl_error($curl) . PHP_EOL . 'cURL error code: ' . $curlErrorNumber . PHP_EOL;
                throw new Bitrix24Exception($errorMsg);
            }
        }
        finally
        {
            curl_close($curl);
        }

        $jsonResult = json_decode($curlResult, true);

        if (!is_array($jsonResult))
        {
            throw new Bitrix24Exception('Parse error. Received: ' . $curlResult);
        }

        return $jsonResult;
    }
}