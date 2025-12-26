<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;


/**
 * @var array $arParams
 * @var array $arResult
 * @var string $templateFolder
 * @var string $templateName
 * @var CMain $APPLICATION
 * @var CBitrixBasketComponent $component
 * @var CBitrixComponentTemplate $this
 */
?>
<script>
    BX.ready(function () {
        window.app = window.app || {};
        window.app["<?= $arResult['OPT']['ROOT_ID'] ?>"] = new gsvCatalogElementAjaxExample(
            "<?= $arResult['OPT']['ROOT_ID'] ?>",
            <?= json_encode($arResult['JS_DATA']) ?>,
            <?= json_encode($arResult['OPT']) ?>
        );
    })
</script>