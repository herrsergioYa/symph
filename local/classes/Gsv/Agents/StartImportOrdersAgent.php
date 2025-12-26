<?php

namespace Gsv\Agents;

class StartImportOrdersAgent extends \Gsv\Bitrix\CronAgent
{
    const SKIP_MISSED_EXECUTIONS = true;
    public function execute(...$params)
    {
        $args = [];
        ImportOrdersAgent::register($args, 'N', 1);
    }
}