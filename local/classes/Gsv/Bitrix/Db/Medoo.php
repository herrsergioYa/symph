<?php

namespace Gsv\Bitrix\Db;

class Medoo extends \Gsv\Util\Db\Medoo
{
    public static function createMedooPdo($name)
    {
        return Db::createMedooPdo($name);
    }
}