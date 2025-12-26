<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
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
/** @var CBitrixComponent $component */
$this->setFrameMode(true);
?>

<form class="modal-dialog" id="form-<?= $arResult['AJAX_KEY']?>" method="post" data-popup-form="<?= $arResult['AJAX_KEY']?>">
    <input type="hidden" name="AJAX_KEY" value="<?= $arResult['AJAX_KEY']?>"/>

</form>

