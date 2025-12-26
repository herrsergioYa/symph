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

//Possible transform
//\Gsv\Bitrix\ComponentByAjax::applyJsonRequest(false);

//Possible check
//if($_REQUEST['sessid'] != bitrix_sessid())
//{
//    $APPLICATION->RestartBuffer();
//    http_response_code(403);
//    die();
//}

define("GSV_AJAX_API_URL", '/local/ajax/api.php');

$ajax_url = GSV_AJAX_API_URL;
$request = $_SERVER['REQUEST_URI'];

if(strpos($request, $ajax_url) === 0)
{
    $request = substr($request, strlen($ajax_url));
    if(($pos = strpos($request, '?')) !== false)
    {
        $request = substr($request, 0, $pos);
    }
}
else
{
    http_response_code(403);
    die();
}

if(empty($request))
{
    http_response_code(403);
    die();
}

if($_SERVER["REQUEST_METHOD"] != "POST")
{
    http_response_code(405);
    die();
}

$matches = [];

if(preg_match('/^\/([\w]+)\/([\w]+)\/?$/', $request, $matches))
{
    //Controller
    $class = '\\Gsv\\Api\\' . ucfirst($matches[1]) . 'Api';
    $method = lcfirst($matches[2]) . 'Action';

    $callable = [
        $class,
        $method,
    ];
}
elseif(preg_match('/^\/([\w.]+)\/?$/', $request, $matches))
{
    //Controller __invoke
    $callable = [
        '\\Gsv\\Api\\' . ucfirst($matches[1]) . 'Api',
        'action',
    ];
}
elseif(preg_match('/^\/([\w]+)\.js$/', $request, $matches))
{
    //Controller JS
    $ajax_class = '\\Gsv\\Api\\' . ucfirst($matches[1]) . 'Api';
    $ajax_url .= '/' . $matches[1] . '/';
    $controller = ucfirst($matches[1]);

    $callable = null;
}
else
{
    http_response_code(403);
    die();
}

$request = \Gsv\Util\JsonAjaxController::getAllParams();

if(isset($callable) && is_array($callable))
{
    $class = $callable[0];
    $method = $callable[1];

    if(!is_a($class, \Gsv\Api\INotInstantiable::class, true))
    {
        $callable[0] = new $class($request);

        if (is_a($class, \Gsv\Api\IDecorator::class, true))
        {
            $decorator = function ($request) use ($callable)
            {
                return $callable[0]->decorate($request, $callable);
            };
            $callable = $decorator;
            unset($decorator);
        }
    }

    if (is_a($class, \Gsv\Api\IStaticDecorator::class, true))
    {
        $decorator = function ($request) use ($callable, $class)
        {
            return $class::decorateStatic($request, $callable);
        };
        $callable = $decorator;
        unset($decorator);
    }
}

if(is_callable($callable))
{
    $response = $callable($request);
    $APPLICATION->RestartBuffer();
    header('Content-Type: application/json');
    echo json_encode($response);
    die();
}
elseif(!empty($controller) && !empty($ajax_class))
{
    $APPLICATION->RestartBuffer();
    header('Content-Type: application/javascript');
    ?>
    // <script type="application/javascript">
    window.ob<?= $controller ?>Api = <?= \Gsv\Util\JsonAjaxController::writeJs($ajax_url, $ajax_class) ?>
    //</script>
    <?
    die();
}
else
{
    http_response_code(403);
    die();
}

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");