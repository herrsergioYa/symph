<?php

namespace Gsv\Bitrix\Db;

class Db extends \Gsv\Util\Db\Db
{
    public static function getBitrixDbConnectionConfig($name = '')
    {
        if(empty($name))
        {
            $name = \Bitrix\Main\Data\ConnectionPool::DEFAULT_CONNECTION_NAME;
        }

        $configParams = static::getBitrixDbConnectionConfigs();
        if (isset($configParams[$name]) && !empty($configParams[$name]))
        {
            return $configParams[$name];
        }

        return parent::getBitrixDbConnectionConfig($name);
    }

    public static function getBitrixDbConnectionConfigs()
    {
        return \Bitrix\Main\Config\Configuration::getValue('connections');
    }

    public static function getBitrixDbConnectionParameters($name = '', $driverType = false)
    {
        $conn = parent::getBitrixDbConnectionParameters($name, $driverType);

        if(is_array($conn))
        {
            $charset = defined('BX_UTF') && BX_UTF === true ? 'utf8' : 'cp1251';
            $conn['charset']  = $charset;
        }

        return $conn;
    }
}