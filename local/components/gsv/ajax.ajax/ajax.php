<?php
define('STOP_STATISTICS', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('DisableEventsCheck', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define('NOT_CHECK_PERMISSIONS', true);

$siteId = isset($_REQUEST['SITE_ID']) && is_string($_REQUEST['SITE_ID']) ? $_REQUEST['SITE_ID'] : '';
$siteId = mb_substr(preg_replace('/[^a-z0-9_]/i', '', $siteId), 0, 2);
if (!empty($siteId) && is_string($siteId))
{
	define('SITE_ID', $siteId);
}

$siteTemplateId = isset($_REQUEST['SITE_TEMPLATE_ID']) && is_string($_REQUEST['SITE_TEMPLATE_ID']) ? $_REQUEST['SITE_TEMPLATE_ID'] : '';
$siteTemplateId = trim($siteTemplateId);
if ($siteTemplateId !== '' && preg_match('/^[a-z0-9_]+$/i', $siteTemplateId))
{
    define('SITE_TEMPLATE_ID', $siteTemplateId);
}

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

$request = Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$request->addFilter(new \Bitrix\Main\Web\PostDecodeFilter);

//$componentName = $componentSalt = basename(dirname(__DIR__)) . ':' . basename(__DIR__);
$componentName = $componentSalt = "gsv:ajax.ajax";
$componentSalt = str_replace(":", '-', $componentSalt);

global $APPLICATION;

$signer = new \Bitrix\Main\Security\Sign\Signer;
try
{
	$signedParamsString = $request->get('signedParams') ?: '';
	$params = $signer->unsign($signedParamsString, $componentSalt);
	$params = unserialize(base64_decode($params), ['allowed_classes' => false]);
}
catch (\Bitrix\Main\Security\Sign\BadSignatureException $e)
{
    $APPLICATION->RestartBuffer();
    http_response_code(403);
	die();
}

//BEGIN: ADDITIONAL CHECKS
//Can be enabled/disabled if necessary

//Only for not-cached (and can break the composite cache)
//if($params['sessid'] != bitrix_sessid())
//{
//    $APPLICATION->RestartBuffer();
//    http_response_code(401);
//    die();
//}
// and an alternative for cached
//if($_REQUEST['sessid'] != bitrix_sessid())
//{
//    $APPLICATION->RestartBuffer();
//    http_response_code(401);
//    die();
//}

if(isset($params['siteId']) && $params['siteId'] != SITE_ID)
{
    $APPLICATION->RestartBuffer();
    http_response_code(418);
    die();
}

if(isset($params['siteTemplateId']) && $params['siteTemplateId'] != SITE_TEMPLATE_ID)
{
    $APPLICATION->RestartBuffer();
    http_response_code(418);
    die();
}

if(isset($params['COMPONENT_NAME']) && $params['COMPONENT_NAME'] != $componentName)
{
    $APPLICATION->RestartBuffer();
    http_response_code(400);
    die();
}
//END: ADDITIONAL CHECKS

$PARAMS = isset($params['PARAMS']) && is_array($params['PARAMS']) ? $params['PARAMS'] : [];
/*$action = $request->get($PARAMS['ACTION_VARIABLE'] ?: 'action');
if (empty($action))
    return;

$PARAMS['ACTION'] = $action;*/
//$PARAMS['AJAX_DATA'] = json_decode($request->get('data'), true);
$PARAMS['IS_AJAX'] = 'Y';

//$componentName = $params['COMPONENT_NAME'] ?: $componentName;
$templateName = $params['COMPONENT_TEMPLATE'] ?: '.default';

$APPLICATION->RestartBuffer();

$APPLICATION->IncludeComponent(
    $componentName,
    $templateName,
    $PARAMS
);

die();