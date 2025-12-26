<?php


    namespace Gsv\Bitrix24\Util;

    use \Gsv\Bitrix24\Exception\Bitrix24NetworkException as Bitrix24Exception;
    use \Gsv\Bitrix24\HttpClient;

    class SocketBitrix24HttpClient implements HttpClient
    {
        /**
         * @var array
         */
        protected $config = [];

        /**
         * @var false|resource
         */
        protected $sock;

        public function  __construct($options = [])
        {
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
            $url = \parse_url($uri);

            if($url['scheme'] === 'https')
            {
                $proto = 'ssl://';
                $port = 443;
            }
            elseif($url['scheme'] === 'http')
            {
                $proto = '';
                $port = 80;
            }
            else
            {
                throw new \Exception("Unknown scheme: '${url["scheme"]}'");
            }

            $host = $url['host'];
            if($url['port'])
            {
                $port = $url['port'];
            }

            $contextOptions = $this->config['context'] ?: [];

            if ($this->config['disableSslVerification'])
            {
                $contextOptions["ssl"]["verify_peer_name"] = false;
                $contextOptions["ssl"]["verify_peer"] = false;
                $contextOptions["ssl"]["allow_self_signed"] = true;
            }

            $context = stream_context_create($contextOptions);

            $connectTimeout = intval($this->config['connectTimeout']);
            $streamTimeout = intval($this->config['streamTimeout']);

            if($connectTimeout <= 0)
                $connectTimeout = ini_get("default_socket_timeout");
            if($streamTimeout <= 0)
                $streamTimeout = null;//ini_get("default_socket_timeout");

            $errno = 0;
            $error = '';

            if($context)
            {
                $this->sock = stream_socket_client($proto.$host.":".$port, $errno, $error, $connectTimeout, STREAM_CLIENT_CONNECT, $context);
            }
            else
            {
                $this->sock = stream_socket_client($proto.$host.":".$port, $errno, $error, $connectTimeout);
            }

            if(empty($this->sock))
            {
                if(intval($errno) > 0)
                {
                    $error = "[".$errno."] ".$error;
                }
                else
                {
                    $error = "Socket connection error.";
                }
                $this->sock = null;
                throw new \Exception($error);
            }

            try
            {
                if($streamTimeout !== null)
                {
                    stream_set_timeout($this->sock, $streamTimeout);
                }

                $method = is_array($params) ? 'POST' : 'GET';

                $path = $url['path'];
                if($path === null || $path === "")
                {
                    $path = '/';
                }
                if($url['query'])
                {
                    $path .= '?' . $url['query'];
                }

                $head = "$method $path HTTP/1.1\r\n";
                $head .= "Host: $host\r\n";

                if(is_array($params))
                {
                    $head .= "Content-Type: application/x-www-form-urlencoded\r\n";
                    $body = http_build_query($params);
                    $head .= "Content-Length: ". static::b_strlen($body) . "\r\n";
                }
                else
                {
                    $body = null;
                }

                $head .= "\r\n";

                $this->write($head);

                if($body !== null)
                {
                    $this->write($body);
                }

                $head = '';
                do
                {
                    $line = fgets($this->sock, 65536);
                    $head .= $line;
                }
                while($line != "\r\n");

                $arResult = static::parseHeaders($head);

                $contentLength = self::getContentLength($arResult);

                $result = "";
                if($contentLength === 'chunked')
                {
                    while(true)
                    {
                        $line = fgets($this->sock, 65536);
                        $line = explode(';', $line)[0];
                        $length = intval(trim($line), 16);
                        if($length > 0)
                        {
                            while ($length > 0)
                            {
                                $res = $this->read($length);
                                $length -= static::b_strlen($res);
                                $result .= $res;
                            }
                        }
                        else
                        {
                            break;
                        }
                    }
                }
                elseif($contentLength !== null)
                {
                    while ($contentLength > static::b_strlen($result))
                    {
                        $length = $contentLength - static::b_strlen($result);
                        $result .= $this->read($length);;
                    }
                }
                else
                {
                    while ((!$data = $this->readAny()) !== false)
                    {
                        $result .= $data;
                    }
                }

                $jsonResult = json_decode($result, true);

                if (!is_array($jsonResult))
                {
                    throw new Bitrix24Exception('Parse error. Received: ' . $result);
                }

                return $jsonResult;
            }
            finally
            {
                fclose($this->sock);
                $this->sock = null;
            }
        }

        protected function write(string $data)
        {
            $ret = fwrite($this->sock, $data);
            if($ret === false || $ret != static::b_strlen($data))
                throw \Exception('Socket write error');
        }

        protected function read($length = 1024) : string
        {
            $ret = fread($this->sock, $length);
            if($ret === false)
                throw \Exception('Socket write error');
            return $ret;
        }

        protected function readAny($length = 1024) : string
        {
            return fread($this->sock, $length);
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

        private static function b_strlen(string $body)
        {
            return function_exists('mb_strlen') ? mb_strlen($body, 'latin1') : strlen($body);
        }

        protected static function getContentLength(array $arResult)
        {
            foreach ($arResult['headers'] as $name => $values)
            {
                if (strtolower(trim($name)) == 'transfer-encoding')
                {
                    if (count($values) == 1)
                    {
                        if (strtolower($values[0]) == 'chunked')
                        {
                            return 'chunked';
                        }
                    }
                    throw new \Exception("Wrong response from the server");
                }
            }
            foreach ($arResult['headers'] as $name => $values)
            {
                if (strtolower(trim($name)) == 'content-length')
                {
                    if (count($values) == 1)
                    {
                        $contentLength = intval($values[0]);
                        if ($contentLength <= 0)
                        {
                            $contentLength = null;
                        }
                        return $contentLength;
                    }
                    throw new \Exception("Wrong response from the server");
                }
            }
            return null;
        }
    }