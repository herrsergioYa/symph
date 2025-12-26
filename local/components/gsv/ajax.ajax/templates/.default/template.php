<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();/** @var array $arParams */
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var array $templateData */
/** @var string $componentPath */
/** @var GsvAjaxAjax $component */
$this->setFrameMode(true);
?>

<form class="modal-dialog" method="post" action="<?= $arResult['AJAX_URL']?>">
    <input type="hidden" name="SITE_ID" value="<?= SITE_ID ?>"/>
    <?/* For not-cached only ?>
    <?= bitrix_sessid_post() */ ?>
    <input type="hidden" name="signedParams" value="<?= htmlspecialchars($arResult['signedParams']) ?>"/>
</form>

