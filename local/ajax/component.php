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

    define('GSV_COMPONENT_AJAX_REQUEST', true);

    require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

    \Gsv\Bitrix\ComponentByAjax::applyJsonRequest(false);

    //Possible check
    if($_REQUEST['sessid'] != bitrix_sessid())
    {
        $APPLICATION->RestartBuffer();
        http_response_code(403);
        die();
    }

    global $APPLICATION;

    if(($d = $_POST['PROTECTED_DATA']) || ($d = $_REQUEST['PROTECTED_DATA']) || ($d = $_GET['PROTECTED_DATA']))
    {
        $component = \Gsv\Bitrix\ComponentByAjax::unprotect($d);
    }
    else if(($d = $_POST['ENCRYPTED_DATA']) || ($d = $_REQUEST['ENCRYPTED_DATA']) || ($d = $_GET['ENCRYPTED_DATA']))
    {
        $component = \Gsv\Bitrix\ComponentByAjax::decrypt($d);
    }
    else if(($d = $_POST['SIGNED_DATA']) || ($d = $_REQUEST['SIGNED_DATA']) || ($d = $_GET['SIGNED_DATA']))
    {
        $component = \Gsv\Bitrix\ComponentByAjax::unsign($d);
    }
    else if(($d = $_POST['OPT_DATA']) || ($d = $_REQUEST['OPT_DATA']) || ($d = $_GET['OPT_DATA']))
    {
        $component = \Gsv\Bitrix\ComponentByAjax::restore($d);
    }
    else if(($d = $_POST['SESS_DATA']) || ($d = $_REQUEST['SESS_DATA']) || ($d = $_GET['SESS_DATA']))
    {
        $component = \Gsv\Bitrix\ComponentByAjax::getSessData($d);
    }
    else
    {
        $component = null;
    }

    if(empty($component))
    {
        http_response_code(403);
    }
    //Possible check (but not for OPT_DATA!)
//    elseif($component['sessid'] != bitrix_sessid())
//    {
//        http_response_code(403);
//    }
    else if(!empty($component['COMPONENT_NAME']))// || !empty($component['COMPONENT_CLASS']))
    {
//        if(empty($component['COMPONENT_NAME']))
//        {
//            $componentClass = \CBitrixComponent::includeComponentClass($component['COMPONENT_NAME']);
//            $component['COMPONENT_NAME'] = $componentClass::abc;
//        }

        if(empty($component['COMPONENT_TEMPLATE']))
        {
            $component['COMPONENT_TEMPLATE'] = '.default';
        }
        else if(!is_string($component['COMPONENT_TEMPLATE']))
        {
            http_response_code(403);
            return;
        }

        if(empty($component['PARAMS']))
        {
            $component['PARAMS'] = array();
        }
        else if(!is_array($component['PARAMS']))
        {
            http_response_code(403);
            return;
        }

        ob_start();
        $APPLICATION->IncludeComponent(
            $component['COMPONENT_NAME'],
            $component['COMPONENT_TEMPLATE'],
            $component['PARAMS'],
            false
        );
        $result = ob_get_contents();
        $APPLICATION->RestartBuffer();
        echo $result;
    }
    else if(!empty($component['CLASS']) || !empty($component['CALLABLE']))
    {
        if(!empty($component['CALLABLE']))
        {
            $callable = $component['CALLABLE'];
        }
        elseif(!empty($component['METHOD']))
        {
            $callable = [
                $component['CLASS'],
                $component['METHOD'],
            ];
        }
        else
        {
            $callable = [$component['CLASS']];

            if(!empty($component['ACTION']))
            {
                $callable[] = $component['ACTION'] . 'Action';
            }
            elseif(isset($component['ACTION']))//Yeah, it should be isset(). We can force CLASS::action()
            {
                $callable[] = 'action';
            }
            else if(($d = $_POST['ACTION']) || ($d = $_REQUEST['ACTION']) || ($d = $_GET['ACTION']))
            {
                $callable[] = $d . 'Action';
            }
            else if(($d = $_POST['action']) || ($d = $_REQUEST['action']) || ($d = $_GET['action']))
            {
                $callable[] = $d . 'Action';
            }
            else
            {
                $callable[] = 'action';
            }
        }

        if(!is_callable($callable))
        {
            http_response_code(403);
            return;
        }

        if(!empty($component['DECORATOR']))
        {
            $decorator = $component['DECORATOR'];
            $result = $decorator($callable, $_POST + $_REQUEST + $_GET);
        }
        else
        {
            $result = $callable($_POST + $_REQUEST + $_GET);
        }

        $result = $callable($_POST + $_REQUEST + $_GET);
        $APPLICATION->RestartBuffer();
        header('Content-Type: application/json');
        echo json_encode($result);
    }
    else
    {
        http_response_code(403);
    }

    die();