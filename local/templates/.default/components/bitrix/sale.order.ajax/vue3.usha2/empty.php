<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
\Bitrix\Main\Localization\Loc::loadLanguageFile(__FILE__);

$APPLICATION->SetPageProperty('page-type', "");
$APPLICATION->SetPageProperty('title', \data::get('CHECKOUT_TITLE'));
$APPLICATION->SetTitle(\data::get('CHECKOUT_TITLE'));
?>
<div class="container-fluid d-flex align-items-center justify-content-center h-100">
    <div>
        <h1 class="tac"><?= \data::get('EMPTY_BASKET_TITLE') ?></h1>
        <div class="mw-400 my-5 pt-3 pb-3">
            <?= \data::get('EMPTY_BASKET_TEXT') ?>
        </div>
        <div class="tac">
            <a class="btn btn-blank py-0" href="<?= $arParams['PATH_TO_CATALOG']?>"><?= \data::get('BACK_TO_CATALOG') ?></a>
        </div>
    </div>
</div>
