(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(/*'gsvUshAuthReg', */['jquery', 'vue'], factory);
        // Or we can do that the 'global' way
        //define(/*'gsvUshAuthReg', */['jquery', 'vue'], function (jQuery, Vue) {
        //    return (root.gsvUshAuthReg = factory(jQuery, Vue));
        //});
    } else if (typeof module === 'object' && module.exports) {
        // Node. Does not work with strict CommonJS, but
        // only CommonJS-like environments that support module.exports,
        // like Node.
        module.exports = /*root.gsvUshAuthReg =*/ factory(require('jquery'), require('vue'));
    } else {
        // Browser globals
        root.gsvUshAuthReg = factory(root.jQuery, root.Vue);
    }
}(typeof self !== 'undefined' ? self : this, function (jQuery, Vue) {
    // Use jQuery & Vue in some fashion.

    let $ = jQuery;
    let {createApp, reactive, ref, watch, computed} = Vue || {};

    let reinit = function () {
        //HACK: Against Bitrix's script reordering. ;-(
        //Doesn't always work, unfortunately.
        if (typeof window !== 'undefined') {
            $ = jQuery = jQuery || window.jQuery;

            Vue = Vue || window.Vue;
            ({createApp, reactive, ref, watch, computed} = Vue || {});
        }
    }

    //BX.ready(reinit);
    reinit();
    document.addEventListener("DOMContentLoaded", reinit);

    //https://emailregex.com/index.html
    let emailRegex = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

    let ret = function (data, opt) {
        this._data = Object.assign(
            {
                phone: '',
                email: '',
                mode: 'sms',
                step: 0,
                register: false,
            },
            data,
            {
                'last_name': '',
                'name': '',
                'birthday': '',
                'agrees': true,

                'error': '',
                loading: 0,
                remainingSecs: 0,
                timer: null,
                reg: {
                    /*email: '',
                    phone: '',
                    last_name: '',
                    name: '',
                    birthday: '',
*/
                    //agrees: '',
                }
            }
        );
        this._opt = Object.assign({}, opt);

        this.init();
    }

    ret.prototype.init = function () {

        if(!this._canUseSms() && this._data.mode == 'sms') {
            this._data.mode = 'email';
        }

        const cfg = {
            setup: (...args) => this.setup(...args),
        }
        const sel = '#' + this._opt.ROOT_ID;

        const app = this.vApp = createApp(cfg);
        const mount = this.vMount = app.mount(sel);
    }

    ret.prototype.setup = function () {

        let data = this.data = reactive(this._data);
        let opt = this.opt = reactive(this._opt);

        let canResendCode = this.canResendCode = computed(() => this._canResendCode());
        let canSendSmsCode = this.canSendSmsCode = computed(() => this._canSendSmsCode());
        let canSendEmailCode = this.canSendEmailCode = computed(() => this._canSendEmailCode());
        let canSendCode = this.canSendCode = computed(() => this._canSendCode());
        let canUseSms = this.canUseSms = computed(() => this._canUseSms());
        let isLoading = this.isLoading = computed(() => this._isLoading());

        return {
            data,
            opt,

            canResendCode,
            canSendSmsCode,
            canSendEmailCode,
            canSendCode,
            canUseSms,
            isLoading,

            useSmsCode: (...args) => this.useSmsCode(...args),
            sendSmsCode: (...args) => this.sendSmsCode(...args),
            verifySmsCode: (...args) => this.verifySmsCode(...args),

            useEmailCode: (...args) => this.useEmailCode(...args),
            sendEmailCode: (...args) => this.sendEmailCode(...args),
            verifyEmailCode: (...args) => this.verifyEmailCode(...args),

            changePhone: (...args) => this.changePhone(...args),
            showRegister: (...args) => this.showRegister(...args),

            registerUser: (...args) => this.registerUser(...args),

            mess: (...args) => this.mess(...args),
        }
    }

    ret.prototype.useCode = function (type) {
        if(this.isLoading.value) {
            return;
        }
        this.data.mode = type;
        this.data.phone = '';
        this.data.email = '';
        this.clear(true);
        this.data.step = 0;
        this.clearTimer();
    }

    ret.prototype.useSmsCode = function () {
        this.useCode('sms');
    }

    ret.prototype.sendSmsCode = async function () {

        if(this.isLoading.value) {
            return;
        }

        let phone = this.getPhone();
        let register = this.data.register;

        if(!this.isPhoneCorrect(phone)) {
            this.showError('ENTER_PHONE_ERROR');
            return;
        }

        this.clear(true);
        this.data.loading ++;

        let result = await this.ajax('sendSmsCode', {
            phone,
            register,
        })

        this.data.loading --;

        if(result.status) {
            this.data.register = false;
            this.data.step = 1;
            this.onSendCode();
        } else if(result.message == 'USER_NOT_FOUND') {
            this.data.register = true;
            return await this.sendSmsCode();
        } else if(result.message == 'USER_ALREADY_EXISTS') {
            this.data.register = false;
            return await this.sendSmsCode();
        } else {
            this.showError(result);
        }
    }

    ret.prototype.verifySmsCode = async function () {

        if(this.isLoading.value) {
            return;
        }

        let phone = this.getPhone();
        let code = this.getCode();
        let register = this.data.register;

        if(!this.isCodeFeasible(code)) {
            this.showError('ENTER_CODE_ERROR');
            return;
        }

        this.clear(false);
        this.data.loading ++;

        let result = await this.ajax('verifySmsCode', {
            phone,
            code,
            register,
        })

        if(result.status) {
            if(this.data.register) {
                this.data.step = 2;
                await this.showProfile(true);
            } else {
                this.reloadPage();
                return;
            }
        } else {
            this.showError(result);
        }

        this.data.loading --;
    }

    ret.prototype.useEmailCode = function () {
        this.useCode('email');
    }

    ret.prototype.sendEmailCode = async function () {

        if(this.isLoading.value) {
            return;
        }

        let email = this.getEmail();
        let register = this.data.register;

        if(!this.isEmailCorrect(email)) {
            this.showError('ENTER_EMAIL_ERROR');
            return;
        }

        this.clear(true);
        this.data.loading ++;

        let result = await this.ajax('sendEmailCode', {
            email,
            register,
        })

        this.data.loading --;

        if(result.status) {
            this.data.step = 1;
            this.onSendCode();
        } else if(result.message == 'USER_NOT_FOUND') {
            this.data.register = true;
            return await this.sendEmailCode();
        } else if(result.message == 'USER_ALREADY_EXISTS') {
            this.data.register = false;
            return await this.sendEmailCode();
        } else {
            this.showError(result);
        }
    }

    ret.prototype.verifyEmailCode = async function () {

        if(this.isLoading.value) {
            return;
        }

        let email = this.getEmail();
        let code = this.getCode();
        let register = this.data.register;

        if(!this.isCodeFeasible(code)) {
            this.showError('ENTER_CODE_ERROR');
            return;
        }

        this.clear(false);
        this.data.loading ++;

        let result = await this.ajax('verifyEmailCode', {
            email,
            code,
            register,
        })

        if(result.status) {
            if(this.data.register) {
                this.data.step = 2;
                await this.showProfile(true);
            } else {
                this.reloadPage();
                return;
            }
        } else {
            this.showError(result);
        }

        this.data.loading --;
    }

    ret.prototype.changePhone = function () {

        if(this.isLoading.value) {
            return;
        }

        this.data.step = 0;

        this.clear(true);
        this.clearTimer();
    }

    ret.prototype.mess = function (code) {
        if(this.opt.MESS[code]) {
            return this.opt.MESS[code];
        } else {
            return code;
        }
    }

    ret.prototype.ajax = async function (action, data) {
        let url = this._opt.URL;
        url = url.replace('#action#', action);
        url = url.replace('#sessid#', BX_PARAMS.bitrix_sessid);

        let body = JSON.stringify(data);

        let response = await fetch(
            url,
            {
                method: 'POST',
                body,
                headers: {
                    'Content-Type': 'application/json',
                }
            }
        )

        let result = await response.json();

        return result;
    }

    ret.prototype._canResendCode = function () {
        return this.data.remainingSecs <= 0;
    }

    ret.prototype.onSendCode = function () {
        this.data.remainingSecs += this.opt.RESEND_INTERVAL_SEC;
        if(!this.timer) {
            let start = Math.floor(new Date().getTime() / 1000);
            this.timer = setInterval(() => {
                let now = Math.floor(new Date().getTime() / 1000);
                let delta = now - start;
                this.data.remainingSecs -= delta;
                if(this.data.remainingSecs <= 0) {
                    this.clearTimer();
                } else {
                    start = now;
                }
            }, 1000);
        }
    }

    ret.prototype._canSendSmsCode = function () {
        let phone = this.getPhone();
        phone = phone.replace(/\D+/g, '');
        return this.isPhoneCorrect(phone);
    }

    ret.prototype.isPhoneCorrect = function (phone) {
        return /^\d{11}$/.test(phone);
    }

    ret.prototype._canSendEmailCode = function () {
        let email = this.getEmail();
        return this.isEmailCorrect(email);
    }

    ret.prototype.isEmailCorrect = function (email) {
        return emailRegex.test(email);
    }

    ret.prototype._canSendCode = function () {
        let code = this.getCode();
        return this.isCodeFeasible(code);
    }

    ret.prototype.isCodeFeasible = function (code) {
        return /^\d{6}$/.test(code);
    }

    ret.prototype.isDateFeasible = function (val) {
        return /^\d{4}-\d{2}-\d{2}$/.test(val);
    }

    ret.prototype._canUseSms = function () {
        //let siteId = BX.message('SITE_ID');
        //return siteId == 's1' || siteId == 'ru';
        return true;
    }

    ret.prototype._isLoading = function () {
        return this.data.loading != 0;
    }

    ret.prototype.clearTimer = function () {
        this.data.remainingSecs = 0;
        if(this.timer) {
            clearInterval(this.timer);
            this.timer = false;
        }
    }

    ret.prototype.getPhone = function () {
        let phone = this.data.phone;
        phone = (phone || '').replace(/\D+/g, '');
        return phone;
    }

    ret.prototype.getEmail = function () {
        let email = this.data.email;
        email = (email || '').trim();
        return email;
    }

    ret.prototype.getCode = function () {
        let code = this.data.code;
        code = (code || '').trim();
        return code;
    }

    ret.prototype.clear = function (codeToo) {
        this.data.error = '';
        if(codeToo) {
            this.data.code = '';
        }
    }

    ret.prototype.reloadPage = function () {
        //window.location.href = BX.message('SITE_DIR');
        window.location.reload();
    }

    ret.prototype.showProfile = async function (register) {
        if(register) {
            let email = this.getEmail();
            let phone = this.getPhone();

            let last_name = this.data.last_name;
            let name = this.data.last_name;
            let birthday = this.data.birthday;

            let type = this.data.mode;

            let hasErrors = !this.checkRegErrors();

            if(!this.data.agrees) {
                this.showError('NOT_AGREED');
                hasErrors = true;
            }

            if(hasErrors) {
                return;
            }

            this.data.loading ++;

            let result = await this.ajax('registerUser', {
                //login,
                type,

                email,
                phone,
                last_name,
                name,
                birthday,
            });

            //return result;

            this.data.loading --;
        }
        window.location.href = BX_PARAMS.SITE_DIR + 'account/personal/';
    }

    ret.prototype.showError = function (result) {
        this.data.error = typeof result === 'object' ?  result.message : result;
    }

    ret.prototype.showRegister = function (register) {

        if(this.isLoading.value) {
            return;
        }

        this.data.register = register;
        this.data.step = 0;
        //this.data.phone = this.data.email = this.data.code = '';
        if(register) {
            if(this._canUseSms()) {
                this.data.email = '';
                this.data.mode = 'sms';
            } else {
                this.data.phone = '';
                this.data.mode = 'email';
            }
        }
        this.clear(true);
        this.clearTimer();
    }

    ret.prototype.registerUser = async function () {

        if(this.isLoading.value) {
            return;
        }

        let email = this.getEmail();
        let phone = this.getPhone();

        let last_name = this.data.last_name;
        let name = this.data.last_name;
        let birthday = this.data.birthday;

        let type = this.data.mode;

        let hasErrors = !this.checkRegErrors();

        if(!this.data.agrees) {
            this.showError('NOT_AGREED');
            hasErrors = true;
        }

        if(hasErrors) {
            return;
        }

        this.data.loading ++;

        let result = await this.ajax('registerUser', {
            //login,
            type,

            email,
            phone,
            last_name,
            name,
            birthday,
        })

        if(result.status) {
            this.reloadPage();
            return;
        } else {
            this.showError(result);
        }

        this.data.loading --;
    }

    ret.prototype.clearRegErrors = function () {
        this.clear(false);
        for(let i in this.data.reg) {
            this.data.reg[i] = '';
        }
    }

    ret.prototype.checkRegErrors = function () {
        let hasErrors = false;
        this.clearRegErrors();
        for(let i in this.data.reg) {
            let val = this.data[i];
            val = (val || '').trim();
            let ok;
            if(i == 'birthday') {
                ok = this.isDateFeasible(val);
            } else if(i == 'email') {
                ok = this.isEmailCorrect(val);
            } else if(i == 'phone') {
                ok = this.isPhoneCorrect(val);
            } else {
                ok = !(val.length <= 0);
            }
            if(!ok) {
                let k = `ENTER_${i.toUpperCase()}_ERROR`;
                this.data.reg[i] = this.opt.MESS[k];
                hasErrors = true;
            }
        }
        return !hasErrors;
    }

    // Just return a value to define the module export.
    // This example returns an object, but the module
    // can return a function as the exported value.
    return ret;
}));