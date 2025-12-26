<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/** @var \GsvAjaxAjax $this */
/** @var array $arParams */
/** @var array $arResult */
/** @var string $componentPath */
/** @var string $componentName */
/** @var string $componentTemplate */
/** @global \CDatabase $DB */
/** @global \CUser $USER */
/** @global \CMain $APPLICATION */

$component = $this;
$template = $this->getTemplate();

if($arParams['IS_AJAX'] == 'Y')
{
    $result = ['success' => 'Y'];

    $APPLICATION->RestartBuffer();
    header("Content-Type: application/json");
    echo json_encode($result);
    die();
}

$arResult['AJAX_URL'] =  $component->GetPath().'/ajax.php';
//$arResult['AJAX_URL'] =  $template->GetFolder().'/ajax.php';

$arResult['AJAX_URL'] .= '?SITE_ID=' . urlencode(SITE_ID);
$arResult['AJAX_URL'] .= '&SITE_TEMPLATE_ID=' . urlencode(SITE_TEMPLATE_ID);
//$arResult['AJAX_URL'] .= '&' . urlencode($arParams['ACTION_VARIABLE'] ?: 'action') . '=#action#';
//$arResult['AJAX_URL'] .= '&sessid=#sessid#';//Sessid for the cached

if(isset($component->arOriginalParams)) {
    $params = $component->arOriginalParams;
} else {
    $params = $arParams;
    foreach ($arParams as $key => $value)
    {
        if (strpos($key, '~') === 0)
        {
            $params[substr($key, 1)] = $value;
            unset($params[$key]);
        }
    }
}

$params = ['PARAMS' => $params];

$sComponentName = $component->__name;
$sComponentTemplate = $component->GetTemplateName();
$sComponentSalt = str_replace(":", '-', $sComponentName);

//$params['COMPONENT_NAME'] = $sComponentName; //Optional
$params['COMPONENT_TEMPLATE'] = $sComponentTemplate; //Not needed if using $template->GetFolder().'/ajax.php';

//Only for not-cached (and can break the composite cache)
//$params['sessid'] = bitrix_sessid();
$params['siteId'] = SITE_ID;
$params['siteTemplateId'] = SITE_TEMPLATE_ID;

$signer = new \Bitrix\Main\Security\Sign\Signer;
$signedParams = $signer->sign(base64_encode(serialize($params)), $sComponentSalt);
$arResult['signedParams'] =  $signedParams;

if($this->startResultCache(false, array(($arParams["CACHE_GROUPS"]==="N"? false: $USER->GetGroups()))))
{
    $this->includeComponentTemplate();
}