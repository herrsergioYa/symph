<?php

// PHP_VERSION_ID is available as of PHP 5.2.7, if our 
// version is lower than that, then emulate it
if(!defined('PHP_VERSION_ID'))
{
//    if(function_exists('php_version_id'))
//    {
//        define('PHP_VERSION_ID', php_version_id());
//    }
//    else
    {
        $version = explode('.', PHP_VERSION);
        $version = intval($version[0]) * 10000 + intval($version[1]) * 100 + intval($version[2]);
        define('PHP_VERSION_ID', $version);
    }
}

if(file_exists(__DIR__ . '/array.php'))
{
    require_once __DIR__ . '/array.php';
}

if(file_exists(__DIR__ . '/string.php'))
{
    require_once __DIR__ . '/string.php';
}

if (extension_loaded('mysqli'))
{
    if (file_exists(__DIR__ . '/mysqli.php'))
    {
        require_once __DIR__ . '/mysqli.php';
    }
}

////if(!function_exists("mysql_connect") && function_exists("mysqli_connect"))
//if (!extension_loaded('mysql') && extension_loaded('mysqli'))
//{
//    if (file_exists(__DIR__ . '/mysql.php'))
//    {
//        require_once __DIR__ . '/mysql.php';
//    }
//}

//Can conflict with \Bitrix\Main\Web\HttpClient!
//if(file_exists(__DIR__ . '/cURL-polyfill.php'))
//{
//    require_once __DIR__ . '/cURL-polyfill.php';
//}