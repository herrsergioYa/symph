<? if (!defined ("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

ob_start();
(function() use($arResult) {
    require $arResult['FILE'];
})();
$content = ob_get_clean();
$cleaned = NormalizePhone(preg_replace('/\D+/', '', $content));
?><a href="tel:<?= $cleaned?>"><?= $content?></a><?
