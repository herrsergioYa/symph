(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        //define('gsvBasketSoa', ['vue', 'v-select2', 'v-popupJq', 'v-imask'], factory);
        // Or we can do that the 'global' way
        define('gsvBasketSoa', ['vue', 'v-select2', 'v-popupJq', 'v-imask'], function (Vue, vSelect2, vPopupJq, vIMask) {
            return (root.gsvBasketSoa = factory(Vue, vSelect2, vPopupJq, vIMask));
        });
    } else if (typeof module === 'object' && module.exports) {
        // Node. Does not work with strict CommonJS, but
        // only CommonJS-like environments that support module.exports,
        // like Node.
        module.exports = factory(require('vue'), require('v-select2'), require('v-popupJq'), require('v-imask'));
    } else {
        // Browser globals
        root.gsvBasketSoa = factory(root.Vue, root.vSelect2, root.vPopupJq, root.vIMask);
    }
}(typeof self !== 'undefined' ? self : this, function (Vue, vSelect2, vPopupJq, vIMask) {

    let {createApp, ref, reactive, computed, onMounted, watch, nextTick, watchEffect } = Vue || {};

    let reinit = function () {
        //HACK: Against Bitrix's script reordering. ;-(
        //Doesn't always work, unfortunately.
        if (typeof window !== 'undefined') {
            Vue = Vue || window.Vue;
            ({createApp, ref, reactive, computed, onMounted, watch, nextTick, watchEffect } = Vue || {});
            vSelect2 = vSelect2 || window.vSelect2;
            vPopupJq = vPopupJq || window.vPopupJq;
            vIMask = vIMask || window.vIMask;
        }
    }

    reinit();
    $(reinit);

    let gsvBasketSoa = function (selector, jsData, opt) {

        //HACK:
        reinit();

        this._jsData = jsData;
        this._opt = opt;

        /** desc = {
         * current()
         * select(id)
         * getUrl()
         * getLang()
         * watch
         * ?query(params)
         * }
         */
        this.desc = {
            current: () => {
                if(this.data.model.CITY) {
                    return {
                        'id': this.data.model.CITY.ID,
                        'text': this.data.model.CITY.NAME,
                        'code': this.data.model.CITY.CODE,
                    }
                } else {
                    return {
                        'id': 0,
                        'text': this.data.model.PROPERTIES.CITY,
                        'code': '',
                    }
                }
            },
            select: (item) => {
                if(item && item.id) {
                    this.data.model.CITY = {
                        ID: item.id,
                        NAME: item.text,
                        CODE: item.code,
                    }
                    this.data.model.PROPERTIES.LOCATION = item.code;
                    this.data.model.PROPERTIES.CITY = item.text;
                } else {
                    this.data.model.CITY = false;
                    if(this.data.model.COUNTRY_ID) {
                        this.data.model.PROPERTIES.LOCATION = this.data.order.COUNTRY_LIST_DEFAULT[this.data.model.COUNTRY_ID].CODE;
                    } else {
                        this.data.model.PROPERTIES.LOCATION = '';
                    }
                    this.data.model.PROPERTIES.CITY = item.text;
                }
            },
            getUrl: () => this._opt.AJAX.url.replace('#action#', 'listCities'),
            getLang: () => this._opt.LANGUAGE_ID,
            watch: () => {},
            query: (params) => {
                return {
                    countryId: this.data.model.COUNTRY_ID,
                    q: params.term || '',
                }
            },
        };

        let directives = {
            //'city-popup': vSelect2.createPopup(this.desc),
            'city-popup': vPopupJq.createPopupJq(this.desc),
            'i-mask': vIMask.vIMask,
        };

        let components = {
            'vMask': vIMask.vMask,
        };

        this.vApp = createApp({
            setup : (...args) => this.setup(...args),
            directives,
            components,
        });
        this.vMount = this.vApp.mount(selector);

        if(window.IPOLSDEK_pvz) {
            //HACK: We should deceive IPOLSDEK_pvz
            window.BX = window.BX || {};
            window.BX.Sale = window.BX.Sale || {};
            window.BX.Sale.OrderAjaxComponent = window.BX.Sale.OrderAjaxComponent || {};
            window.BX.Sale.OrderAjaxComponent.sendRequest = function () {
                for (let handler of gsvBasketSoa.handlers) {
                    handler();
                }
            }
            gsvBasketSoa.handlers = gsvBasketSoa.handlers || [];
            gsvBasketSoa.handlers.push(() => this.updateOrder());
        }
    }

    gsvBasketSoa.prototype.setup = function () {
        let data = reactive({
            order: this._jsData,
            model: {},
            lastOrderUpdate: 0,
            needsUpdateOrder: false,
            errors : [],//It should be used.
            updateInProgress: 0,
            confirmInProgress: 0,
            couponInProgress: 0,
            wasConfirmed: false,
            required : {},//Is it used?
            firstLoad: true,
            cartVersion: 1,
        });
        this.data = data;

        let opt = reactive(this._opt);
        this.opt = opt;

        let order = this.prepareData(this._jsData);
        this.data.model = order;

        this.data.firstLoad = false;

        watch(() => data.model.COUNTRY_ID, (...args) => this.onCountryChanged(...args));
        watch(() => data.model.CITY?.ID ?? 0, (...args) => this.onCityChanged(...args));

        watch(() => data.model.PERSON_TYPE_ID, (...args) => this.onPersonTypeChanged(...args));
        watch(() => data.model.DELIVERY_ID, (...args) => this.onDeliveryChanged(...args));
        watch(() => data.model.PAY_SYSTEM_ID, (...args) => this.onPaySystemChanged(...args));

        watchEffect(() => this.watchQty());

        let isAddressEntered = this.$isAddressEntered = computed(() => this.isAddressEntered());
        let requiredFields = this.$requiredFields = computed(() => this.requiredFields());
        let isSDEKDelivery = this.$isSDEKDelivery = computed(() => this.isSDEKDelivery());
        let isSelfDelivery = this.$isSelfDelivery = computed(() => this.isSelfDelivery());
        let isLoading = this.$isLoading = computed(() => this.isLoading());
        let isConfirming = this.$isConfirming = computed(() => this.isConfirming());
        let isUpdatingCoupon = this.$isUpdatingCoupon = computed(() => this.isUpdatingCoupon());

        watch(
            () => isLoading.value,
            (...args) => this.watchLoading(...args),
            { immediate: true }
        );

        onMounted(() => this.postreceiveOrder());

        $(document).on('basketRefreshed', (event, jsBasket, version) => {
            if(version != data.cartVersion) {
                data.cartVersion = version;
                this.updateOrder();
            }
        })

        return {
            data,
            opt,

            isAddressEntered,
            requiredFields,
            isSDEKDelivery,
            isSelfDelivery,
            isLoading,
            isConfirming,
            isUpdatingCoupon,

            applyCoupon: (...args) => this.applyCoupon(...args),
            removeCoupon: (...args) => this.removeCoupon(...args),

            editQty: (...args) => this.editQty(...args),
            getQtys: (...args) => this.getQtys(...args),
            applyQty: (...args) => this.applyQty(...args),
            getSkuVariants: (...args) => this.getSkuVariants(...args),
            getSizes: (...args) => this.getSizes(...args),
            getCupSizes: (...args) => this.getCupSizes(...args),
            removeQty: (...args) => this.removeQty(...args),
            plural_form: (...args) => gsvBasketSoa.plural_form(...args),

            onConfirmOrder: (...args) => this.onConfirmOrder(...args),

            preventDefault: (...args) => this.preventDefault(...args),
        };
    }

    gsvBasketSoa.prototype.prepareData = function (order) {

        let firstLoad = this.data.firstLoad;

        let result = {};

        result.PROPERTIES = this.parseProperties(order);

        let items = [];
        for(let i in order.GRID.ROWS) {
            let item = order.GRID.ROWS[i];
            item = Object.assign({columns:item.columns, edited: false}, item.data);

            let properties = {};
            for(let j in item.PROPS) {
                let property = item.PROPS[j];
                properties[property.CODE] = property;
            }
            item.PROPS = properties;

            items.push(item);
        }
        result.ITEMS = items;

        let deliveries = [];
        if(firstLoad) {
            result.DELIVERY_ID = 0;
        } else {
            result.DELIVERY_ID = this.data.model.DELIVERY_ID;
        }
        for(let i in order.DELIVERY) {
            let delivery = order.DELIVERY[i];
            if(delivery.CHECKED == 'Y') {
                result.DELIVERY_ID = delivery.ID;
            }
            deliveries.push(delivery);
        }
        result.DELIVERY = deliveries;

        let paySystems = [];
        if(firstLoad) {
            result.PAY_SYSTEM_ID = 0;
        } else {
            result.PAY_SYSTEM_ID = this.data.model.PAY_SYSTEM_ID;
        }
        for(let i in order.PAY_SYSTEM) {
            let paySystem = order.PAY_SYSTEM[i];
            if(paySystem.CHECKED == 'Y') {
                result.PAY_SYSTEM_ID = paySystem.ID;
            }
            paySystems.push(paySystem);
        }
        result.PAY_SYSTEM = paySystems;

        let personTypes = [];
        if(firstLoad) {
            result.PERSON_TYPE_ID = 0;
        } else {
            result.PERSON_TYPE_ID = this.data.model.PERSON_TYPE_ID;
        }
        for(let i in order.PERSON_TYPE) {
            let personType = order.PERSON_TYPE[i];
            if(personType.CHECKED == 'Y') {
                result.PERSON_TYPE_ID = personType.ID;
            }
            personTypes.push(personType);
        }
        result.PERSON_TYPE = personTypes;

        let profiles = [];
        if(firstLoad) {
            result.PROFILE_ID = 0;
        } else {
            result.PROFILE_ID = this.data.model.PROFILE_ID;
        }
        for(let i in order.USER_PROFILES) {
            let profile = order.USER_PROFILES[i];
            if(profile.CHECKED == 'Y') {
                result.PROFILE_ID = profile.ID;
            }
            profiles.push(profile);
        }
        result.USER_PROFILES = profiles;

        if(order.COUNTRY) {
            result.COUNTRY_ID = order.COUNTRY.ID;
            if(order.CITY) {
                result.CITY = order.CITY;
                result.PROPERTIES.CITY = result.CITY.NAME;
            } else {
                result.CITY = false;
                result.PROPERTIES.ADDRESS = result.PROPERTIES.ZIP = '';
            }
        } else {
            result.COUNTRY_ID = 0;
            result.CITY = false;
            result.PROPERTIES.CITY = '';
            result.PROPERTIES.ADDRESS = result.PROPERTIES.ZIP = '';
        }
        result.COUPONS = this.getCoupons(order.COUPON_LIST);

        return result;
    }

    gsvBasketSoa.prototype.getCoupon = function (basketCoupon) {
        return {
            COUPON: basketCoupon.COUPON,
            ERROR: basketCoupon.STATUS != 4 && basketCoupon.STATUS != 8,
            APPLIED: true,
        };
    }

    gsvBasketSoa.prototype.getCoupons = function (COUPON_LIST) {
        let coupons = [];
        for(let i in COUPON_LIST) {
            let basketCoupon = COUPON_LIST[i];
            let coupon = this.getCoupon(basketCoupon);
            coupons.push(coupon);
        }
        if(coupons.length == 0) {
            coupons.push(this.getEmptyCoupon());
        }
        return coupons;
    }

    gsvBasketSoa.prototype.getEmptyCoupon = function ()
    {
        let coupon = {
            COUPON: '',
            ERROR: false,
            APPLIED: false,
        }
        return coupon;
    }

    gsvBasketSoa.prototype.isAddressEntered = function () {
        if(this.data.model.COUNTRY_ID == 0 || !this.data.model.CITY) {
            return false;
        }
        if(this.data.model.LOCATION == '' || 'LOCATION' in this.$requiredFields) {
            //Is it redundant?
            return false;
        }
        if('CITY' in this.$requiredFields || 'ADDRESS' in this.$requiredFields) {
            return false;
        }

        return true;
    }

    gsvBasketSoa.prototype.onCountryChanged = async function (countryId, oldCountryId) {

        let country = this.data.order.COUNTRY_LIST_DEFAULT[countryId];
        if(country) {
            this.data.model.COUNTRY_ID = country.ID;
            this.data.model.CITY = false;
            this.data.model.PROPERTIES.LOCATION = country.CODE;
        } else {
            this.data.model.COUNTRY_ID = 0;
            this.data.model.CITY = false;
            this.data.model.PROPERTIES.LOCATION = '';
        }

        this.emptyAddr();//Смена страны не всегда приводит к смене города (он мог быть не выбран)
        await this.updateOrder(false);

    }

    gsvBasketSoa.prototype.onCityChanged = async function (cityId, oldCityId) {
        this.desc.watch();
        this.emptyAddr();
        await this.updateOrder();
    }

    gsvBasketSoa.prototype.onPersonTypeChanged = async function (deliveryId, oldDeliveryId) {
        await this.updateOrder(false);
    }

    gsvBasketSoa.prototype.onDeliveryChanged = async function (deliveryId, oldDeliveryId) {
        await this.updateOrder(false);
    }

    gsvBasketSoa.prototype.onPaySystemChanged = async function (deliveryId, oldDeliveryId) {
        await this.updateOrder(false);
    }

    gsvBasketSoa.prototype.applyCoupon = async function (coupon) {

        if(this.$isUpdatingCoupon.value){
            return;
        }

        let body = {};
        let code  = coupon.COUPON || '';
        code = code.trim();
        body['basket[coupon]'] = code;

        this.data.couponInProgress++;
        let result = await this.ajaxBasket('recalculateAjax', body);
        this.data.couponInProgress--;

        //TODO: Should I update all the coupons? Thru getCoupons()? What if we have duplicates?
        debugger;
        this.data.model.COUPONS = this.getCoupons(result.BASKET_DATA.COUPON_LIST);
        coupon.APPLIED = true;
        coupon.ERROR = true;
        code = code.toUpperCase();
        for(let i in this.data.model.COUPONS) {
            let basketCoupon = this.data.model.COUPONS[i];
            if(code == (basketCoupon.COUPON || '').trim().toUpperCase()) {
                coupon = basketCoupon;
                break;
            }
        }
        if(coupon.ERROR == false) {
            await this.updateOrder();
        }
    }

    gsvBasketSoa.prototype.removeCoupon = async function (coupon) {

        if(this.$isUpdatingCoupon.value){
            return;
        }

        let body = {};
        body[`basket[delete_coupon][${coupon.COUPON}]`] = coupon.COUPON;
        let needsUpdatingOrder = coupon.APPLIED && !coupon.ERROR;

        this.data.couponInProgress++;
        let result = await this.ajaxBasket('recalculateAjax', body);
        this.data.couponInProgress--;

        this.data.model.COUPONS = this.getCoupons(result.BASKET_DATA.COUPON_LIST);
        if(needsUpdatingOrder){
            await this.updateOrder();
        }
    }

    gsvBasketSoa.prototype.getProcessOrderReq = function(data, confirm) {
        let body = {
            sessid: BX.bitrix_sessid(),
            //  'soa-action': 'saveOrderAjax',
            'location_type': 'code',
            'PERSON_TYPE': data.model.PERSON_TYPE_ID,
            'DELIVERY_ID': data.model.DELIVERY_ID,
            'PAY_SYSTEM_ID': data.model.PAY_SYSTEM_ID,
            //'PROFILE_ID': data.PROFILE_ID,
            //'AJAX_KEY': ajaxKey,
        }

        Object.assign(body, this.fillProperties(confirm));

        // body.via_ajax = 'Y';//Do we need this one?
        body.confirmorder = confirm ? 'Y' : 'N';
        body.is_ajax_post = 'Y';//It should RestartBuffer()
        body.json = 'Y';//We need JSON. Not HTML.

        return body;
    }

    gsvBasketSoa.prototype.processOrder = async function (confirm, profileChanged = false) {

        let body = this.getProcessOrderReq(this.data, confirm);

        if(!confirm) {
            if (profileChanged) {
                body.profile_change = 'Y';
            }
        }

        let result = await this.ajaxMe(body);
        return result;
    }

    gsvBasketSoa.prototype.getRefreshOrderAjaxReq = function(data) {

        let AJAX = this._opt.SOA_AJAX;

        let body = {
            sessid: BX.bitrix_sessid(),
            [AJAX.act_var]: 'saveOrderAjax',
            'location_type': 'code',
            'PERSON_TYPE_ID': data.model.PERSON_TYPE_ID,
            'DELIVERY_ID': data.model.DELIVERY_ID,
            'PAY_SYSTEM_ID': data.model.PAY_SYSTEM_ID,
            //'PROFILE_ID': data.model.PROFILE_ID,
        }

        Object.assign(body, this.fillProperties(false));

        body = {
            order: body,
        }

        body.sessid = body.order.sessid;
        body.via_ajax = 'Y';
        body.SITE_ID = this._opt.SITE_ID;
        body.signedParamsString = AJAX.post.signedParamsString;
        body[AJAX.act_var] = 'refreshOrderAjax';

        return body;
    }

    gsvBasketSoa.prototype.getSaveOrderAjaxReq = function(data) {

        let AJAX = this._opt.SOA_AJAX;

        let body = {
            sessid: BX.bitrix_sessid(),
            [AJAX.act_var]: 'saveOrderAjax',
            'location_type': 'code',
            'PERSON_TYPE': data.model.PERSON_TYPE_ID,
            'DELIVERY_ID': data.model.DELIVERY_ID,
            'PAY_SYSTEM_ID': data.model.PAY_SYSTEM_ID,
            //'PROFILE_ID': data.model.PROFILE_ID,
        }

        Object.assign(body, this.fillProperties(false));

        // body = {
        //     order: body,
        // }

        //body.sessid = body.order.sessid;
        body.via_ajax = 'Y';
        body.SITE_ID = this._opt.SITE_ID;
        body.signedParamsString = AJAX.post.signedParamsString;
        //body['soa-action'] = 'refreshOrderAjax';

        return body;
    }

    gsvBasketSoa.prototype.getProcessOrderAjaxReq = function(data, confirm) {

        let AJAX = this._opt.SOA_AJAX;

        let body = {
            sessid: BX.bitrix_sessid(),
            [AJAX.act_var]: 'saveOrderAjax',
            'location_type': 'code',
            'PERSON_TYPE': data.model.PERSON_TYPE_ID,
            'DELIVERY_ID': data.model.DELIVERY_ID,
            'PAY_SYSTEM_ID': data.model.PAY_SYSTEM_ID,
            'PROFILE_ID': data.model.PROFILE_ID,
        }

        Object.assign(body, this.fillProperties(false));

        if(!confirm) {
            body = {
                order: body,
                //sessid: body.sessid,
            }
            body.sessid = body.order.sessid;
        }

        body.via_ajax = 'Y';
        body.SITE_ID = this._opt.SITE_ID;
        body.signedParamsString = AJAX.post.signedParamsString;
        if(!confirm) {
            body[AJAX.act_var] = 'refreshOrderAjax';
        }

        return body;
    }

    gsvBasketSoa.prototype.processOrderAjax = async function (confirm, profileChanged = false) {

        let body = this.getProcessOrderAjaxReq(this.data, confirm);

        if(!confirm) {
            if (profileChanged) {
                body.order.profile_change = 'Y';
            }
        }

        let result = await this.ajaxSoa(body);
        return result;
    }

    gsvBasketSoa.prototype.parseProperties = function (order) {
        let properties = {};

        for (let i in order.ORDER_PROP.properties) {
            let property = order.ORDER_PROP.properties[i];
            let value = property.VALUE;
            if(Array.isArray(value) && property.MULTIPLE != 'Y') {
                value = value.join(', ');
            }
            properties[property.CODE] = value;
            if(property.IS_ADDRESS === 'Y' || property.IS_ADDRESS === true) {
                properties['SDEK__' + property.CODE + "__ID"] = property.ID;
            }
        }

        properties.FIO = (properties.FIO || '').trim();
        let fioMatch = properties.FIO.match(/^(\S+)\s+(\S+)$/);
        if(fioMatch) {
            properties.FIRST_NAME = fioMatch[1];
            properties.LAST_NAME = fioMatch[2];
        } else {
            properties.FIRST_NAME = properties.FIO;
            properties.LAST_NAME = '';
        }

        return properties;
    }

    gsvBasketSoa.prototype.fillProperties = function (confirm) {
        let result = {};
        let order = this.data.order;
        let model = this.data.model;

        model.PROPERTIES.FIO = model.PROPERTIES.FIRST_NAME + ' ' + model.PROPERTIES.LAST_NAME;

        for (let i in order.ORDER_PROP.properties) {
            let property = order.ORDER_PROP.properties[i];
            result['ORDER_PROP_' + property.ID] = model.PROPERTIES[property.CODE];

            if(property.TYPE == 'LOCATION') {
                let altField = +property.INPUT_FIELD_LOCATION;
                if(altField > 0) {
                    result[`LOCATION_ALT_PROP_DISPLAY_MANUAL[${property.ID}]`] = '1';
                }
            }
        }

        return result;
    }

    gsvBasketSoa.prototype.preventDefault = function ($event) {
        $event.preventDefault();
    }

    gsvBasketSoa.prototype.editQty = function(item) {
        if(!item.edited) {
            item.edited = true;
            item.VAR = {};
            for(let prop of this.data.order.SKU_PROPS) {
                if(prop in item.PROPS) {
                    item.VAR[prop] = Object.assign({}, item.PROPS[prop]);
                }
            }
            item.VAR_QUANT = item.QUANTITY;
        }
    }

    gsvBasketSoa.prototype.getQtys = function(item) {
        let result = [];
        let quantity = this.getOffer(item)?.QUANTITY ?? 0;
        if(quantity <= 0) {
            quantity = 7;
        }
        for(let i = 0; i < quantity; i++) {
            result.push(i + 1);
        }
        return result;
    }

    gsvBasketSoa.prototype.applyQty = async function(item) {
        item.edited = false;

        let body = {};
        let body2 = {};

        body2[`basket[QUANTITY_${item.ID}]`] = item.VAR_QUANT;
        //To prevent blinking...
        //item.QUANTITY = item.VAR_QUANT;

        for(let code in item.PROPS) {
            for(let id in item.VARIANTS) {
                let variant = item.VARIANTS[id];
                if(variant.PROPS[code].VALUE_XML_ID == item.VAR[code].VALUE_XML_ID) {
                    body[`basket[OFFER_${item.ID}][${code}]`] = variant.PROPS[code].VALUE;
                    //To prevent blinking...
                    //item.PROPS[code].VALUE = variant.PROPS[code].VALUE;
                    break;
                }
            }
        }

        this.data.updateInProgress++;
        await this.ajaxBasket('recalculateAjax', body);
        await this.ajaxBasket('recalculateAjax', body2);
        await this.updateOrder();
        this.data.updateInProgress--;

        this.data.cartVersion++;// = typeof cartVersion === 'function' ? cartVersion() : 0;
        $(document).trigger('refreshBasket');
    }

    gsvBasketSoa.prototype.isVariant = function(variant, props) {
        for (let prop in props) {
            if (prop in variant.PROPS) {
                if(variant.PROPS[prop].VALUE_XML_ID != props[prop].VALUE_XML_ID) {
                    return false;
                }
            } else {
                return false;
            }
        }
        return true;
    }

    gsvBasketSoa.prototype.isAvailable = function(offer) {
        return offer && (offer.AVAILABLE === 'Y' || offer.AVAILABLE === true);
    }

    gsvBasketSoa.prototype.getSkuProps = function(item, before = '') {
        let result = {};
        for(let skuProp of this.data.order.SKU_PROPS) {
            if(skuProp == before) {
                break;
            }
            if(!item.VAR[skuProp]) {
                continue;
            }
            result[skuProp] = item.VAR[skuProp];
        }
        return result;
    }

    gsvBasketSoa.prototype.getBySkuProps = function(item, props, available, code = '') {
        let result = [];
        let present = {};
        for(let id in item.VARIANTS) {
            let variant = item.VARIANTS[id];
            if(this.isVariant(variant, props)) {
                if(available && !this.isAvailable(variant)) {
                    continue;
                }
                if(code) {
                    if(variant.PROPS[code].VALUE_XML_ID in present) {
                        continue;
                    } else {
                        present[variant.PROPS[code].VALUE_XML_ID] = variant;
                    }
                }
                result.push(variant);
            }
        }
        return result;
    }

    /*gsvBasketSoa.prototype.getSkuPropSlice = function(item, props) {
        debugger;
        let result = {};
        for(let skuProp of this.data.order.SKU_PROPS) {
            if(skuProp in props) {
                result[skuProp] = item.VAR;
            }
        }
        return result;
    }*/

    gsvBasketSoa.prototype.getSkuVariants = function(item, code, available = true) {
        let slice = this.getSkuProps(item, code);
        let variants = this.getBySkuProps(item, slice, available, code);
        return variants;
    }

    /*
    gsvBasketSoa.prototype.getSizes = function(item) {
        let result = [];

        let present = {};
        for(let id in item.VARIANTS) {
            let variant = item.VARIANTS[id];

            let tree = this.getCupSizesImpl(item, variant.PROPS.SIZE.VALUE_XML_ID);
            if(tree.length == 0) {
                continue;
            }

            if(!(variant.PROPS.SIZE.VALUE_XML_ID in present)) {
                result.push(variant);
                present[variant.PROPS.SIZE.VALUE_XML_ID] = item.ID;
            }
        }

        return result;
    }

    gsvBasketSoa.prototype.getCupSizes = function(item) {
        let sizeXmlId = item.SIZE.XML_ID;
        return this.getCupSizesImpl(item, sizeXmlId);
    }

    gsvBasketSoa.prototype.getCupSizesImpl = function(item, sizeXmlId) {
        let result = [];

        for(let id in item.VARIANTS) {
            let variant = item.VARIANTS[id];

            let offer = this.getOfferImpl(item, variant.SIZE.VALUE_XML_ID, variant.SIZE_CUP.VALUE_XML_ID);
            if(!offer || offer.AVAILABLE !== 'Y' && offer.AVAILABLE !== true) {
                continue;
            }

            if(variant.SIZE.VALUE_XML_ID == sizeXmlId) {
                result.push(variant);
            }
        }

        return result;
    }
*/
    gsvBasketSoa.prototype.getOffer = function(item) {
        let props = this.getSkuProps(item);
        return this.getOfferImpl(item, props);
    }

    gsvBasketSoa.prototype.getOfferImpl = function (item, props, available = true) {
        let variants = this.getBySkuProps(item, props, available);
        return variants.length ? variants[0] : null;
    }

    gsvBasketSoa.prototype.watchQty = async function() {
        for(let item of this.data.model.ITEMS) {
            if(!item.edited) {
                continue;
            }
            for(let skuProp of this.data.order.SKU_PROPS) {
                if(!(skuProp in item.VAR)) {
                    continue;
                }
                let sizes = this.getSkuVariants(item, skuProp);
                let sizeFound = false;
                for (let size of sizes) {
                    if (size.PROPS[skuProp].VALUE_XML_ID == item.VAR[skuProp].VALUE_XML_ID) {
                        sizeFound = true;
                        break;
                    }
                }
                if (!sizeFound) {
                    for (let size of sizes) {
                        item.VAR[skuProp].VALUE_XML_ID = size.PROPS[skuProp].VALUE_XML_ID;
                        break;
                    }
                }
            }

            let offer = this.getOffer(item);
            if(offer.QUANTITY < item.VAR_QUANT) {
                item.VAR_QUANT = offer.QUANTITY;
            }
        }
    }

    gsvBasketSoa.prototype.removeQty = async function(item) {
        let body = {};
        body[`basket[DELETE_${item.ID}]`] = 'Y';

        this.data.updateInProgress++;
        await this.ajaxBasket('recalculateAjax', body);
        await this.updateOrder();
        this.data.updateInProgress--;
    }

    gsvBasketSoa.prototype.updateOrder = async function (profileChanged = false) {

        this.presendOrder();

        let updateNum = ++this.data.lastOrderUpdate;

        this.data.updateInProgress++;

        let result = await this.processOrder(false);

        if (this.data.needsUpdateOrder) {
            this.data.needsUpdateOrder = false;
            await this.updateOrder();
        } else if (updateNum == this.data.lastOrderUpdate) {

            if('SDEK' in result) {
                let temp = result.SDEK;
                delete result.SDEK;
                temp.order = result;

                if(window.IPOLSDEK_pvz) {
                    IPOLSDEK_pvz.onLoad(temp);
                }
            }

            this.data.order = this._jsData = result;
            this.data.model = this.prepareData(result);

            this.postreceiveOrder();
        } else {
            //Already updated. At least I hope so.
        }
        this.data.updateInProgress--;
    }

    gsvBasketSoa.prototype.onConfirmOrder = async function ($event) {
        this.data.wasConfirmed = true;
        if($event) {
            this.preventDefault($event);
        }
        if(this.$isConfirming.value) {
            return;
        }
        this.presendOrder();
        if(Object.keys(this.$requiredFields.value).length > 0) {
            return;
        }
        let result = await this.confirmOrder();
        if(result.order.ERROR) {
            let errors = [];
            for(let type in result.order.ERROR) {
                let errorOfType = result.order.ERROR[type];
                if(Array.isArray(errorOfType)) {
                    for (let error of errorOfType) {
                        errors.push(errors);
                    }
                } else {
                    errors.push(errorOfType);
                }
            }
            if(errors.length) {
                alert(errors.join("\n"));
                return;
            }
        }
        this.data.confirmInProgress++;
        window.location.href = result.order.REDIRECT_URL;
    }

    gsvBasketSoa.prototype.confirmOrder = async function () {

        this.data.confirmInProgress++;

        let result = await this.processOrderAjax(true);

        this.data.confirmInProgress--;

        return result;
    }

    gsvBasketSoa.prototype.presendOrder = function () {
        if(this.$isSDEKDelivery.value) {
            let field = $(`[name="ORDER_PROP_${this.data.model.PROPERTIES.SDEK__ADDRESS__ID}"`);
            let address = field.val();
            this.data.model.PROPERTIES.ADDRESS = address;
            this.data.model.PROPERTIES.ADDRESS_2 = '';
        } else if(this.$isSelfDelivery.value) {
            this.data.model.PROPERTIES.ADDRESS = '-';
            this.data.model.PROPERTIES.ADDRESS_2 = '';
        }
    }

    gsvBasketSoa.prototype.postreceiveOrder = function () {
        if(this.$isSDEKDelivery.value) {
            let field = $(`[name="ORDER_PROP_${this.data.model.PROPERTIES.SDEK__ADDRESS__ID}"`);
            let address = this.data.model.PROPERTIES.ADDRESS;
            field.val(address);
        } else if(this.$isSelfDelivery.value) {
            this.data.model.PROPERTIES.ADDRESS = '';
            this.data.model.PROPERTIES.ADDRESS_2 = '';
        }
    }

    gsvBasketSoa.prototype.emptySdek = function () {
        let field = $(`[name="ORDER_PROP_${this.data.model.PROPERTIES.SDEK__ADDRESS__ID}"`);
        if(field.length) {
            field.val('');
        }
    }

    gsvBasketSoa.prototype.requiredFields = function () {

        let data = this.data;
        let requiredFields = {};

        if(data && data.wasConfirmed) {
            let properties = data.model.PROPERTIES;
            for(let i in data.order.ORDER_PROP.properties) {
                let property = data.order.ORDER_PROP.properties[i];
                if(property.REQUIRED === 'Y' || property.REQUIRED === true) {
                    let code = property.CODE;
                    let val = properties[code];
                    val = (val || '').trim();
                    if(property.IS_PAYER === 'Y' || property.IS_PAYER === true || code == 'FIO') {
                        let firstName = (properties.FIRST_NAME || '').trim();
                        if(firstName.length == 0) {
                            requiredFields['FIRST_NAME'] = true;
                        }
                        let lastName = (properties.LAST_NAME || '').trim();
                        if(lastName.length == 0) {
                            requiredFields['LAST_NAME'] = true;
                        }
                        continue;
                    } else if(property.IS_EMAIL === 'Y' || property.IS_EMAIL === true) {
                        if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
                            val = '';
                        }
                    } else if(property.IS_PHONE === 'Y' || property.IS_PHONE === true) {
                        val = val.replace(/\D+/g, '');
                    } else if(code === 'CITY') {
                        if(!data.model.CITY) {
                            val = '';
                        }
                    }
                    if(val.length == 0) {
                        requiredFields[code] = true;
                    }
                }
            }
        }

        return requiredFields;
    }

    gsvBasketSoa.prototype.isLoading = function() {
        return this.data.updateInProgress > 0;
    }

    gsvBasketSoa.prototype.isConfirming = function() {
        return this.data.confirmInProgress > 0;
    }

    gsvBasketSoa.prototype.isUpdatingCoupon = function() {
        return this.data.couponInProgress > 0;
    }

    gsvBasketSoa.prototype.watchLoading = function(value, previous) {
        /*let isLoading = this.$isLoading.value;
        let $loader = $('.preloader');
        if(isLoading) {
            $loader.removeClass('d-none');
        } else {
            $loader.addClass('d-none');
        }*/
        let val = 0;
        if(value) {
            val++;
        }
        if(previous) {
            val--;
        }
        window.deltaLoading(val);
    }

    gsvBasketSoa.prototype.ajax = async function (AJAX, action, data) {
        let url = AJAX.url;
        if(typeof data === 'undefined') {
            if(typeof action === 'object') {
                data = action;
                action = null;
            } else {
                data = {};
            }
        }
        if (action != null) {
            url = url.replace('#action#', action);
        } else {
            url = url.replace('#action#', '');
        }
        let post = Object.assign({}, AJAX.post, data);

        let body = new URLSearchParams(post);
        let response = await fetch(
            url,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body
            }
        );

        let result = await response.json();
        return result;
    }

    gsvBasketSoa.prototype.ajaxMe = async function(action, body) {
        return await this.ajax(this._opt.AJAX, action, body);
    }

    gsvBasketSoa.prototype.ajaxSoa = async function(action, body) {
        return await this.ajax(this._opt.SOA_AJAX, action, body);
    }

    gsvBasketSoa.prototype.ajaxBasket = async function(action, body) {
        return await this.ajax(this._opt.BASKET_AJAX, action, body);
    }

    gsvBasketSoa.prototype.emptyAddr = function () {
        this.data.model.PROPERTIES.ADDRESS = this.data.model.PROPERTIES.ADDRESS_2 = '';
        this.emptySdek();
    }

    gsvBasketSoa.prototype.isSDEKDelivery = function () {
        if(window.IPOLSDEK_pvz) {
            for(var i in IPOLSDEK_pvz.deliveries) {
                for (var j in IPOLSDEK_pvz.deliveries[i]) {
                    if(this.data.model.DELIVERY_ID == j) {
                        return i || true;
                    }
                }
            }
        }
        return false;
    }

    gsvBasketSoa.prototype.isSelfDelivery = function () {
        if(this.$isSDEKDelivery.value) {
            return true;
        }

        let delivery = null;
        let model = this.data.model;
        for(let deliveryOption of model.DELIVERY) {
            if(deliveryOption.ID == model.DELIVERY_ID) {
                delivery = deliveryOption;
                break;
            }
        }

        if(delivery) {
            return !!delivery.IS_SELF_DELIVERY;
        }

        return false;
    }

    gsvBasketSoa.plural_form = function(number, item, lang = 'ru')
    {
        if(typeof item === 'string') {
            item = item.split('/');
        }
        lang = (lang || '').toLowerCase();
        if(lang == 'ru') {
            const cases = [2, 0, 1, 1, 1, 2];
            return item[(number % 100 > 4 && number % 100 < 20) ? 2 : cases[Math.min(number % 10, 5)]];
        } else if(lang == 'en') {
            //0 itemS <!--
            return number == 1 ? item[0] : item[1];
        } else {
            return null;
        }
    }

    return gsvBasketSoa;

}));