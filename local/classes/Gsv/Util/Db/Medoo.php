<?php

namespace Gsv\Util\Db;

class Medoo extends \Gsv\Db\Medoo
{
    protected static $arInstances = [];

    public function __construct($name)
    {
        parent::__construct(static::createMedooPdo($name));
    }

    public static function createMedooPdo($name)
    {
        return Db::createMedooPdo($name);
    }

    public static function getInstance()
    {
        if(!array_key_exists(static::class, static::$arInstances))
        {
            static::$arInstances[static::class] = new static();
        }

        return static::$arInstances[static::class];
    }
}