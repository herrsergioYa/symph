<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();?>
<?/** @var array $arParams */
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
/** @var SaleOrderAjax $component */
$this->setFrameMode(false);?>
<? $APPLICATION->SetPageProperty('page-type', "order-page");?>
<?
$context = \Bitrix\Main\Application::getInstance()->getContext();
$request = $context->getRequest();
$server = $context->getServer();
?>
<?$APPLICATION->IncludeComponent(
    "bitrix:main.include",
    "",
    array(
        "SECTION_ID" => SIGNATURES_SECTION_ID_CHECKOUT,
        "AREA_FILE_SHOW" => "file",
        "AREA_FILE_SUFFIX" => "inc",
        "PATH" => SITE_DIR."content/signatures.php"
    )
);?>
<?
if (strlen($request->get('ORDER_ID')) > 0):
    require __DIR__ . '/confirm.php';
elseif($arResult['SHOW_EMPTY_BASKET']):
    require __DIR__ . '/empty.php';
else:
    $APPLICATION->SetPageProperty('show_loader', "Y");
?>
<? ob_start(); ?>
<? $arBasketParams = $arParams['~BASKET'] ?: $arParams['BASKET'];
$arBasketParams = json_decode($arBasketParams, true);
$arBasketParams['GLOBAL_VARIABLE'] = 'arrBasket';?>
<?$APPLICATION->IncludeComponent(
    "bitrix:sale.basket.basket",
    "none",
    $arBasketParams
);?>
<? $arResult['BASKET'] = $GLOBALS['arrBasket'];?>
<? ob_end_clean(); ?>
<?
    $arResult["BASKET"] = $arResult['BASKET']['arResult'];
    $arResult['JS_DATA']["BASKET"] = $arResult['BASKET']['JS_DATA'];
    $arResult['JS_DATA']["COUPON_LIST"] = $arResult['BASKET']['COUPON_LIST'];

    $arResult['VUE']['BASKET_AJAX'] = $arResult['BASKET']['ajaxRequest'];
    $arResult['VUE']['MESSAGES'] += $arResult['BASKET']['messages'];
    if(is_array($GLOBALS['DATA'])) {
        $arResult['VUE']['MESSAGES']['MESS'] = $GLOBALS['DATA'];
    }

    foreach ($arResult["BASKET"]['ITEMS'] as $type => $arItems) {
        foreach ($arItems as $arItem) {
//            if($arItem['CAN_BUY'] !== 'Y' && $arItem['CAN_BUY'] !== true) {
//                continue;
//            }
            if(!isset($arResult['JS_DATA']['GRID']['ROWS'][$arItem['ID']])) {
                continue;
            }
            $arTarget = &$arResult['JS_DATA']['GRID']['ROWS'][$arItem['ID']]['data'];
            $arTarget['AVAILABLE_QUANTITY'] = $arItem['AVAILABLE_QUANTITY'];
            unset($arTarget);
        }
    }

    if($arParams['IS_AJAX'] == 'Y')
    {
        if(method_exists(\CDeliverySDEK::class, 'onAjaxAnswer'))
        {
            $arTemp = [
                'order' => $arResult['JS_DATA'],
            ];
            \CDeliverySDEK::onAjaxAnswer($arTemp);
            unset($arTemp['order']);
            $arResult['JS_DATA']['SDEK'] = $arTemp;
            unset($arTemp);
        }

        $APPLICATION->RestartBuffer();;
        header('Content-Type: application/json');
        echo json_encode($arResult['JS_DATA']);
        die();
    }

    ?>
<div class="container-xl bg-white my-xl-5">
    <div class="row px-xl-4" id="<?= $arResult['VUE']['ID']?>" v-cloak>
        <div class="col-md-6 my-5">
            <div class="d-flex justify-content-between align-items-baseline mb-3">
                <div class="fs-20 fw-400"><?= \data::get('CONTACT') ?></div>
                <?/*div><a class="fs-13 u-line" href="#">Log in</a></div*/?>
            </div>
            <form class="row order-form" @submit="preventDefault($event)">
                <div class="input-wrap">
                    <div class="pr">
                        <input id="email" class="form-control" :class="{'input-error': 'EMAIL' in requiredFields}" type="email" v-model="data.model.PROPERTIES.EMAIL" placeholder="<?= \data::get('EMAIL') ?>" required>
                        <span v-if="'EMAIL' in requiredFields" class="dn pa error-msg"><?= \data::get('WRONG_EMAIL') ?></span>
                    </div>
                    <label class="form-check-label fs-13 mt-3">
                        <input class="form-check-input" type="checkbox" name="" value="">
                        <span class="checkbox"></span>
                        <span class="label"><?= \data::get('SUBSCRIBE_ME') ?></span>
                    </label>
                </div>
                <div class="col-6 input-wrap">
                    <div class="pr">
                        <input id="name" class="form-control" :class="{'input-error': 'FIRST_NAME' in requiredFields}" type="text" value="" placeholder="<?= \data::get('FIRST_NAME') ?>" v-model="data.model.PROPERTIES.FIRST_NAME" required>
                        <span v-if="'FIRST_NAME' in requiredFields" class="dn pa error-msg"><?= \data::get('WRONG_NAME') ?></span>
                    </div>
                </div>
                <div class="col-6 input-wrap">
                    <div class="pr">
                        <input id="surname" class="form-control" :class="{'input-error': 'LAST_NAME' in requiredFields}" type="text" placeholder="<?= \data::get('LAST_NAME') ?>" v-model="data.model.PROPERTIES.LAST_NAME" value="" required>
                        <span v-if="'LAST_NAME' in requiredFields" class="dn pa error-msg"><?= \data::get('WRONG_SURNAME') ?></span>
                    </div>
                </div>
                <div class="input-wrap">
                    <div class="pr">
                        <input id="phone" class="form-control" :class="{'input-error': 'PHONE' in requiredFields}" type="tel" placeholder="<?= \data::get('PHONE') ?>" v-model="data.model.PROPERTIES.PHONE" value="" required>
                        <span v-if="'PHONE' in requiredFields" class="dn pa error-msg"><?= \data::get('WRONG_PHONE') ?></span>
                    </div>
                </div>
                <div class="fs-20 fw-400 mb-3"><?= \data::get('DELIVERY') ?></div>
                <div class="col-12 input-wrap">
                    <div class="pr">
                        <select id="country" name="country" class="form-control" v-model="data.model.COUNTRY_ID">
                            <option hidden value="0"><?= \data::get('COUNTRY_REGION') ?></option>
                            <option v-for="country in data.order.COUNTRY_LIST_DEFAULT" :value="country.ID">{{country.NAME}}</option>
                            <?/*option value="">Россия</option>
                            <option value="">Казахстан</option>
                            <option value="" disabled>Беларусь</option>
                            <option value="">Армения</option>
                            <option value="">Грузия</option>
                            <option value="">США</option>
                            <option value="">Германия</option*/?>
                        </select>
                    </div>
                </div>
                <div class="input-wrap" v-show="!isSelfDelivery">
                    <div class="pr">
                        <input id="address" class="form-control" :class="{'input-error': 'ADDRESS' in requiredFields}" type="text" placeholder="<?= \data::get('WRONG_ADDRESS') ?>" v-model="data.model.PROPERTIES.ADDRESS" value="" required>
                        <span v-if="'ADDRESS' in requiredFields" class="dn pa error-msg"><?= \data::get('WRONG_ADDRESS') ?></span>
                    </div>
                </div>
                <div class="input-wrap" v-show="!isSelfDelivery">
                    <div class="pr">
                        <input id="address" class="form-control" :class="{'input-error': 'ADDRESS_2' in requiredFields}" type="text" placeholder="<?= \data::get('ADDRESS_2') ?>" v-model="data.model.PROPERTIES.ADDRESS_2"  value="">
                        <span v-if="'ADDRESS_2' in requiredFields" class="dn pa error-msg"><?= \data::get('WRONG_ADDRESS_2') ?></span>
                    </div>
                </div>
                <div class="col-6 input-wrap">
                    <div class="pr">
                        <input id="postcode" class="form-control" :class="{'input-error': 'ZIP' in requiredFields}" type="text" placeholder="<?= \data::get('ZIP') ?>" v-model="data.model.PROPERTIES.ZIP" value="" required>
                        <span v-if="'ZIP' in requiredFields" class="dn pa error-msg"><?= \data::get('WRONG_ZIP') ?></span>
                    </div>
                </div>
                <div class="col-6 input-wrap">
                    <div class="pr">
                        <input id="city" class="form-control" :class="{'input-error': 'CITY' in requiredFields}" type="text" placeholder="<?= \data::get('CITY') ?>" data-___v-model="data.model.PROPERTIES.CITY" value="" required v-city-popup="">
                        <?/*select id="city" class="form-control" v-city-popup=""></select*/?>
                        <span v-if="'CITY' in requiredFields" class="dn pa error-msg"><?= \data::get('WRONG_CITY') ?></span>
                    </div>
                </div>

                <div class="fs-20 fw-400 mb-3"><?= \data::get('SHIPPING_METHOD') ?></div>
                <div class="input-wrap">
                    <div class="fs-13 p-3 bg-gray shipping-method-warning" v-if="!isAddressEntered">
                        <i class="fal fa-exclamation-triangle"></i> <?= \data::get('SHIPPING_NEEDS_ADDRESS') ?>
                    </div>
                    <div class="mt-3 shipping-method" v-for="delivery in data.model.DELIVERY" :class="{'disabled':!isAddressEntered}">
                        <label class="form-check-label">
                            <input class="form-check-input" type="radio" name="deliveryOptions" :value="delivery.ID" v-model="data.model.DELIVERY_ID">
                            <span class="radio"></span>
                            <span class="label">
                                <span v-html="delivery.NAME"></span> - <span v-html="delivery.PRICE > 0 ? delivery.PRICE_FORMATED : 'FREE'"></span>
                                <br/>
                                <template v-if="delivery.CALCULATE_DESCRIPTION">
                                <?= \data::get('ESTIMATED_DELIVERY') ?>: <span v-html="delivery.CALCULATE_DESCRIPTION"></span>
                                </template>
                                <?/*2-3 working day delivery (Standard Fastest Delivery) — €10.00<br>
                                Estimated delivery: 31 October 2024*/?>
                            </span>
                        </label>
                    </div>
                    <div v-show="isSDEKDelivery">
                        <div class="input-wrap">&nbsp;</div>
                        <div id="IPOLSDEK_injectHere"></div>
                        <div>
                            <input type="hidden" :class="{'input-error': 'ADDRESS' in requiredFields}" readonly value="" :name="'ORDER_PROP_' + data.model.PROPERTIES.SDEK__ADDRESS__ID">
                            <span v-if="'ADDRESS' in requiredFields" style="position: relative;" class="dn pa error-msg"><?= \data::get('WRONG_STORE') ?></span>
                        </div>
                    </div>
                </div>

                <div class="fs-20 fw-400 mb-1"><?= \data::get('PAYMENT') ?></div>
                <div class="fs-13 c-gray"><?= \data::get('PAYMENT_SECURITY') ?></div>

                <div class="my-4" v-for="paySystem in data.model.PAY_SYSTEM">
                    <label class="form-check-label">
                        <input class="form-check-input" type="radio" name="paymentOptions" v-model="data.model.PAY_SYSTEM_ID" :value="paySystem.ID" checked>
                        <span class="radio"></span>
                        <span class="label" v-html="paySystem.NAME"></span>
                    </label>
                </div>

                <div class="d-lg-none mt-4 order-promocode">
                    <div class="d-flex gap-2 promocode-wrap" v-for="coupon in data.model.COUPONS">
                        <div class="pr w-100">
                            <input id="promocode" class="form-control" :class="{'input-success': coupon.APPLIED, 'input-error': coupon.ERROR}" type="text" placeholder="<?= \data::get('PROMOCODE') ?>" v-model="coupon.COUPON" :readonly="coupon.APPLIED">
                            <span class="dn pa error-msg" v-if="coupon.ERROR"><?= \data::get('WRONG_PROMOCODE') ?></span>
                        </div>
                        <button class="btn btn-primary py-0" type="button" v-if="coupon.APPLIED" @click="removeCoupon(coupon)"><?= \data::get('RESET') ?></button>
                        <button class="btn btn-secondary py-0" type="button" v-else-if="coupon.COUPON.trim().length > 0" @click="applyCoupon(coupon)"><?= \data::get('APPLY') ?></button>
                        <button class="btn btn-secondary py-0 btn-disabled" type="button" v-else><?= \data::get('APPLY') ?></button>
                    </div>
                </div>

                <div class="d-lg-none mt-5 order-total">
                    <div class="fs-14 d-flex justify-content-between mb-2">
                        <div><?= \data::get('SUBTOTAL') ?> • {{data.order.TOTAL.BASKET_POSITIONS}} {{plural_form(data.order.TOTAL.BASKET_POSITIONS, <?= json_encode(\data::get('ITEM_CNT')) ?>, opt.LANGUAGE_ID)}}</div>
                        <div v-html="data.order.TOTAL.ORDER_PRICE_FORMATED"></div>
                    </div>
                    <div class="fs-14 d-flex justify-content-between mb-2">
                        <div><?= \data::get('DELIVERY') ?></div>
                        <div v-if="data.order.TOTAL.DELIVERY_PRICE > 0" v-html="data.order.TOTAL.DELIVERY_PRICE_FORMATED"></div>
                        <div v-else><?= \data::get('FREE') ?></div>
                    </div>
                    <div class="fs-20 fw-500 d-flex justify-content-between mt-3">
                        <div><?= \data::get('TOTAL') ?></div>
                        <div v-html="data.order.TOTAL.ORDER_TOTAL_PRICE_FORMATED"></div>
                    </div>
                </div>

                <div class="mt-5">
                    <button class="btn btn-primary btn-100" :class="{'btn-loading':isConfirming}" @click="onConfirmOrder($event)"><?= \data::get('CHECKOUT') ?></button>
                    <div class="fs-13 c-gray mt-3 tac">
                        <?= \data::get('AGREEMENT') ?>
                    </div>
                </div>
            </form>
        </div>
        <div class="col-md-6 col-lg-4 mt-md-5 mb-5 mx-auto">
            <div class="fs-20 fw-400 mb-3"><?= \data::get('BASKET') ?></div>
            <div class="pr order-cart-wrap">
                <div class="order-cart">
                    <div class="d-flex gap-3 modal-cart-item" v-for="item in data.model.ITEMS">
                        <div class="my-4 flex-shrink-0 modal-cart-img">
                            <a class="db" :href="item.DETAIL_PAGE_URL"><img width="90px" :src="item.DETAIL_PICTURE_SRC" :alt="item.NAME"></a>
                        </div>
                        <div class="d-flex flex-column gap-2 my-4 w-100 modal-cart-info">
                            <div class="d-flex justify-content-between gap-1">
                                <div class="fw-400 ttu text-crop-1 item-name"><a :href="item.DETAIL_PAGE_URL" v-html="item.NAME"></a></div>
                                <div class="d-flex flex-shrink-0 gap-2 fw-400 item-price"><div class="c-gray old-price" v-if="item.SUM_BASE > item.SUM_NUM" v-html="item.SUM_BASE_FORMATED"></div><span v-html="item.SUM"></span></div>
                            </div>
                            <?foreach ($arResult['JS_DATA']['SKU_PROPS'] as $skuProp):?>
                            <div class="item-size" v-if="item.PROPS.<?= $skuProp ?>">
                                <?= \data::get($skuProp) ?>:
                                <select v-if="item.edited" v-model="item.VAR.<?= $skuProp ?>.VALUE_XML_ID">
                                    <option v-for="variant in getSkuVariants(item, '<?= $skuProp ?>')" :value="variant.PROPS.<?= $skuProp ?>.VALUE_XML_ID">{{variant.PROPS.<?= $skuProp ?>.VALUE}}</option>
                                    <!--option v-for="size in data.order.SIZES" :value="size.XML_ID">{{size.NAME}}</option-->
                                </select>
                                <span v-else-if="item.VAR" v-html="item.VAR.<?= $skuProp ?>.VALUE"></span>
                                <span v-else v-html="item.PROPS.<?= $skuProp ?>.VALUE"></span>
                            </div>
                            <?endforeach;?>
                            <div class="item-qty">
                                <?= \data::get('QUANTITY') ?>:
                                <select v-if="/*false &&*/ item.edited" v-model="item.VAR_QUANT">
                                    <option v-for="i in getQtys(item)" :value="i">{{i}}</option>
                                    <option v-if="item.AVAILABLE_QUANTITY < item.VAR_QUANT" :value="item.VAR_QUANT">{{item.VAR_QUANT}}</option>
                                </select>
                                <template v-else-if="typeof item.VAR_QUANT !== 'undefined'">
                                {{item.VAR_QUANT}}
                                </template>
                                <template v-else>
                                {{item.QUANTITY}}
                                </template>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mt-auto">
                                <div v-if="item.edited == false"><button class="btn u-line btn-edit-item" type="button" @click="editQty(item)"><?= \data::get('EDIT') ?></button></div>
                                <div v-else><button class="btn u-line btn-edit-item" type="button" @click="applyQty(item)"><?= \data::get('APPLY') ?></button></div>
                                <div class="ms-auto"><button class="btn u-line btn-remove-item" type="button" @click="removeQty(item)"><?= \data::get('REMOVE') ?></button></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-none d-lg-block mt-4 order-promocode">
                <div class="d-flex gap-2 promocode-wrap" v-for="coupon in data.model.COUPONS">
                    <div class="pr w-100">
                        <input id="promocode" class="form-control" :class="{'input-success': coupon.APPLIED, 'input-error': coupon.ERROR}" type="text" placeholder="<?= \data::get('PROMOCODE') ?>" v-model="coupon.COUPON" :readonly="coupon.APPLIED">
                        <span class="dn pa error-msg" v-if="coupon.ERROR"><?= \data::get('WRONG_PROMOCODE') ?></span>
                    </div>
                    <button class="btn btn-primary py-0" :class="{'btn-loading':isUpdatingCoupon}" type="button" v-if="coupon.APPLIED" @click="removeCoupon(coupon)"><?= \data::get('RESET') ?></button>
                    <button class="btn btn-secondary py-0" :class="{'btn-loading':isUpdatingCoupon}" type="button" v-else-if="coupon.COUPON.trim().length > 0" @click="applyCoupon(coupon)"><?= \data::get('APPLY') ?></button>
                    <button class="btn btn-secondary py-0 btn-disabled" :class="{'btn-loading':isUpdatingCoupon}" type="button" v-else><?= \data::get('APPLY') ?></button>
                </div>
            </div>

            <div class="d-none d-lg-block mt-4 order-total">
                <div class="fs-14 d-flex justify-content-between mb-2">
                    <div><?= \data::get('SUBTOTAL') ?> • {{data.order.TOTAL.BASKET_POSITIONS}} {{plural_form(data.order.TOTAL.BASKET_POSITIONS, <?= json_encode(\data::get('ITEM_CNT')) ?>, opt.LANGUAGE_ID)}}</div>
                    <div v-html="data.order.TOTAL.ORDER_PRICE_FORMATED"></div>
                </div>
                <div class="fs-14 d-flex justify-content-between mb-2">
                    <div><?= \data::get('DELIVERY') ?></div>
                    <div v-if="data.order.TOTAL.DELIVERY_PRICE > 0" v-html="data.order.TOTAL.DELIVERY_PRICE_FORMATED"></div>
                    <div v-else><?= \data::get('FREE') ?></div>
                </div>
                <div class="fs-20 fw-500 d-flex justify-content-between mt-3">
                    <div><?= \data::get('TOTAL') ?></div>
                    <div v-html="data.order.TOTAL.ORDER_TOTAL_PRICE_FORMATED"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    $(function() {
        window.vApps = window.vApps || {};
        window.vApps["<?= $arResult['VUE']['ID']?>"] = new gsvBasketSoa("#<?= $arResult['VUE']['ID']?>", <?= json_encode($arResult['JS_DATA']) ?>, <?= json_encode($arResult['VUE']) ?>);
    });
</script>
<?endif?>


