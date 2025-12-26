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

define("PULL_AJAX_INIT", true);
define("PUBLIC_AJAX_MODE", true);
define("STOP_STATISTICS", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC", "Y");
define("NO_AGENT_CHECK", true);
define("NOT_CHECK_PERMISSIONS", true);
define("DisableEventsCheck", true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define('B24APP_default_AUTH', true);
define('NEED_AUTH', true);
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

$b24app = \Gsv\Bitrix\Bitrix24\Bitrix24App::getInstance();

if($_REQUEST['code'])
{
    $b24app->requestAndAcceptAccessToken($_REQUEST['code']);
}
elseif($USER->IsAdmin())
{
    LocalRedirect($b24app->getAuthorizePage());
    die();
}
else
{
    LocalRedirect('/');
    die();
}

$user = $b24app->userCurrent(true);

echo json_encode($user);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");