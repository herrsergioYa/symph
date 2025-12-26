<?php


    namespace Gsv\Util\Http;


    class SocketHttpClient extends AbstractHttpClient
    {
        /**
         * @var array
         */
        protected $config = [];

        /**
         * @var false|resource
         */
        protected $sock;

        public function  __construct($options = [], $bodyDefaultsToJson = null, $autoParseResponse = true)
        {
            parent::__construct($bodyDefaultsToJson, $autoParseResponse);
            $this->config = $options;
        }

        protected function callInternal(string $method, string $url, array $headers, ?string $body): array
        {
            $url = \parse_url($url);

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

                $method = strtoupper($method);

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

                $headers = static::createHeaderString($headers);
                if(static::b_strlen($headers))
                {
                    $head .= "$headers\r\n";
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
                        $result .= $this->read($length);
                    }
                }
                else
                {
                    while ((!$data = $this->readAny()) !== false)
                    {
                        $result .= $data;
                    }
                }

                $arResult['data'] = $result;

                return $arResult;
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