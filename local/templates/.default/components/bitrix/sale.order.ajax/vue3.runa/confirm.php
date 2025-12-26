<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
\Bitrix\Main\Localization\Loc::loadLanguageFile(__FILE__);
$_SESSION['ORDER_ACCOUNT_NUMBER'] = $arResult['ACCOUNT_NUMBER'];
?>
<div class="container-xl bg-white my-xl-5">
    <div class="d-flex h-100">
        <div class="tac m-auto pb-5">
            <div class="mb-4"><i class="fad fa-check-circle fa-5x c-pink"></i></div>
            <div class="fs-36 fw-400 ttu mb-4"><?= \data::get('THANK_YOU') ?></div>
            <div class="fw-400 mb-2"><?= \data::get('ORDER_NO') ?><?= $arResult['ACCOUNT_NUMBER'] ?></div>
            <div class=""><?= \data::get('CALL') ?></div>
            <?if($arResult['PAY_SYSTEM']):?>
                <?= $arResult['PAY_SYSTEM']['BUFFERED_OUTPUT'] ?>
            <script>
                (function() {
                    let form = document.querySelector('[name="paymentform"]');
                    if(form) {
                        form.submit();
                    }
                })();
            </script>
            <?endif;?>
        </div>
    </div>
</div>
