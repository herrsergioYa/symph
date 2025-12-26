<?php

//Warning!
//These function are NOT prefixed so they CAN conflict with new versions!
//Proceed with CAUTION!

if(file_exists(__DIR__ . '/array.php'))
{
    require_once __DIR__ . '/array.php';
}

if(file_exists(__DIR__ . '/string.php'))
{
    require_once __DIR__ . '/string.php';
}

if (file_exists(__DIR__ . '/url.php'))
{
    require_once __DIR__ . '/url.php';
}

if (file_exists(__DIR__ . '/mysqli.php'))
{
    require_once __DIR__ . '/mysqli.php';
}