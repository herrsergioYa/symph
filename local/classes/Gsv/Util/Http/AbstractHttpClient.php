<?php


    namespace Gsv\Util\Http;


    abstract class AbstractHttpClient extends IHttpClient
    {
        /**
         * @var ?bool
         */
        private $bodyDefaultsToJson = null;
        /**
         * @var bool
         */
        private $autoParseResponse = true;

        public function call(string $method, string $url, /*string|array*/ $query, array $headers, $body) : array
        {
            if (is_array($query) && count($query))
            {
                $query = http_build_query($query);
            }
            if (is_string($query) && static::b_strlen($query))
            {
                if (strpos($url, '?') !== false)
                    $url .= '&' . $query;
                else
                    $url .= '?' . $query;
            }
            $arHeaders = [];
            foreach($headers as $name => $value)
            {
                $arHeaders[strtolower(trim($name))] = [
                    'name' => $name,
                    'value' => $value,
                ];
            }
            if(is_array($body))
            {
                $isJson =isset($arHeaders['content-type']) && static::isJsonContentType($arHeaders['content-type']['value']);
                if($this->getBodyDefaultsToJson() === true || $this->getBodyDefaultsToJson() !== false && $isJson)
                {
                    if(!$isJson)
                    {
                        $arHeaders['content-type'] = [
                            'name' => 'Content-Type',
                            'value' => 'application/json',
                        ];
                    }
                    $body = json_encode($body);
                }
                else
                {
                    $isUrlEncoded = isset($arHeaders['content-type']) && static::isUrlEncodedContentType($arHeaders['content-type']['value']);
                    if(!$isUrlEncoded)
                    {
                        $arHeaders['content-type'] = [
                            'name' => 'Content-Type',
                            'value' => 'application/x-www-form-urlencoded',
                        ];
                    }
                    $body = http_build_query($body);
                }
            }
            elseif(is_string($body) && static::b_strlen($body))
            {
                if(!isset($arHeaders['content-type']))
                {
                    $arHeaders['content-type'] = [
                        'name' => 'Content-Type',
                        'value' => 'application/octet-stream',
                    ];
                }
            }
            else
            {
                $body = null;
                unset($arHeaders['content-type']);
            }

            if($body !== null)
            {
                $arHeaders['content-length'] = [
                    'name' => 'Content-Length',
                    'value' => static::b_strlen($body),
                ];
            }
            else
            {
                unset($arHeaders['content-length']);
            }

            unset($arHeaders['host']);

            try
            {
                $return = $this->callInternal($method, $url, $arHeaders, $body);
            }
            catch (\Exception $e)
            {
                return [
                    'error' => $e->getMessage(),
                ];
            }
            if($this->getAutoParseResponse() && $return && empty($return['error']) && empty($return['errors']))
            {
                foreach($return['headers'] as $name => $values)
                {
                    if(strtolower(trim($name)) === 'content-type')
                    {
                        foreach((array)$values as $value)
                        {
                            $return['content-type'] = $value;
                            if(static::isJsonContentType($value))
                            {
                                $return['data'] = json_decode($return['data'], true);
                            }
                            elseif(static::isUrlEncodedContentType($value))
                            {
                                //Is that actually possible?
                                $buffer = $return['data'];
                                $return['data'] = [];
                                parse_str($buffer, $return['data']);
                            }
                            break 2;
                        }
                    }
                }
            }
            return $return;
        }

        protected abstract function callInternal(string $method, string $url, array $headers, ?string $body) : array;
//        {
//            return [
//                'code' => 200,
//                'headers' => [],
//                'data' => '',
//            ];
//        }

        public function getBodyDefaultsToJson() : ?bool
        {
            return $this->bodyDefaultsToJson;
        }

        public function setBodyDefaultsToJson(?bool $bodyDefaultsToJson)
        {
            $this->bodyDefaultsToJson = $bodyDefaultsToJson;
        }

        public function getAutoParseResponse() : bool
        {
            return $this->autoParseResponse;
        }

        public function setAutoParseResponse(bool $autoParseResponse)
        {
            $this->autoParseResponse = $autoParseResponse;
        }

        protected static function isJsonContentType($value): bool
        {
            $value = trim($value);
            return stripos($value, 'application/json') !== false || stripos($value, 'json') !== false;
        }

        protected static function isUrlEncodedContentType($value): bool
        {
            $value = trim($value);
            return stripos($value, 'application/x-www-form-urlencoded') !== false || stripos($value, 'x-www-form-urlencoded') !== false;
        }

        protected static function extractHttpCode(string $status_line): int
        {
            return intval(preg_split('/\s+/', trim($status_line))[1]);
        }

        protected static function parseHeader($header): array
        {
            $temp = preg_split('/:/', $header, 2);
            $name = trim($temp[0]);
            $value = trim($temp[1]);
            return array($name, $value);
        }

        protected function parseHeaders($http_response_header, ?string $result = null): array
        {
            if($result === null && is_string($http_response_header))
            {
                $delim = strpos($http_response_header, "\r\n\r\n");
                if($delim !== false)
                {
                    $result = substr($http_response_header, $delim + 4);
                    $http_response_header = substr($http_response_header, 0, $delim);
                }
            }
            $arResult = [
                'code' => 0,//static::extractHttpCode($status_line),
                'data' => $result,
                'headers' => [],
            ];
            if(is_string($http_response_header))
            {
                $http_response_header = explode("\r\n", trim($http_response_header));
            }
            foreach ($http_response_header as $i => $header)
            {
                if ($i > 0)
                {
                    list($name, $value) = self::parseHeader($header);
                    $arResult['headers'][$name][] = $value;
                }
                elseif($i === 0)
                {
                    $arResult['code'] = static::extractHttpCode($header);
                }

            }
            return $arResult;
        }

        protected static function createHeaderArray(array $headers): array
        {
            $header = [];
            foreach ($headers as $arHeader)
            {
                if (!is_array($arHeader['value']))
                {
                    $arHeader['value'] = array($arHeader['value']);
                }
                foreach ($arHeader['value'] as $value)
                {
                    $header[] = $arHeader['name'] . ':' . $value;
                }
            }
            return $header;
        }

        protected static function createHeaderString(array $headers): string
        {
            return implode("\r\n", static::createHeaderArray($headers));
        }

        public static function b_strlen($str)
        {
            static $exists = null;
            if($exists === null)
                $exists = function_exists('mb_strlen');
            if($exists)
                return mb_strlen($str, 'latin1');
            else
                return strlen($str);
        }
    }