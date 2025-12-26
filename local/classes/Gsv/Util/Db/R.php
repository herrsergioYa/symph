<?php

namespace Gsv\Util\Db;

class R
{
    const DB_CLASS = Db::class;

    protected $dbName;

    public function __construct($dbName)
    {
        $this->dbName = $dbName;
        static::ensureDb($dbName);
    }

    protected static function ensureDb($dbName)
    {
        if(!\R::hasDatabase($dbName))
        {
            $dbClass = static::DB_CLASS;
            $params = $dbClass::getBitrixDbConnectionParameters($dbName, 'pdo');
            $pdo = $dbClass::createPdo($params);
            if($dbName == 'default')
            {
                \R::setup($pdo, null, null, true);
            }
            else
            {
                \R::addDatabase($dbName, $pdo, null, null, true);
            }
        }
    }

    public function __call($name, $arguments)
    {
        $nameLower = strtolower($name);
        if($nameLower == 'selectdatabase')
        {
            $this->dbName = $arguments[0];
            static::ensureDb($this->dbName);
            return NULL;
        }
        else
        {
            if($nameLower != 'setup' && $nameLower != 'adddatabase')
            {
                \R::selectDatabase($this->dbName, false);
            }

            return \R::$name(...$arguments);
        }
    }
}