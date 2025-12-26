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
<? $APPLICATION->SetPageProperty('page-type', "order-page");
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
    $APPLICATION->SetPageProperty('NO_HEADER_ON_CHECKOUT_PAGE', "Y");
    //$APPLICATION->SetPageProperty('show_loader', "Y");
?>
<? ob_start(); ?>
<? $arBasketParams = $arParams['~BASKET'] ?: $arParams['BASKET'];
$arBasketParams = json_decode($arBasketParams, true);
$arBasketParams['GLOBAL_VARIABLE'] = 'arrBasket';?>
<?$APPLICATION->IncludeComponent(
    "gsv:sale.basket.basket",
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
<div class="row h-100" id="<?= $arResult['VUE']['ID'] ?>"<?if($arResult['JS_DATA']['IS_AUTHORIZED']):?> v-cloak<?endif;?>>
    <div class="col-lg-8 d-flex flex-column pb-6 pb-lg-0 order-left">
        <div class="d-flex justify-content-between align-items-center info-bar">
            <a class="py-3 my-n3 ttu" href="<?= SITE_DIR ?>store/">Назад в каталог</a>
            <a class="py-3 my-n3 ttu d-lg-none" href="">Помощь</a>
        </div>

        <div class="row h-100 flex-column">
            <div class="border-top step-1" :class="getTabStyles('auth')" @click="returnToStep('auth')">
                <div class="py-4 ttu tac">1. Авторизация</div>
                <div class="d-flex align-items-center justify-content-center flex-grow-1" v-if="isTabActive('auth')">
                    <div class="mb-5 py-5 pt-lg-0 w-100 mw-480 step-content" id="<?= $arResult['JS_DATA']['ROOT_ID']?>_auth">
                        <?$APPLICATION->IncludeComponent(
                            'gsv:empty.runtime',
                            'auth_reg',
                            Array(
                                'REGISTER' => 'N',
                                'HEADLESS' => 'Y',
                                'ROOT_ID' => $arResult['JS_DATA']['ROOT_ID'] . '_auth',
                            ),
                        );?>
                        <div>
                            Если сообщение так и&nbsp;не&nbsp;поступило, пожалуйста, обратитесь к&nbsp;<button class="btn btn-link u-line" type="button" style="text-transform: none">менеджеру</button>
                            или получите код <button class="btn-link u-line"type="button">через E-mail</button>.
                        </div>
                    </div>
                </div>
            </div>
            <?if($arResult['JS_DATA']['IS_AUTHORIZED']):?>
            <div class="border-top step-2" :class="getTabStyles('delivery')" @click="returnToStep('delivery')">
                <div class="py-4 ttu tac">2. Доставка</div>
                <div class="d-flex align-items-center justify-content-center flex-grow-1"
                     :class="{'d-none': !isTabActive('delivery')}"
                     v-show="isTabActive('delivery')">
                    <div class="w-100 mw-480 oh step-content">
                        <form method="post" action="">
                            <div class="d-flex align-items-center justify-content-between mb-6">
                                <div class="h1">Контактные данные</div>
                                <div>*Обязательные поля</div>
                            </div>
                            <div class="pr mb-6">
                                <input id="name" class="form-control"
                                       :class="getInputStyles('FIRST_NAME')"
                                       type="text" v-model="firstName" required>
                                <label class="placeholder" for="name">Имя*</label>
                                <span class="dn pa error-msg" v-if="isMissing('FIRST_NAME')">Заполните поле</span>
                            </div>
                            <div class="pr mb-6">
                                <input id="phone" class="form-control"
                                       :class="getInputStyles('LAST_NAME')"
                                       type="text" v-model="lastName" required>
                                <label class="placeholder" for="phone">Фамилия*</label>
                                <span class="dn pa error-msg" v-if="isMissing('LAST_NAME')">Заполните поле</span>
                            </div>
                            <div class="pr mb-6">
                                <input id="phone" class="form-control"
                                       :class="getInputStyles('PHONE')"
                                       type="tel" autocomplete="off" value="" v-model="data.model.PROPERTIES.PHONE" required>
                                <label class="placeholder" for="phone">Телефон*</label>
                                <span class="dn pa error-msg" v-if="isMissing('PHONE')">Заполните поле</span>
                            </div>
                            <div class="mb-6">
                                <div class="pr mb-3">
                                    <input id="email" class="form-control"
                                           :class="getInputStyles('EMAIL')"
                                           type="email" autocorrect="off" v-model="data.model.PROPERTIES.EMAIL" autocapitalize="off" autocomplete="off" value="" required>
                                    <label class="placeholder" for="email">E-mail*</label>
                                    <span class="dn pa error-msg" v-if="isMissing('EMAIL')">Заполните поле</span>
                                </div>
                                <label class="pr form-check-label d-inline-flex">
                                    <div>
                                        <input class="form-check-input" type="checkbox" name="" value="" v-model="data.model.PROPERTIES.SUBSCRIBE" checked>
                                        <span class="checkbox"></span>
                                    </div>
                                    <span>Подписаться на&nbsp;рассылку новостей и&nbsp;акций</span>
                                </label>
                            </div>

                            <div class="h1 mb-6 mt-10">Адрес доставки</div>
                            <div class="pr mb-6">
                                <select v-model="data.model.COUNTRY.ID" class="form-select w-100 placeholder-static"
                                        :class="getInputStyles('COUNTRY')">
                                    <option v-for="country in data.order.COUNTRY_LIST" :value="country.ID">{{country.NAME}}</option>
                                </select>
                                <label class="placeholder" for="country">Страна*</label>
                                <span class="dn pa error-msg" v-if="isMissing('COUNTRY')">Заполните поле</span>
                            </div>
                            <div class="row gx-6">
                                <div class="col">
                                    <div class="pr">
                                        <v3-popup-jq  class="form-control" placeholder="<?= \data::get('CITY') ?>"
                                                  v-model="data.model.CITY"
                                                  v-model:text="data.model.PROPERTIES.CITY"
                                                  :url="ajaxCitiesUrl"
                                                  :lang="opt.LANGUAGE_ID"
                                                  :query="ajaxCitiesQuery"
                                                  :class="getInputStyles('CITY')"
                                        ></v3-popup-jq>
                                        <label class="placeholder" for="city">Город*</label>
                                        <span class="dn pa error-msg" v-if="isMissing('CITY')">Заполните поле</span>
                                    </div>
                                </div>
                                <div class="col-4" v-if="!isRussia">
                                    <div class="pr">
                                        <input id="postcode" class="form-control"
                                               :class="getInputStyles('ZIP')"
                                               type="text" v-model="data.model.PROPERTIES.ZIP">
                                        <label class="placeholder" for="postcode">Индекс</label>
                                        <span class="dn pa error-msg" v-if="isMissing('ZIP')">Заполните поле</span>
                                    </div>
                                </div>
                            </div>

                            <div class="h1 mb-6 mt-10">Способ доставки</div>
                            <div class="pr block-radio" v-for="delivery in data.model.DELIVERY" key="'deliv' + delivery.ID">
                                <input :id="'delivery-' + delivery.ID" class="form-check-input" type="radio" name="delivery" :value="delivery.ID" v-model="data.model.DELIVERY_ID">
                                <div class="d-flex flex-vertical align-items-center radio-content" @click="console.log(delivery.ID);data.model.DELIVERY_ID = delivery.ID;console.log(data.model.DELIVERY_ID);">
                                    <label :for="'delivery-' + delivery.ID" class="form-check-label w-100 p-4 pt-3 ms-4">
                                        <div class="pa radio-group">
                                            <span class="radio"></span>
                                        </div>
                                        <div class="dib fs-14" v-html="delivery.NAME"></div>
                                        <div class="fr fs-14" v-if="delivery.PRICE > 0" v-html="delivery.PRICE_FORMATED"></div>
                                        <div class="fr fs-14" v-else>Бесплатно</div>
                                        <div class="mt-3" v-html="delivery.DESCRIPTION"></div>
                                    </label>
                                    <div v-if="delivery.ID == data.model.DELIVERY_ID && isSDEKDelivery">
                                        <div id="IPOLSDEK_injectHere"></div>
                                        <div>
                                            <input type="hidden" :class="getInputStyles('ADDRESS')" readonly value="" :name="'ORDER_PROP_' + data.model.PROPERTIES.SDEK__ADDRESS__ID">
                                            <span v-if="isMissing('ADDRESS')" style="position: relative;" class="dn pa error-msg">Выберите пункт самовывоза<?/*= \data::get('WRONG_STORE') */?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="pr mt-6" v-show="!isSelfDelivery">
                                <input id="address" class="form-control"
                                       :class="getInputStyles('ADDRESS')" v-if="!isUsingDaData"
                                       type="text" value="" v-model="data.model.PROPERTIES.ADDRESS" required>
                                <v3-popup-jq id="address" class="form-control"
                                              v-model="data.model.ADDRESS"
                                              v-model:text="data.model.PROPERTIES.ADDRESS"
                                              :url="ajaxRuAddressUrl"
                                              :lang="opt.LANGUAGE_ID"
                                              :query="ajaxRuAddressQuery"
                                              :class="getInputStyles('ADDRESS')" v-else
                                ></v3-popup-jq>
                                <label class="placeholder" for="address">Адрес доставки *</label>
                                <span class="dn pa error-msg" v-if="isMissing('ADDRESS')">Заполните поле</span>
                            </div>

                            <div class="mt-6">
                                <button class="btn btn-100 btn-primary" @click.prevent.stop="onDeliveryApproved($event)">Перейти к оплате</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="border-y step-3" :class="getTabStyles('payment')" @click="returnToStep('payment')">
                <div class="py-4 ttu tac">3. Оплата</div>
                <div class="d-flex align-items-center justify-content-center flex-grow-1" v-if="isTabActive('payment')">
                    <div class="w-100 mw-480 step-content">
                        <form method="post" action="">
                            <div class="h1 mb-6">Способ оплаты</div>

                            <div class="pr block-radio" v-for="paySystem in data.model.PAY_SYSTEM">
                                <input :id="'payment-' + paySystem.ID" class="form-check-input" type="radio" name="payment-2" :value="paySystem.ID" v-model="data.model.PAY_SYSTEM_ID">
                                <div class="d-flex align-items-center radio-content">
                                    <label :for="'payment-' + paySystem.ID" class="form-check-label w-100 p-4 pt-3 ms-4"
                                           @click="data.model.PAY_SYSTEM_ID = paySystem.ID">
                                        <div class="pa radio-group">
                                            <span class="radio"></span>
                                        </div>
                                        <div class="fs-14">{{paySystem.NAME}}</div>
                                        <div class="mt-3">{{paySystem.DESCRIPTION}}</div>
                                    </label>
                                    <button class="btn btn-link btn-info me-4" type="button"
                                            @click.prevent="onSelectPaySystem(paySystem)"
                                            data-modal="#payment-info-modal" v-if="paySystem.HINT">
                                        <svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <circle cx="7.5" cy="7.5" r="7.05" stroke="currentColor" stroke-width="0.9"></circle>
                                            <path d="M7 4.99609V4H7.87891V4.99609H7ZM7.87891 11.1777H7V5.94824H7.87891V11.1777Z" fill="currentColor"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="mt-5">
                                <div class="pr">
                                    <label class="fs-10 ttu db mb-2" for="order-comment">Комментарий к заказу</label>
                                    <textarea id="order-comment" class="form-control p-3 border" v-model="data.model.DESCRIPTION" rows="2"></textarea>
                                </div>
                            </div>

                            <div class="mt-5" v-if="data.order.USER?.ACCOUNT && data.order.USER.ACCOUNT.CURRENT_BUDGET > 0">
                                <label class="pr form-check-label d-inline-flex">
                                    <div>
                                        <input class="form-check-input" type="checkbox" name="" v-model="useBonuses">
                                        <span class="checkbox"></span>
                                    </div>
                                    <span v-if="data.order.USER?.ACCOUNT">
                                    У вас {{data.order.USER.ACCOUNT.CURRENT_BUDGET}} {{plural_form(data.order.USER.ACCOUNT.CURRENT_BUDGET, 'бонусный балл/бонусных балла/бонусных баллов',
                                        '<?= LANGUAGE_ID ?>')}}. Списать?
                                    </span>
                                </label>
                            </div>

                            <div class="mt-6 pt-6">
                                <template v-for="(coupon, i) in data.model.COUPONS">
                                    <template v-if="i > 0">
                                        <br/>
                                        <br/>
                                    </template>
                                    <div class="pr">
                                        <input :id="'coupon-' + i" class="form-control"
                                               :class="getCouponStyles(coupon)"
                                               type="text" v-model="coupon.COUPON">
                                        <label class="placeholder" :for="'coupon-' + i">Промокод</label>
                                        <button class="btn btn-link pa" type="button" v-if="!coupon.APPLIED"
                                                @click.prevent="applyCoupon(coupon)">Применить</button>
                                        <button class="btn btn-link pa" type="button" v-else
                                                @click.prevent="removeCoupon(coupon)">Сбросить</button>
                                        <?/*span class="dn pa error-msg">Такой промокод не найден!</span*/?>
                                        <span class="dn pa error-msg"  v-if="coupon.ERROR">{{coupon.ERROR}}</span>
                                    </div>
                                </template>
                            </div>

                            <div class="mt-6">
                                <div class="d-flex justify-content-between">
                                    <div class="ttu">Сумма</div>
                                    <div v-html="data.order.TOTAL.PRICE_WITHOUT_DISCOUNT"></div>
                                </div>
                                <div class="d-flex justify-content-between mt-4">
                                    <div class="ttu">Доставка</div>
                                    <div class="ttu" v-if="data.order.TOTAL.DELIVERY_PRICE > 0" v-html="data.order.TOTAL.DELIVERY_PRICE_FORMATED"></div>
                                    <div class="ttu" v-else>Бесплатно</div>
                                </div>
                                <div class="d-flex justify-content-between mt-4" v-if="data.order.TOTAL.BASKET_PRICE_DISCOUNT_DIFF_VALUE > 0">
                                    <div class="ttu">Скидка</div>
                                    <div>− <span v-html="data.order.TOTAL.BASKET_PRICE_DISCOUNT_DIFF"></span></div>
                                </div>
                                <div class="d-flex justify-content-between mt-4">
                                    <div class="ttu">К оплате</div>
                                    <div v-html="data.order.TOTAL.ORDER_TOTAL_PRICE_FORMATED"></div>
                                </div>
                            </div>

                            <div class="mt-6">
                                <button class="btn btn-100 btn-primary" @click.prevent.stop="onPaymentApproved()">Оплатить</button>
                            </div>
                            <div class="mt-4 mw-360">Нажимая на&nbsp;кнопку «Оплатить» вы&nbsp;даёте согласие&nbsp;на&nbsp;<a class="u-line" href="" target="_blank">обработку персональных данных</a>.</div>
                        </form>
                    </div>
                    <Teleport to="#payment-info-modal" v-if="!!paySystemSelected && !!paySystemSelected.HINT">
                        <div class="d-flex justify-content-between align-items-center px-4 top">
                            <span class="ttu" v-html="paySystemSelected.NAME"></span>
                            <button class="btn btn-link btn-close" type="button" data-modal-close>Закрыть</button>
                        </div>
                        <div class="d-flex flex-column middle" v-html="paySystemSelected.HINT.DETAIL_TEXT"></div>
                        <div class="mt-auto p-4 bottom">
                            <button class="btn btn-100 btn-primary"
                                @click="onCheckPaySystem(paySystemSelected)"
                                type="button">Выбрать "{{paySystemSelected['~NAME'] || paySystemSelected['NAME']}}"</button>
                        </div>
                    </Teleport>
                </div>
            </div>
            <?else:?>
            <div class="border-top d-flex flex-column flex-grow-0 border-gray c-gray step-2">
                <div class="py-4 ttu tac">2. Доставка</div>
            </div>
            <div class="border-y d-flex flex-column flex-grow-0 border-gray c-gray step-3">
                <div class="py-4 ttu tac">3. Оплата</div>
            </div>
            <?endif;?>
        </div>
    </div>

    <div class="col-lg-4 d-flex flex-column h-100vh px-0 order-right" id="<?= $arResult['VUE']['ID'] ?>_basket"<?if(!$arResult['JS_DATA']['IS_AUTHORIZED']):?> v-cloak<?endif;?>>
        <div class="d-flex justify-content-center justify-content-lg-between align-items-center px-4 info-bar">
            <span class="ttu">Состав заказа ({{data.order.TOTAL.BASKET_POSITIONS}})</span>
            <!-- демо кнопки -->
            <?/*div class="d-flex flex-wrap justify-content-center gap-2">
                Товар:
                <button class="bg-gray" onclick="addItem()">Добавить</button>
                <button class="bg-gray" onclick="removeItem()">Удалить</button>
            </div*/?>
            <!-- .демо кнопки -->
            <a class="d-none d-lg-block py-3 my-n3 ttu" href="">Помощь</a>
        </div>

        <div class="d-flex flex-column pt-6 custom-scroll h-100 order-cart" ref="basketTable">
            <div class="d-flex pr lh-1 me-4 cart-item" v-for="ROW in data.order.GRID.ROWS">
                <div class="cart-img">
                    <img class="w-100" :src="ROW.data.DETAIL_PICTURE_SRC" :alt="ROW.data.NAME">
                </div>
                <div class="d-flex flex-column w-100 cart-info">
                    <div class="mb-3 pe-4 ttu item-name">{{ROW.data.NAME}}</div>
                    <div class="mb-3">{{ROW.data.PROP_DESC}}</div>
                    <div class="mb-3">Кол-во: {{ROW.data.QUANTITY}}</div>
                    <div class="d-flex justify-content-between mt-auto">
                        <button class="btn btn-link" type="button"
                                @click="onEditItem(ROW)"
                                data-modal="#cart-item-edit-modal">Изменить</button>
                        <div class="pr tar item-price">
                            <div class="mb-1 old-price" v-if="ROW.data.SUM_DISCOUNT_DIFF > 0"
                                 v-html="ROW.data.SUM_BASE_FORMATED"></div>
                            <div>
                                <span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                                <span v-html="ROW.data.SUM"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <button class="btn btn-delete pa btn-remove-item" type="button" title="Удалить товар"
                        @click="onDeleteItem(ROW)" :data-soa-basket-item-id="ROW.data.ID"
                        data-__fancybox data-src="#cart-confirm-delete"></button>
            </div>
        </div>

        <div class="mt-auto p-4 bottom">
            <div class="d-flex justify-content-between align-items-end mb-6">
                <div class="ttu">Итого</div>
                <div class="ttu" v-html="data.order.TOTAL.ORDER_PRICE_FORMATED"></div>
            </div>
            Нужна помощь с заказом?<br>
            Свяжитесь с нами через мессенджер или онлайн-чат<br>
            09:00–21:00 МСК, без выходных
            <br><br>
            <div class="d-flex align-items-center gap-5">
                <a class="btn btn-link fs-10" href="" target="_blank">
                    <svg width="16" height="13" viewBox="0 0 16 13" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M1.32676 5.69346L15.3988 0.691283L13.0333 12.2231L9.15451 9.38676L8.85717 9.16933L8.58528 9.41785L6.41472 11.4019L4.86721 7.15833L4.79551 6.96172L4.59897 6.88986L1.32676 5.69346Z" stroke="currentColor" stroke-width="0.9"></path>
                    </svg>
                    Telegram
                </a>
                <a class="btn btn-link fs-10" href="" target="_blank">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3.74184 12.6835L3.58168 12.5915L3.40305 12.6385L0.589627 13.3794L1.229 10.3229L1.26194 10.1654L1.18759 10.0228C0.716503 9.11893 0.45 8.09125 0.45 7C0.45 3.38254 3.38254 0.45 7 0.45C10.6175 0.45 13.55 3.38254 13.55 7C13.55 10.6175 10.6175 13.55 7 13.55C5.81302 13.55 4.70105 13.2347 3.74184 12.6835Z" stroke="currentColor" stroke-width="0.9"></path>
                        <path d="M5.40241 4.31288C5.32364 4.30853 5.23322 4.30762 5.14306 4.30762C5.05286 4.30762 4.90628 4.34423 4.78221 4.49004C4.65823 4.63599 4.30859 4.98863 4.30859 5.70602C4.30859 6.40896 4.77414 7.08864 4.85661 7.20736L4.8612 7.21395C4.92879 7.31116 5.8154 8.78357 7.17281 9.41487C7.49568 9.56504 7.7478 9.65469 7.94427 9.72197C8.26845 9.83282 8.56345 9.81717 8.79658 9.77973C9.05657 9.73781 9.59719 9.42705 9.70995 9.08654C9.82271 8.74604 9.82271 8.45427 9.78889 8.3934C9.75512 8.33267 9.66487 8.2962 9.52954 8.2232C9.46522 8.18851 9.28112 8.09066 9.09234 7.99226L9.03371 7.96178C8.84514 7.86393 8.66385 7.77212 8.60492 7.74901C8.48085 7.7004 8.39065 7.67601 8.30045 7.82191C8.2102 7.96786 7.95094 8.2962 7.87195 8.3934C7.79305 8.4908 7.71411 8.50302 7.57878 8.43002C7.44346 8.35702 7.0074 8.20306 6.49053 7.70649C6.08826 7.32003 5.81671 6.8426 5.73781 6.6966C5.66652 6.56507 5.71699 6.48799 5.77733 6.42076L5.78718 6.40992L5.79713 6.39925C5.85801 6.33399 5.93241 6.22895 6.00009 6.14392C6.06769 6.0587 6.09034 5.99797 6.13537 5.90062C6.18045 5.80341 6.15794 5.7182 6.12416 5.64529C6.10385 5.60149 5.99831 5.32565 5.89272 5.04899L5.87866 5.01215C5.81306 4.84025 5.74899 4.67197 5.70684 4.56304C5.59705 4.27888 5.48552 4.31727 5.40241 4.31288Z" fill="currentColor"></path>
                    </svg>
                    Whatsapp
                </a>
                <button class="btn btn-link fs-10" type="button">
                    <svg width="13" height="14" viewBox="0 0 13 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12.55 10.8H5.5H5.37094L5.2615 10.8684L1.95 12.9381V11.25V10.8H1.5H0.45V0.7H12.5498L12.55 10.8Z" stroke="currentColor" stroke-width="0.9"></path>
                    </svg>
                    Онлайн–чат
                </button>
            </div>
        </div>
        <Teleport to="#cart-item-edit-modal" v-if="data.edited">
            <div class="d-flex justify-content-between align-items-center px-4 top">
                <span class="ttu">Изменить</span>
                <button class="btn btn-link btn-close" type="button" data-modal-close>Закрыть</button>
            </div>
            <div class="d-flex flex-column middle">
                <div class="mb-6 px-4">
                    <div class="d-flex justify-content-between gap-3 mb-5">
                        <div class="ttu item-name" v-html="data.edited.ROW.data.NAME"></div>
                        <div class="wsn item-price" v-html="data.edited.ROW.data.SUM"></div>
                    </div>
                    <form class="mb-6" id="item-edit-form">
                        <div class="mb-3" v-for="prop in getSkuPropEditedVals(data.edited)" v-show="prop.VALUES.length > 0">
                            <div class="pr select-wrap">
                                <span class="select-label left">{{prop.NAME}}:</span>
                                <span class="select-value" v-html="prop.VALUES_ASSOC[data.edited.CURRENT.PROPS[prop.CODE]].NAME"></span>
                                <select class="w-100 form-select v2 __js-select-native" v-model="data.edited.CURRENT.PROPS[prop.CODE]">
                                    <option :value="val.ID" v-for="val in prop.VALUES"
                                            v-html="val.NAME"></option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="pr select-wrap">
                                <span class="select-label left">Кол-во:</span>
                                <span class="select-value">{{data.edited.CURRENT.QTY}}</span>
                                <select class="w-100 form-select v2 __js-select-native" v-model="data.edited.CURRENT.QTY">
                                    <option :value="qty" v-for="qty in +data.edited.QTY">{{qty}}</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="mt-auto p-4 bottom">
                <button class="btn btn-100 btn-primary" :class="{'btn-disabled': data.edited.OFFERS.length == 0}" form="item-edit-form" @click.prevent="applyQty(data.edited.ROW)">Сохранить</button>
            </div>
        </Teleport>
    </div>
</div>
<?$this->SetViewTarget('modal-windows') ?>
<!-- изменение товара -->
<div class="d-flex flex-column border-left side-modal cart-item-edit-modal" id="cart-item-edit-modal"></div>
<!-- оплата по частям -->
<div class="d-flex flex-column border-left side-modal payment-info-modal" id="payment-info-modal"></div>
<?$this->EndViewTarget() ?>
<?$this->SetViewTarget('footer-scripts') ?>
<script src="<?= $templateFolder ?>/script.js"></script>
<?$this->EndViewTarget() ?>
<?$this->SetViewTarget('head-styles') ?>
<link rel="stylesheet" href="<?= $templateFolder ?>/style.css">
<?$this->EndViewTarget() ?>
<script>
    BX(function() {
        <?if(!$arResult['JS_DATA']['IS_AUTHORIZED']):
            $arResult['VUE']['ID'] .= '_basket';
        endif;?>
        window.vApps = window.vApps || {};
        window.vApps["<?= $arResult['VUE']['ID']?>"] = new gsvBasketSoa("#<?= $arResult['VUE']['ID']?>", <?= json_encode($arResult['JS_DATA']) ?>, <?= json_encode($arResult['VUE']) ?>);
    });
</script>
<?endif;?>
