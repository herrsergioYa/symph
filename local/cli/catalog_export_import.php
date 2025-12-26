<?php

$_SERVER["DOCUMENT_ROOT"] = realpath(dirname(__FILE__)."/../..");
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define("BX_CAT_CRON", true);
define('NO_AGENT_CHECK', true);
define('NO_EVENT_CHECK', true);
define('BX_CRONTAB_SUPPORT', false);
define('BX_WITH_ON_AFTER_EPILOG', true);
define('BX_NO_ACCELERATOR_RESET', true);
define('GSV_CLI', true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

@set_time_limit(0);
@ignore_user_abort(true);

\Bitrix\Main\Loader::requireModule('iblock');
\Bitrix\Main\Loader::requireModule('catalog');

while(ob_end_clean());

foreach($argv as $profileId)
{
    $profileId = intval($profileId);
    if($profileId < 0)
        \CCatalogImport::PreGenerateImport(-$profileId);
    elseif($profileId > 0)
        \CCatalogExport::PreGenerateExport($profileId);
    sleep(1);//break;
}

CMain::FinalActions();

//require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");