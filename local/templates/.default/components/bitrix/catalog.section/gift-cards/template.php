<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

?>
<div class="container-fluid">
    <div class="row" id="<?= $arResult['JS_DATA']['ROOT_ID'] ?>">
        <div class="col-lg-8 order-lg-2">
            <div class="mx-n3 product-images">
                <img class="d-lg-none" src="https://mongokhto.ru/work/walk-of-shame/temp/products/gift-card/photo-1-mob.jpg" alt="Подарочная карты WOS" title="Подарочная карты WOS">
                <img class="d-none d-lg-block" src="https://mongokhto.ru/work/walk-of-shame/temp/products/gift-card/photo-1.jpg" alt="Подарочная карты WOS" title="Подарочная карты WOS">
            </div>
        </div>

        <div class="col-lg-2 d-lg-flex flex-column justify-content-between left-col">
            <div class="sticky">
                <div class="d-flex flex-column align-items-center justify-content-center lh-1_2 tac middle">
                    <h1 class="mt-1 mt-lg-0 product-title">Подарочная карта WOS</h1>
                    &nbsp; <!-- спецом для распорки -->
                </div>
                <div class="d-none d-lg-block w-100 mw-440 mx-auto tac bg-white bottom">
                    <a class="btn btn-link" href="https://api.whatsapp.com/send?phone=79777856520&text=%D0%97%D0%B4%D1%80%D0%B0%D0%B2%D1%81%D1%82%D0%B2%D1%83%D0%B9%D1%82%D0%B5%2C%20" target="_blank" rel="noreferrer">Поддержка</a>
                </div>
            </div>
        </div>

        <div class="col-lg-2 d-lg-flex flex-column justify-content-between order-lg-3 right-col">
            <div class="sticky">
                <ul class="d-flex flex-lg-column mw-440 mx-auto middle tac product-sizes gift-card-nominals" data-product-sel>
                    <?foreach ($arResult['ITEMS'] as $arItem):?>
                    <li>
                        <label class="pr size">
                            <input type="radio" class="pa" name="size" value="<?= $arItem['ID'] ?>"<?if($arResult['OFFER_ID_SELECTED'] == $arItem['ID']):?> checked<?endif;?>>
                            <span><?= $arItem['PRICE']['BASE_PRICE_FORMATED'] ?></span>
                        </label>
                    </li>
                    <?endforeach;?>
                </ul>

                <div class="d-flex flex-row-reverse flex-lg-column w-100 mw-440 mx-auto bg-white bottom btns-group">
                    <button class="btn btn-100 btn-link btn-more" data-modal="#details-modal">Детали</button>
                    <button class="btn btn-100 btn-primary" data-href="<?= $arResult['ADD_URL_TEMPLATE'] ?>" data-btn-add-to-cart
                            data-choose-an-offer-text="Выберите номинал"
                            data-out-of-stock-text="Закончилась"
                            data-add-to-cart-text="Купить"
                            data-go-to-cart-text="В корзине">Купить</button>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mx-auto recommendation-products">
            <h2 class="d-none d-lg-block pt-8 pb-2">Создай образ</h2>
            <div class="row row-cols-2 row-cols-lg-3 no-gutters products-wrap">
                <div class="col">
                    <a class="db mb-4 pb-2 product-card" href="product.html">
                        <div class="mb-1 pr product-images">
                            <img class="" src="temp/products/catalog/photo-1.jpg" alt="">
                        </div>
                        <div class="px-3 lh-1_2 product-bottom">
                            <div class="fs-9 ttu name">Эластичная водолазка</div>
                            <div class="fs-9 price-wrap"><span class="me-2 wsn c-gray old-price">15&nbsp;600 ₽</span>12&nbsp;000 ₽</div>
                        </div>
                    </a>
                </div>
                <div class="col">
                    <a class="db mb-4 pb-2 product-card" href="">
                        <div class="mb-1 pr product-images">
                            <img class="" src="temp/products/catalog/photo-2.jpg" alt="">
                        </div>
                        <div class="px-3 lh-1_2 product-bottom">
                            <div class="fs-9 ttu name">Миди юбка расшитая кольцами</div>
                            <div class="fs-9 price-wrap">75&nbsp;000 ₽</div>
                        </div>
                    </a>
                </div>
                <div class="col">
                    <a class="db mb-4 pb-2 product-card" href="">
                        <div class="mb-1 pr product-images">
                            <img class="" src="temp/products/catalog/photo-3.jpg" alt="">
                        </div>
                        <div class="px-3 lh-1_2 product-bottom">
                            <div class="fs-9 ttu name">Джинсовая куртка с вышивкой</div>
                            <div class="fs-9 price-wrap">45&nbsp;000 ₽</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-8 mx-auto recommendation-products">
            <h2 class="pt-8 pb-2">Аксессуары</h2>
            <div class="row row-cols-2 row-cols-lg-3 no-gutters products-wrap">
                <div class="col">
                    <a class="db mb-4 pb-2 product-card" href="product.html">
                        <div class="mb-1 pr product-images">
                            <img class="" src="temp/products/catalog/photo-1.jpg" alt="">
                        </div>
                        <div class="px-3 lh-1_2 product-bottom">
                            <div class="fs-9 ttu name">Эластичная водолазка</div>
                            <div class="fs-9 price-wrap"><span class="me-2 wsn c-gray old-price">15&nbsp;600 ₽</span>12&nbsp;000 ₽</div>
                        </div>
                    </a>
                </div>
                <div class="col">
                    <a class="db mb-4 pb-2 product-card" href="">
                        <div class="mb-1 pr product-images">
                            <img class="" src="temp/products/catalog/photo-2.jpg" alt="">
                        </div>
                        <div class="px-3 lh-1_2 product-bottom">
                            <div class="fs-9 ttu name">Миди юбка расшитая кольцами</div>
                            <div class="fs-9 price-wrap">75&nbsp;000 ₽</div>
                        </div>
                    </a>
                </div>
                <div class="col">
                    <a class="db mb-4 pb-2 product-card" href="">
                        <div class="mb-1 pr product-images">
                            <img class="" src="temp/products/catalog/photo-3.jpg" alt="">
                        </div>
                        <div class="px-3 lh-1_2 product-bottom">
                            <div class="fs-9 ttu name">Джинсовая куртка с вышивкой</div>
                            <div class="fs-9 price-wrap">45&nbsp;000 ₽</div>
                        </div>
                    </a>
                </div>

                <div class="col">
                    <a class="db mb-4 pb-2 product-card" href="">
                        <div class="mb-1 pr product-images">
                            <img class="" src="temp/products/catalog/photo-4.jpg" alt="">
                        </div>
                        <div class="px-3 lh-1_2 product-bottom">
                            <div class="fs-9 ttu name">Пижамные шорты</div>
                            <div class="fs-9 price-wrap">15&nbsp;000 ₽</div>
                        </div>
                    </a>
                </div>
                <div class="col">
                    <a class="db mb-4 pb-2 product-card" href="">
                        <div class="mb-1 pr product-images">
                            <img class="" src="temp/products/catalog/photo-5.jpg" alt="">
                        </div>
                        <div class="px-3 lh-1_2 product-bottom">
                            <div class="fs-9 ttu name">Дутая пижамная рубашка</div>
                            <div class="fs-9 price-wrap">48&nbsp;000 ₽</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script data-catalog-area="init-event">
    BX.ready(function() {
        window.apps = window.apps || {};
        window.apps['<?= $arResult['JS_DATA']['ROOT_ID'] ?>'] = new gsvGiftCard(<?= json_encode($arResult['JS_DATA']) ?>, window.basket);
    })
</script>

<?$this->SetViewTarget('footer-scripts');?>
<script src="<?= filename_mtime($templateFolder . '/script.js') ?>"></script>
<?$this->EndViewTarget();?>


<? if (false):?>
    <div class="h-100 container-fluid">
        <div class="h-100 row" id="<?= $arResult['JS_DATA']['ROOT_ID'] ?>">
            <?if($arResult['UF_PHOTO']):?>
            <div class="col-lg-6 px-0 tac mb-3 mb-lg-0">
                <div class="h-100 product-images-wrap">
                    <div class="h-100 product-images gift-card-images">
                        <?foreach($arResult['UF_PHOTO'] as $arPhoto):?>
                        <img class="object-fit" src="<?= $arPhoto['SRC'] ?>" alt="<?= $arResult['NAME'] ?>" title="<?= $arResult['NAME'] ?>">
                        <?endforeach;?>
                    </div>
                </div>
            </div>
            <?endif;?>

            <div class="col-lg-5 col-xl mx-auto">
                <div class="mw-440 mx-auto mt-lg-5 pt-lg-3 sticky product-info">
                    <div class="d-flex justify-content-between mb-6">
                        <h1 class="product-title"><?= $arResult['NAME'] ?></h1>
                    </div>

                    <div class="mb-3">
                        <div class="pr select-wrap">
                            <span class="select-label">Выбрать сумму</span>
                            <select class="w-100 form-select v2" data-product-sel>
                                <?foreach ($arResult['ITEMS'] as $arItem):?>
                                <option value="<?= $arItem['ID'] ?>"<?if($arResult['OFFER_ID_SELECTED'] == $arItem['ID']):?> selected<?endif;?>><?= $arItem['PRICE']['BASE_PRICE_FORMATED'] ?></option>
                                <?endforeach;?>
                            </select>
                        </div>
                    </div>

                    <?/*div class="mb-3">
                        <div class="pr select-wrap">
                            <span class="select-label left">Сумма:</span>
                            <span class="select-value">3 000 RUB</span>
                            <select class="w-100 form-select v2 js-select-native">
                                <option value="3000" selected>3 000 RUB</option>
                                <option value="5000">5 000 RUB</option>
                                <option value="7000">7 000 RUB</option>
                                <option value="10000">10 000 RUB</option>
                                <option value="15000">15 000 RUB</option>
                                <option value="20000">20 000 RUB</option>
                                <option value="30000">30 000 RUB</option>
                                <option value="40000">40 000 RUB</option>
                                <option value="50000">50 000 RUB</option>
                            </select>
                        </div>
                    </div*/?>

                    <!-- демка js select -->
                    <!-- <div class="mb-3"> -->
                    <!-- <div class="pr select-wrap"> -->
                    <!-- <select class="c-select w-100 form-select v2"> -->
                    <!-- <option value="3000">3 000 RUB</option> -->
                    <!-- <option value="5000">5 000 RUB</option> -->
                    <!-- <option value="7000">7 000 RUB</option> -->
                    <!-- <option value="10000">10 000 RUB</option> -->
                    <!-- <option value="15000">15 000 RUB</option> -->
                    <!-- <option value="20000">20 000 RUB</option> -->
                    <!-- <option value="30000">30 000 RUB</option> -->
                    <!-- <option value="40000">40 000 RUB</option> -->
                    <!-- <option value="50000">50 000 RUB</option> -->
                    <!-- </select> -->
                    <!-- <span class="select-label">Выбрать сумму</span> -->
                    <!-- </div> -->
                    <!-- </div> -->

                    <div class="d-flex flex-column gap-3 mb-6">
                        <button class="btn btn-100 btn-primary" data-href="<?= $arResult['ADD_URL_TEMPLATE'] ?>" data-btn-add-to-cart
                                data-choose-an-offer-text="Выберите номинал"
                                data-out-of-stock-text="Закончилась"
                                data-add-to-cart-text="В корзину"
                                data-go-to-cart-text="В корзине"
                                type="button">В корзину</button>
                    </div>

                    <!-- <div class="mb-6 product-description"> -->
                    <!-- Если у&nbsp;вас нет возможности выбрать подарок самостоятельно, лучший и&nbsp;беспроигрышный вариант&nbsp;&mdash; приобрести электронную подарочную карту.  -->
                    <!-- После оплаты указанному адресату на&nbsp;почту придет поздравительная открытка с&nbsp;уникальным промокодом, который равен сумме оплаченного дарителем номинала.  -->
                    <!-- Подарочной картой можно воспользоваться не&nbsp;только при оформлении заказа на&nbsp;сайте ushatava.com, но&nbsp;и&nbsp;в&nbsp;любом из&nbsp;бутиков Ushatava. -->
                    <!-- </div> -->

                    <div class="d-flex flex-column pb-5">
                        <button class="btn btn-link btn-arrow py-4" type="button" data-modal="#details-modal">Детали</button>
                        <button class="btn btn-link btn-arrow py-4" type="button" data-modal="#conditions-modal">Подробные условия использования</button>
                        <button class="btn btn-link btn-arrow py-4" type="button" data-modal="#consultation-modal">Задать вопрос про товар</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?endif;?>
