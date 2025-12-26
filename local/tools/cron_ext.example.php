<?php

$ALLOW_SITE_OVERRIDE_CLI = true;
$ALLOW_SITE_OVERRIDE_WEB = true;
$AUTH_KEY = '545t4ghhrhrbn6h5etr64654gcTGDG';

$isCli = PHP_SAPI == 'cli';

if($isCli) {
    //$_SERVER["DOCUMENT_ROOT"] = realpath(dirname(__FILE__)."/../..");
    if($ALLOW_SITE_OVERRIDE_CLI && ($siteId = $argv[1])) {
        $siteId = substr(preg_replace('/[^a-z0-9_]/i', '', $siteId), 0, 2);
        if (!empty($siteId) && is_string($siteId)) {
            define('SITE_ID', $siteId);
        }
    }
    if($ALLOW_SITE_OVERRIDE_CLI && ($siteTemplateId = $argv[2])) {
        $siteTemplateId = trim($siteTemplateId);
        if ($siteTemplateId !== '' && preg_match('/^[a-z0-9_]+$/i', $siteTemplateId)) {
            define('SITE_TEMPLATE_ID', $siteTemplateId);
        }
    }
    if($ALLOW_SITE_OVERRIDE_CLI && ($admin = $argv[3])) {
        if ($admin === 'admin') {
            define('ADMIN_SECTION', true);
        }
    }
} elseif($_REQUEST['KEY'] !== $AUTH_KEY) {
    http_response_code(403);
    die();
} else if($ALLOW_SITE_OVERRIDE_WEB) { //Allows to override the parameters?
    $siteId = isset($_REQUEST['SITE_ID']) && is_string($_REQUEST['SITE_ID']) ? $_REQUEST['SITE_ID'] : '';
    $siteId = substr(preg_replace('/[^a-z0-9_]/i', '', $siteId), 0, 2);
    if (!empty($siteId) && is_string($siteId)) {
        define('SITE_ID', $siteId);
    }
    if (isset($_REQUEST['admin_section']) && $_REQUEST['admin_section'] === 'Y') {
        define('ADMIN_SECTION', true);
    }

    $siteTemplateId = isset($_REQUEST['SITE_TEMPLATE_ID']) && is_string($_REQUEST['SITE_TEMPLATE_ID']) ? $_REQUEST['SITE_TEMPLATE_ID'] : '';
    $siteTemplateId = trim($siteTemplateId);
    if ($siteTemplateId !== '' && preg_match('/^[a-z0-9_]+$/i', $siteTemplateId)) {
        define('SITE_TEMPLATE_ID', $siteTemplateId);
    }
}

//Default values
//define('SITE_ID', 's1');
//define('ADMIN_SECTION', false);
//define('SITE_TEMPLATE_ID', 'moozzg');

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
define('GSV_CLI', $isCli);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

@set_time_limit(0);
@ignore_user_abort(true);

// YOUR CODE ---------- //

$APPLICATION->RestartBuffer();
if(!$isCli) {
    http_response_code(200);
    header('Content-Type: text/csv');
    header("Content-Disposition: attachment; filename=\"filename.csv\"");
}

// WRITE SOMETHING ---------- //

CMain::FinalActions();

//require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");