<?
define('STOP_STATISTICS', true);
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);

if (isset($_REQUEST['site_id']) && is_string($_REQUEST['site_id']))
{
	$siteID = trim($_REQUEST['site_id']);
	if ($siteID !== '' && preg_match('/^[a-z0-9_]{2}$/i', $siteID) === 1)
	{
		define('SITE_ID', $siteID);
	}
}

if (isset($_REQUEST['site_template_id']) && is_string($_REQUEST['site_template_id']))
{
	$siteTemplateId = trim($_REQUEST['site_template_id']);

	if ($siteTemplateId !== '' && preg_match('/^[a-z0-9_]+$/i', $siteTemplateId))
	{
		define('SITE_TEMPLATE_ID', $siteTemplateId);
	}
}

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$request->addFilter(new \Bitrix\Main\Web\PostDecodeFilter);

require_once __DIR__ . '/ajax_class.php';

if(check_bitrix_sessid() && $_REQUEST[\Gsv\Internals\GsvBasketSoaAjax::ACTION_NAME]) {
    \Gsv\Internals\GsvBasketSoaAjax::execute();
}

if (!check_bitrix_sessid() || !$request->isPost())
	return;

$component = dirname(__DIR__);
if(basename($component) == 'templates') {
    //We are in the component's folder?
    $component = dirname($component);
}
$component = basename(dirname($component)) . ':' . basename($component);
$template = basename(__DIR__);

if(strpos($component, 'bitrix:') === 0) {
    $salt = substr($component, 7);
    $salt = str_replace(':', '', $salt);
} else {
    $salt = str_replace(':', '', $component);
}

$params = array();

$signer = new \Bitrix\Main\Security\Sign\Signer;
try
{
    $params = $signer->unsign($request->get('signedParamsString'), $salt);
    $params = unserialize(base64_decode($params), ['allowed_classes' => false]);
}
catch (\Bitrix\Main\Security\Sign\BadSignatureException $e)
{
    die('Bad signature.');
}

$params['IS_AJAX'] = 'Y';

//try
//{
//    $template = $signer->unsign($request->get('template'), $componentSalt);
//}
//catch (Exception $e)
//{
//    $template = '.default';
//}

global $APPLICATION;

$APPLICATION->IncludeComponent(
	$component,
	$template,
	$params
);