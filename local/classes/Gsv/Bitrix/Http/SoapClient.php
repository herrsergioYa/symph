<?php


namespace Gsv\Bitrix;

\Bitrix\Main\Loader::requireModule('webservice');

//Fixes the TLS issue
class SoapClient extends \CSOAPClient
{
    protected $bSsl;
    protected $defaultNamespace = '';

    public function __construct(string $url, ?string $defaultNamespace = null, ?string $login = null, ?string $password = null)
    {
        $url = parse_url($url);
        if(empty($url['host']) || empty($url['path']) || empty($url['scheme']) || $url['scheme'] != 'https' && $url['scheme'] != 'http')
        {
            throw new \Exception('Incorrect WS url provided!');
        }
        $this->bSsl = (bool)strpos($url['scheme'], 's');
        if(empty($url['port']))
        {
            $url['port'] = $this->bSsl ? 443 : 80;
        }


        try {
            parent::__construct($url['host'], $url['path'], $url['port']);
        } catch (\Exception $exception) {
            //For the older versions
            parent::CSOAPClient($url['host'], $url['path'], $url['port']);
        }

        if(!is_null($login) && is_null($password))
        {
            $password = $login;
            $login = $defaultNamespace;
            $defaultNamespace = null;
        }

        if(!is_null($defaultNamespace))
        {
            $this->SetDefaultNamespace($defaultNamespace);
        }

        if(!is_null($login))
        {
            $this->setLogin($login);
        }
        if(!is_null($password))
        {
            $this->setPassword($password);
        }
    }

    public function __call($name, $arguments)
    {
        if (count($arguments) != 1 && count($arguments) != 2)
            throw new \Exception('Wrong arguments count! You need to provide one or two!');
        $argument = array_shift($arguments);
        $unpack = array_shift($arguments);
        return $this->call($name, $argument, $unpack);
    }

    public function call($name, $argument, $unpack = false)
    {
        $request = new \CSOAPRequest($name, $this->GetDefaultNamespace(), $argument);
        $response = $this->send($request);
        if($response)
        {
            if($response->isFault())
            {
                throw new \Exception('Bad response to ' . $name);
            }
            $response = $response->value();
            if($unpack)
            {
                if(!is_array($response) || count($response) != 1)
                {
                    throw new \Exception('Unwrapping impossible in ' . $name);
                }
                return array_shift($response);
            }
            return $response;
        }
        else
        {
            throw new \Exception('No response to ' . $name);
        }
    }


    public function send( $request )
    {
        $fullUrl = ($this->bSsl ? "https" : "http")."://".$this->Server.":".$this->Port.$this->Path;

        $uri = new \Bitrix\Main\Web\Uri($fullUrl);
        if($uri->getHost() == '')
        {
            $this->ErrorString = '<b>Error:</b> CSOAPClient::send() : Wrong server parameters.';
            return 0;
        }
        else
        {
            $this->Server = $uri->getHost();
            $this->Port = $uri->getPort();
            $this->Path = $uri->getPathQuery();
        }

        $Server = $this->Server;
        if($this->bSsl)
        {
            //Здесь фиксим баг из ядра
            $Server = 'tls://' . $Server;
        }

        if ( $this->Timeout != 0 )
        {
            $fp = fsockopen( $Server,
                $this->Port,
                $this->errorNumber,
                $this->errorString,
                $this->Timeout );
        }
        else
        {
            $fp = fsockopen( $Server,
                $this->Port,
                $this->errorNumber,
                $this->errorString );
        }

        if ( $fp == 0 )
        {
            $this->ErrorString = '<b>Error:</b> CSOAPClient::send() : Unable to open connection to ' . $this->Server . '.';
            return 0;
        }

        try
        {
            $payload = $request->payload();

            $authentification = "";
            if (($this->login() != ""))
            {
                $authentification = "Authorization: Basic " . base64_encode($this->login() . ":" . $this->password()) . "\r\n";
            }

            $name = $request->name();
            $namespace = $request->get_namespace();
            if (strlen($namespace) < 1 || $namespace[strlen($namespace) - 1] != "/")
                $namespace .= "/";

            $HTTPRequest = "POST " . $this->Path . " HTTP/1.0\r\n" .
                "User-Agent: BITRIX SOAP Client\r\n" .
                "Host: " . $this->Server . "\r\n" .
                $authentification .
                "Content-Type: text/xml; charset=utf-8\r\n" .
                "SOAPAction: \"" . $namespace . $request->name() . "\"\r\n" .
                "Content-Length: " . (defined('BX_UTF') && BX_UTF == 1 && function_exists('mb_strlen') ? mb_strlen($payload, 'latin1') : strlen($payload)) . "\r\n\r\n" .
                $payload;

            $this->SOAPRawRequest = $HTTPRequest;
            if (!fwrite($fp, $HTTPRequest /*, strlen( $HTTPRequest )*/))
            {
                $this->ErrorString = "<b>Error:</b> could not send the SOAP request. Could not write to the socket.";
                $response = 0;
                return $response;
            }

            $rawResponse = "";
            // fetch the SOAP response
            while ($data = fread($fp, 32768))
            {
                $rawResponse .= $data;
            }
        }
        finally
        {
            // close the socket
            fclose($fp);
        }

        $this->SOAPRawResponse = $rawResponse;
        $response = new \CSOAPResponse();
        $response->decodeStream( $request, $rawResponse );
        return $response;
    }

    public function SetDefaultNamespace($defaultNamespace)
    {
        $this->defaultNamespace = $defaultNamespace;
    }

    public function GetDefaultNamespace()
    {
        return $this->defaultNamespace;
    }
}