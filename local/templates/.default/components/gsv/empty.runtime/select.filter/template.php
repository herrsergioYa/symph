<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
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
<div class="select-block relative">
    <select id="select-filter" name="sort" class="caption1 py-2 pl-3 md:pr-20 pr-10 rounded-lg border border-line">
        <?foreach ($arResult['ITEMS'] as $sort => $arSort):?>
            <option value="<?= $sort ?>"<?if($arSort['SELECTED']):?> selected<?endif;?>><?= $arSort['NAME'] ?></option>
        <?endforeach;?>
    </select>
    <i class="ph ph-caret-down absolute top-1/2 -translate-y-1/2 md:right-4 right-2"></i>
</div>
<?$this->SetViewTarget('footer-scripts') ?>
<script src="<?= $templateFolder ?>/script.js"></script>
<?$this->EndViewTarget() ?>
