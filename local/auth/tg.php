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
//define('NEED_AUTH', true);
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

/*gsv_dump([
    '_GET' => $_GET,
    '_POST' => $_POST,
    'body' => file_get_contents('php://input'),
    'headers' => getallheaders(),
], __DIR__ . '/' . date('Y-m-d_H-i-s') . '.log');*/

$obApp = new \Gsv\Bitrix\TelegramAuth();

$auth_data = $_GET;
unset($auth_data['TYPE']);
unset($auth_data['RETURN_TO']);
$user = $obApp->checkTelegramAuthorization($auth_data);
//gsv_dump($auth_data, false, false);

if($user['OK']) {
    if($_REQUEST['TYPE'] == 'BIND') {
        $arRes = $obApp->bindUser($auth_data);
        \LocalRedirect($_REQUEST['RETURN_TO'] ?: SITE_DIR . 'personal/');
    } else if($_REQUEST['TYPE'] == 'AUTH' || empty($_REQUEST['TYPE'])) {
        $arRes = $obApp->authorizeUser($auth_data, true, true);
        \LocalRedirect($_REQUEST['RETURN_TO'] ?: SITE_DIR);
    } else {
        http_response_code(400);
    }
} else if($user['CODE'] == $obApp::FORBIDDEN) {
    http_response_code(403);
} else {
    ?>
    <div><p>Ваш запрос устарел. Пожалуйста, пройдите авторизацию заново.</p></div>
    <?
}

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");