<?php

/** @var ?\Composer\Autoload\ClassLoader[] $composerClassLoaders */
global $composerClassLoaders;
$composerClassLoaders = $composerClassLoaders ?: [];

if(defined('COMPOSER_DIRECTORIES') && COMPOSER_DIRECTORIES)
{
    foreach ((array)COMPOSER_DIRECTORIES as $sDir)
    {
        if (file_exists($sDir . '/vendor/autoload.php'))
        {
            $composerClassLoaders[] = require $sDir . '/vendor/autoload.php';

            /*if($composerClassLoader && is_object($composerClassLoader)
                && ($composerClassLoader instanceof \Composer\Autoload\ClassLoader))
            {
                $composerClassLoaders[] = $composerClassLoader;
            }*/
        }
    }
}

//and here we are done

if(class_exists(\Composer\Autoload\ClassLoader::class, false))
{
    $hasChanged = false;
    foreach ($composerClassLoaders as $i => $composerClassLoader)
    {
        if(!($composerClassLoader instanceof \Composer\Autoload\ClassLoader))
        {
            unset($composerClassLoaders[$i]);
            $hasChanged = true;
        }
    }
    if($hasChanged)
    {
        $composerClassLoaders = array_values($composerClassLoaders);
    }
    //Now we know the array $composerClassLoaders contains only \Composer\Autoload\ClassLoader(s)
}
else if($composerClassLoaders)
{
    //They cannot be here 'cause there is no such a class...
    $composerClassLoaders = [];
}