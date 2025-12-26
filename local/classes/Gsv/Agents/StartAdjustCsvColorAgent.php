<?php

namespace Gsv\Agents;

class StartAdjustCsvColorAgent extends \Gsv\Bitrix\CronAgent
{
    const SKIP_MISSED_EXECUTIONS = true;
    public function execute(...$params)
    {
        $args = [null, false];
        AdjustCsvColorAgent::register($args, 'N', 1);
    }
}