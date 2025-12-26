<?php

namespace Gsv\Util\Db;

class Db
{
    //Classes aren't loaded here so it be okay...
    const BITRIX_DATABASES = [
        \Bitrix\Main\DB\MysqliConnection::class => [
            'type' => 'mysqli',
            'ext' => 'mysqli',
            'pdo' => 'mysql',
            'pdo_ext' => 'pdo_mysql',
        ],
        \Bitrix\Main\DB\MysqlConnection::class => [
            'type' => 'mysql',
            'ext' => 'mysql',
            'pdo' => 'mysql',
            'pdo_ext' => 'pdo_mysql',
        ],
        \Bitrix\Main\DB\MssqlConnection::class => [
            'type' => 'mssql',
            'ext' => 'sqlsrv',
            'pdo' => 'sqlsrv',
            'pdo_ext' => 'pdo_sqlsrv',
        ],
        \Bitrix\Main\DB\OracleConnection::class => [
            'type' => 'oracle',
            'ext' => 'oci8',
            'pdo' => 'oci',
            'pdo_ext' => 'pdo_oci',
        ],
        \Bitrix\Main\DB\PgsqlConnection::class => [
            'type' => 'pgsql',
            'ext' => 'pgsql',
            'pdo' => 'pgsql',
            'pdo_ext' => 'pdo_pgsql',
        ],
        '*SQLITE*' => [
            'type' => 'sqlite',
            'ext' => 'sqlite3',
            'pdo' => 'sqlite',
            'pdo_ext' => 'pdo_sqlite',
        ],
    ];

    public static function getBitrixDbDriver($connectionClass, $driverType = '') {
        $className = ltrim($connectionClass, '\\');
        if(!array_key_exists($className, static::BITRIX_DATABASES)) {
            $className = strtolower($className);
            foreach (static::BITRIX_DATABASES as $class => $unused) {
                if(strtolower($class) == $className) {
                    $className = $class;
                    break;
                }
            }
            if(!array_key_exists($className, static::BITRIX_DATABASES)) {
                return null;
            }
        }
        if(is_string($driverType)) {
            $driver = static::BITRIX_DATABASES[$className];
            if($driverType) {
                $driver = $driver[$driverType];
            }
        } else {
            $driver = $className;
        }
        return $driver;
    }

    public static function getBitrixDbConnectionConfig($name = '')
    {
        if (empty($name))
        {
            $name = 'default';
        }

        if(defined('ALT_DB_CONNECTIONS') && is_array(ALT_DB_CONNECTIONS))
        {
            if(array_key_exists($name, ALT_DB_CONNECTIONS))
            {
                return ALT_DB_CONNECTIONS[$name];
            }
        }

        if($name == ':memory:'
            || (strpos($name, '/') !== false  || strpos($name, '\\') !== false) && file_exists($name))
        {
            return [
                'className' => '*SQLITE*',
                'login' => '',
                'password' => '',
                'dbname'   => $name,
                'host'     => '',
            ];
        }

        /*$configParams = \Bitrix\Main\Config\Configuration::getValue('connections');
        if (isset($configParams[$name]) && !empty($configParams[$name]))
        {
            return $configParams[$name];
        }*/

        return false;
    }

    public static function getBitrixDbConnectionParameters($name = '', $driverType = false)
    {
        //$bxConnectionConfig = \Bitrix\Main\Application::getConnection($name)->getConfiguration();
        $bxConnectionConfig = static::getBitrixDbConnectionConfig($name);
        if(empty($bxConnectionConfig)) {
            return false;
        }

        $driver = static::getBitrixDbDriver($bxConnectionConfig['className'], $driverType);
        $charset = 'utf8';//defined('BX_UTF') && BX_UTF === true ? 'utf8' : 'cp1251';

        $conn = array(
            'driver'   => $driver,
            'user'     => $bxConnectionConfig['login'],
            'password' => $bxConnectionConfig['password'],
            'dbname'   => $bxConnectionConfig['database'],
            'host'     => $bxConnectionConfig['host'],
            'charset'  => $charset,
        );

        return $conn;
    }

    public static function ext_connect($name, $pconnect = false)
    {
        if(is_string($name)) {
            $arDb = static::getBitrixDbConnectionParameters($name, 'ext');
        } else if(is_array($name)) {
            $arDb = $name;
        } else {
            return false;
        }

        if(empty($arDb)) {
            return false;
        }

        if($arDb['driver'] == 'mysqli') {
            $host = $arDb['host'];
            if($pconnect) {
                $host = "p:$host";
            }
            $link = mysqli_connect(
                $host,
                $arDb['user'],
                $arDb['password'],
                $arDb['dbname']
            );
            return $link;
        } else if($arDb['driver'] == 'mysql') {
            $host = $arDb['host'];
            if ($pconnect) {
                $link = mysql_pconnect(
                    $host,
                    $arDb['user'],
                    $arDb['password']
                );
            } else {
                $link = mysql_connect(
                    $host,
                    $arDb['user'],
                    $arDb['password'],
                    true
                );
            }
            if ($link && !empty($arDb['dbname'])) {
                if (!mysql_select_db($arDb['dbname'], $link)) {
                    mysql_close($link);
                    return false;
                }
            }
            return $link;
        } else if($arDb['driver'] == 'sqlite3') {
            return new \SQLite3($arDb['dbname']);
        } else if($arDb['driver'] == 'sqlite') {
            return sqlite_open($arDb['dbname']);
        } else {
            return false;
        }
    }

    public static function getPdoParams($name = '')
    {
        if(is_string($name)) {
            $arDb = static::getBitrixDbConnectionParameters($name, 'pdo');
        } else if(is_array($name)) {
            $arDb = $name;
        } else {
            return false;
        }
        if($arDb['driver'] == 'sqlite')
        {
            return [
                'dsn' => "sqlite:${arDb['dbname']}",
                'user' => null,
                'password' => null,
                'options' => null,
            ];
        }

        if(($pos = strrpos($arDb['host'], ":")) !== false)
        {
            $arDb['port'] = substr($arDb['host'], $pos + 1);
            $arDb['host'] = substr($arDb['host'], 0, $pos);
        }

        $dsn = "${arDb['driver']}:host=${arDb['host']}";
        if(!empty($arDb['port']))
        {
            $dsn .= ";port=${arDb['port']}";
        }
        $dsn .= ";dbname=${arDb['dbname']}";
        $user = null;
        $password = null;
        $options = null;

        if($arDb['driver'] == 'mysql')
        {
            $dsn .= ";charset=${arDb['charset']}";
            $user = $arDb['user'];
            $password = $arDb['password'];
        }
        else if($arDb['driver'] == 'pgsql')
        {
            $dsn .= ";user=${arDb['user']}";
            $dsn .= ";password=${arDb['password']}";
        }
        else
        {
            return false;
        }

        return [
            'dsn' => $dsn,
            'user' => $user,
            'password' => $password,
            'options' => $options,
        ];
    }

    public static function createPdo($name = '')
    {
        $arDb = static::getPdoParams($name);
        return new \PDO($arDb['dsn'], $arDb['user'], $arDb['password'], $arDb['options']);
    }

    public static function createMedooPdo($name)
    {
        $params = static::getBitrixDbConnectionParameters($name, 'pdo');
        return [
            'pdo' => static::createPdo($params),
            'type' => $params['driver'],
            'database_type' => $params['driver'], //For Medoo 5
        ];
    }
}