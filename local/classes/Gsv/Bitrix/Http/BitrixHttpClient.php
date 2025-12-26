<?php

    namespace Gsv\Bitrix\Http;

    class BitrixHttpClient extends RawBitrixHttpClient
    {
        /**
         * @var RestClient
         */
        private $restClient;

        public function  __construct($options = [], $bodyDefaultsToJson = null, $autoParseResponse = true)
        {
            parent::__construct($options, $bodyDefaultsToJson, $autoParseResponse);
            //if(!static::isPsrCompatible())
            {
                $this->restClient = new RestClient($this->options);
            }
        }

        protected function callInternal(string $method, string $url, array $headers, ?string $body): array
        {
            if(!empty($this->restClient))
            {
                //Здесь баг, скорее всего, устранен дважды...
                $this->restClient->clearHeaders();
                return self::callImpl($headers, $this->restClient, $body, $method, $url);
            }
            return parent::callInternal($method, $url, $headers, $body);
        }
    }