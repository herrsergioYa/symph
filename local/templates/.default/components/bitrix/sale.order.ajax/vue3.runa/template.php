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
<? //$APPLICATION->SetPageProperty('page-type', "order-page");
$APPLICATION->SetPageProperty('title', \data::get('CHECKOUT_TITLE'));
$APPLICATION->SetTitle(\data::get('CHECKOUT_TITLE')); ?>
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
    //$APPLICATION->SetPageProperty('show_loader', "Y");
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
<div class="checkout-block relative md:pt-[74px] pt-[56px]" id="<?= $arResult['VUE']['ID']?>" v-cloak>
    <div class="content-main flex max-lg:flex-col-reverse justify-between">
        <div class="left flex lg:justify-end w-full">
            <div class="lg:max-w-[716px] flex-shrink-0 w-full lg:pt-20 pt-12 lg:pr-[70px] pl-[16px] max-lg:pr-[16px]">
                <form>
                    <div class="login flex justify-between gap-4">
                        <h4 class="heading4"><?= \data::get('CONTACTS') ?></h4>
                        <?/*a href="<?= SITE_DIR ?>auth/" class="text-button underline">Login here</a*/?>
                    </div>
                    <div>
                        <input v-model="data.model.PROPERTIES.EMAIL" type="email" class="border-line mt-5 px-4 py-3 w-full rounded-lg" placeholder="<?= \data::get('EMAIL') ?>" required="">
                        <?/*div class="flex items-center mt-5">
                            <div class="block-input">
                                <input type="checkbox" name="remember" id="remember">
                                <i class="ph-fill ph-check-square icon-checkbox text-2xl"></i>
                            </div>
                            <label for="remember" class="pl-2 cursor-pointer">Email me with news and offers</label>
                        </div*/?>
                        <span v-if="'EMAIL' in requiredFields" class="soa-field-error"><?= \data::get('WRONG_EMAIL') ?></span>
                    </div>
                    <div class="information md:mt-10 mt-6">
                        <div class="heading5"><?= \data::get('DELIVERY') ?></div>
                        <div class="deli_type mt-5">
                            <div v-for="(delivery, i) in data.model.DELIVERY" class="item flex items-center gap-2 relative px-5 border border-line" :class="{'rounded-t-lg' : i == 0, 'rounded-b-lg' : i == data.model.DELIVERY.length - 1}">
                                <input type="radio" name="deli_type" id="ship_type" class="cursor-pointer" v-model="data.model.DELIVERY_ID" :value="delivery.ID">
                                <label for="ship_type" class="w-full py-4 cursor-pointer" v-html="delivery.NAME"></label>
                                <span class="ph ph-truck text-xl absolute top-1/2 right-5 -translate-y-1/2" v-if="!delivery.IS_SELF_DELIVERY"></span>
                                <span class="ph ph-storefront text-xl absolute top-1/2 right-5 -translate-y-1/2" v-else></span>
                            </div>
                        </div>
                        <div class="form-checkout mt-5">
                            <div class="grid sm:grid-cols-2 gap-4 gap-y-5 flex-wrap">
                                <div class="col-span-full select-block">
                                    <v3-popup-jq  class="border border-line px-4 py-3 w-full rounded-lg" placeholder="<?= \data::get('COUNTRY_REGION') ?>"
                                            v-model="data.model.COUNTRY"
                                            v-model:text="data.model.PROPERTIES.COUNTRY"
                                            :url="ajaxCountriesUrl"
                                            :lang="opt.LANGUAGE_ID"
                                        ></v3-popup-jq>
                                    <?/*select v-model="data.model.COUNTRY_ID" class="border border-line px-4 py-3 w-full rounded-lg" id="region" name="region">
                                        <option value="0"><?= \data::get('COUNTRY_REGION') ?></option>
                                        <option v-for="country in data.order.COUNTRY_LIST" :value="country.ID">{{country.NAME}}</option>
                                    </select>
                                    <i class="ph ph-caret-down arrow-down"></i*/?>
                                    <span v-if="'COUNTRY' in requiredFields" class="soa-field-error"><?= \data::get('WRONG_COUNTRY') ?></span>
                                </div>
                                <div class="">
                                    <input v-model="data.model.PROPERTIES.FIRST_NAME" class="border-line px-4 py-3 w-full rounded-lg" id="firstName" type="text" placeholder="<?= \data::get('FIRST_NAME') ?>" required="">
                                    <span v-if="'FIRST_NAME' in requiredFields" class="soa-field-error"><?= \data::get('WRONG_FIRST_NAME') ?></span>
                                </div>
                                <div class="">
                                    <input v-model="data.model.PROPERTIES.LAST_NAME" class="border-line px-4 py-3 w-full rounded-lg" id="lastName" type="text" placeholder="<?= \data::get('LAST_NAME') ?>" required="">
                                    <span v-if="'LAST_NAME' in requiredFields" class="soa-field-error"><?= \data::get('WRONG_LAST_NAME') ?></span>
                                </div>
                                <div class="col-span-full relative" v-if="!isSelfDelivery">
                                    <input v-model="data.model.PROPERTIES.ADDRESS" class="border-line pl-4 pr-12 py-3 w-full rounded-lg" id="address" type="text" placeholder="<?= \data::get('ADDRESS') ?>" required="">
                                    <span class="ph ph-magnifying-glass text-xl absolute top-1/2 -translate-y-1/2 right-5"></span>
                                    <span v-if="'ADDRESS' in requiredFields" class="soa-field-error"><?= \data::get('WRONG_ADDRESS') ?></span>
                                </div>
                                <div class="col-span-full relative" v-if="!isSelfDelivery">
                                    <input v-model="data.model.PROPERTIES.ADDRESS_2" class="border-line pl-4 pr-12 py-3 w-full rounded-lg" id="apartment" type="text" placeholder="<?= \data::get('ADDRESS_2') ?>" required="">
                                    <span class="ph ph-magnifying-glass text-xl absolute top-1/2 -translate-y-1/2 right-5"></span>
                                </div>
                                <?/*div class="">
                                    <input v-model="data.model.PROPERTIES.CITY" class="border-line px-4 py-3 w-full rounded-lg" id="city" type="text" placeholder="City" required="">
                                </div*/?>
                                <div class="select-block">
                                    <v3-popup-jq  class="border border-line px-4 py-3 w-full rounded-lg" placeholder="<?= \data::get('CITY') ?>"
                                            v-model="data.model.CITY"
                                            v-model:text="data.model.PROPERTIES.CITY"
                                            :url="ajaxCitiesUrl"
                                            :lang="opt.LANGUAGE_ID"
                                            :query="ajaxCitiesQuery"
                                    ></v3-popup-jq>
                                    <?/*select class="border border-line px-4 py-3 w-full rounded-lg" id="state" name="state" v-model="data.model.CITY.ID">
                                        <option value="0">City</option>
                                        <option :value="city.ID" v-for="city in data.order.CITIES" v-html="city.FULL_NAME"></option>
                                    </select>
                                    <i class="ph ph-caret-down arrow-down"></i*/?>
                                    <span v-if="'CITY' in requiredFields" class="soa-field-error"><?= \data::get('WRONG_CITY') ?></span>
                                </div>
                                <div class="">
                                    <input v-model="data.model.PROPERTIES.ZIP" class="border-line px-4 py-3 w-full rounded-lg" id="zipcode" type="text" placeholder="<?= \data::get('ZIP') ?>" required="">
                                    <span v-if="'ZIP' in requiredFields" class="soa-field-error"><?= \data::get('WRONG_ZIP') ?></span>
                                </div>
                            </div>
                            <!-- SDEK -->
                            <div v-show="isSDEKDelivery">
                                <div class="input-wrap">&nbsp;</div>
                                <div id="IPOLSDEK_injectHere"></div>
                                <div>
                                    <input type="hidden" :class="{'input-error': 'ADDRESS' in requiredFields}" readonly value="" :name="'ORDER_PROP_' + data.model.PROPERTIES.SDEK__ADDRESS__ID">
                                    <span v-if="'ADDRESS' in requiredFields" style="position: relative;" class="dn pa error-msg"><?= \data::get('WRONG_STORE') ?></span>
                                </div>
                            </div>
                            <?/*h4 class="heading4 md:mt-10 mt-6">Shipping method</h4>
                            <div class="body1 text-secondary2 py-6 px-5 border border-line rounded-lg bg-surface mt-5">Enter your shipping address to view available shipping methods</div*/?>
                            <div class="payment-block md:mt-10 mt-6">
                                <h4 class="heading4"><?= \data::get('PAYMENT') ?></h4>
                                <p class="body1 text-secondary2 mt-3"><?= \data::get('PAYMENT_SECURE') ?></p>
                                <div class="list-payment mt-5 deli_type">
                                    <div class="item">
                                        <div v-for="(paySystem, i) in data.model.PAY_SYSTEM" class="item type flex items-center justify-between bg-linear p-5 border border-line" :class="{'rounded-t-lg' : i == 0, 'rounded-b-lg' : i == data.model.PAY_SYSTEM.length - 1}">
                                            <input type="radio" name="pay_type" id="ship_type" class="cursor-pointer" checked="" v-model="data.model.PAY_SYSTEM_ID" :value="paySystem.ID">
                                            <strong class="text-title" v-html="paySystem.NAME"></strong>
                                            <span class="ph ph-credit-card text-2xl"></span>
                                        </div>
                                        <?/*div class="form_payment grid grid-cols-2 gap-4 gap-y-5 p-5 rounded-b-lg bg-surface">
                                            <div class="col-span-full relative">
                                                <input class="border-line pl-4 pr-12 py-3 w-full rounded-lg" id="cardNumbers" type="text" placeholder="Card Numbers" required="">
                                                <span class="ph ph-lock-simple text-xl text-secondary absolute top-1/2 -translate-y-1/2 right-5"></span>
                                            </div>
                                            <div class="relative">
                                                <input class="border-line px-4 py-3 w-full rounded-lg" id="expirationDate" type="text" placeholder="Expiration date (MM /YY)" required="">
                                            </div>
                                            <div class="relative">
                                                <input class="border-line pl-4 pr-12 py-3 w-full rounded-lg" id="securityCode" type="text" placeholder="Security code" required="">
                                                <span class="ph ph-question text-xl text-secondary absolute top-1/2 -translate-y-1/2 right-5"></span>
                                            </div>
                                            <div class="col-span-full relative">
                                                <input class="border-line px-4 py-3 w-full rounded-lg" id="cardName" type="text" placeholder="Name On Card" required="">
                                            </div>
                                            <div class="col-span-full flex items-center">
                                                <div class="block-input">
                                                    <input type="checkbox" name="useAddress" id="useAddress">
                                                    <i class="ph-fill ph-check-square icon-checkbox text-2xl"></i>
                                                </div>
                                                <label for="useAddress" class="text-title pl-2 cursor-pointer">Use shipping address as billing address</label>
                                            </div>
                                        </div*/?>
                                    </div>
                                </div>
                            </div>
                            <div class="block-button md:mt-10 mt-6">
                                <button class="button-main w-full tracking-widest" @click="onConfirmOrder($event)"><?= \data::get('CHECKOUT') ?></button>
                            </div>
                        </div>
                    </div>
                </form>
                <div class="copyright caption1 md:mt-20 mt-12 py-3 border-t border-line">©<?= date('Y') ?> <?= \data::get('BRAND') ?>. <?= \data::get('COPYRIGHT') ?>.</div>
            </div>
        </div>
        <div class="right justify-start flex-shrink-0 lg:w-[47%] bg-surface lg:py-20 py-12">
            <div class="lg:sticky lg:top-24 h-fit lg:max-w-[606px] w-full flex-shrink-0 lg:pl-[80px] pr-[16px] max-lg:pl-[16px]">
                <div class="list_prd flex flex-col gap-7">
                    <div class="item flex items-center justify-between gap-6" v-for="item in data.model.ITEMS">
                        <div class="flex items-center gap-6">
                            <div class="bg_img relative flex-shrink-0 w-[100px] h-[100px]">
                                <img :src="item.DETAIL_PICTURE_SRC" :alt="item.PRODUCT_NAME" class="w-full h-full object-cover rounded-lg">
                                <span class="quantity flex items-center justify-center absolute -top-3 -right-3 w-7 h-7 rounded-full bg-black text-white" v-if="item.QUANTITY != 1">{{item.QUANTITY}}</span>
                            </div>
                            <div>
                                <strong class="name text-title" v-html="item.NAME"></strong>
                                <div class="flex items-center gap-2 mt-2">
                                    <span class="ph ph-tag text-secondary" v-if="item.SUM_DISCOUNT_DIFF > 0"></span>
                                    <span class="code text-secondary"><span v-html="item.PROP_VALS"></span> <span v-if="item.SUM_DISCOUNT_DIFF > 0" class="discount">(-<span v-html="item.SUM_DISCOUNT_DIFF_FORMATED"></span>)</span></span>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col gap-1">
                            <del class="caption1 text-secondary text-end org_price" v-html="item.SUM_BASE_FORMATED"></del>
                            <strong class="text-title price" v-html="item.SUM"></strong>
                        </div>
                    </div>
                </div>
                <form class="form_discount flex gap-3 mt-8" v-for="coupon in data.model.COUPONS">
                    <input type="text" :readonly="coupon.APPLIED" placeholder="<?= \data::get('DISCOUNT_CODE') ?>" class="w-full border border-line rounded-lg px-4" v-model="coupon.COUPON" required="">
                    <button v-if="!coupon.APPLIED" :class="{'disabled': !(coupon.COUPON || '').trim() }" class="flex-shrink-0 button-main bg-black" @click="applyCoupon(coupon, $event)"><?= \data::get('DISCOUNT_CODE_APPLY') ?></button>
                    <button v-else class="flex-shrink-0 button-main bg-black coupon-clear" @click="removeCoupon(coupon, $event)"><?= \data::get('DISCOUNT_CODE_REMOVE') ?></button>
                    <span v-if="coupon.ERROR" class="error" style="flex-basis: 100%;">{{coupon.ERROR}}</span>
                    <?/*button class="flex-shrink-0 button-main bg-black">applied</button*/?>
                </form>
                <div class="subtotal flex items-center justify-between mt-8">
                    <strong class="heading6"><?= \data::get('SUBTOTAL') ?></strong>
                    <strong class="heading6" v-html="data.order.TOTAL.ORDER_PRICE_FORMATED"></strong>
                </div>
                <div class="ship-block flex items-center justify-between mt-4">
                    <strong class="heading6"><?= \data::get('SHIPPING') ?></strong>
                    <span class="body1 text-secondary"
                        v-if="!(data.model.DELIVERY_ID > 0)"><?= \data::get('SHIPPING_ADDRESS_NEEDED') ?></span>
                    <span class="body1 text-secondary"
                          v-else-if="data.order.TOTAL.DELIVERY_PRICE > 0"
                          v-html="data.order.TOTAL.DELIVERY_PRICE_FORMATED"></span>
                    <span class="body1 text-secondary"
                          v-else><?= \data::get('FREE') ?></span>
                </div>
                <div class="total-cart-block flex items-center justify-between mt-4">
                    <strong class="heading4"><?= \data::get('TOTAL') ?></strong>
                    <div class="flex items-end gap-2">
                        <span class="body1 text-secondary"><?= CATALOG_CURRENCY ?></span>
                        <strong class="heading4" v-html="data.order.TOTAL.ORDER_TOTAL_PRICE_FORMATED"></strong>
                    </div>
                </div>
                <div class="total-saving-block flex items-center gap-2 mt-4" v-if="data.order.TOTAL.DISCOUNT_PRICE > 0">
                    <span class="ph-bold ph-tag text-xl"></span>
                    <strong class="heading5"><?= \data::get('TOTAL_SAVINGS') ?></strong>
                    <strong class="heading5" v-html="data.order.TOTAL.DISCOUNT_PRICE_FORMATED"></strong>
                </div>
            </div>
        </div>
    </div>
</div>
<?$this->SetViewTarget('footer-scripts') ?>
<script src="<?= $templateFolder ?>/script.js"></script>
<?$this->EndViewTarget() ?>
<?$this->SetViewTarget('head-styles') ?>
<link rel="stylesheet" href="<?= $templateFolder ?>/style.css">
<?$this->EndViewTarget() ?>
<script>
    BX(function() {
        window.vApps = window.vApps || {};
        window.vApps["<?= $arResult['VUE']['ID']?>"] = new gsvBasketSoa("#<?= $arResult['VUE']['ID']?>", <?= json_encode($arResult['JS_DATA']) ?>, <?= json_encode($arResult['VUE']) ?>);
    });
</script>
<?endif;?>
