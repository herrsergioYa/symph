<?php

/** @var ?\Psr4AutoloaderClass $psr4ClassLoader */
global $psr4ClassLoader;

if(!($psr4ClassLoader && $psr4ClassLoader instanceof \Psr4AutoloaderClass)
    && (class_exists(\Psr4AutoloaderClass::class, false)
        || file_exists(__DIR__ . '/classes/Psr4AutoloaderClass.php')))
{
    if(!class_exists(\Psr4AutoloaderClass::class, false))
    {
        require_once __DIR__ . '/classes/Psr4AutoloaderClass.php';
    }
    $psr4ClassLoader = new \Psr4AutoloaderClass();
    $psr4ClassLoader->register();
}

/** @var ?\Psr0AutoloaderClass $psr0ClassLoader */
global $psr0ClassLoader;

if(!($psr0ClassLoader && $psr0ClassLoader instanceof \Psr0AutoloaderClass)
    && (class_exists(\Psr0AutoloaderClass::class, false)
        || file_exists(__DIR__ . '/classes/Psr0AutoloaderClass.php')))
{
    if(!class_exists(\Psr0AutoloaderClass::class, false))
    {
        require_once __DIR__ . '/classes/Psr0AutoloaderClass.php';
    }
    $psr0ClassLoader = new \Psr0AutoloaderClass();
    $psr0ClassLoader->register();
}