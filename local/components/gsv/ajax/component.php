<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var \CStdAjaxComponent $this */
/** @var array $arParams */
/** @var array $arResult */
/** @var string $componentPath */
/** @var string $componentName */
/** @var string $componentTemplate */
/** @global \CDatabase $DB */
/** @global \CUser $USER */
/** @global \CMain $APPLICATION */

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

//Some adjustments
$component = $this;
//$componentName is already here
$template = $this->getTemplate();
$templateName = $componentTemplate;

//Fill them in BEFORE the mutator!
//$arResult['JS_DATA'] = [];

//Should we use that ?
$componentName = $component->getName();
$templateName = $template->getName();

if(strpos($componentName, 'bitrix:') === 0) {
    $salt = substr($componentName, 7);
    $salt = str_replace(':', '', $salt);
} else {
    $salt = str_replace(':', '', $componentName);
}

$signer = new \Bitrix\Main\Security\Sign\Signer;
$signedTemplate = $signer->sign($templateName, $salt);
//$params = $arParams;
//foreach ($arParams as $key => $value)
//{
//    if (strpos($key, '~') === 0)
//    {
//        $params[substr($key, 1)] = $value;
//        unset($params[$key]);
//    }
//}
//$signedParams = $signer->sign(base64_encode(serialize($params)), $salt);
$signedParams = $signer->sign(base64_encode(serialize($arParams)), $salt);
//$signedParams = $signer->sign(base64_encode(serialize($component->arOriginalParams)), $salt);
$messages = Loc::loadLanguageFile(__FILE__);

$ajaxUrl = $component->GetPath().'/ajax.php';
// - or -
//$ajaxUrl = $template->GetFolder().'/ajax.php';

$ajaxUrl .= '?' . urlencode($arParams['ACTION_VARIABLE'] ?: 'action') . '=#action#';
$ajaxUrl .= '&sessid=#sessid#';//In a runtime component it can be set in the AJAX_REQ
$rand = $this->randString(14);

$arResult['OPT'] = [
    'AJAX_URL' => $ajaxUrl,
    'AJAX_REQ' => [
        'site_id' => SITE_ID,
        'site_template_id' => SITE_TEMPLATE_ID,
        'signedParamsString' => $signedParams,
        'template' => $signedTemplate,
        //'sessid' => bitrix_sessid(),//Shouldn't be cached!
        'data' => json_encode($arResult['JS_DATA']),
    ],
    'ROOT_ID' => 'ajax-' . $rand,
    'MESSAGES' => $messages,
];
//or (if possible! The query shouldn't be too long)
$ajaxUrl .= '&' . http_build_query([
        'site_id' => SITE_ID,
        'site_template_id' => SITE_TEMPLATE_ID,
        'signedParamsString' => $signedParams,
        'template' => $signedTemplate,
        //'sessid' => bitrix_sessid(),//Shouldn't be cached!
        //'data' => json_encode($arResult['JS_DATA']), - probably it shouldn't be here
    ]);
$arResult['AJAX_URL'] = $ajaxUrl;