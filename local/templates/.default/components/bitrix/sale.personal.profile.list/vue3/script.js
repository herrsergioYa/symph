(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define('gsvSaleProfile', ['vue', 'jquery', 'v-select2', 'v-popupJq'], factory);
        // Or we can do that the 'global' way
        /*define('gsvSaleProfile', ['vue', 'jquery', 'v-select2', 'v-popupJq'], function (Vue, vSelect2, vPopupJq) {
            return (root.gsvSaleProfile = factory(Vue, vSelect2, vPopupJq));
        });*/
    } else if (typeof module === 'object' && module.exports) {
        // Node. Does not work with strict CommonJS, but
        // only CommonJS-like environments that support module.exports,
        // like Node.
        module.exports = factory(require('vue'), require('jquery'), require('v-select2'), require('v-popupJq'));
    } else {
        // Browser globals
        root.gsvSaleProfile = factory(root.Vue, root.jQuery, root.vSelect2, root.vPopupJq);
    }
}(typeof self !== 'undefined' ? self : this, function (Vue, jQuery, vSelect2, vPopupJq) {

    let {createApp, ref, reactive, computed, onMounted, watch, nextTick, watchEffect} = Vue || {};
    let $ = jQuery;

    let reinit = function () {
        //HACK: Against Bitrix's script reordering. ;-(
        //Doesn't always work, unfortunately.
        if (typeof window !== 'undefined') {
            $ = jQuery = jQuery || window.jQuery;

            Vue = Vue || window.Vue;
            ({createApp, ref, reactive, computed, onMounted, watch, nextTick, watchEffect} = Vue || {});
        }
    }

    BX.ready(reinit);
    reinit();
    document.addEventListener("DOMContentLoaded", reinit);

    let exports = function(data, opt) {

        this._data = data;
        this._opt = opt;

        //CHANGED!!
        /*let getProfileById = function (binding) {
            let profileId = binding.value;
            if(profileId == 'new') {
                return binding.instance.data.v;
            }
            let ALL_PROFILES = binding.instance.data.PROFILES; // w/o '.value'!
            for (let i in ALL_PROFILES) {
                let profile = ALL_PROFILES[i];
                if(profile.ID == profileId) {
                    return profile.v;
                }
            }
            return null;
        }

        let getMainAjaxUrl = (action => opt.MAIN_AJAX_URL.replace('#action#', action));*/

        //let directives = vSelect2(getProfileById, getMainAjaxUrl, opt);
        //let {directives, components} = vPopupJq(getProfileById, getMainAjaxUrl, opt);

        this.init();
    }

    exports.prototype.init = function () {

        let data = {
            adding: false,
            profileId: 0,
            profile: {},
            loading: 0,
            suggestions: {
                countries: {},
                cities: {},
                addresses : {},
            },
            errors: [],
        }

        this._data = Object.assign(data, this._data);

        this.vApp = {
            setup: () => this.setup(),
            //directives,
            //components,
        };

        this.app = createApp(this.vApp);
        this.mounted = this.app.mount('#' + this._opt.ROOT_ID);
    }

    exports.prototype.setup = function () {

        let data = this.data = reactive(this._data);
        let opt = this.opt = ref(this._opt);

        let removeBtn = this.removeBtn = ref(null);

        this.prepareProfiles(this.data);

        let errors = this.errors = computed(() => this._errors());

        onMounted(function() {
            //this.createPopups();
        });

        watchEffect(function () {
            /*let loader = $('.pf.loader');
            if(data.loading != 0) {
                loader.removeClass('d-none');
            } else {
                loader.addClass('d-none');
            }*/
        })

        return {
            data,
            opt,

            errors,

            removeBtn,

            add: (...args) => this.add(...args),
            create: (...args) => this.create(...args),
            edit: (...args) => this.edit(...args),
            save: (...args) => this.save(...args),
            remove: (...args) => this.remove(...args),
            normalize: (...args) => this.normalize(...args),
            isPhone: (...args) => this.isPhone(...args),
            //formatPhone: vPopupJq.formatPhone,
            selectProfile: (...args) => this.selectProfile(...args),
        }
    }

    exports.prototype.extractProfile = function (profile = null) {
        let v = {};

        if(!profile) {
            profile = {
                PROPERTIES: {},
            };
        }

        for (let code in this.data.PROPERTIES) {
            if(profile.PROPERTIES[code]) {
                v[code] = profile.PROPERTIES[code].VALUE;
            } else {
                v[code] = '';
            }
        }

        //v.LOCATION = v.CITY;

        if(profile.LOCATION) {
            v.COUNTRY = profile.LOCATION.COUNTRY;
            //v.CITY = profile.LOCATION.CITY;
            v.COUNTRY_ID = profile.LOCATION.COUNTRY_ID;
            v.CITY_ID = profile.LOCATION.CITY_ID;
        } else {
            v.COUNTRY = '';
            v.CITY = '';
            v.COUNTRY_ID = 0;
            v.CITY_ID = 0;
            for(let countryId in this.data.COUNTRIES) {
                let country = this.data.COUNTRIES[countryId];
                v.COUNTRY = country.COUNTRY;
                v.COUNTRY_ID = country.COUNTRY_ID;
                break;
            }
        }
        //this.normalizeProfile(v);

        return v;
    }

    exports.prototype.prepareProfiles = function (data) {

        data.adding = true;
        data.profileId = 0;
        for (let profile of data.PROFILES) {
            profile.edited = false;
            profile.d = this.extractProfile(profile);
            profile.v = this.extractProfile(profile);
            data.adding = false;
            if(profile.v.IS_MAIN_ADDRESS == 'Y') {
                data.profileId = profile.ID;
            }
        }

        data.v = this.extractProfile();
    }

    /*exports.prototype.normalize = function (obj, field) {
        if(!(obj && field in obj)) {
            return;
        }
        let val = obj[field] || '';
        val = val.trim();
        if(val.length < 1) {
            return;
        }
        val = val.substring(0, 1).toUpperCase() + val.substring(1).toLowerCase();
        obj[field] = val;
    }

    exports.prototype.normalizeProfile = function (profile) {
        this.normalize(profile, 'USER_NAME');
        this.normalize(profile, 'USER_LAST_NAME');
    }*/

    exports.prototype.getProperties = function (v) {
        let props = {};

        for(let code in this.data.PROPERTIES) {
            if(!(code in v)) {
                continue;
            }
            /*if(code == 'COUNTRY') {
                continue;
            } else*/ if(code == 'LOCATION') {
                let prop = this.data.PROPERTIES[code];
                props[prop.ID] = v.COUNTRY_ID;
            } else /*if(code == 'CITY') {
                let prop = this.data.PROPERTIES[code];
                props[prop.ID] = v.LOCATION;
            } else*/ {
                let prop = this.data.PROPERTIES[code];
                props[prop.ID] = v[code];
            }
        }

        return props;
    }

    exports.prototype.getProfileById = function(id) {
        if(id == 'new') {
            return this.data.v;
        }
        for(let profile of this.data.PROFILES) {
            if(profile.ID == id) {
                return profile.v;
            }
        }
        return null;
    }

    exports.prototype._errors = function (profile) {
        let err = [];
        /*if(!(profile.USER_NAME || '').trim()) {
            err.name = {
                type: 'name',
                text: vueOpt.value.MESSAGES['GSV_PROFILE_LIST_ERROR_NAME'],
            };
            err.count++;
        }
        if(!(profile.USER_LAST_NAME || '').trim()) {
            err.surname = {
                type: 'surname',
                text: vueOpt.value.MESSAGES['GSV_PROFILE_LIST_ERROR_SURNAME'],
            };
            err.count++;
        }
        if(!profile.COUNTRY_ID/ * || !profile.LOCATION* /) {
            err.country = {
                type: 'country',
                text: vueOpt.value.MESSAGES['GSV_PROFILE_LIST_ERROR_COUNTRY'],
            };
            err.count++;
        }
        if(!profile.CITY_ID || !profile.LOCATION) {
            err.city = {
                type: 'city',
                text: vueOpt.value.MESSAGES['GSV_PROFILE_LIST_ERROR_CITY'],
            };
            err.count++;
        }
        if(!(profile.ZIP || '').trim()) {
            err.zip = {
                type: 'zip',
                text: vueOpt.value.MESSAGES['GSV_PROFILE_LIST_ERROR_ZIP'],
            };
            err.count++;
        }
        if(!(profile.ADDR_DADATA || '').trim()
        ) {
            err.addr = {
                type: 'addr',
                text: vueOpt.value.MESSAGES['GSV_PROFILE_LIST_ERROR_ADDR'],
            };
            err.count++;
        }
        if(!(profile.PHONE || '').trim()) {
            err.phone = {
                type: 'phone',
                text: vueOpt.value.MESSAGES['GSV_PROFILE_LIST_ERROR_PHONE'],
            };
            err.count++;
        }
        if(err.count > 0) {
            let msg = [];
            for(let i in err) {
                if(i == 'count') {
                    continue;
                }
                msg.push(err[i].text);
            }
            msg = msg.join(', ');
            msg = vueOpt.value.MESSAGES.GSV_PROFILE_LIST_ERROR.replace('#NAME#', msg);
            return msg;
        }*/
        return err;
    }

    exports.prototype.validate = function (profile) {
        /*let errMsg = errors(profile);
        if(!errMsg) {
            data.errorMsg = false;*/
            return true;/*
        }

        data.errorMsg = errMsg;*/
        return false;
    }

    watchEffect(function() {
        /*let $msg = $(`[data-bottom-error="${vueOpt.value.VUE_ID}"]`);
        if(!data.errorMsg) {
            $msg.find('span').html('');
            $msg.addClass('dn')
        } else {
            $msg.find('span').html(data.errorMsg);
            $msg.removeClass('dn');
        }*/
    })

    exports.prototype.add = function() {
        this.data.adding = true;
        this.data.v = this.extractProfile();
    }

    exports.prototype.create = async function () {

        if(this.data.loading) {
            return;
        }

        let profile = this.getProperties(this.data.v);

        //normalizeProfile(profile);

        if(!this.validate(this.data.v)) {
            return;
        }

        this.data.loading ++;

        let body = {
            profile,
        };

        let result = await this.ajax('upsertProfile', body);

        if(result.result) {
            this.closeFancybox();
        }
        //console.log(result);

        await this.update();

        this.data.loading --;

    }

    exports.prototype.edit = function(profile) {
        profile.edited = true;
        this.data.profile = profile;
        profile.v = this.extractProfile(profile);
    }

    exports.prototype.save = async function (_profile) {

        if(this.data.loading) {
            return;
        }

        let profile = this.getProperties(_profile.v);

        //normalizeProfile(profile);

        if(!this.validate(_profile.v)) {
            return;
        }

        this.data.loading ++;

        let body = {
            profile,
            ID: _profile.ID,
        };

        let result = await this.ajax('upsertProfile', body);

        if(result.result) {
            this.closeFancybox();
        }

        //console.log(result);

        await this.update();

        this.data.loading --;
    }

    exports.prototype.remove = async function(_profile) {
debugger;
        let answer = await gsvJsHelper.fancyAnswer(this.removeBtn.value[0]);

        if(!answer) {
            return;
        }

        this.data.loading ++;

        let body = {
            ID: _profile.ID,
        };

        let result = await this.ajax('removeProfile', body);

        //console.log(result);

        await this.update();

        this.data.loading --;
    }

    exports.prototype.update = async function() {

        this.data.loading ++;

        let result = await this.ajaxMe();

        //console.log(result);

        Object.assign(this.data, result);

        this.prepareProfiles(this.data);

        await nextTick();

        //this.createPopups();

        this.data.loading --;

    }

    exports.prototype.isPhone = async function(event) {
        event = (event) ? event : window.event;
        if (!"1234567890()- ".split('').includes(event.key)) {
            event.preventDefault();
        } else {
            var phone = event.target.value;
            phone = phone.match(/\d+/g).join('');
            var right = false;
            if(phone.length <= 10) {
                return true;
            }
            event.preventDefault();
        }
    }

    exports.prototype.ajax = async function (action, data) {
        let url = this._opt.MAIN_AJAX_URL;
        url = url.replace('#action#', action);
        url = url.replace('#sessid#', BX.bitrix_sessid());

        let body = JSON.stringify(data);

        let response = await fetch(url, {
            method: 'POST',
            body,
            headers: {
                'X-Ajax': 'Y',
                'Content-Type': 'application/json',
            },
        });

        let result = await response.json();

        return result;
    }

    exports.prototype.ajaxMe = async function () {
        let url = this._opt.AJAX_URL;

        let body = {
            AJAX_KEY: this._opt.AJAX_KEY,
        };

        body = new URLSearchParams(body);

        let response = await fetch(url, {
            headers: {
                'X-Ajax': 'Y'
            },
            method: 'POST',
            body,
        });

        let result = await response.json();

        return result;
    }

    exports.prototype.closeFancybox = function () {
        if(typeof Fancybox !== 'undefined') {
            Fancybox.close();
        }
        if($('#address-add-modal.show').length) {
            $($('#address-add-modal [data-modal-close]')[0]).trigger('click');
        }
        if($('#address-edit-modal.show').length) {
            $($('#address-edit-modal [data-modal-close]')[0]).trigger('click');
        }
    }

    exports.prototype.selectProfile = async function (profileId) {
        debugger;

        let _profile = null;
        for(let profile of this.data.PROFILES) {
            if(profile.ID == profileId) {
                _profile = profile;
                break;
            }
        }

        if(!_profile) {
            return;
        }

        let temp = Object.assign({}, _profile.d, {
            'IS_MAIN_ADDRESS': 'Y',
        });
        let profile = this.getProperties(temp);

        let body = {
            profile,
            ID: _profile.ID,
        };

        this.data.loading++;

        let result = await this.ajax('upsertProfile', body);

        if(result.result) {
            //this.closeFancybox();
        }

        this.data.profileId = _profile.ID;

        //console.log(result);

        await this.update();
        //this.data.profileId = profileId;

        this.data.loading--;
    }

    return exports;

}));