<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>
<?
$this->setFrameMode(true);
$isAjax = $arParams['IS_AJAX'] == 'Y';
$ajaxed = $isAjax && (!empty($arResult["MESSAGE"]['SENT']) || !empty($arResult["MESSAGE"]['UPD']));
$subscribed = !empty($arResult["SUBSCRIPTION"]["ID"]) && $arResult["SUBSCRIPTION"]["ACTIVE"] == 'Y';
$email = $arResult['REQUEST']['EMAIL'];
if($ajaxed && $subscribed) {
    $email = $arResult["SUBSCRIPTION"]['~EMAIL'] ?: $arResult["SUBSCRIPTION"]['EMAIL'];
    if($email) {
        //You can do something with the email

        //And then do not forget to escape it!
        $email = $arResult["SUBSCRIPTION"]['EMAIL'];
    }
    //or you can empty the field
    //$email = '';
}

$authorized = !!$USER->IsAuthorized();
$errorMessages = [];
foreach ($arResult["ERROR"] as $errorMessage) {
    $errorMessages[] = $errorMessage;
}
/*if($arResult["MESSAGE"]['UPD']) {
    $errorMessages[] = GetMessage("SUBSCR_ERROR_EMAIL_EXISTS");
}*/
$unsubscribeLink = "?ID=" . $arResult['SUBSCRIPTION']['ID'] . "&action=unsubscribe";
?>
<div class="subscribe-form-wrap">
    <h3 class="mb-3 subscribe-title">Рассылка</h3>
    <div class="mb-5 lh-1_5">Новости о&nbsp;новинках модного Дома, специальные предложения, а&nbsp;также идеи для стайлинга и&nbsp;инсайты от&nbsp;дизайн-команды Ushatava.</div>
    <div class="subscribe-form">
        <form method="post" action="<?= $templateFolder ?>/ajax.php">
            <div class="pr lh-1_5">
                <input id="footer-subscribe" class="form-control" name="EMAIL" type="email"
                       autocorrect="off" autocapitalize="off" autocomplete="email" required
                       value="<?= $email ?>">
                <label class="placeholder" for="footer-subscribe">E–mail</label>
                <button class="btn btn-link pa" data-subscribe>Подписаться</button>
            </div>
            <? foreach ($arResult["RUBRICS"] as $itemID => $itemValue): ?>
                <input type="hidden" name="RUB_ID[]" value="<?= $itemValue["ID"] ?>">
            <? endforeach; ?>
            <input type="hidden" name="PostAction" value="Add">
            <input type="hidden" name="ID" value="<? echo $arResult["SUBSCRIPTION"]["ID"]; ?>">
            <input type="hidden" name="FORMAT" value="html">
            <?if($isAjax):?>
                <? echo bitrix_sessid_post();//Isn't allowed in the Composite site ?>
            <?else:?>
                <input type="hidden" name="sessid" value="">
                <script>
                    BX.ready(function() {
                        $('.subscribe-form-wrap .subscribe-form form input[name=sessid]').val(BX.bitrix_sessid());
                    })
                </script>
            <?endif;?>
        </form>
        <!-- <div class="small mt-2">Нажав на&nbsp;&laquo;Подписаться&raquo;, вы&nbsp;соглашаетесь с <a class="u-line" href="" target="_blank">политикой конфиденциальности</a>.</div> -->
        <? if($isAjax): ?>
            <? if($ajaxed): ?>
                <div class="mt-2 subscribe-form-success">
                    Спасибо! Вы&nbsp;подписались на&nbsp;новости и&nbsp;акции.
                </div>
            <? endif; ?>
            <?foreach($errorMessages as $errorMessage):?>
                <div class="mt-2 subscribe-form-failure">
                    <?= $errorMessage ?>
                </div>
            <? endforeach; ?>
        <?endif;?>
    </div>
</div>
<?if(!$isAjax):?>
<script src="<?= $templateFolder ?>/script.js"></script>
<?endif;?>
<? return ?>

<div class="col-md mb-5 order-md-1" id="subscribe">
    <div class="ms-md-5 ms-xl-0 mw-400 subscribe-form-wrap"
            <?
            //Already done in the Prolog. Should we repeat it just in case?
            setcookie("GSV_SUBSCR_IS_AUTHORIZED", $authorized ? "Y" : "N", time() + 604800, '/');
            setcookie("GSV_SUBSCR_IS_SUBSCRIBED", $subscribed ? "Y" : "N", time() + 604800, '/');
            ?>
        >
        <?//if($isAjax && !($authorized && $subscribed)):?>
        <div class="my-2 ttu subscribe-title subscription-hidden" data-subscribe-form-main="title"><?= GetMessage('GSV_SUBSCRIBE_EDIT_TITLE') ?></div>
        <div class="subscribe-form subscription-hidden" data-subscribe-form-main="body">
            <?//if($subscribed)://=> !$authorized?>
            <?/*form class="d-none" action="<?= $templateFolder ?>/ajax.php" method="post"></form*/?>
            <div class="lh-1_25 ttu mt-2 subscribe-form-success subscription-hidden" data-subscribe-form-subscribed="Y">
                <!--a href="<?= SITE_DIR ?>subscription/"><?= GetMessage("GSV_SUBSCRIBE_EDIT_UNSUBSCRIBE") ?></--a-->
                <a href="<?= $unsubscribeLink ?>"><?= GetMessage("GSV_SUBSCRIBE_EDIT_UNSUBSCRIBE") ?></a>
            </div>
            <? //else: ?>
            <form<?if($ajaxed && !$errorMessages):?> class="d-none"<?else:?> class="subscription-hidden"<?endif;?>  action="<?= $templateFolder ?>/ajax.php" method="post" data-subscribe-form-subscribed="N">
                <div class="pr">
                    <input class="form-control" type="email" name="EMAIL" placeholder="E-mail" autocorrect="off" autocapitalize="off" autocomplete="email" required value="<?=$_REQUEST['EMAIL']?:($USER->IsAuthorized()?$USER->GetEmail():'')?>">
                    <button class="btn btn-secondary pa"><?= GetMessage('GSV_SUBSCRIBE_EDIT_SUBMIT') ?></button>
                    <? foreach ($arResult["RUBRICS"] as $itemID => $itemValue): ?>
                        <input type="hidden" name="RUB_ID[]" value="<?= $itemValue["ID"] ?>">
                    <? endforeach; ?>
                    <input type="hidden" name="PostAction" value="Add">
                    <input type="hidden" name="ID" value="<? echo $arResult["SUBSCRIPTION"]["ID"]; ?>">
                    <input type="hidden" name="FORMAT" value="html">
                    <?if($isAjax):?>
                    <? echo bitrix_sessid_post();//Isn't allowed in the Composite site ?>
                    <?else:?>
                    <input type="hidden" name="sessid" value="">
                    <script>
                        BX.ready(function() {
                            $('#subscribe .subscribe-form [name=sessid]').val(BX.bitrix_sessid());
                        })
                    </script>
                    <?endif;?>
                </div>
                <div class="lh-1_5 c-gray mt-2"><?= GetMessage('GSV_SUBSCRIBE_EDIT_AGREEMENT')?></div>
            </form>
            <? //endif; ?>
            <? if($isAjax && $errorMessages): ?>
                <script>
                    <?foreach ($errorMessages as $errorMessage):?>
                    $(document).trigger('gsvErrorTop', <?= json_encode($errorMessage) ?>);
                    <?endforeach;?>
                </script>
            <? elseif($ajaxed && $arResult["MESSAGE"]['UPD']): ?>
                <div class="lh-1_25 ttu mt-2 subscribe-form-success">
                    <?= GetMessage("SUBSCR_ERROR_EMAIL_EXISTS") ?>
                </div>
            <? elseif ($ajaxed): ?>
                <div class="lh-1_25 ttu mt-2 subscribe-form-success">
                    <?= GetMessage('GSV_SUBSCRIBE_EDIT_SENT')?>
                </div>
            <? endif; ?>
        </div>
        <? /*else: ?>
        <div class="subscribe-form">
            <form class="d-none"  action="<?= $templateFolder ?>/ajax.php" method="post"></form>
        </div>
        <? endif;*/ ?>
    </div>
</div>
