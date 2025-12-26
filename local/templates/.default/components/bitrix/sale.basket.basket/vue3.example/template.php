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


if (empty($arResult['ERROR_MESSAGE']) || $arResult['EMPTY_BASKET'])
{
	?>
<script>
    BX.ready(function () {
        window.vApp = window.vApp || {};
        window.vApp["<?= $arResult['VUE']['ROOT_ID'] ?>"] = new gsvSaleBasketVue3Example(
            "<?= $arResult['VUE']['ROOT_ID'] ?>",
            <?= json_encode($arResult['JS_DATA']) ?>,
            <?= json_encode($arResult['VUE']) ?>
        );
    })
</script>
    <?
}
/*elseif ($arResult['EMPTY_BASKET'])
{
	include(__DIR__.'/empty.php');
}*/
else
{
	ShowError($arResult['ERROR_MESSAGE']);
}