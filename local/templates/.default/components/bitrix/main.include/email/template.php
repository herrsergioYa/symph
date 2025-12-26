<? if (!defined ("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

ob_start();
(function() use($arResult) {
    require $arResult['FILE'];
})();
$content = ob_get_clean();
$cleaned = $content;
?><a href="mailto:<?= $cleaned?>"><?= $content?></a><?
