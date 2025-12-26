<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
?>
<!-- корзина -->
<div class="d-flex flex-column border-left side-modal cart-modal" id="cart-modal" data-basket-area="modal">
    <div class="d-flex justify-content-between align-items-center px-4 top">
        <span class="ttu" data-basket-area="title">Корзина <?= $arResult['ITEMS_COUNT'] ?></span>
        <button class="btn btn-link btn-close" type="button" data-modal-close>Закрыть</button>
    </div>
    <div class="d-flex flex-column custom-scroll middle" data-basket-area="checkout">
        <?foreach ($arResult["ITEMS"]["AnDelCanBuy"] as $arItem):?>
        <? $arProduct = $arItem['PRODUCT']; ?>
        <? $arOffer = $arItem['OFFER']; ?>
        <div class="d-flex pr lh-1 me-4 cart-item<?if($arItem['CAN_BUY'] === false || $arItem['CAN_BUY'] === 'N'):?> item-soldout" data-cart-soldout="Товар закончился<?endif;?>"
             data-basket-item-id="<?=$arItem['ID']?>" data-product-id="<?=$arItem['PRODUCT_ID']?>">
            <div class="cart-img">
                <?if ($arItem['PHOTO']):?>
                <a class="db" href="<?= $arItem['DETAIL_PAGE_URL'] ?>">
                    <img class="w-100" src="<?= $arItem['PHOTO']['SRC'] ?>" alt="<?= $arOffer['NAME'] ?>">
                </a>
                <?endif;?>
            </div>
            <div class="d-flex flex-column w-100 cart-info">
                <div class="mb-3 pe-4 ttu item-name">
                    <a href="<?= $arItem['DETAIL_PAGE_URL'] ?>"><?= $arProduct['NAME'] ?></a>
                </div>
                <div><?= $arItem['PROP_DESC'] ?></div>
                <div class="d-flex justify-content-between mt-auto item-bottom">
                    <div class="d-flex gap-4 mt-auto item-count">
                        Кол-во
                        <div class="d-inline-flex align-items-center gap-4">
                            <button class="pr btn-quantity decrement" title="Убавить"<?if($arItem['QUANTITY'] <= 1):?> disabled<?endif;?> __data-fancybox data-src="#cart-confirm-delete">
                                <i class="minus"></i>
                            </button>
                            <span class="tac qty"><?= $arItem['QUANTITY'] ?></span>
                            <button class="pr btn-quantity increment" title="Добавить"<?if($arItem['QUANTITY'] >= $arItem['AVAILABLE_QUANTITY']):?> disabled<?endif;?>>
                                <i class="plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="pr tar item-price">
                        <?if($arItem['SUM_DISCOUNT_PRICE'] > 0):?>
                        <div class="mb-1 old-price"><?= $arItem['SUM_FULL_PRICE_FORMATED'] ?></div>
                        <?endif?>
                        <?= $arItem['SUM'] ?>
                    </div>
                </div>
            </div>
            <button class="btn btn-delete pa btn-remove-item" type="button" title="Удалить товар" __data-fancybox data-src="#cart-confirm-delete"></button>
        </div>
        <?endforeach;?>

        <div class="d-flex pr lh-1 me-4 cart-item<?if(!$arItem['CAN_BUY']):?> item-soldout" data-cart-soldout="Товар закончился<?endif;?>">
            <div class="cart-img">
                <a class="db" href=""><img class="w-100" src="temp/products/cart/img-4.jpg" alt=""></a>
            </div>
            <div class="d-flex flex-column w-100 cart-info">
                <div class="mb-3 pe-4 ttu item-name"><a href="">Аcимметричное платье мини из атласа</a></div>
                <div>L | Красный</div>
                <div class="d-flex justify-content-between mt-auto item-bottom">
                    <div class="d-flex gap-4 mt-auto item-count">
                        Кол-во<div class="d-inline-flex align-items-center gap-4"><button class="pr btn-quantity decrement" title="Убавить" data-fancybox data-src="#cart-confirm-delete"><i class="minus"></i></button><span class="tac qty">1</span><button class="pr btn-quantity increment" title="Добавить"><i class="plus"></i></button></div>
                    </div>
                    <div class="pr tar item-price">26 500 RUB</div>
                </div>
            </div>
            <button class="btn btn-delete pa btn-remove-item" type="button" title="Удалить товар" data-fancybox data-src="#cart-confirm-delete"></button>
        </div>
    </div>
    <div class="mt-auto p-4 bottom" data-basket-area="total">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div class="ttu">Итого</div>
            <div class="ttu"><?= $arResult['allSum_FORMATED']?></div>
        </div>
        <a class="btn btn-100 btn-primary" href="<?= SITE_DIR ?>checkout/">Оформить заказ</a>
    </div>
</div>