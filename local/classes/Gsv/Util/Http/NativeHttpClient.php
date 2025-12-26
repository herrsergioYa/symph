<?php


    namespace Gsv\Util\Http;


    class NativeHttpClient extends AbstractHttpClient
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
            $header = static::createHeaderString($headers);

            if($body !== null)
            {
                $param = array(
                    'http' => array(
                            'method' => $method,
                            'header' => $header,
                            'content' => $body
                        ) + $this->config,
                );
            }
            else
            {
                $param = array(
                    'http' => array(
                        'method' => $method,
                        'header' => $header,
                    ) + $this->config,
                );
            }
            $context = @stream_context_create($param);
            $fp = @fopen($url, 'rb', false, $context);
            if (empty($fp))
            {
                return [
                    'error' => 'fopen() error',
                ];
            }
            try
            {
                $result = @stream_get_contents($fp);
            }
            finally
            {
                @fclose($fp);
            }
            /**
             * @var array $http_response_header materializes out of thin air
             */
            if($result !== false)
            {
                return static::parseHeaders($http_response_header, $result);
            }
            else
            {
                return [
                    'error' => 'stream_get_contents() error',
                ];
            }
        }
    }