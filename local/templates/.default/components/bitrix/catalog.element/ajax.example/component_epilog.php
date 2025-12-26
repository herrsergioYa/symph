<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\Loader::requireModule('sale');

$basket = \Bitrix\Sale\Basket::loadItemsForFUser(\Bitrix\Sale\Fuser::getId(), SITE_ID);

$arInBasket = [];

foreach ($basket->getBasketItems() as $basketItem) {
    $productId = $basketItem->getProductId();
    if(isset($arInBasket[$productId]))
        $arInBasket[$productId] += $basketItem->getQuantity();
    else
        $arInBasket[$productId] = $basketItem->getQuantity();
}
?>
<script>
    window.IN_BASKET = Object.assign(/*{}, */window.IN_BASKET || {}, <?= json_encode($arInBasket) ?>);
</script>