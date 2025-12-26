<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
$this->setFrameMode(false);
/** @var array $templateData */

if($arParams['GLOBAL_VARIABLE'])
{
    $signer = new \Bitrix\Main\Security\Sign\Signer;
    $signedTemplate = $signer->sign($templateName, 'sale.basket.basket');
    $signedParams = $signer->sign(base64_encode(serialize($arParams)), 'sale.basket.basket');
    $messages = \Bitrix\Main\Localization\Loc::loadLanguageFile(__FILE__);

    $ajaxUrl = $this->__component->getPath() . '/ajax.php';
    $ajaxUrl .= '?site_id=' . urlencode(SITE_ID);
    $ajaxUrl .= '&site_template_id=' . urlencode(SITE_TEMPLATE_ID);
    $ajaxUrl .= '&via_ajax=Y';
    $ajaxUrl .= '&' . bitrix_sessid_get();
    $ajaxUrl .= '&' . $arParams['ACTION_VARIABLE'] . '=#action#';

    $ajaxRequest = [
        'url' => $ajaxUrl,
        'post' => [
            'template' => $signedTemplate,
            'signedParamsString' => $signedParams,
        ],
    ];

    $arResult['signedTemplate'] = $signedTemplate;
    $arResult['signedParams'] = $signedParams;
    $arResult['messages'] = $messages;
    $arResult['ajaxRequest'] = $ajaxRequest;

    $GLOBALS[$arParams['GLOBAL_VARIABLE']] = [
        'arParams' => $arParams,
        'arResult' => $arResult,
        'templateData' => $templateData,

        'signedTemplate' => $signedTemplate,
        'signedParams' => $signedParams,
        'messages' => $messages,

        'ajaxRequest' => $ajaxRequest,
    ];
}




