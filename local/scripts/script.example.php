<?php

$_SERVER["DOCUMENT_ROOT"] = realpath(dirname(__FILE__) . "/../..");
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

$siteId = isset($_REQUEST['SITE_ID']) && is_string($_REQUEST['SITE_ID'])? $_REQUEST['SITE_ID'] : '';
$siteId = substr(preg_replace('/[^a-z0-9_]/i', '', $siteId), 0, 2);
if(!empty($siteId) && is_string($siteId))
{
    define('SITE_ID', $siteId);
}
if (isset($_REQUEST['admin_section']) && $_REQUEST['admin_section'] === 'Y')
{
    define('ADMIN_SECTION', true);
}

$siteTemplateId = isset($_REQUEST['SITE_TEMPLATE_ID']) && is_string($_REQUEST['SITE_TEMPLATE_ID']) ? $_REQUEST['SITE_TEMPLATE_ID'] : '';
$siteTemplateId = trim($siteTemplateId);
if ($siteTemplateId !== '' && preg_match('/^[a-z0-9_]+$/i', $siteTemplateId))
{
    define('SITE_TEMPLATE_ID', $siteTemplateId);
}

define("PULL_AJAX_INIT", true);
define("PUBLIC_AJAX_MODE", true);
define("STOP_STATISTICS", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC", "Y");
define("NO_AGENT_CHECK", true);
define("NOT_CHECK_PERMISSIONS", true);
define("DisableEventsCheck", true);
define('BX_SECURITY_SHOW_MESSAGE', true);

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

if(PHP_SAPI == 'cli') {
    @set_time_limit(0);
    @ignore_user_abort(true);
    ini_set('output_buffering', 'Off');
} else if(!$USER->IsAdmin()) {
    $APPLICATION->RestartBuffer();
    http_response_code(403);
    die();
}

$dt = date('Y-m-d_H-i-s');
$utcTimezone = new DateTimeZone("UTC");

if(PHP_SAPI != 'cli') {
    $APPLICATION->RestartBuffer();
    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=\"o$dt.csv\"");
    $bForceDirectInput = true;
} else {
    $bForceDirectInput = true;
}

//\Bitrix\Main\Loader::requireModule('iblock');
//\Bitrix\Main\Loader::requireModule('catalog');
//\Bitrix\Main\Loader::requireModule('sale');

if($bForceDirectInput) {
    while (ob_end_clean()) ;
}

$exitCode = 0;

if(PHP_SAPI == 'cli') {
    exit($exitCode);
}

if($bForceDirectInput) {
    die ();
}

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");