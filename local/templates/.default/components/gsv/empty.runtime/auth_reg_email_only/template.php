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
<!-- вход/регистрация -->
<div class="d-flex flex-column mw-600 side-modal auth-modal" id="<?= $arResult['VUE']['ROOT_ID'] ?>" v-cloak>
    <div class="d-flex justify-content-between align-items-center pr p-4 top">
        <span>Личный кабинет</span>
        <button class="pr btn-close js-modal-close" title="Закрыть"></button>
    </div>
    <div class="middle">
        <div class="d-flex flex-column h-100 p-4">
            <div class="mw-480 my-auto pb-5">
                <div class="mb-3">Войдите или зарегистрируйтесь</div>
                <div class="fs-13 mb-5">Введите адрес электронной почты. На&nbsp;него будет отправленно письмо с&nbsp;кодом авторизации.</div>
                <form class="auth-form" autocomplete="off">
                    <div class="input-wrap">
                        <div class="pr">
                            <input id="email" class="form-control" :class="{'input-error': data.error && data.step == 0, 'input-filled': data.email.length > 0}" type="email"
                                   v-model.trim="data.email" :readonly="data.step != 0" required autofocus>
                            <label class="placeholder" for="email">Email</label>
                            <div v-show="data.error && data.step == 0" class="dn pa error-msg">{{data.error}}</div>
                        </div>
                    </div>
                    <div class="input-wrap" v-show="data.step == 1">
                        <div class="pr">
                            <input id="auth-code" class="form-control" :class="{'input-error': !!data.error, 'input-filled': data.email.length > 0}" type="text" inputmode="numeric" maxlength="6"
                                   v-model.trim="data.code" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                            <label class="placeholder" for="auth-code">Код</label>
                            <div v-show="data.error" class="dn pa error-msg">Неверный код. Введите ещё раз или запросите новый</div>
                        </div>
                        <div class="fs-13 mt-4">
                            <template v-if="!canResendCode">
                            Отправить код повторно через: {{data.remainingSecs}}
                            </template>
                            <button v-else class="btn fs-13 u-line" type="button"
                                    @click.prevent="sendEmailCode">Получить новый код</button>
                        </div>
                    </div>
                    <div class="mt-5">
                        <button class="btn btn-primary btn-m100 btn-240px" :class="{'btn-loading': isLoading, 'btn-disabled': !canSendEmailCode}"
                                v-if="data.step == 0" @click.prevent="sendEmailCode()">Получить код</button>
                        <button class="btn btn-primary btn-m100 btn-240px" :class="{'btn-loading': isLoading, 'btn-disabled': !canSendCode}"
                                v-else-if="data.step == 1" @click.prevent="verifyEmailCode()">Войти</button>
                        <div class="fs-13 mt-4">Нажимая эту кнопку, вы&nbsp;подтверждаете, что ознакомились&nbsp;с <a class="u-line" href="" target="_blank" rel="noopener">политикой конфиденциальности</a>.</div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<? $this->SetViewTarget('footer-scripts');?>
<script src="<?= $templateFolder ?>/script.js"></script>
<script>
    (function() {
        let fn = function() {
            window.vApps = window.vApps || {};
            window.vApps['<?= $arResult['VUE']['ROOT_ID'] ?>'] = new gsvUshAuthReg(
                <?= json_encode($arResult['JS_DATA']) ?>,
                <?= json_encode($arResult['VUE']) ?>
            );
        };
        if (document.readyState === "complete" || document.readyState === "interactive") {
            // call on next available tick
            setTimeout(fn, 1);
        } else {
            document.addEventListener("DOMContentLoaded", fn);
        }
    })();
</script>
<? $this->EndViewTarget();?>