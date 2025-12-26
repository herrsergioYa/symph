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
<details open data-catalog-area="smart-filter-sort">
    <summary class="btn-link gap-2 pb-3 ttu">Сортировать</summary>
    <div class="filer-block">
        <ul>
            <?foreach($arResult['ITEMS'] as $arItem):?>
            <li><label class="pr py-2 py-lg-0">
                    <input type="radio" class="pa" name="sort" value="<?= $arItem['ID']?>"<?if($arItem['SELECTED']):?> checked<?endif;?>>
                    <i class="db pa"></i>
                    <span><?= $arItem['NAME'] ?></span>
                </label>
            </li>
            <?endforeach;?>
        </ul>
    </div>
</details>
