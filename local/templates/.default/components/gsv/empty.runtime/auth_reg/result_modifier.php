<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */

$rootId = $this->randString(24);
$rootId = 'auth_reg-' . $rootId;

$ajaxUrl = '/local/ajax/auth_reg.php?';
$ajaxUrl .= \Gsv\Ajax\AuthRegAjax::buildQuery();

$arResult['VUE'] = [
    'ROOT_ID' => $rootId,
    'URL' => $ajaxUrl,
    //'REQ' => [],
    'MESS' => \data::getAll(),
    'RESEND_INTERVAL_SEC' => 60,
];
$arResult['JS_DATA'] = [
    'mode' => 'sms',
    'step' => 0,
    'phone' => '',
    'email' => '',
];

$this->__component->SetResultCacheKeys(['VUE']);