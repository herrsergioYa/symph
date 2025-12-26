<?php

namespace Gsv\Agents;

class StartImportCatalogAgent extends \Gsv\Bitrix\CronAgent
{
    const SKIP_MISSED_EXECUTIONS = true;
    public function execute(...$params)
    {
        $args = [];
        if(isset($params[0])) {
            $args[] = 0;
            $args[] = time() - intval($params[0]);
        }
        ImportCatalogAgent::register($args, 'N', 1);
    }
}