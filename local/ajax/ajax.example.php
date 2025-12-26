<?php

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

//$siteTemplateId = isset($_REQUEST['SITE_TEMPLATE_ID']) && is_string($_REQUEST['SITE_TEMPLATE_ID']) ? $_REQUEST['SITE_TEMPLATE_ID'] : '';
//$siteTemplateId = trim($siteTemplateId);
//if ($siteTemplateId !== '' && preg_match('/^[a-z0-9_]+$/i', $siteTemplateId))
//{
//    define('SITE_TEMPLATE_ID', $siteTemplateId);
//}

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

//Possible transform (already provided by JsonAjaxController's $arParams!)
//\Gsv\Bitrix\JsonAjaxController::applyJsonParams(false);

//Possible check
if($_REQUEST['sessid'] != bitrix_sessid())
{
    $APPLICATION->RestartBuffer();
    http_response_code(403);
    die();
}

//You can call here something like that
//\Gsv\Ajax\Some_Child_Of_JsonAjaxController::execute();
//or do it by hand

$result = [];

$APPLICATION->RestartBuffer();
header("Content-Type: application/json");
echo json_encode($result);
//die();
//or
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");