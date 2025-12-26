<?php

namespace Gsv\Bitrix24;

use \Gsv\Bitrix24\Exception\Bitrix24Exception;
use \Gsv\Bitrix24\Exception\Bitrix24ApiException;
use \Gsv\Bitrix24\Exception\Bitrix24SecurityException;
use \Gsv\Bitrix24\Exception\Bitrix24AccessException;
use \Gsv\Bitrix24\Exception\Bitrix24ArgumentException;

class Bitrix24Api
{
    /* @var HttpClient */
    protected $httpClient;

    /* @var string */
    protected $domain= '';

    /* @var string */
   // protected $oauthDomain= '';

    /* @var boolean */
    protected $isHttps = true;

    /* @var string */
    protected $path = '/rest/';

    /* @var string */
    protected $authPath = '/';

    /**
     * access token
     * @var string
     */
    protected $accessToken = '';

    /**
     * refresh token
     * @var string
     */
    protected $refreshToken = '';

    /**
     * expires
     * @var string
     */
    protected $expires = '';

    /**
     * client id
     * @var string
     */
    protected $applicationId = '';

    /**
     * client secret
     * @var string
     */
    protected $applicationSecretCode = '';

    /**
     * scope
     * @var array
     */
    protected $applicationScope = array();

    /**
     * access token
     * @var string
     */
    protected $redirectUri = '';

    public function __construct(HttpClient $httpClient, ?string $domain = null, ?string $authPath = null)
    {
        $this->httpClient = $httpClient;

        if(is_string($domain))
        {
            $this->setDomain($domain);
        }
        if(is_string($authPath))
        {
            $this->setAuthPath($authPath);
        }
    }

    public function getDomain():string
    {
        return $this->domain;
    }

    public function setDomain(string $domain)
    {
        $this->domain = $domain;
    }

//    public function getOauthDomain():string
//    {
//        return $this->oauthDomain;
//    }
//
//    public function setOauthDomain(string $domain)
//    {
//        $this->oauthDomain = $domain;
//    }

    public function isHttps():bool
    {
        return $this->isHttps;
    }

    public function setHttps(bool $isHttps)
    {
        $this->isHttps = $isHttps;
    }

    public function getPath():string
    {
        return $this->path;
    }

    public function setPath(string $path)
    {
        $this->path = $path;
    }

    public function getAuthPath():string
    {
        return $this->authPath;
    }

    public function setAuthPath(string $authPath)
    {
        $this->authPath = $authPath;
    }

    public function getAccessToken():string
    {
        return $this->accessToken;
    }

    public function setAccessToken(string $accessToken)
    {
        $this->accessToken = $accessToken;
    }

    public function getApplicationId():string
    {
        return $this->applicationId;
    }

    public function setApplicationId(string $applicationId)
    {
        $this->applicationId = $applicationId;
    }

    public function getApplicationSecretCode():string
    {
        return $this->applicationSecretCode;
    }

    public function setApplicationSecretCode(string $applicationSecretCode)
    {
        $this->applicationSecretCode = $applicationSecretCode;
    }

    public function getApplicationScope():array
    {
        return $this->applicationScope;
    }

    public function setApplicationScope(array $applicationScope)
    {
        $this->applicationScope = $applicationScope;
    }

    public function getRefreshToken():string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(string $refreshToken)
    {
        $this->refreshToken = $refreshToken;
    }

    public function getTokenExpires():string
    {
        return $this->expires;
    }

    public function setTokenExpires($tokenExpires)
    {
        $this->expires = $tokenExpires;
    }

    public function getRedirectUri():string
    {
        return $this->redirectUri;
    }

    public function setRedirectUri(string $redirectUri)
    {
        $this->redirectUri = $redirectUri;
    }

    /**
     * Осуществляет запрос
     * @param string $methodName Имя вызываемого метода
     * @param array $params Параметры вызываемог омтеода
     * @param string|int|null $start Настройки пагинации.
     *          Возможные значения:
     *          Неотрицательное число (string|int) - Начало выборки
     *          Отрицательное число (string|int) - Выбрать все в необходимое число запросов
     *          null - Не указывать параметры пагинации
     *          '' (string) - Для методов, заканчивающихся на ".list", использовать отрицательное число,
     *              а для всех остальных null (значение по умолчанию)
     * @return array|bool|mixed|string Возвращаемое CRM значение
     * @throws Bitrix24Exception
     */
    public function call(string $methodName, array $params, $start = '')
    {
        $this->checkAccess();

        if (0 == strlen($methodName))
        {
            throw new Bitrix24ArgumentException('method name not found, you must set method name');
        }

        $uri = $this->getUri() .  $methodName . '.json';

        if(!is_null($this->accessToken) && strlen($this->accessToken) > 0)
        {
            $params['auth'] = $this->accessToken;
        }

        if ($start === '')
        {
            if(substr($methodName, -5) === '.list')
            {
                $start = -1;
            }
            else
            {
                $start = null;
            }
        }

        if(is_numeric($start))
        {
            $start = intval($start);
            if($start >= 0)
            {
                return $this->doCall($uri, $params, $start);
            }
            else
            {
                $ret = ['result' => [], 'total' => 0];
                $start = 0;
                while (true)
                {
                    $response = $this->doCall($uri, $params, $start);
                    if(array_key_exists('error', $response))
                    {
                        $response = array_intersect_key($response, array_flip(['error']));
                        return $response;
                    }
                    $ret['result'] = array_merge($ret['result'], $response['result']);
                    if($response['total'] > 0)
                    {
                        $ret['total'] = $response['total'];
                    }
                    if($response['next'] > 0)
                    {
                        $start = intval($response['next']);
                    }
                    else
                    {
                        break;
                    }
                }
                return $ret;
            }
        }
        elseif($start === false)
        {
            return $this->yieldList($uri, $params);
        }
        elseif($start === true)
        {
            $ret  = [];
            foreach($this->yieldList($uri, $params) as $key => $value)
            {
                $ret[$key] = $value;
            }
            return $ret;
        }

        return $this->doCall($uri, $params);
    }

    protected function yieldList(string $uri, array $params)
    {
        $start = 0;
        while (true)
        {
            $response = $this->doCall($uri, $params, $start);
            foreach ($response['result'] as $k => $v)
            {
                if(\is_numeric($k))
                    yield $v;
                else
                    yield $k => $v;
            }
            if($response['next'] > 0)
            {
                $start = intval($response['next']);
            }
            else
            {
                break;
            }
        }
        return $response['total'];
    }


    public function __call(string $name, array $params)
    {
        $name = preg_replace('/[A-Z]/', '.$0', $name);
        $name = strtolower($name);

        $param = [];
        $start = '';

        foreach ($params as $p)
        {
            if(is_numeric($p) || is_null($p) || is_string($p) || is_bool($p))
            {
                $start = $p;
            }

            if(is_array($p))
            {
                $param = $p;
            }
        }

        return $this->call($name, $param, $start);
    }

    public function callBatch(array $batchList, $unpackBatch = true, $halt = true) : array
    {
        $this->checkAccess();

        $params = $this->build_batch_query($batchList);

        $uri = $this->getUri() . 'batch.json';

        if(!is_null($this->accessToken) && strlen($this->accessToken) > 0)
        {
            $params['auth'] = $this->accessToken;
        }
        $params['halt'] = intval($halt);

        $return = $this->doCall($uri, $params, null);
        if($unpackBatch)
        {
            $return = static::unpack($return);
        }
        return $return;
    }

    public static function unpack(array $result)
    {
        $return = [];
        if(!is_array($result['result']))
        {
            return false;
        }
        foreach($result['result'] as $param => $values)
        {
            if($param === 'result')
            {

            }
            elseif(strpos($param, 'result_') === 0)
            {
                $param = substr($param, 7);
            }
            else
            {
                return false;
            }

            foreach($values as $k => $v)
            {
                if(!array_key_exists($k, $return))
                {
                    $return[$k] = [];
                }
                $return[$k][$param] = $v;
            }
        }
//        unset($result['result']);
//        if(!array_key_exists('', $return))
//        {
//            $return[''] = $result;
//        }
        $result['result'] = $return;
        return $result;
    }

    protected function build_command_body($cmd, $params)
    {
        return $cmd."?".http_build_query($params);
    }

    /**
     * @return string Адрес, на который необходимо отправлять запросы
     */
    protected function getUri(): string
    {
        $uri = ($this->isHttps ? 'https://' : 'http://') . $this->domain . '/' . trim($this->path, '/') . '/';
        $authPath = trim($this->authPath, '/');
        if (strlen($authPath) > 0)
        {
            $uri .= $authPath . '/';
        }
        return $uri;
    }

    /**
     * Осуществляет запрос на низком уровне
     * @param string $uri Адрес вызываемого метода
     * @param array $params Параметры вызываемог омтеода
     * @param string|int|null $start Настройки пагинации.
     *          Возможные значения:
     *          Неотрицательное число (string|int) - Начало выборки
     *          null - Не указывать параметры пагинации
     * @param array $query Дополнительные параметры строки запроса
     * @throws Bitrix24Exception
     * @return array Возвращаемое CRM значение
     */
    protected function doCall(string $uri, ?array $params = [], $start = null, ?array $query = []) : array
    {
        if(is_array($params))
            unset($params['start']);
        if(is_array($query))
            unset($query['start']);
        else
            $query = [];

        if(is_numeric($start))
        {
            $start = intval($start);
            if ($start >= 0)
            {
                $query['start'] = $start;
            }
        }

        if($query)
        {
            $q = http_build_query($query);
            if (strpos($uri, '?') !== false)
                $uri .= '&' . $q;
            else
                $uri .= '?' . $q;
        }

        sleep(1);

        $return = $this->httpClient->doCall($uri, $params);
        if(array_key_exists('error', $return))
        {
            throw new Bitrix24ApiException($return['error']);
        }
        return $return;
    }

    /**
     * @param array $batchList
     * @return array
     */
    protected function build_batch_query(array $batchList): array
    {
        $params = ['cmd' => []];

        foreach ($batchList as $k => $cmd)
        {
            $params['cmd'][$cmd["id"] ?? $k] = $this->build_command_body($cmd["cmd"], $cmd["params"]);
        }
        return $params;
    }

    /**
    * Сheck is access token expired
    * @return boolean
    */
    public function hasAccessTokenExpired()
    {
        return intval($this->expires) - time() < 300;
    }

    /**
     * Сheck is access token expire, сall list of all available api-methods from B24 portal with current access token
     * if we have an error code expired_token then return true else return false
     * @return boolean
     * @throws Bitrix24Exception
     */
    public function hasAccessTokenExpiredEx()
    {
        $accessToken = $this->getAccessToken();
        $domain = $this->getDomain();

        if(is_null($domain))
        {
            throw new Bitrix24AccessException('domain not found, you must call setDomain method before');
        }
        elseif(is_null($accessToken))
        {
            throw new Bitrix24AccessException('application id not found, you must call setAccessToken method before');
        }
  //      $url = 'https://'.$domain."/rest/methods.json?auth=".$accessToken.'&full=true';
    //    $requestResult = $this->executeRequest($url);
        $uri = $this->getUri() . 'methods.json';
        $requestResult = $this->doCall($uri, [], null, ['full' => 'true']);
        if((isset($requestResult['error'])) &&
            (('expired_token' == $requestResult['error']) || ('invalid_token' == $requestResult['error'])))
        {
            return true;
        }
        return false;
    }

    public function getAuthorizePage()
    {
        $url = 'https://'.$this->getDomain(). "/oauth/authorize/".
            "?client_id=".urlencode($this->getApplicationId());
        if(strlen($this->getRedirectUri()) > 0)
            $url .= '&redirect_uri='.urlencode($this->getRedirectUri());
        return $url;
    }

    public function requestAccessToken($code)
    {
        $query = 'https://'.$this->getDomain(). "/oauth/token/?";
        $query .= "grant_type=authorization_code" . "&";
        $query .= "client_id=" . urlencode($this->getApplicationId()) . "&";
        $query .= "client_secret=" . urlencode($this->getApplicationSecretCode()) . "&";
        $query .= "code=" . urlencode($code);

        $requestResult = $this->doCall($query, []);
        if (isset($requestResult['error']))
            throw new Bitrix24SecurityException($requestResult['error']);//return false;
        else
            return $requestResult;
    }

    public function requestAndAcceptAccessToken($code)
    {
        $result = $this->requestAccessToken($code);
        if ($result)
        {
            $this->acceptAccessToken($result);
            return $result;
        }
        return false;
    }

    public function refreshAccessToken()
    {
        $domain = $this->getDomain();
        $applicationId = $this->getApplicationId();
        $applicationSecret = $this->getApplicationSecretCode();
        $refreshToken = $this->getRefreshToken();
        $applicationScope = $this->getApplicationScope();
        $redirectUri = $this->getRedirectUri();

        if(is_null($domain))
        {
            throw new Bitrix24AccessException('domain not found, you must call setDomain method before');
        }
        elseif(is_null($applicationId))
        {
            throw new Bitrix24AccessException('application id not found, you must call setApplicationId method before');
        }
        elseif(is_null($applicationSecret))
        {
            throw new Bitrix24AccessException('application id not found, you must call setApplicationSecret method before');
        }
        elseif(is_null($refreshToken))
        {
            throw new Bitrix24AccessException('application id not found, you must call setRefreshToken method before');
        }
//        elseif(is_null($applicationScope))
//        {
//            throw new Bitrix24AccessException('application scope not found, you must call setApplicationScope method before');
//        }
//        elseif(is_null($redirectUri))
//        {
//            throw new Bitrix24AccessException('application redirect URI not found, you must call setRedirectUri method before');
//        }

        $url = 'https://'.$domain."/oauth/token/".
            "?client_id=".urlencode($applicationId).
            "&grant_type=refresh_token".
            "&client_secret=".$applicationSecret.
            "&refresh_token=".$refreshToken;
        if(!is_null($applicationScope))
            $url .=  '&scope='.implode(',', array_map('urlencode', array_unique($applicationScope)));
        if(strlen($redirectUri) > 0)
            $url .=  '&redirect_uri='.urlencode($redirectUri);
        $requestResult = $this->doCall($url, []);
        if (isset($requestResult['error']))
            throw new Bitrix24SecurityException($requestResult['error']);//return false;
        else
            return $requestResult;
    }

    public function refreshAndAcceptAccessToken()
    {
        $result = $this->refreshAccessToken();
        if ($result)
        {
            $this->acceptAccessToken($result);
            return $result;
        }
        return false;
    }

    public function acceptAccessToken(array $requestResult)
    {
        $this->setAccessToken($requestResult['access_token']);
        $this->setRefreshToken($requestResult['refresh_token']);
        $this->setTokenExpires($requestResult['expires']);
    }

    /**
     * Get list of all methods available for current application
     * @param array | null $applicationScope
     * @param bool $isFull
     * @return array
     * @throws Bitrix24Exception
     */
    public function getAvailableMethods($applicationScope = array(), $isFull = false)
    {
     //   $accessToken = $this->getAccessToken();
     //   $domain = $this->getDomain();

//        if(is_null($domain))
//        {
//            throw new Bitrix24AccessException('domain not found, you must call setDomain method before');
//        }
//        elseif(is_null($accessToken))
//        {
//            throw new Bitrix24AccessException('application id not found, you must call setAccessToken method before');
//        }

        $uri = $this->getUri() . 'methods.json';
        $query = [];

        if($isFull)
        {
            $query['full'] = 'true';
        }

        if(!is_array($applicationScope))
        {
            $uri .= '?scope';
        }
        elseif(count(array_unique($applicationScope)) > 0)
        {
            $query['scope'] = implode(',', array_unique($applicationScope));
        }
       // $url = 'https://'.$domain."/rest/methods.json?auth=".$accessToken.$showAll.$scope;
        $requestResult = $this->doCall($uri, [], null, $query);
        return $requestResult;
    }

    /**
     * get list of scope for current application from bitrix24 api
     * @param bool $isFull
     * @return array
     * @throws Bitrix24Exception
     */
    public function getScope($isFull=false)
    {
  //      $accessToken = $this->getAccessToken();
    //    $domain = $this->getDomain();
        $this->checkAccess();
//        if(is_null($domain))
//        {
//            throw new Bitrix24AccessException('domain not found, you must call setDomain method before');
//        }
//        elseif(is_null($accessToken))
//        {
//            throw new Bitrix24AccessException('application id not found, you must call setAccessToken method before');
//        }

        $uri = $this->getUri() . 'scope.json';
        $query = [];

        if($isFull)
        {
            $query['full'] = 'true';
        }

        $requestResult = $this->doCall($uri, [], null, $query);
        return $requestResult;
    }

    protected function checkAccess(): void
    {
        if (is_null($this->getDomain()))
        {
            throw new Bitrix24AccessException('domain not found, you must call setDomain method before');
        }
        if(!$this->hasAuthPath())
        {
            if (is_null($this->getAccessToken()))
            {
                throw new Bitrix24AccessException('access token not found, you must call setAccessToken method before or use webhooks');
            }

            if ($this->hasAccessTokenExpired())
            {
                if ($this->refreshAndAcceptAccessToken() == false)
                {
                    throw new Bitrix24AccessException('access token expired BUT cannot be refreshed!');
                }
            }
        }
    }

    public function hasAuthPath(): bool
    {
        return strlen(trim($this->authPath, '/')) > 0;
    }

    public function getHttpClient() : HttpClient
    {
        return $this->httpClient;
    }
}