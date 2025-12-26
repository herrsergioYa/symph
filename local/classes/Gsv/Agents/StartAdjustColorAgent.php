<?php

namespace Gsv\Agents;

class StartAdjustColorAgent extends \Gsv\Bitrix\CronAgent
{
    const SKIP_MISSED_EXECUTIONS = true;
    public function execute(...$params)
    {
        $args = [null, false];
        AdjustColorAgent::register($args, 'N', 1);
    }
}