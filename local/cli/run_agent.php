<?php

$_SERVER["DOCUMENT_ROOT"] = realpath(dirname(__FILE__)."/../..");
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define("BX_CAT_CRON", false);
define('NO_AGENT_CHECK', true);
define('NO_EVENT_CHECK', true);
define('BX_CRONTAB_SUPPORT', false);
define('BX_WITH_ON_AFTER_EPILOG', true);
define('BX_NO_ACCELERATOR_RESET', true);
define('GSV_CLI', true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

@set_time_limit(0);
@ignore_user_abort(true);

$arParams = $argv;
array_shift($arParams);

if(count($arParams))
{
    $param = array_shift($arParams);
    if(is_numeric($param)) //Does it work now?
    {
        $param = (array)intval($param);
        while (count($arParams))
        {
            $param[] = intval(array_shift($arParams));
        }
        \CAgent::ExecuteAgents('AND 0 = 1 OR ID IN (' . implode(',', $param) . ')');
    }
    else
    {
        list($agent, $command, $user_id) = explode(':', $param . "::");
        $exec = '\Gsv\Agents\\' . $agent . '::run' . $command;

        $GLOBALS['USER'] = new \CUser();
        $GLOBALS['USER']->Authorize($user_id ?: 1);
        echo call_user_func_array($exec, $arParams);
    }
}

CMain::FinalActions();

//require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");