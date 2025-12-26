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
?>
<div class="mx-auto mw-480" id="<?= $arResult['VUE']['ROOT_ID'] ?>" v-cloak>
    <h1 class="tac page-title title-margin v-cloak" v-if="!data.register"><?= \data::get('SIGN_IN') ?></h1>
    <h1 class="tac page-title title-margin v-cloak" v-else><?= \data::get('SIGN_UP') ?></h1>
    <div class="mb-6 v-cloak" v-if="data.register">
        <?= \data::get('NO_VPN_ALLOWED') ?>
    </div>
    <template v-if="true">
        <form class="mb-4 pb-3" method="post" action="" class="v-cloak">
            <template v-if="data.step < 2 || !data.register">
                <div class="pr mb-6" v-if="data.step == 0">
                    <template v-if="data.mode == 'sms'">
                        <input id="phone" class="form-control" :class="{'input-error': !!data.error, 'input-filled': !!data.phone}" type="tel" v-model.trim="data.phone" data-placeholder="+7 921 156-00-00" required="">
                        <label class="placeholder" for="phone"><?= \data::get('PHONE_NUMBER') ?></label>
                        <span v-if="data.error" class="dn pa error-msg">{{mess(data.error)}}</span>
                    </template>
                    <template v-if="data.mode == 'email'">
                        <input id="email" class="form-control" :class="{'input-error': !!data.error, 'input-filled': !!data.email}" type="email" v-model.trim="data.email" required="">
                        <label class="placeholder" for="email"><?= \data::get('EMAIL_ADDRESS') ?></label>
                        <span v-if="data.error" class="dn pa error-msg">{{mess(data.error)}}</span>
                    </template>
                </div>
                <div class="pr mb-6" v-else-if="data.step == 1">
                    <template v-if="data.mode == 'sms'">
                        <input id="phone" class="form-control input-filled input-disabled" type="tel" :value="data.phone" required="" disabled="">
                        <label class="placeholder" for="phone"><?= \data::get('PHONE_NUMBER') ?></label>
                        <button class="btn btn-link pa" type="button" @click.prevent="changePhone"><?= \data::get('CHANGE_LOGIN') ?></button>
                    </template>
                    <template v-if="data.mode == 'email'">
                        <input id="email" class="form-control input-filled input-disabled" type="email" :value="data.email" required="" disabled="">
                        <label class="placeholder" for="phone"><?= \data::get('EMAIL_ADDRESS') ?></label>
                        <button class="btn btn-link pa" type="button" @click.prevent="changePhone"><?= \data::get('CHANGE_LOGIN') ?></button>
                    </template>
                </div>
                <?/*div class="pr mb-6">
                    <input id="password" class="form-control" type="text" required="">
                    <label class="placeholder" for="password">Пароль</label>
                </div*/?>
                <div class="pr mb-6" v-if="data.step == 1">
                    <input id="auth-code" class="form-control" :class="{'input-error': !!data.error, 'input-filled': !!data.code}"
                        v-model.trim="data.code" inputmode="numeric" maxlength="6" type="text" required="">
                    <label class="placeholder" for="auth-code" v-if="data.mode == 'sms'"><?= \data::get('SMS_CODE') ?></label>
                    <label class="placeholder" for="auth-code" v-if="data.mode == 'email'"><?= \data::get('EMAIL_CODE') ?></label>
                    <span v-if="data.error" class="dn pa error-msg">{{mess(data.error)}}</span>
                    <div class="mt-3" v-if="!canResendCode">
                        {{opt.MESS.RESEND_CODE_AFTER.replace('#sec#', data.remainingSecs)}}
                    </div>
                    <div class="mt-3" v-else-if="data.mode == 'sms'">
                        <a @click.prevent="sendSmsCode">{{opt.MESS.RESEND_CODE}}</a>
                    </div>
                    <div class="mt-3" v-else-if="data.mode == 'email'">
                        <a @click.prevent="sendEmailCode">{{opt.MESS.RESEND_CODE}}</a>
                    </div>
                </div>
                <template v-if="data.mode == 'sms'">
                    <button class="btn btn-100 btn-primary" :class="{'btn-loading': isLoading, 'btn-disabled': !canSendSmsCode}" @click.prevent="sendSmsCode" v-if="data.step == 0"><?= \data::get('SEND_CODE') ?></button>
                    <button class="btn btn-100 btn-primary" :class="{'btn-loading': isLoading, 'btn-disabled': !canSendCode}" @click.prevent="verifySmsCode" v-else-if="data.step == 1"><?= \data::get('SIGN_IN_NOW') ?></button>
                </template>
                <template v-if="data.mode == 'email'">
                    <button class="btn btn-100 btn-primary" :class="{'btn-loading': isLoading, 'btn-disabled': !canSendEmailCode}" @click.prevent="sendEmailCode" v-if="data.step == 0"><?= \data::get('SEND_CODE') ?></button>
                    <button class="btn btn-100 btn-primary" :class="{'btn-loading': isLoading, 'btn-disabled': !canSendCode}" @click.prevent="verifyEmailCode" v-else-if="data.step == 1"><?= \data::get('SIGN_IN_NOW') ?></button>
                </template>
            </template>
            <template v-else-if="data.step == 2 && data.register">
                <div class="pr mb-6">
                    <input id="phone" class="form-control"
                           :class="{'input-disable': data.mode == 'sms', 'input-filled': !!data.phone, 'input-error': !!data.reg.phone}"
                           type="tel" autocomplete="off" required="" :disabled="data.mode == 'sms'"
                           v-model.trim="data.phone">
                    <label class="placeholder" for="phone"><?= \data::get('PHONE_NUMBER') ?></label>
                    <span v-if="data.reg.phone" class="dn pa error-msg">{{mess(data.reg.phone)}}</span>
                </div>
                <div class="pr mb-6">
                    <input id="email" class="form-control"
                           :class="{'input-disable': data.mode == 'email', 'input-filled': !!data.email, 'input-error': !!data.reg.email}"
                           type="email" autocomplete="off" required="" :disabled="data.mode == 'email'"
                           v-model.trim="data.email">
                    <label class="placeholder" for="email"><?= \data::get('EMAIL_ADDRESS') ?></label>
                    <span v-if="data.reg.email" class="dn pa error-msg">{{mess(data.reg.email)}}</span>
                </div>
                <div class="pr mb-6">
                    <input id="surname" class="form-control"
                           :class="{'input-filled': !!data.last_name, 'input-error': !!data.reg.last_name}"
                           type="text" autocomplete="off" required="" v-model.trim="data.last_name">
                    <label class="placeholder" for="surname"><?= \data::get('SURNAME') ?></label>
                    <span v-if="data.reg.last_name" class="dn pa error-msg">{{mess(data.reg.last_name)}}</span>
                </div>
                <div class="pr mb-6">
                    <input id="name" class="form-control"
                           :class="{'input-filled': !!data.name, 'input-error': !!data.reg.name}"
                           type="text" autocomplete="off" required="" v-model.trim="data.name">
                    <label class="placeholder" for="name"><?= \data::get('NAME') ?></label>
                    <span v-if="data.reg.name" class="dn pa error-msg">{{mess(data.reg.name)}}</span>
                </div>
                <div class="pr mb-5">
                    <input id="birthday" class="form-control"
                           :class="{'input-filled': !!data.birthday, 'input-error': !!data.reg.birthday}"
                           type="date" autocomplete="off" v-model="data.birthday">
                    <label class="placeholder" for="birthday"><?= \data::get('BIRTHDAY') ?></label>
                    <span v-if="data.reg.birthday" class="dn pa error-msg">{{mess(data.reg.birthday)}}</span>
                </div>
                <div class="pr mb-5">
                    <label class="pr form-check-label d-inline-flex">
                        <div>
                            <input class="form-check-input" type="checkbox" name="" value="" required="" v-model="data.agrees">
                            <span class="checkbox"></span>
                        </div>
                        <span><?= \data::get('AGREE_TO') ?></span>
                    </label>
                    <span v-if="data.error" class="__dn pa error-msg">{{mess(data.error)}}</span>
                </div>
                <button class="btn btn-100 btn-primary" :class="{'btn-loading': isLoading}" @click.prevent="registerUser"><?= \data::get('SIGN_UP_NOW') ?></button>
            </template>
        </form>

        <div class="d-flex justify-content-between" v-if="data.mode != 'email' && !data.register">
            <button class="btn-link u-line" type="button" @click.prevent="useEmailCode"><?= \data::get('USE_EMAIL_CODE') ?></button>
            <a class="u-line" @click.prevent="showRegister(true)"><?= \data::get('SIGN_UP_FORM') ?></a>
        </div>

        <template v-if="data.mode != 'sms' && !data.register">
            <div class="d-flex justify-content-between align-items-center" v-if="canUseSms">
                <button class="btn-link u-line" @click.prevent="useSmsCode"><?= \data::get('USE_SMS_CODE') ?></button>
                <a class="u-line" @click.prevent="showRegister(true)"><?= \data::get('SIGN_UP_FORM') ?></a>
            </div>
            <div class="tac" v-else>
                <a class="u-line" @click.prevent="showRegister(true)"><?= \data::get('SIGN_UP_FORM') ?></a>
            </div>
        </template>

        <div class="tac" v-if="data.register">
            <a class="u-line" @click.prevent="showRegister(false)"><?= \data::get('SIGN_IN_FORM') ?></a>
        </div>

        <div class="mt-10">
            <button class="btn btn-100 btn-blank" type="button"><?= \data::get('COMMUNICATE_IN_CHAT') ?></button>
        </div>
    </template>
</div>
<? $this->SetViewTarget('head-styles');?>
<link rel="stylesheet" href="<?= $templateFolder ?>/style.css">
<? $this->EndViewTarget();?>
<? $this->SetViewTarget('footer-scripts');?>
<script src="<?= $templateFolder ?>/script.js"></script>
<script>
    BX(function() {
        window.vApps = window.vApps || {};
        window.vApps['<?= $arResult['VUE']['ROOT_ID'] ?>'] = new gsvUshAuthReg(
            <?= json_encode($arResult['JS_DATA']) ?>,
            <?= json_encode($arResult['VUE']) ?>
        );
    });
</script>
<? $this->EndViewTarget();?>
