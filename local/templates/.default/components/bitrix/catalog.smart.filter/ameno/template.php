<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
$checked = 0;
//$this->SetViewTarget("filter");
/*?><div class="pt-4"><?*/
?>
    <div class="pr pb-2 filter-toolbar">
        <button class="btn btn-filter btn-sm<?if($arResult['CHECKED']):?> filter-marker<?endif;?>"  data-show-catalog-container="smart-filter-btn">
            <div class="pr ico">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-funnel" viewBox="0 0 16 16">
                    <path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5zm1 .5v1.308l4.372 4.858A.5.5 0 0 1 7 8.5v5.306l2-.666V8.5a.5.5 0 0 1 .128-.334L13.5 3.308V2z"/>
                </svg>
            </div>
            <span><?= \data::get('FILTER_BY') ?></span>
        </button>
        <div class="dn mt-2 border-top products-filter" data-smart-filter-root="<?= $arResult["FORM_ACTION"] ?>" data-show-catalog-container="smart-filter">
            <div class="d-lg-flex align-items-center gap-3 py-3">
                <div class="row flex-fill">
                    <?foreach ($arResult['ITEMS'] as $arItem):?>
                    <?if($arItem['IS_SIZE'] == 'Y'):?>
                    <div class="col-md mb-4 mb-lg-0">
                        <div class="fw-400 wsn mb-2 mb-lg-3"><?= \data::get($arItem['CODE']) ?></div>
                        <div class="d-flex flex-wrap gap-2 filter-sizes" data-smart-filter="sizes">
                            <?foreach ($arItem['VALUES'] as $arValue):?>
                            <?if($arValue['DISABLED'] && !$arValue['CHECKED']): continue; endif;?>
                            <label class="size<?if($arValue['CHECKED']):?> selected<?endif;?>"
                                   data-name="<?= $arValue['CONTROL_NAME']?>"
                                   data-value="<?= $arValue['HTML_VALUE']?>"
                            ><?= $arValue['VALUE'] ?></label>
                            <?endforeach;?>
                        </div>
                        <?if($arItems = $arItem['CHILDREN']):?>
                            <?foreach ($arItems as $k => $arItem):?>
                            <?//if($arItem['IS_SIZE'] == 'Y'):?>
                            <br/>
                            <div class="fw-400 wsn mb-2 mb-lg-3"><?= \data::get($arItem['CODE']) ?></div>
                            <div class="d-flex flex-wrap gap-2 filter-sizes" data-smart-filter="sizes">
                                <?foreach ($arItem['VALUES'] as $arValue):?>
                                    <?if($arValue['DISABLED'] && !$arValue['CHECKED']): continue; endif;?>
                                    <label class="size<?if($arValue['CHECKED']):?> selected<?endif;?>"
                                           data-name="<?= $arValue['CONTROL_NAME']?>"
                                           data-value="<?= $arValue['HTML_VALUE']?>"
                                    ><?= $arValue['VALUE'] ?></label>
                                <?endforeach;?>
                            </div>
                            <?//endif;?>
                            <?endforeach;?>
                        <?endif;?>
                    </div>
                    <?endif;?>
                    <?endforeach;?>
                    <?foreach ($arResult['ITEMS'] as $arItem):?>
                    <?if($arItem['IS_COLOR'] == 'Y'):?>
                    <div class="col-md mb-4 mb-lg-0">
                        <div class="fw-400 wsn mb-2 mb-lg-3"><?= \data::get($arItem['CODE']) ?></div>
                        <div class="d-flex flex-wrap gap-2 filter-colors" data-smart-filter="colors">
                            <?foreach ($arItem['VALUES'] as $arValue):?>
                            <?if($arValue['DISABLED'] && !$arValue['CHECKED']): continue; endif;?>
                            <label class="pr form-check-label">
                                <input class="pa" type="checkbox" name="<?= $arValue['CONTROL_NAME'] ?>" value="<?= $arValue['HTML_VALUE'] ?>"<?if($arValue['CHECKED']):?> checked<?endif;?>>
                                <span class="color" style="--filter-color: <?= $arValue['COLOR']['COLOR_CODE'] ?>"></span>
                            </label>
                            <?endforeach;?>
                        </div>
                    </div>
                    <?endif;?>
                    <?endforeach;?>
                    <?foreach ($arResult['ITEMS'] as $arItem):?>
                    <?if($arItem['IS_CHECKBOX'] == 'Y'):?>
                    <div class="col-md mb-4 mb-lg-0">
                        <div class="fw-400 wsn mb-2 mb-lg-3"><?= \data::get($arItem['CODE']) ?></div>
                        <div class="d-flex flex-wrap check-groups" data-smart-filter="size-cup">
                            <?foreach ($arItem['VALUES'] as $arValue):?>
                            <?if($arValue['DISABLED'] && !$arValue['CHECKED']): continue; endif;?>
                            <label class="pr form-check-label">
                                <input class="pa" type="radio" name="<?= $arValue['CONTROL_NAME'] ?>" value="<?= $arValue['HTML_VALUE'] ?>"<?if($arValue['CHECKED']):?> checked<?endif;?>>
                                <i class="radio"></i>
                                <span><?= $arValue['VALUE'] ?></span>
                            </label>
                            <?endforeach;?>
                        </div>
                        <div class="d-flex flex-wrap check-groups">
                            <?foreach ($arItem['VALUES'] as $arValue):?>
                            <?if($arValue['DISABLED'] && !$arValue['CHECKED']): continue; endif;?>
                            <label class="pr form-check-label">
                                <input class="pa" type="checkbox" name="<?= $arValue['CONTROL_NAME'] ?>" value="<?= $arValue['HTML_VALUE'] ?>"<?if($arValue['CHECKED']):?> checked<?endif;?>>
                                <i class="checkbox"></i>
                                <span><?= $arValue['VALUE'] ?></span>
                            </label>
                            <?endforeach;?>
                        </div>
                    </div>
                    <?endif;?>
                    <?endforeach;?>
                </div>
                <div class="d-flex flex-column flex-md-row flex-lg-column flex-xl-row gap-2 ms-auto">
                    <button class="btn btn-primary btn-180" type="button" data-smart-filter-button="apply"><?= \data::get('FILTER_APPLY') ?></button>
                    <button class="btn btn-secondary btn-180" type="button" data-smart-filter-button="clear"><?= \data::get('FILTER_CLEAR') ?></button>
                </div>
            </div>
        </div>
    </div>