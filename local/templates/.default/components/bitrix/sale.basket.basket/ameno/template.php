<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$list = [];
//if ($_REQUEST["mime"] == "json") {
if ($arParams['IS_JSON'] == 'Y') {
    global $json;
    ob_start();
}
?>
    <div class="d-flex flex-column mw-500 side-modal cart-modal" id="cart-modal">
        <div class="d-flex justify-content-between align-items-center pr p-4 top" data-cart-area="title">
            <span class="ttu fw-400"><?= \data::get('BASKET') ?> (<?= $arResult['ORDERABLE_BASKET_ITEMS_COUNT'] ?>)</span>
            <button class="pr btn-close" title="<?= \data::get('CLOSE') ?>"></button>
        </div>
        <div class="px-4 middle" data-cart-area="checkout">
            <?foreach ($arResult["ITEMS"]["AnDelCanBuy"] as $arItem):?>
            <? $arOffer = $arResult['INFO'][$arItem['PRODUCT_ID']]; ?>
            <div class="d-flex gap-3 modal-cart-item" data-cart-area="checkout-<?=$arItem['ID']?>">
                <div class="d-flex flex-column gap-2 my-4 w-100 modal-cart-info">
                    <div class="fw-400 ttu text-crop-1 item-name"><a href="<?= $arItem['DETAIL_PAGE_URL'] ?>"><?= $arOffer['SKU']['NAME'] ?></a></div>
                    <div class="d-flex flex-shrink-0 gap-2 fw-400 item-price"><?= $arItem['SUM'] ?><?if($arItem['SUM_DISCOUNT_PRICE'] > 0):?><div class="c-gray old-price"><?= $arItem['SUM_FULL_PRICE_FORMATED'] ?></div><?endif?></div>
                    <?foreach ($arItem['PROPS'] as $arProp):?>
                    <div class="c-gray item-size"><?= \data::get($arProp['CODE']) ?>: <?= $arProp['VALUE'] ?></div>
                    <?endforeach;?>
                    <div class="c-gray item-qty"><?= \data::get('QUANTITY') ?>: <?= $arItem['QUANTITY'] ?></div>
                    <div class="d-flex mt-auto">
                        <button class="btn u-line btn-remove-item" data-item-id="<?= $arItem['ID']?>" data-product-id="<?= $arItem['PRODUCT_ID']?>" type="button"><?= \data::get('REMOVE') ?></button>
                    </div>
                </div>
                <div class="my-4 flex-shrink-0 modal-cart-img">
                    <a class="db" href="<?= $arItem['DETAIL_PAGE_URL'] ?>"><img width="90px" src="<?= $arOffer['SKU']['PICTURE']['SRC'] ?>" alt="<?= $arOffer['SKU']['NAME'] ?>"></a>
                </div>
            </div>
            <? endforeach;?>
        </div>
        <div class="mt-auto p-4 border-top bg-white bottom" data-cart-area="total">
            <div class="d-flex justify-content-between align-items-end mb-3">
                <div class="fw-400 ttu"><?= \data::get('TOTAL') ?></div>
                <div class="fw-400"><?= $arResult['allSum_FORMATED']?></div>
            </div>
            <div class="d-flex flex-column flex-md-row gap-4">
                <button class="btn btn-secondary btn-100 js-close-m-cart" type="button"><?= \data::get('CONTINUE_SHOPPING') ?></button>
                <a class="btn btn-primary btn-100" href="<?= SITE_DIR ?>checkout/"><?= \data::get('CHECKOUT') ?></a>
            </div>
        </div>
    </div>
<? if ($arParams['IS_JSON'] == 'Y') {
    $json['cart_html'] = ob_get_clean();
}
return; ?>

    <div class="d-flex justify-content-between align-items-center mb-3 pr top">
	<span class="fs-24 fw-600"><?=data::get("korzina");?></span>
	<span class="pr mr-n3 close-btn" title="<?=data::get("zakryt");?>"></span>
</div><?
if (is_array($arResult["ITEMS"]["AnDelCanBuy"]) && !empty($arResult["ITEMS"]["AnDelCanBuy"])) {
    ?><div class="middle"><?
	foreach ($arResult["ITEMS"]["AnDelCanBuy"] as $arItem) {
        ?><div class="d-flex modal-cart-item pr">
    		<div class="my-4 modal-cart-img">
    			<a class="db border" href="<?=$arResult["INFO"][$arItem["PRODUCT_ID"]]["URL"];?>"><?pictures::show($arResult["INFO"][$arItem["PRODUCT_ID"]]["IMAGE"], 4, addslashes($arResult["INFO"][$arItem["PRODUCT_ID"]]["NAME"]), "", false);?></a>
    		</div>
    		<div class="d-flex flex-column my-4 w-100 modal-cart-info">
    			<div class="mb-2 mr-5 item-name"><a class="fw-500" href="<?=$arResult["INFO"][$arItem["PRODUCT_ID"]]["URL"];?>"><?=$arResult["INFO"][$arItem["PRODUCT_ID"]]["NAME"];?></a></div>
    			<div class="fs-12 mb-2 c-gray"><?=data::get("artikul");?>: <?=$arResult["INFO"][$arItem["PRODUCT_ID"]]["ARTICLE"];?></div><?
    			foreach ($arItem["PROPS"] as $arProp) {
    			    if ($arProp["CODE"] == "SIZE_NAME" && $arProp["VALUE"]) {
    			        ?><div class="fs-12 mb-2 c-gray"><?=data::get("razmer");?>: <?=$arProp["VALUE"];?></div><?
    			    }
    			}
    		    ?><div class="d-flex justify-content-between mt-auto item-bottom">
    				<div class="fw-500 mt-auto"><?=data::get("kolichestvo");?>: <?=$arItem["QUANTITY"];?></div>
    				<div class="fw-500 tar item-price"><?if ($arItem["FULL_PRICE"] > $arItem["PRICE"]) {?><div class="c-gray old-price"><?=$arItem["FULL_PRICE_FORMATED"];?></div><?}?><?=$arItem["PRICE_FORMATED"];?></div>
    			</div>
    		</div>
    		<span class="pa remove-item-btn" title="<?=data::get("udalit-tovar");?>" data-product-id="<?=$arItem["PRODUCT_ID"];?>">
    			<i class="fal fa-times-square"></i>
    		</span>
    	</div><?
    	$list[] = [
    	    "ID" => $arItem["PRODUCT_ID"],
    	    "ADDITIONAL" => [
    	        "PRICE" => $arItem["PRICE"],
    	        "QUANTITY" => $arItem["QUANTITY"]
    	    ]
    	];
	}
	if (!empty($list)) {
	    ?><div class="dn ecommerce"><?=json_encode(ecommerce::getCode("view_cart", "cart", $list), JSON_UNESCAPED_UNICODE);?></div><?
	}
    ?></div><?
    $coupon = "";
    if (is_array($arResult["COUPON_LIST"])) {
        foreach ($arResult["COUPON_LIST"] as $arCoupon) {
            if (in_array($arCoupon["JS_STATUS"], ["APPLYED", "APPLIED"])) {
                $coupon = $arCoupon["COUPON"];
                break;
            }
        }
    }

    $couponError = "";
    if ($_REQUEST["coupon"] && strlen($coupon) <= 0 && $json["result"] == 1) {
        $coupon = $_REQUEST["coupon"];
        $couponError = "Купон не действует на товары в корзине";
    }
    ?><div class="mt-auto pt-5 pb-5 bottom">
		<div class="pr mb-4 d-flex">
			<input id="coupon-basket" class="mr-3 form-control coupon-input<?if (strlen($coupon) > 0) {?> input-filled<?}?>"<?if (strlen($coupon) > 0) {?> disabled<?}?> type="text" tabindex="0" value="<?=$coupon;?>">
			<label class="placeholder" for="coupon-basket"><?=data::get("promokod");?></label>
			<button class="ml-4 py-1 px-4 btn btn-blank ml-auto coupon-button coupon-basket<?if (strlen($coupon) > 0) {?> coupon-clear" data-text="<?=data::get("udalit-promokod");?>" style="min-width: 140px;"><?=data::get("udalit-promokod");?><?} else {?>" data-text="<?=data::get("primenit");?>" style="min-width: 140px;"><?=data::get("primenit");?><?}?></button>
			<span class="<?if (strlen($couponError) <= 0) {?>dn <?}?>pa error-msg"><?=$couponError;?></span>
		</div>
		<div class="d-flex justify-content-between align-items-end mb-4">
			<div class="fs-18 fw-500"><?=data::get("itogo");?></div>
			<div class="fs-18 fw-500 modal-total"><?=$arResult["allSum_FORMATED"];?></div>
		</div>
		<a class="py-4 btn btn-black btn-100" href="<?=SITE_DIR;?>checkout/#order-form" style="z-index: 255; -webkit-transform: translateZ(0);"><?=data::get("oformit-zakaz");?></a>
	</div><?
} else {
    ?><div class="middle"><?=data::get("pusto");?></div><?
}

if ($_REQUEST["mime"] != "json") {
    ?></div><?
} else {
    $json["basket"] = ob_get_clean();

    if ($_REQUEST["where"] == "checkout") {
        ob_start();
        ?><h3 class="fw-600 mb-3">Состав заказа</h3><?
        foreach ($arResult["ITEMS"]["AnDelCanBuy"] as $arItem) {
        ?><div class="d-flex modal-cart-item pr">
        	<div class="my-4 modal-cart-img">
				<a class="db border" href="<?=$arResult["INFO"][$arItem["PRODUCT_ID"]]["URL"];?>"><?pictures::show($arResult["INFO"][$arItem["PRODUCT_ID"]]["IMAGE"], 4, addslashes($arResult["INFO"][$arItem["PRODUCT_ID"]]["NAME"]), "", false);?></a>
			</div>
			<div class="d-flex flex-column my-4 w-100 modal-cart-info">
				<div class="mb-2 mr-5 item-name"><a class="fw-500" href="<?=$arResult["INFO"][$arItem["PRODUCT_ID"]]["URL"];?>"><?=$arResult["INFO"][$arItem["PRODUCT_ID"]]["NAME"];?></a></div>
				<div class="fs-12 mb-2 c-gray">Артикул: <?=$arResult["INFO"][$arItem["PRODUCT_ID"]]["ARTICLE"];?></div><?
    			foreach ($arItem["PROPS"] as $arProp) {
    			    if ($arProp["CODE"] == "SIZE_NAME" && $arProp["VALUE"]) {
    			        ?><div class="fs-12 mb-2 c-gray"><?=data::get("razmer");?>: <?=$arProp["VALUE"];?></div><?
    			    }
    			}
    		    ?><div class="d-flex justify-content-between mt-auto item-bottom">
					<div class="fw-500 mt-auto">Кол-во: <?=$arItem["QUANTITY"];?></div>
					<div class="fw-500 tar item-price"><?if ($arItem["FULL_PRICE"] > $arItem["PRICE"]) {?><div class="c-gray old-price"><?=$arItem["FULL_PRICE_FORMATED"];?></div><?}?><?=$arItem["PRICE_FORMATED"];?></div>
				</div>
			</div>
			<span class="pa remove-item-btn" title="Удалить товар" data-product-id="<?=$arItem["PRODUCT_ID"];?>">
				<i class="fal fa-times-square"></i>
			</span>
		</div><?
		}
		?><div class="d-flex justify-content-between mt-4 pt-4 border-top total">
			<div class="fs-16 fw-600">Предварительная сумма</div>
			<div class="fs-16 fw-600 total-price"><?=$arResult["allSum_FORMATED"];?></div>
		</div><?
        $json["items"] = ob_get_clean();

        ob_start();
        ?><input id="coupon" class="form-control coupon-input<?if (strlen($coupon) > 0) {?> input-filled<?}?>"<?if (strlen($coupon) > 0) {?> disabled<?}?> type="text" value="<?=$coupon;?>">
		<label class="placeholder" for="coupon">Промокод</label>
		<button class="ml-4 py-1 px-4 btn btn-blank coupon-button coupon-checkout<?if (strlen($coupon) > 0) {?> coupon-clear" data-text="Удалить" style="min-width: 140px;">Удалить<?} else {?>" data-text="Применить" style="min-width: 140px;">Применить<?}?></button>
		<span class="<?if (strlen($couponError) <= 0) {?>dn <?}?>pa error-msg"><?=$couponError;?></span><?
        $json["coupon"] = ob_get_clean();
    }
}?>