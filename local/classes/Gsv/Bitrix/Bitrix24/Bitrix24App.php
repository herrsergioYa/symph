<?php


namespace Gsv\Bitrix\Bitrix24;


use Gsv\Bitrix24\Exception\Bitrix24AccessException;
use Gsv\Bitrix24\Exception\Bitrix24ArgumentException;
use Gsv\Bitrix24\HttpClient;
use Bitrix\Main\Config\Option;

class Bitrix24App extends \Gsv\Util\Bitrix24\Bitrix24App
{
    /* @var string */
    protected $name;

    public function __construct($app = '', $requireAccess = true)
    {
        if(static::isAppName($app))
        {
            if(strlen($app) > 0)
                $name = $app;
            else
                $name = 'default';

            $this->name = $name;

            $domain = Option::get('gsv.b24.app', "b24app_${name}_domain", '');

            parent::__construct(false, false);

            $this->setDomain($domain);

            $applicationId = Option::get('gsv.b24.app', "b24app_${name}_client_id", '');
            $applicationSecret = Option::get('gsv.b24.app', "b24app_${name}_client_secret", '');

            $this->setApplicationId($applicationId);
            $this->setApplicationSecretCode($applicationSecret);

            $access = Option::get('gsv.b24.app', "b24app_${name}_access", '');
            $arAccess = unserialize($access);
            if(is_array($arAccess))
            {
                $this->acceptAccessTokenOnly($arAccess);
            }
            elseif(preg_match('#\d+/\w+#', $access))
            {
                $this->setAuthPath($access);
            }
            elseif($requireAccess)
            {
                throw new Bitrix24AccessException("No accessToken or authPath configured for $name !");
            }

            if($requireAccess)
            {
                $this->checkAccess();
            }
        }
        else
        {
            $this->name = '';
            parent::__construct($app, $requireAccess);
        }

        $this->setRedirectUri('/local/auth/b24app.php');
    }

    public function acceptAccessToken(array $requestResult)
    {
        parent::acceptAccessToken($requestResult);
        $name = $this->name;
        if(strlen($name) > 0)
        {
            Option::set('gsv.b24.app', "b24app_${name}_access", serialize($requestResult));
        }
    }

    protected function acceptAccessTokenOnly(array $requestResult)
    {
        parent::acceptAccessToken($requestResult);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    public static function getInstance($app = '')
    {
        return self::getInstanceImpl($app, self::class);
    }

    protected static function getInstanceImpl($app, $className)
    {
        if(!static::isAppName($app))
        {
            return parent::getInstanceImpl($app, $className);
        }
        else
        {
            $name = $app;
        }

        static $instances = [];
        if(strlen($name) <= 0)
            $name = 'default';
        //if(!defined('B24APP_AUTH'))
        define("B24APP_${name}_AUTH", false);
        if(array_key_exists($name, $instances))
            return $instances[$name];
        $authMode = constant("B24APP_${name}_AUTH");
        return $instances[$name] = new self($name, !($authMode === 'Y' || $authMode === true));
    }

    public static function isAppName($app)
    {
        return is_string($app) && !static::parseHookUrl($app);
    }
}