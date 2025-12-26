<?php


namespace Gsv\Bitrix24;


use \Gsv\Bitrix24\Exception\Bitrix24AccessException;
use \Gsv\Bitrix24\Exception\Bitrix24ArgumentException;
use \Bitrix\Main\Config\Option;

abstract class Bitrix24App extends \Gsv\Bitrix24\Bitrix24Api
{
    public function __construct($app = false, $requireAccess = true)
    {
        $httpClient = $this->createHttpClient();

        if(is_array($app))
        {
            if(isset($app['AUTH_ID']) && isset($app['REFRESH_ID']) && isset($app['DOMAIN']) && isset($app['EXPIRES'] ))
            {
                $access_token = $app['AUTH_ID'];
                $refresh_token = $app['REFRESH_ID'];
                $domain = $app['DOMAIN'];
                $expires = $app['EXPIRES'];
            }
            elseif(isset($app['access_token']) && isset($app['refresh_token']) && isset($app['domain']) && isset($app['expires']))
            {
                $access_token = $app['access_token'];
                $refresh_token = $app['refresh_token'];
                $domain = $app['domain'];
                $expires = $app['expires'];
            }
            elseif(isset($appv['auth']['access_token']) && isset($app['auth']['refresh_token']) && isset($app['auth']['domain']) && isset($app['auth']['expires']))
            {
                $access_token = $app['auth']['access_token'];
                $refresh_token = $app['auth']['access_token'];
                $domain = $app['auth']['access_token'];
                $expires = $app['auth']['access_token'];
            }
            else
            {
                throw new Bitrix24ArgumentException("App format not supported yet!");
            }
            parent::__construct($httpClient, $domain);
            $this->setAccessToken($access_token);
            $this->setRefreshToken($refresh_token);
            $this->setTokenExpires($expires);
        }
        elseif(is_string($app))
        {
            $arUrl = self::parseHookUrl($app);

            if($arUrl)
            {
                parent::__construct($httpClient, $arUrl['host'], $arUrl['auth_path']);
                $this->setPath($arUrl['path']);
                if(isset($arUrl['https']))
                    $this->setHttps($arUrl['https']);
            }
            else
            {
                throw new Bitrix24AccessException("Cannot parse $app !");
            }
        }
        elseif($app === false || $app === null)
        {
            parent::__construct($httpClient);
            return;//Don't check access if we have no app!
        }
        else
        {
            throw new Bitrix24ArgumentException("Invalid argument 'app'!");
        }

        if($requireAccess)
        {
            $this->checkAccess();
        }
    }

    protected abstract function createHttpClient() : HttpClient;

    public static function parseHookUrl(string $app)
    {
        $arUrl = parse_url($app);

        if(empty($arUrl))
        {
            return false;
        }

        if (empty($arUrl['host']) && $arUrl['path'] && $arUrl['path'][0] != '/')
        {
            $arTemp = explode('/', $arUrl['path']);
            $arUrl['host'] = $arTemp[0];
            $arTemp[0] = '';
            $arUrl['path'] = implode('/', $arTemp);
        }

        if($arUrl && $arUrl['host'] && $arUrl['path'])
        {
            if ($arUrl['port'])
            {
                $arUrl['host'] .= ':' . $arUrl['port'];
            }

            $matches = [];

            if (preg_match('#^(/.+/)(\d+/[^/]+)/?$#', $arUrl['path'], $matches))
            {
                $arUrl['path'] = $matches[1];
                $arUrl['auth_path'] = $matches[2];

                if($arUrl['scheme'] == 'https')
                    $arUrl['https'] = true;
                elseif($arUrl['scheme'] == 'http')
                    $arUrl['https'] = false;
                elseif(!empty($arUrl['scheme']))
                    return false;

                $arUrl = array_intersect_key($arUrl, array_flip(['host', 'path', 'auth_path', 'https']));
                return $arUrl;
            }
        }

        return false;
    }
}