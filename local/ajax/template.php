<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
    define('STOP_STATISTICS', true);
    define('NO_KEEP_STATISTIC', 'Y');
    define('NO_AGENT_STATISTIC', 'Y');
    define('DisableEventsCheck', true);
    define('BX_SECURITY_SHOW_MESSAGE', true);
    define('NOT_CHECK_PERMISSIONS', true);

    define('GSV_COMPONENT_VIA_AJAX', true);

    $siteId = isset($_REQUEST['SITE_ID']) && is_string($_REQUEST['SITE_ID']) ? $_REQUEST['SITE_ID'] : '';
    $siteId = mb_substr(preg_replace('/[^a-z0-9_]/i', '', $siteId), 0, 2);
    if (!empty($siteId) && is_string($siteId)) {
        define('SITE_ID', $siteId);
    }

    $siteTemplateId = isset($_REQUEST['SITE_TEMPLATE_ID']) && is_string($_REQUEST['SITE_TEMPLATE_ID']) ? $_REQUEST['SITE_TEMPLATE_ID'] : '';
    $siteTemplateId = trim($siteTemplateId);
    if ($siteTemplateId !== '' && preg_match('/^[a-z0-9_]+$/i', $siteTemplateId)) {
        define('SITE_TEMPLATE_ID', $siteTemplateId);
    }

    require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

    $request = Bitrix\Main\Application::getInstance()->getContext()->getRequest();
    $request->addFilter(new \Bitrix\Main\Web\PostDecodeFilter);

    $componentSalt = "gsv-template-ajax";

    global $APPLICATION;

    $signer = new \Bitrix\Main\Security\Sign\Signer;
    try {
        $signedParamsString = $request->get('signedParams') ?: '';
        $params = $signer->unsign($signedParamsString, $componentSalt);
        $params = unserialize(base64_decode($params), ['allowed_classes' => false]);
    } catch (\Bitrix\Main\Security\Sign\BadSignatureException $e) {
        $APPLICATION->RestartBuffer();
        http_response_code(403);
        die();
    }

    //BEGIN: ADDITIONAL CHECKS
    //Can be enabled/disabled if necessary

    if ($_REQUEST['sessid'] != bitrix_sessid()) {
        $APPLICATION->RestartBuffer();
        http_response_code(401);
        die();
    }

    if (isset($params['siteId']) && $params['siteId'] != SITE_ID) {
        $APPLICATION->RestartBuffer();
        http_response_code(418);
        die();
    }

    if (isset($params['siteTemplateId']) && $params['siteTemplateId'] != SITE_TEMPLATE_ID) {
        $APPLICATION->RestartBuffer();
        http_response_code(418);
        die();
    }
    //END: ADDITIONAL CHECKS

    if (empty($params['COMPONENT_NAME']) || !is_string($params['COMPONENT_NAME'])
        || strlen($params['COMPONENT_NAME'] = trim($params['COMPONENT_NAME'])) <= 0) {
        $APPLICATION->RestartBuffer();
        http_response_code(400);
        die();
    }

    //Probably you need that
    //$params['PARAMS']['IS_AJAX'] = 'Y';

    $componentName = $params['COMPONENT_NAME'];
    $componentTemplate = $params['COMPONENT_TEMPLATE'] ?: '.default';
    $componentParams = $params['PARAMS'] ?: [];

    $APPLICATION->RestartBuffer();

    $APPLICATION->IncludeComponent(
        $componentName,
        $componentTemplate,
        $componentParams
    );

    die();
}
else
{
    //Use this code to form and sign the params
    /**
     * If you need to call this inside a component just write
     * $component = $this;
     * If in class.php add this one (not needed in the component's file component.php)
     * $arParams = $this->arParams;
     * Then you should write these lines whether it be a template or a component
     * $signedParams = $ajaxUrl = '';
     * require $_SERVER['DOCUMENT_ROOT'] . '/local/ajax/template.php';
     * All you need will be in the variables $signedParams & $ajaxUrl
     */
    /**
     * @var array $arParams
     * @var \CBitrixComponent $component
     */
    /** @noinspection PhpUnreachableStatementInspection */
    $params = $arParams;
    foreach ($arParams as $key => $value) {
        if (strpos($key, '~') === 0) {
            $params[substr($key, 1)] = $value;
            unset($params[$key]);
        }
    }

    $sComponentName = $component->__name;
    $sComponentTemplate = $component->getTemplateName();
    $sComponentSalt = "gsv-template-ajax";

    $params = [
        'COMPONENT_NAME' => $sComponentName,
        'COMPONENT_TEMPLATE' => $sComponentTemplate,
        'siteId' => SITE_ID,
        'siteTemplateId' => SITE_TEMPLATE_ID,
        'PARAMS' => $params,
    ];

    $signer = new \Bitrix\Main\Security\Sign\Signer;
    $signedParams = $signer->sign(base64_encode(serialize($params)), $sComponentSalt);

    $ajaxUrl =  '/local/ajax/template.php';

    $ajaxUrl .= '?SITE_ID=' . urlencode(SITE_ID);
    $ajaxUrl .= '&SITE_TEMPLATE_ID=' . urlencode(SITE_TEMPLATE_ID);
    $ajaxUrl .= '&sessid=#sessid#';//Sessid isn't needed if not checked
}