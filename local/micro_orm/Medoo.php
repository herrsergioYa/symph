<?php

$version = defined('PHP_VERSION_ID') ? PHP_VERSION_ID : php_version_id();

if($version >= 70300) {
    require_once __DIR__ . '/Medoo.v2.1.12.php';
} else if($version >= 50400) {
    require_once __DIR__ . '/Medoo.v1.7.10.php';
} else if($version >= 50100) {
    require_once __DIR__ . '/Medoo.v1.1.2.php';
} else {
    throw new \Exception("No Medoo supported!");
}
