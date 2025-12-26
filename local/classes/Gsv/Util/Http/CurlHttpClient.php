<?php

namespace Gsv\Util\Http;


class CurlHttpClient extends AbstractHttpClient
{
    /**
     * @var array
     */
    protected $config = [];

    public function  __construct($options = [], $bodyDefaultsToJson = null, $autoParseResponse = true)
    {
        parent::__construct($bodyDefaultsToJson, $autoParseResponse);
        $this->config = $options;
    }

    protected function callInternal(string $method, string $url, array $headers, ?string $body): array
    {
        $header = static::createHeaderArray($headers);

        $curlOptions = array(
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_URL => $url,
            CURLOPT_HEADER => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $header,
        );

        if($body !== null)
        {
            $curlOptions += array(
                CURLOPT_POSTFIELDS => $body,
            );
        }
//        else
//        {
//            $curlOptions += array(
//                CURLOPT_POSTFIELDS => '',
//
//            );
//        }

        $curlOptions += $this->config;

        $curl = curl_init();
        try
        {
            curl_setopt_array($curl, $curlOptions);

            $result = curl_exec($curl);
            $requestInfo = curl_getinfo($curl);
            $curlErrorNumber = curl_errno($curl);

            if ($curlErrorNumber > 0)
            {
                return [
                    'error' => 'Curl error: ' . curl_error($curl),
                ];
            }
        }
        finally
        {
            curl_close($curl);
        }

        if($result !== false)
        {
            $arResult = static::parseHeaders($result);
            if(isset($requestInfo['http_code']))
                $arResult['code'] = intval($requestInfo['http_code']);
            return $arResult;
        }
        else
        {
            return [
                'error' => 'Curl error: ' . "unknown",
            ];
        }
    }
}