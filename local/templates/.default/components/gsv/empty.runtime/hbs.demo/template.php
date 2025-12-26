<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
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
/** @var CBitrixComponent $component */
$this->setFrameMode(false);

$obTemplate = new \Gsv\Util\Templates\Template(
    new \Gsv\Util\Templates\HandlebarsTemplateEngine()
);
?>
<div class="container" id="<?= $arResult['ROOT_ID'] ?>">
    <div class="container-area">
        <? $obTemplate->begin(); ?>
        <table>
            <thead>
                <tr>
                    <th>
                        Name:
                    </th>
                    <th>
                        Surname:
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th>
                        {{NAME}}
                    </th>
                    <th>
                        {{LAST_NAME}}
                    </th>
                </tr>
            </tbody>
        </table>
        <? $obTemplate->end(); ?>
        <? $obTemplate->display($arResult['JS_DATA']); ?>
    </div>
    <? $obTemplate->echo(["container-area_tmpl"]); ?>
</div>
<script>
    BX(function() {
        window.vApps = window.vApps || {};
        window.vApps['<?= $arResult['ROOT_ID'] ?>'] = new gsvHbsDemo(
            '<?= $arResult['ROOT_ID'] ?>',
            <?= json_encode($arResult['JS_DATA']) ?>,
        );
    });
</script>
