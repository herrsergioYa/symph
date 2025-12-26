<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

?>
<button class="btn btn-link btn-filter-toggle" data-modal="#filter-modal">
    Фильтр
    <svg width="10" height="9" viewBox="0 0 10 9" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M2 8.9407e-08L2 9" stroke="currentColor" stroke-width=".9"/>
        <path d="M8 8.9407e-08L8 9" stroke="currentColor" stroke-width=".9"/>
        <path d="M6 5.5L10 5.5" stroke="currentColor" stroke-width=".9"/>
        <path d="M-7.45058e-08 3.5L4 3.5" stroke="currentColor" stroke-width=".9"/>
    </svg>
</button>

<?$this->SetViewTarget('modal-windows');?>
<!-- фильтр -->
<div class="d-flex flex-column border-left side-modal filter-modal" id="filter-modal" data-smart-filter="<?= $arResult["FORM_ACTION"] ?>">
    <div class="d-flex justify-content-between align-items-center px-4 top">
        <span class="ttu">Фильтры</span>
        <button class="btn btn-link btn-close" type="button" data-modal-close>Закрыть</button>
    </div>
    <div class="px-4 custom-scroll middle" data-_catalog-area="smart-filter">
        <div class="products-filter">
            <?$APPLICATION->IncludeComponent(
                'gsv:empty.runtime',
                'sort',
                Array(
                    'SORT' => $arParams["~SORT"] ?: $arParams["SORT"],
                    'SORT_NAME' => $arParams["~SORT_NAME"] ?: $arParams["SORT_NAME"],
                    'SORT_LIST' => $arParams["~SORT_LIST"] ?: $arParams["SORT_LIST"],
                )
            );?>
            <?foreach ($arResult["ITEMS"] as $arItem):?>
            <?if($arItem['PRICE']) continue;?>
            <details data-catalog-area="smart-filter_<?= $arItem["ID"]?>">
                <summary class="btn-link gap-2 py-3 ttu"><?= $arItem['NAME'] ?></summary>
                <div class="filer-block">
                    <ul<?if(count($arItem["VALUES"]) > 5):?> class="columns"<?endif;?>>
                        <?foreach ($arItem["VALUES"] as $arValue):?>
                        <li>
                            <label class="pr py-2 py-lg-0<?if($arValue['DISABLED']):?> disabled<?endif?>">
                                <input type="checkbox" class="pa"<?if($arValue['CHECKED']):?> checked<?endif?>
                                       name="<?= $arValue['CONTROL_NAME']?>"
                                       value="<?= $arValue['HTML_VALUE']?>">
                                <i class="db pa"></i>
                                <span><?= $arValue['VALUE'] ?></span>
                            </label>
                        </li>
                        <?endforeach;?>
                    </ul>
                </div>
            </details>
            <?endforeach;?>
            <?/*
            <details>
                <summary class="btn-link gap-2 py-3 ttu">Цвет</summary>
                <div class="filer-block">
                    <ul>
                        <li><label class="pr py-2 py-lg-0"><input type="checkbox" class="pa"><i class="db pa"></i><span>Чёрный</span></label></li>
                        <li><label class="pr py-2 py-lg-0"><input type="checkbox" class="pa"><i class="db pa"></i><span>Бежевый</span></label></li>
                        <li><label class="pr py-2 py-lg-0"><input type="checkbox" class="pa"><i class="db pa"></i><span>Жёлтый</span></label></li>
                        <li><label class="pr py-2 py-lg-0 disabled"><input type="checkbox" class="pa"><i class="db pa"></i><span>Зелёный</span></label></li>
                        <li><label class="pr py-2 py-lg-0 disabled"><input type="checkbox" class="pa"><i class="db pa"></i><span>Коричневый</span></label></li>
                        <li><label class="pr py-2 py-lg-0"><input type="checkbox" class="pa"><i class="db pa"></i><span>Красный</span></label></li>
                        <li><label class="pr py-2 py-lg-0"><input type="checkbox" class="pa"><i class="db pa"></i><span>Молочный</span></label></li>
                        <li><label class="pr py-2 py-lg-0"><input type="checkbox" class="pa"><i class="db pa"></i><span>Оранжевый</span></label></li>
                        <li><label class="pr py-2 py-lg-0"><input type="checkbox" class="pa"><i class="db pa"></i><span>Серый</span></label></li>
                        <li><label class="pr py-2 py-lg-0"><input type="checkbox" class="pa"><i class="db pa"></i><span>Синий</span></label></li>
                        <li><label class="pr py-2 py-lg-0"><input type="checkbox" class="pa"><i class="db pa"></i><span>Фиолетовый</span></label></li>
                    </ul>
                </div>
            </details>
            <details>
                <summary class="btn-link gap-2 py-3 ttu">Размер</summary>
                <div class="filer-block">
                    <!-- сделать: если больше 5, то добавить класс columns -->
                    <ul class="columns">
                        <li><label class="pr py-2 py-lg-0"><input type="checkbox" class="pa"><i class="db pa"></i><span>XSmall</span></label></li>
                        <li><label class="pr py-2 py-lg-0"><input type="checkbox" class="pa"><i class="db pa"></i><span>Small</span></label></li>
                        <li><label class="pr py-2 py-lg-0"><input type="checkbox" class="pa"><i class="db pa"></i><span>Tall</span></label></li>
                        <li><label class="pr py-2 py-lg-0"><input type="checkbox" class="pa"><i class="db pa"></i><span>XTall</span></label></li>
                        <li><label class="pr py-2 py-lg-0 disabled"><input type="checkbox" class="pa"><i class="db pa"></i><span>XS (RU 40)</span></label></li>
                        <li><label class="pr py-2 py-lg-0"><input type="checkbox" class="pa"><i class="db pa"></i><span>S (RU 42)</span></label></li>
                        <li><label class="pr py-2 py-lg-0"><input type="checkbox" class="pa"><i class="db pa"></i><span>M (RU 44)</span></label></li>
                        <li><label class="pr py-2 py-lg-0"><input type="checkbox" class="pa"><i class="db pa"></i><span>L (RU 46)</span></label></li>
                        <li><label class="pr py-2 py-lg-0"><input type="checkbox" class="pa"><i class="db pa"></i><span>XL (RU 48)</span></label></li>
                        <li><label class="pr py-2 py-lg-0"><input type="checkbox" class="pa"><i class="db pa"></i><span>One Size</span></label></li>
                    </ul>
                </div>
            </details>
            <details>
                <summary class="btn-link gap-2 py-3 ttu">Размер</summary>
                <div class="filer-block">
                    <!-- сделать: если больше 5, то добавить класс columns -->
                    <ul class="columns">
                        <li><label class="pr py-2 py-lg-0"><input type="checkbox" class="pa"><i class="db pa"></i><span>36</span></label></li>
                        <li><label class="pr py-2 py-lg-0"><input type="checkbox" class="pa"><i class="db pa"></i><span>37</span></label></li>
                        <li><label class="pr py-2 py-lg-0 disabled"><input type="checkbox" class="pa"><i class="db pa"></i><span>38</span></label></li>
                        <li><label class="pr py-2 py-lg-0 disabled"><input type="checkbox" class="pa"><i class="db pa"></i><span>39</span></label></li>
                        <li><label class="pr py-2 py-lg-0"><input type="checkbox" class="pa"><i class="db pa"></i><span>40</span></label></li>
                        <li><label class="pr py-2 py-lg-0"><input type="checkbox" class="pa"><i class="db pa"></i><span>41</span></label></li>
                        <li><label class="pr py-2 py-lg-0"><input type="checkbox" class="pa"><i class="db pa"></i><span>42</span></label></li>
                        <li><label class="pr py-2 py-lg-0"><input type="checkbox" class="pa"><i class="db pa"></i><span>43</span></label></li>
                        <li><label class="pr py-2 py-lg-0"><input type="checkbox" class="pa"><i class="db pa"></i><span>44</span></label></li>
                    </ul>
                </div>
            </details>
            <details>
                <summary class="btn-link gap-2 py-3 ttu">Унисекс</summary>
                <div class="filer-block">
                    <ul>
                        <li><label class="pr py-2 py-lg-0"><input type="checkbox" class="pa"><i class="db pa"></i><span>Да</span></label></li>
                    </ul>
                </div>
            </details>
            <details>
                <summary class="btn-link gap-2 py-3 ttu">Наличие в бутиках</summary>
                <div class="filer-block">
                    <ul>
                        <li><label class="pr py-2 py-lg-0"><input type="checkbox" class="pa"><i class="db pa"></i><span>Москва (Петровка, 17/1)</span></label></li>
                        <li><label class="pr py-2 py-lg-0"><input type="checkbox" class="pa"><i class="db pa"></i><span>Москва (ТЦ «Времена года»)</span></label></li>
                        <li><label class="pr py-2 py-lg-0"><input type="checkbox" class="pa"><i class="db pa"></i><span>Санкт-Петербург</span></label></li>
                        <li><label class="pr py-2 py-lg-0"><input type="checkbox" class="pa"><i class="db pa"></i><span>Екатеринбург</span></label></li>
                        <li><label class="pr py-2 py-lg-0"><input type="checkbox" class="pa"><i class="db pa"></i><span>Алматы</span></label></li>
                        <li><label class="pr py-2 py-lg-0"><input type="checkbox" class="pa"><i class="db pa"></i><span>Интернет-магазин</span></label></li>
                    </ul>
                </div>
            </details>
            */?>
        </div>
    </div>
    <div class="d-flex gap-3 mt-auto p-4 bottom" data-catalog-area="smart-filter-btn">
        <button class="btn btn-100 btn-secondary" data-smart-filter-button="clear">Сбросить</button>
        <button class="btn btn-100 btn-primary" data-smart-filter-button="apply">Показать <span data-smart-filter-count><?if(isset($arResult['ELEMENT_COUNT'])):?>(<?= $arResult['ELEMENT_COUNT'] ?>)<?endif;?></span></button>
    </div>
</div>
<?$this->EndViewTarget();?>
<?$this->SetViewTarget('footer-scripts');?>
<script src="<?= filename_mtime($templateFolder . '/script.js') ?>"></script>
<?$this->EndViewTarget();?>
<?return?>
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