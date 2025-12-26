<?php

namespace Gsv\Agents;

class StartImportUsersAgent extends \Gsv\Bitrix\CronAgent
{
    const SKIP_MISSED_EXECUTIONS = true;
    public function execute(...$params)
    {
        $args = [];
        ImportUsersAgent::register($args, 'N', 1);
    }
}