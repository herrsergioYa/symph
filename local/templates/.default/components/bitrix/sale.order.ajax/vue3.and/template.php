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
<? $APPLICATION->SetPageProperty('page_type', "order-page");?>
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
    <div class="container-fluid" id="<?= $arResult['VUE']['ID']?>" v-cloak>
        <div class="row">
            <div class="col-md-6 left-col">
                <div class="fs-13 mw-800 ms-auto mb-3 sign-in">
                    <?/*button class="btn u-line" type="button">Авторизуйтесь</button>, если зарегистрированы.*/?>
                    Оформите <button class="btn u-line" type="button" @click="fastBuy($event)">Быстрый заказ</button> или заполните подробную форму ниже:
                </div>
                <form class="mw-800 ms-auto" @submit="preventDefault($event)">
                    <div class="row mb-5">
                        <h3 class="mb-4">1. Контактные данные</h3>
                        <div class="col-6 input-wrap">
                            <div class="pr">
                                <input id="name" class="form-control" :class="{'input-error': 'FIRST_NAME' in requiredFields}"
                                       v-model="data.model.PROPERTIES.FIRST_NAME" type="text" value="" required>
                                <label class="placeholder" for="name">Имя</label>
                                <span v-if="'FIRST_NAME' in requiredFields"  class="dn pa error-msg">Обязательное поле</span>
                            </div>
                        </div>
                        <div class="col-6 input-wrap">
                            <div class="pr">
                                <input id="surname" class="form-control" :class="{'input-error': 'LAST_NAME' in requiredFields}"
                                       v-model="data.model.PROPERTIES.LAST_NAME" type="text" value="">
                                <label class="placeholder" for="surname">Фамилия</label>
                                <span v-if="'LAST_NAME' in requiredFields"  class="dn pa error-msg">Обязательное поле</span>
                            </div>
                        </div>
                        <div class="col-12 input-wrap">
                            <div class="pr">
                                <input id="phone" class="form-control placeholder-hidden" :class="{'input-error': 'PHONE' in requiredFields}"
                                       v-model="data.model.PROPERTIES.PHONE" type="tel" value="" placeholder="+7 999 999-99-99" required>
                                <label class="placeholder" for="phone">Телефон</label>
                                <span v-if="'PHONE' in requiredFields"  class="dn pa error-msg">Обязательное поле</span>
                            </div>
                        </div>
                        <div class="col-12 input-wrap">
                            <div class="pr">
                                <input id="email" class="form-control" :class="{'input-error': 'EMAIL' in requiredFields}"
                                       v-model="data.model.PROPERTIES.EMAIL"  type="email" value="" autocomplete="email" required>
                                <label class="placeholder" for="email">Email</label>
                                <span v-if="'EMAIL' in requiredFields"  class="dn pa error-msg">Обязательное поле</span>
                            </div>
                        </div>
                        <div class="col-12 input-wrap">
                            <div class="pr">
                                <input id="city" class="form-control" :class="{'input-error': 'CITY' in requiredFields}"
                                       v-model="data.model.PROPERTIES.CITY" type="text" value="" required v-city-popup>
                                <label class="placeholder" for="city">Город</label>
                                <span v-if="'CITY' in requiredFields"  class="dn pa error-msg">Обязательное поле</span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h3 class="mb-2">2. Доставка</h3>
                        <div class="block-radio" v-for="delivery in data.model.DELIVERY" :class="{'selected': data.model.DELIVERY_ID == delivery.ID}">
                            <label class="form-check-label d-flex py-3">
                                <div class="pr radio-group">
                                    <input class="form-check-input" type="radio" name="delivery"
                                            :value="delivery.ID"
                                           v-model="data.model.DELIVERY_ID">
                                    <span class="radio"></span>
                                </div>
                                <div class="w-100">
                                    <span class="label" v-html="delivery.NAME"></span>
                                    <div class="fr fw-400" v-if="delivery.PRICE > 0" v-html="delivery.PRICE_FORMATED"></div>
                                    <div class="fs-13 mt-1 c-gray" v-if="delivery.DESCRIPTION" v-html="delivery.DESCRIPTION"></div>
                                    <div class="fs-13 mt-1 c-gray" v-if="delivery.CALCULATE_DESCRIPTION" v-html="delivery.CALCULATE_DESCRIPTION"></div>
                                    <div class="fs-13 mt-1 c-gray" v-if="delivery.PERIOD_TEXT" v-html="delivery.PERIOD_TEXT"></div>
                                </div>
                            </label>
                            <div class="py-3 input-wrap" v-show="data.model.DELIVERY_ID == delivery.ID && !isSelfDelivery">
                                <div class="d-flex">
                                    <div class="pr flex-fill">
                                        <input id="delivery-address" class="form-control placeholder-hidden" :class="{'input-error': 'ADDRESS' in requiredFields}"
                                               v-model="data.model.PROPERTIES.ADDRESS"  type="text" value="" placeholder="Улица, дом, кв./офис" required>
                                        <label class="placeholder" for="delivery-address">Адрес доставки</label>
                                        <span v-if="'ADDRESS' in requiredFields"  class="dn pa error-msg">Обязательное поле</span>
                                    </div>
                                    <?/*button class="btn fs-13 c-gray border-bottom border-black" type="button">Мои адреса</button*/?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h3 class="mb-2">3. Оплаты</h3>
                        <div class="block-radio" :class="{'selected': data.model.PAY_SYSTEM_ID == paySystem.ID}" v-for="paySystem in data.model.PAY_SYSTEM">
                            <label class="form-check-label d-flex py-3">
                                <div class="pr radio-group">
                                    <input class="form-check-input" type="radio" name="payment"
                                           :value="paySystem.ID"
                                           v-model="data.model.PAY_SYSTEM_ID">
                                    <span class="radio"></span>
                                </div>
                                <div class="w-100">
                                    <div class="fr ms-2 ico-payments" v-if="paySystem.PSA_LOGOTIP">
                                        <img width="58" height="20" :src="paySystem.PSA_LOGOTIP.SRC">
                                        <?/*svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 0 46 16">
                                            <path fill="currentColor" d="M29.098 1.82v12.047h-2.094V3.45h-3.739v10.417h-2.094V1.82h7.927Zm3.507 12.22c-.923 0-1.633-.179-2.129-.537v-1.681c.265.185.554.329.866.433.323.104.703.156 1.142.156.658 0 1.154-.19 1.488-.572.335-.393.543-.93.623-1.612h-3.028v-1.63h3.011c-.196-1.328-.877-1.993-2.042-1.993-.358 0-.716.058-1.073.174a4.115 4.115 0 0 0-.831.364V5.39c.22-.116.513-.214.883-.295.369-.08.813-.121 1.332-.121.646 0 1.212.116 1.697.347.496.22.905.531 1.229.936.334.393.582.872.744 1.438.173.555.26 1.162.26 1.82 0 1.468-.347 2.589-1.04 3.363-.68.774-1.724 1.161-3.132 1.161Zm9.067-10.036c-.831 0-1.46-.208-1.887-.624-.415-.416-.623-.936-.623-1.56h1.73c0 .3.07.52.208.659.15.139.34.208.572.208.219 0 .392-.07.519-.208.127-.15.19-.37.19-.659h1.731c0 .3-.052.584-.156.85a1.89 1.89 0 0 1-.467.693c-.196.196-.45.352-.761.468-.3.116-.653.173-1.056.173Zm-1.523 1.144v5.408l3.15-5.408h1.834v8.719h-1.99v-5.39l-3.15 5.39h-1.835V5.148h1.99Z"></path>
                                            <rect fill="#fc3f1d" width="16" height="16" x="0.867" fill="inherit" rx="8"></rect>
                                            <path fill="#fff" d="M9.994 12.808h1.671v-9.6h-2.43c-2.445 0-3.73 1.257-3.73 3.108 0 1.478.704 2.348 1.961 3.246l-2.182 3.246h1.81l2.43-3.633-.842-.566c-1.022-.69-1.52-1.23-1.52-2.39 0-1.022.719-1.713 2.086-1.713h.746v8.302Z"></path>
                                        </svg*/?>
                                    </div>
                                    <span class="label" v-html="paySystem.NAME"></span>
                                    <div class="fs-13 mt-1 c-gray" v-html="paySystem.DESCRIPTION"></div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="mb-5">
                        <div class="pr">
                            <textarea v-model="data.model.DESCRIPTION" class="mt-2 form-control" placeholder="Комментарий к заказу" maxlength="200" rows="3"></textarea>
                            <div class="c-gray textarea-count"><span class="count">200</span> знаков</div>
                        </div>
                    </div>

                    <div class="d-md-none mb-5">
                        <div class="py-4 promocode-wrap">
                            <div class="d-flex" v-for="coupon in data.model.COUPONS">
                                <div class="w-100 pr">
                                    <input id="promocode" :readonly="coupon.APPLIED" class="form-control"
                                           :class="{'input-error': !!coupon.ERROR }" type="text" v-model="coupon.COUPON">
                                    <label class="placeholder" for="promocode">Промокод</label>
                                    <!-- класс .js-promocode-delete для js -->
                                    <div class="dn pa error-msg" v-if="coupon.ERROR">Промокод не найден</div>
                                </div>
                                <button class="btn fs-13 flex-shrink-0" type="button" v-if="!coupon.APPLIED" @click="applyCoupon(coupon)">Применить</button>
                                <button class="btn fs-13 flex-shrink-0" type="button" v-else @click="removeCoupon(coupon)">Отменить</button>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between pt-3">
                            <div class="fs-13">Скидка</div>
                            <div class="fs-13 fw-400" v-if="data.order.TOTAL.DISCOUNT_PRICE > 0" v-html="data.order.TOTAL.DISCOUNT_PRICE_FORMATED"></div>
                            <div class="fs-13 fw-400" v-else>—</div>
                        </div>

                        <div class="d-flex justify-content-between pt-3">
                            <div class="fs-13">Доставка</div>
                            <div class="fs-13 fw-400" v-if="data.order.TOTAL.DELIVERY_PRICE > 0" v-html="data.order.TOTAL.DELIVERY_PRICE_FORMATED"></div>
                            <div class="fs-13 fw-400" v-else>—</div>
                        </div>

                        <div class="d-flex justify-content-between pt-3">
                            <div class="fw-400">К оплате</div>
                            <div class="fw-400" v-html="data.order.TOTAL.ORDER_TOTAL_PRICE_FORMATED"></div>
                        </div>
                    </div>

                    <div class="col-12">
                        <button class="btn btn-primary btn-240px btn-m100"
                                :class="{'btn-disabled': false}"
                            @click="onConfirmOrder($event)">Оформить заказ</button>
                        <div class="small c-gray tac-sm mt-4">Нажимая на&nbsp;кнопку «Оформить заказ» вы&nbsp;даете согласие&nbsp;на&nbsp;<a class="c-gray u-line" href="" target="_blank">обработку персональных данных</a>.</div>
                    </div>
                </form>
            </div>

            <div class="col-md-6 right-col">
                <div class="pb-lg-5 pe-lg-5 mw-600">
                    <h3 class="">Корзина</h3>
                    <div class="order-cart">
                        <div class="d-flex gap-3 modal-cart-item" v-for="item in data.model.ITEMS">
                            <div class="my-4 flex-shrink-0 modal-cart-img">
                                <a class="db" :href="item.DETAIL_PAGE_URL">
                                    <img width="90px" :src="item.DETAIL_PICTURE_SRC" :alt="item.NAME">
                                </a>
                            </div>
                            <div class="d-flex flex-column gap-2 my-4 w-100 fs-13 modal-cart-info">
                                <div class="d-flex justify-content-between gap-1">
                                    <div class="text-crop-1 item-name">
                                        <a :href="item.DETAIL_PAGE_URL">{{item.NAME}}</a>
                                    </div>
                                </div>
                                <div v-if="item.PRODUCT.COLOR.length" class="c-gray item-size">{{item.PRODUCT.COLOR[0].NAME}} ({{item.OFFER.size.name}})</div>
                                <div v-else class="c-gray item-size">{{item.OFFER.size.name}}</div>
                                <div class="item-count">
                                    <div class="d-inline-flex align-items-center gap-2">
                                        <button class="pr btn-quantity __decrement"
                                                :class="{'btn-disabled': item.QUANTITY < 2, 'o-25': item.QUANTITY < 2}"
                                                title="Убавить" type="button" @click="addQty(item, -1)">
                                            <svg width="7" height="1" viewBox="0 0 7 1" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="7" height="1" fill="currentColor"></rect></svg>
                                        </button>
                                        <span class="tac qty">{{item.QUANTITY}}</span>
                                        <button class="pr btn-quantity __increment"
                                                :class="{'btn-disabled': item.QUANTITY >= item.AVAILABLE_QUANTITY, 'o-25': item.QUANTITY >= item.AVAILABLE_QUANTITY}"
                                                title="Добавить" type="button" @click="addQty(item, +1)">
                                            <svg width="7" height="7" viewBox="0 0 7 7" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M3 4V7H4V4H7V3H4V0H3V3H0V4H3Z" fill="currentColor"></path></svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between mt-auto">
                                    <div class="d-flex flex-shrink-0 gap-2 fw-400 item-price"><div class="c-gray old-price" v-if="item.SUM_BASE > item.SUM_NUM" v-html="item.SUM_BASE_FORMATED"></div><span v-html="item.SUM"></span></div>
                                    <button class="c-gray btn u-line __btn-remove-item"
                                            ___data-fancybox data-src="#cart-confirm-delete"
                                            @click="removeQty(item, $event.target)"
                                    >Удалить</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-none d-md-block">
                        <div class="py-4 promocode-wrap">
                            <div class="d-flex" v-for="coupon in data.model.COUPONS">
                                <div class="w-100 pr">
                                    <input id="promocode" :readonly="coupon.APPLIED" class="form-control input-filled"
                                           :class="{'input-error': !!coupon.ERROR }" type="text" v-model="coupon.COUPON">
                                    <label class="placeholder" for="promocode">Промокод</label>
                                    <!-- класс .js-promocode-delete для js -->
                                    <div class="dn pa error-msg" v-if="coupon.ERROR">Промокод не найден</div>
                                </div>
                                <button class="btn fs-13 flex-shrink-0" type="button" v-if="!coupon.APPLIED" @click="applyCoupon(coupon)">Применить</button>
                                <button class="btn fs-13 flex-shrink-0" type="button" v-else @click="removeCoupon(coupon)">Отменить</button>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between pt-3">
                            <div class="fs-13">Скидка</div>
                            <div class="fs-13 fw-400" v-if="data.order.TOTAL.DISCOUNT_PRICE > 0" v-html="data.order.TOTAL.DISCOUNT_PRICE_FORMATED"></div>
                            <div class="fs-13 fw-400" v-else>—</div>
                        </div>

                        <div class="d-flex justify-content-between pt-3">
                            <div class="fs-13">Доставка</div>
                            <div class="fs-13 fw-400" v-if="data.order.TOTAL.DELIVERY_PRICE > 0" v-html="data.order.TOTAL.DELIVERY_PRICE_FORMATED"></div>
                            <div class="fs-13 fw-400" v-else>—</div>
                        </div>

                        <div class="d-flex justify-content-between pt-3">
                            <div class="fw-400">К оплате</div>
                            <div class="fw-400" v-html="data.order.TOTAL.ORDER_TOTAL_PRICE_FORMATED"></div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <? $this->SetViewTarget("sizetable-modal");?>
    <!-- быстрая покупка -->
    <div class="d-flex flex-column mw-600 side-modal fastbuy-modal" id="fastbuy-modal">
        <div class="d-flex justify-content-between align-items-center pr p-4 top">
            <span>Быстрая покупка</span>
            <button class="pr btn-close js-modal-close" title="Закрыть"></button>
        </div>
        <div class="middle">
            <div class="d-flex flex-column h-100 p-4">
                <div class="mw-480 my-auto pb-5">
                    <div class="fastbuy-form">
                        <div class="input-wrap">
                            <div class="pr">
                                <input id="name" name="first_name" class="form-control" type="text" value="" required>
                                <label class="placeholder" for="name">Имя</label>
                            </div>
                        </div>
                        <div class="input-wrap">
                            <div class="pr">
                                <input id="phone" name="phone" class="form-control placeholder-hidden" type="tel" value="" placeholder="+7 999 999-99-99" required>
                                <label class="placeholder" for="phone">Телефон</label>
                            </div>
                        </div>
                        <div class="mt-5">
                            <button data-buy-button class="btn btn-primary btn-240px btn-disabled">Купить</button>
                            <div class="fs-13 mt-4">Нажимая эту кнопку, вы&nbsp;подтверждаете, что ознакомились&nbsp;с <a class="u-line" href="" target="_blank">политикой конфиденциальности</a>.</div>
                        </div>
                    </div>
                    <!-- после отправки формы класс d-none удалить -->
                    <div class="d-none fastbuy-success-message">
                        <div class="mb-2">Спасибо!</div>
                        <div class="fs-13">Ваш заказ оформлен. Наш менеджер скоро свяжется с&nbsp;вами.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <? $this->EndViewTarget();?>

    <?$this->SetViewTarget('footer-scripts');?>
    <script src="<?= $templateFolder ?>/script.js?v=3"></script>
    <? $this->EndViewTarget();?>
    <?$this->SetViewTarget('header-scripts');?>
    <link rel="stylesheet" href="<?= $templateFolder?>/style.css">
    <?$this->EndViewTarget();?>
    <script>
        (function() {
            let fn = function () {
                window.vApps = window.vApps || {};
                window.vApps["<?= $arResult['VUE']['ID']?>"] = new gsvBasketSoa("#<?= $arResult['VUE']['ID']?>", <?= json_encode($arResult['JS_DATA']) ?>, <?= json_encode($arResult['VUE']) ?>);
            }
            if (document.readyState === "complete" || document.readyState === "interactive") {
                // call on next available tick
                setTimeout(fn, 1);
            } else {
                document.addEventListener("DOMContentLoaded", fn);
            }
        })();
    </script>
    <script type="application/json" class="ecommerce">
        <?= \Gsv\Helpers\Ecommerce::jsonEncode($arResult['ECOMM']) ?>
    </script>
<?endif?>


