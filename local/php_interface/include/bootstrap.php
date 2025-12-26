<?php

if(file_exists(__DIR__ . '/config.php'))
{
    require_once __DIR__ . '/config.php';
}

if(file_exists(__DIR__ . '/preload.php'))
{
    require_once __DIR__ . '/preload.php';
}

if(file_exists(__DIR__ . '/constants.php'))
{
    require_once __DIR__ . '/constants.php';
}

if(file_exists(__DIR__ . '/dbconn.php'))
{
    require_once __DIR__ . '/dbconn.php';
}

if(file_exists(__DIR__ . '/functions.php'))
{
    require_once __DIR__ . '/functions.php';
}

if(file_exists(__DIR__ . '/composer.php'))
{
    require_once __DIR__ . '/composer.php';
}

if(file_exists(__DIR__ . '/autoload.php'))
{
    require_once __DIR__ . '/autoload.php';
}

if(file_exists(__DIR__ . '/autoloaders.php'))
{
    require_once __DIR__ . '/autoloaders.php';
}

if(file_exists(__DIR__ . '/helpers.php'))
{
    require_once __DIR__ . '/helpers.php';
}

if(file_exists(__DIR__ . '/custom.php'))
{
    require_once __DIR__ . '/custom.php';
}

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
    return;
}

//Bitrix specific actions

if(file_exists(__DIR__ . '/event_handlers.php'))
{
    require_once __DIR__ . '/event_handlers.php';
}

//if(file_exists(__DIR__ . '/debug.php'))
//{
//    require_once __DIR__ . '/debug.php';
//}

//There are some dirty HACKs in catalog.element involving \CUtil::PhpToJSObject()
//so proceed with caution!
//if(file_exists(__DIR__ . '/bx_js_encode.php'))
//{
//    require_once __DIR__ . '/bx_js_encode.php';
//}

if(file_exists(__DIR__ . '/bx_custom.php'))
{
    require_once __DIR__ . '/bx_custom.php';
}