<?php

namespace Gsv\Bitrix24;

use \Gsv\Bitrix24\Exception\Bitrix24NetworkException;
use \Gsv\Bitrix24\Exception\Bitrix24ApiException;
use \Gsv\Bitrix24\Exception\Bitrix24ArgumentException;
use \Gsv\Bitrix24\Exception\Bitrix24Exception;

class CurlHttpClient implements HttpClient
{
    /**
     * SDK version
     */
    const VERSION = '1.0.ie.fork';

    /**
     * raw request, contain all cURL options array and API query
     * @var array
     */
    public $rawRequest = null;

    /**
     * request info data structure from curl_getinfo function
     * @var array
     */
    public $requestInfo = null;

    /**
     * @var bool if true raw response from bitrix24 will be available from method getRawResponse, this is debug mode
     */
    public $isSaveRawResponse = false;

    /**
     * @var array raw response from bitrix24
     */
    public $rawResponse = null;

    public function __construct($isSaveRawResponse = false)
    {
        if (!extension_loaded('curl'))
        {
            throw new Bitrix24NetworkException('cURL extension must be installed to use this library');
        }
        if(!is_bool($isSaveRawResponse))
        {
            throw new Bitrix24ArgumentException('isSaveRawResponse flag must be boolean');
        }
        $this->isSaveRawResponse = $isSaveRawResponse;
    }

    /**
     * Execute a request API to Bitrix24 using cURL
     * @param string $url
     * @param array $additionalParameters
     * @throws Bitrix24Exception
     * @return array
     */
    protected function executeRequest($url, array $additionalParameters = array())
    {
        $curlOptions = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_VERBOSE => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_USERAGENT => strtolower(__CLASS__.'-PHP-SDK/v'.self::VERSION),
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($additionalParameters),
            CURLOPT_URL => $url
        );

        $this->rawRequest = $curlOptions;
        $curl = curl_init();
        curl_setopt_array($curl, $curlOptions);
        $curlResult = curl_exec($curl);
        $this->requestInfo = curl_getinfo($curl);
        $curlErrorNumber = curl_errno($curl);
        // handling network I/O errors
        if($curlErrorNumber > 0)
        {
            $errorMsg = curl_error($curl).PHP_EOL.'cURL error code: '.$curlErrorNumber.PHP_EOL;
            curl_close($curl);
            throw new Bitrix24NetworkException($errorMsg);
        }
        else
        {
            curl_close($curl);
        }
        if(true === $this->isSaveRawResponse)
        {
            $this->rawResponse = $curlResult;
        }
        // handling json_decode errors
        $jsonResult = json_decode($curlResult, true);
        $jsonErrorCode = json_last_error();
        if (!is_null($jsonResult) && (JSON_ERROR_NONE == $jsonErrorCode)) unset($curlResult);

        if(is_null($jsonResult) && (JSON_ERROR_NONE != $jsonErrorCode))
        {
            switch ($jsonErrorCode) {
                case JSON_ERROR_NONE:
                    $jsonErrorMessage = 'No errors';
                    break;
                case JSON_ERROR_DEPTH:
                    $jsonErrorMessage = 'Maximum stack depth exceeded';
                    break;
                case JSON_ERROR_STATE_MISMATCH:
                    $jsonErrorMessage = 'Underflow or the modes mismatch';
                    break;
                case JSON_ERROR_CTRL_CHAR:
                    $jsonErrorMessage = 'Unexpected control character found';
                    break;
                case JSON_ERROR_SYNTAX:
                    $jsonErrorMessage = 'Syntax error, malformed JSON';
                    break;
                case JSON_ERROR_UTF8:
                    $jsonErrorMessage = 'Malformed UTF-8 characters, possibly incorrectly encoded';
                    break;
                default:
                    $jsonErrorMessage = 'Unknown error';
                    break;
            }

            $errorMsg = 'URL: '.$url.PHP_EOL.' Fatal error in function json_decode.'.PHP_EOL.'Error code: '.$jsonErrorCode.' ('.$jsonErrorMessage.') '.PHP_EOL.$curlResult.PHP_EOL;//." on data ".$curlResult.PHP_EOL;
            throw new Bitrix24ApiException($errorMsg);
        }
        return $jsonResult;
    }

    /**
     * Осуществляет запрос на низком уровне
     * @param string $uri Адрес вызываемого метода
     * @param array|null $params Параметры вызываемого метода
     * @return array Возвращаемое CRM значение
     * @throws Bitrix24Exception
     */
    public function doCall(string $uri, ?array $params = []) : array
    {
        return $this->executeRequest($uri, $params);
    }
}