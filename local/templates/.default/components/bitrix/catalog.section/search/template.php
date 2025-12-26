<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
use \Bitrix\Main\Localization\Loc;

?>
<div class="col-sm-8 col-md-9 col-lg-10 d-sm-flex flex-wrap pb-4">
    <?foreach($arResult["ITEMS"] as $arItem):?>
    <div class="d-inline-flex gap-3 col-sm-6 col-lg-4 col-xxl-3 mt-4 search-item">
        <div class="flex-shrink-0 item-image">
            <a class="db" href="<?=$arItem["DETAIL_PAGE_URL"]?>">
                <?/*img width="90px" src="<?= $arItem['PICTURE']['SRC']?>" alt="<?=$arItem["NAME"]?>"*/?>
                <?= \Gsv\pictures::html($arItem['PICTURE'], $arItem["NAME"], "", true) ?>
            </a>
        </div>
        <div class="d-flex flex-column gap-2">
            <div class="d-flex gap-2 fs-12 c-gray badge-wrap">
                <?/*if($arItem['MORE_COLORS'] > 0):?>
                    <?$add_color = $arItem['MORE_COLORS'];?>
                    <div class="wsn badge">+<?= $arItem['MORE_COLORS'] ?> <?=declOfNum($add_color, ['цвет', 'цвета', 'цветов'])?></div>
                <?endif*/?>
            </div>
            <div class="fs-13 pe-4 item-name">
                <a class="lh-1_25 text-crop-1" href="<?=$arItem["DETAIL_PAGE_URL"]?>">
                    <?=$arItem["NAME"]?>
                </a>
            </div>
            <? $arPrice = $arItem['price']; ?>
            <div class="d-flex gap-2 fs-13 fw-400 item-price">
                <?= $arPrice["price_formatted"]?>
                <?if($arPrice["discount"] > 0):?>
                <div class="c-gray old-price"><?=$arPrice["base_price_formatted"]?></div>
                <?endif?>
            </div>
        </div>
    </div>
    <?endforeach;?>
</div>