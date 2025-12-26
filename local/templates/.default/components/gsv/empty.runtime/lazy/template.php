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
/** @var array $templateData */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
$this->setFrameMode(false);
?>
<div class="container-fluid" data-lazy-block="<?= $arParams['BLOCK']?>"<?if($arResult['QUERY_STRING']):?> data-lazy-params="<?= $arResult['QUERY_STRING']?>"<?endif;?>>
    <div class="mt-5 pt-4 pb-3">
        <div class="ttu title">&nbsp;</div>
        <div class="ttu title">&nbsp;</div>
        <div class="ttu title">&nbsp;</div>
    </div>
</div>

<?$this->SetViewTarget('footer-scripts');?>
<script src="<?= $templateFolder ?>/script.js"></script>
<?$this->EndViewTarget();?>
