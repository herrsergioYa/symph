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
}(typeof self !== 'undefined' ? self : this, function (Vue, vSelect2, v3PopupJq, vIMask) {

    let {createApp, ref, reactive, computed, onMounted, watch, nextTick, watchEffect } = Vue || {};

    let reinit = function () {
        //HACK: Against Bitrix's script reordering. ;-(
        //Doesn't always work, unfortunately.
        if (typeof window !== 'undefined') {
            Vue = Vue || window.Vue;
            ({createApp, ref, reactive, computed, onMounted, watch, nextTick, watchEffect } = Vue || {});
            vSelect2 = vSelect2 || window.vSelect2;
            v3PopupJq = v3PopupJq || window.v3PopupJq;
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

        let directives = {
            //'city-popup': vSelect2.createPopup(this.desc),
            //'i-mask': vIMask.vIMask,
        };

        let components = {
            //'vMask': vIMask.vMask,
            v3PopupJq,
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
            step: !this._jsData.IS_AUTHORIZED ? 'auth' : 'delivery',
            wasDeliveryApproved: false,
            wasPaymentApproved: false,
            edited: false,
            paySystemSelectedId : 0,
        });
        this.data = data;

        let opt = reactive(this._opt);
        this.opt = opt;

        let order = this.prepareData(this._jsData);
        this.data.model = order;
        this.parseLocation(this.data.model, this._jsData);

        this.data.firstLoad = false;

        watch(() => data.model.COUNTRY?.ID ?? 0, (...args) => this.onCountryChanged(...args));
        watch(() => data.model.CITY?.ID ?? 0, (...args) => this.onCityChanged(...args));

        watch(() => data.model.PERSON_TYPE_ID, (...args) => this.onPersonTypeChanged(...args));
        watch(() => data.model.DELIVERY_ID, (...args) => this.onDeliveryChanged(...args));
        watch(() => data.model.PAY_SYSTEM_ID, (...args) => this.onPaySystemChanged(...args));

        watchEffect(() => this.watchOffers());

        let isAddressEntered = this.isAddressEntered = computed(() => this._isAddressEntered());
        let isDeliveryEntered = this.isDeliveryEntered = computed(() => this._isDeliveryEntered());
        let isPaymentEntered = this.isPaymentEntered = computed(() => this._isPaymentEntered());
        let requiredFields = this.requiredFields = computed(() => this._requiredFields());
        let isSDEKDelivery = this.isSDEKDelivery = computed(() => this._isSDEKDelivery());
        let isSelfDelivery = this.isSelfDelivery = computed(() => this._isSelfDelivery());
        let isLoading = this.isLoading = computed(() => this._isLoading());
        let isConfirming = this.isConfirming = computed(() => this._isConfirming());
        let isUpdatingCoupon = this.isUpdatingCoupon = computed(() => this._isUpdatingCoupon());

        let firstName = this.firstName = computed({
            get: () => this._getFirstName(),
            set: (val) => this._setFirstName(val),
        });
        let lastName = this.lastName = computed({
            get: () => this._getLastName(),
            set: (val) => this._setLastName(val),
        });

        let useBonuses = this.useBonuses = computed({
            get: () => this._getUseBonuses(),
            set: (val) => this._setUseBonuses(val),
        });

        let paySystemSelected = this.paySystemSelected = computed(() => this._getPaySystemSelected());

        let isRussia = this.isRussia = computed(() => this._isRussia());
        let isUsingDaData = this.isUsingDaData = computed(() => this._isUsingDaData());

        let basketTable = this.basketTable = ref(null);

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

        let ajaxCountriesUrl = this._opt.AJAX.url.replace('#action#', 'listCountries');
        let ajaxCitiesUrl = this._opt.AJAX.url.replace('#action#', 'listCities');
        let ajaxCitiesQuery = params => ({
                countryId: this.data.model.COUNTRY?.ID ?? 0,
                q: params.term || '',
        });
        let ajaxRuAddressUrl = this._opt.AJAX.url.replace('#action#', 'listRuAddresses');
        let ajaxRuAddressQuery = params => ({
                countryId: this.data.model.COUNTRY?.ID ?? 0,
                cityId: this.data.model.CITY?.ID ?? 0,
                q: params.term || '',
        });

        if(window.IPOLSDEK_pvz) {
            onMounted(() => {
                if (this.isSDEKDelivery.value) {
                    //We should force initialization of SDEK!
                    this.updateOrder();
                }
            })
        }

        if(this.data.order.USER && !this.data.order.USER.ACCOUNT) {
            this.updateBonuses();
        }

        return {
            data,
            opt,

            isAddressEntered,
            isDeliveryEntered,
            isPaymentEntered,
            requiredFields,
            isSDEKDelivery,
            isSelfDelivery,
            isLoading,
            isConfirming,
            isUpdatingCoupon,
            isRussia,
            isUsingDaData,

            firstName,
            lastName,
            useBonuses,

            paySystemSelected,

            basketTable,

            getTabStyles: (...args) => this.getTabStyles(...args),
            isTabActive: (...args) => this.isTabActive(...args),
            getInputStyles: (...args) => this.getInputStyles(...args),
            isMissing: (...args) => this.isMissing(...args),
            getCouponStyles: (...args) => this.getCouponStyles(...args),

            returnToStep: (...args) => this.returnToStep(...args),
            onSelectPaySystem: (...args) => this.onSelectPaySystem(...args),
            onCheckPaySystem: (...args) => this.onCheckPaySystem(...args),

            applyCoupon: (...args) => this.applyCoupon(...args),
            removeCoupon: (...args) => this.removeCoupon(...args),

            onEditItem: (...args) => this.onEditItem(...args),
            onDeleteItem: (...args) => this.onDeleteItem(...args),

            applyQty: (...args) => this.applyQty(...args),
            removeQty: (...args) => this.removeQty(...args),
            plural_form: (...args) => gsvBasketSoa.plural_form(...args),

            getSkuPropEditedVals: (...args) => this.getSkuPropEditedVals(...args),

            onDeliveryApproved: (...args) => this.onDeliveryApproved(...args),
            onPaymentApproved: (...args) => this.onPaymentApproved(...args),

            onConfirmOrder: (...args) => this.onConfirmOrder(...args),

            preventDefault: (...args) => this.preventDefault(...args),

            ajaxCountriesUrl,
            ajaxCitiesUrl,
            ajaxCitiesQuery,
            ajaxRuAddressUrl,
            ajaxRuAddressQuery,
        };
    }

    gsvBasketSoa.prototype.prepareData = function (order) {

        let result = {};

        order.COUNTRY_LIST_DEFAULT = this.getCountryList(order.COUNTRY_LIST);

        result.PROPERTIES = this.parseProperties(order);
        result.DESCRIPTION = order.ORDER_DESCRIPTION ? order.ORDER_DESCRIPTION : '';
        result.ITEMS = this.getItems(order.GRID.ROWS);

        ({id:result.DELIVERY_ID, list:result.DELIVERY} = this.getEntityList('DELIVERY_ID', order.DELIVERY));
        ({id:result.PAY_SYSTEM_ID, list:result.PAY_SYSTEM} = this.getEntityList('PAY_SYSTEM_ID', order.PAY_SYSTEM));
        ({id:result.PERSON_TYPE_ID, list:result.PERSON_TYPE} = this.getEntityList('PERSON_TYPE_ID', order.PERSON_TYPE));
        ({id:result.PROFILE_ID, list:result.USER_PROFILES} = this.getEntityList('PROFILE_ID', order.USER_PROFILES));

        //this.parseLocation(result, order);
        result.COUPONS = this.getCoupons(order.COUPON_LIST);

        return result;
    }

    gsvBasketSoa.prototype.getCountryList = function (list) {
        let result = {};
        for (let country of list) {
            result[country.ID] = Object.assign({}, country);
        }
        return result;
    }

    gsvBasketSoa.prototype.getItems = function (ROWS) {
        let items = [];
        for(let i in ROWS) {
            let item = ROWS[i];
            item = Object.assign({columns:item.columns, edited: false}, item.data);

            let properties = {};
            for(let j in item.PROPS) {
                let property = item.PROPS[j];
                properties[property.CODE] = property;
            }
            item.PROPS = properties;

            items.push(item);
        }
        return items;
    }

    gsvBasketSoa.prototype.getEntityList = function (fieldId, entities) {
        let firstLoad = this.data.firstLoad;
        let list = [];
        let resultId;
        if(firstLoad) {
            resultId = 0;
        } else {
            resultId = this.data.model[fieldId];
        }
        for(let i in entities) {
            let entity = entities[i];
            if(entity.CHECKED == 'Y') {
                resultId = entity.ID;
            } else if(fieldId == 'PAY_SYSTEM_ID') {
                //continue;
            }
            list.push(entity);
        }
        return {id:resultId, list};
    }

    gsvBasketSoa.prototype.parseLocation = function (result, order) {
        if(order.COUNTRY) {
            result.COUNTRY = order.COUNTRY;
            result.PROPERTIES.COUNTRY = result.COUNTRY.NAME;
            if(order.CITY) {
                result.CITY = order.CITY;
                result.PROPERTIES.CITY = result.CITY.NAME;
                result.ADDRESS = {
                    ID: (result.PROPERTIES.ADDRESS || '').trim().length,
                    NAME: result.PROPERTIES.ADDRESS,
                    CODE: result.PROPERTIES.ZIP,
                };
            } else {
                result.CITY = false;
                //Do NOT touch result.PROPERTIES.CITY 'cause we should allow to edit it.
                this.emptyAddr();
            }
        } else {
            result.COUNTRY = {
                'ID': 0,
                'CODE': "",
                'NAME': '',
            };
            //Do NOT touch result.PROPERTIES.COUNTRY 'cause we should allow to edit it.
            result.CITY = false;
            result.PROPERTIES.CITY = '';
            this.emptyAddr();
        }
    }

    gsvBasketSoa.prototype.getCoupon = function (basketCoupon) {
        return {
            COUPON: basketCoupon.COUPON,
            ERROR: basketCoupon.STATUS != 4 && basketCoupon.STATUS != 8 ? basketCoupon.JS_CHECK_CODE || true : false,
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

    gsvBasketSoa.prototype.getCouponStyles = function (coupon)
    {
        let styles = [];

        let val = coupon?.COUPON || '';
        if(val) {
            styles.push('input-filled');
        }

        if(coupon?.ERROR) {
            styles.push('input-error');
        }

        if(coupon?.APPLIED) {
            styles.push('input-disabled');
        }

        return styles;
    }

    gsvBasketSoa.prototype._isAddressEntered = function () {
        if(!this.data.model.COUNTRY || !this.data.model.COUNTRY.ID || !this.data.model.CITY || !this.data.model.CITY.ID) {
            return false;
        }
        if(this.data.model.LOCATION == '' || 'LOCATION' in this.requiredFields.value) {
            //Is it redundant?
            return false;
        }
        if('CITY' in this.requiredFields.value || 'ADDRESS' in this.requiredFields.value) {
            return false;
        }

        return true;
    }

    gsvBasketSoa.prototype._isDeliveryEntered = function () {
        if(!this.isAddressEntered.value) {
            return false;
        }

        if(this.firstName.value == '' || this.lastName.value == '' || 'PHONE' in this.requiredFields.value) {
            return false;
        }

        return true;
    }

    gsvBasketSoa.prototype._isPaymentEntered = function () {
        return true;
    }

    gsvBasketSoa.prototype.onCountryChanged = async function (countryId, oldCountryId) {
        this.data.model.COUNTRY = this.data.order.COUNTRY_LIST_DEFAULT[countryId];
        if(this.data.model.COUNTRY && this.data.model.COUNTRY.ID == countryId) {
            this.data.model.PROPERTIES.LOCATION = this.data.model.COUNTRY.CODE;
        } else {
            this.data.model.PROPERTIES.LOCATION = '';
        }
        this.data.model.CITY = false;
        this.data.model.PROPERTIES.CITY = '';

        this.emptyAddr();//Смена страны не всегда приводит к смене города (он мог быть не выбран)
        await this.updateOrder(false);

    }

    gsvBasketSoa.prototype.onCityChanged = async function (cityId, oldCityId) {
        if(this.data.model.CITY && this.data.model.CITY.ID == cityId) {
            this.data.model.PROPERTIES.LOCATION = this.data.model.CITY.CODE;
        } else if(this.data.model.COUNTRY) {
            this.data.model.PROPERTIES.LOCATION = this.data.model.COUNTRY.CODE;
        } else {
            this.data.model.PROPERTIES.LOCATION = '';
        }
        this.emptyAddr();
        await this.updateOrder();
    }

    gsvBasketSoa.prototype.onPersonTypeChanged = async function (deliveryId, oldDeliveryId) {
        await this.updateOrder(false);
    }

    gsvBasketSoa.prototype.onDeliveryChanged = async function (deliveryId, oldDeliveryId) {
        let wasSelfDelivery = (this.isISelfDelivery(oldDeliveryId) || this.isItSDEKDelivery(oldDeliveryId)),
            isSelfDelivery = (this.isItSelfDelivery(deliveryId) || this.isItSDEKDelivery(deliveryId));
        if(wasSelfDelivery !== isSelfDelivery) {
            this.emptyAddr();
        }
        await this.updateOrder(false);
    }

    gsvBasketSoa.prototype.onPaySystemChanged = async function (deliveryId, oldDeliveryId) {
        await this.updateOrder(false);
    }

    gsvBasketSoa.prototype.emptyAddr = function () {
        this.data.model.PROPERTIES.ADDRESS = /*this.data.model.PROPERTIES.ADDRESS_2 = */'';
        this.data.model.PROPERTIES.ZIP = '';
        this.data.model.ADDRESS = false;
        this.emptySdek();
    }

    gsvBasketSoa.prototype.applyCoupon = async function (coupon) {

        if(this.isUpdatingCoupon.value){
            return;
        }

        let body = {};
        let code  = coupon.COUPON || '';
        code = code.trim();
        body['basket[coupon]'] = code;

        this.data.couponInProgress++;
        let result = await this.ajaxBasket('recalculateAjax', body);
        this.data.couponInProgress--;

        this.data.model.COUPONS = this.getCoupons(result.BASKET_DATA.COUPON_LIST);
        //coupon.APPLIED = true;
        //coupon.ERROR = true;
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

        if(this.isUpdatingCoupon.value){
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
            'PROFILE_ID': data.model.PROFILE_ID,
            'ORDER_DESCRIPTION': data.model.DESCRIPTION,
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
            'ORDER_DESCRIPTION': data.model.DESCRIPTION,
        }

        Object.assign(body, this.fillProperties(false));

        if(!confirm) {
            body = {
                order: body,
                sessid: body.sessid,
            }
            //body.sessid = body.order.sessid;
            body[AJAX.act_var] = 'refreshOrderAjax';
        }

        body.via_ajax = 'Y';
        body.SITE_ID = this._opt.SITE_ID;
        body.signedParamsString = AJAX.post.signedParamsString;

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
        /*let fioMatch = properties.FIO.match(/^(\S+)\s+(\S+)$/);
        if(fioMatch) {
            properties.FIRST_NAME = fioMatch[1];
            properties.LAST_NAME = fioMatch[2];
        } else {
            properties.FIRST_NAME = properties.FIO;
            properties.LAST_NAME = '';
        }*/

        return properties;
    }

    gsvBasketSoa.prototype.fillProperties = function (confirm) {
        let result = {};
        let order = this.data.order;
        let model = this.data.model;

        //model.PROPERTIES.FIO = model.PROPERTIES.FIRST_NAME + ' ' + model.PROPERTIES.LAST_NAME;

        for (let i in order.ORDER_PROP.properties) {
            let property = order.ORDER_PROP.properties[i];
            if(property.CODE)
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
        if($event) {
            $event.preventDefault();
        }
    }

    gsvBasketSoa.prototype.applyQty = async function(ROW) {
        let edited = this.data.edited;

        if(!edited || edited.ROW != ROW) {
            return;
        }

        let body = {};
        let bodyQty = {};

        bodyQty[`basket[QUANTITY_${ROW.data.ID}]`] = edited.CURRENT.QTY;

        /*for(let code in this.data.order.SKU_PROPS.PROP_VALS) {
            body[`basket[OFFER_${ROW.data.ID}][${code}]`] = edited.CURRENT.PROPS[code];
        }*/
        body[`basket[OFFER_${ROW.data.ID}]`] = edited.OFFERS[0].ID;

        this.data.updateInProgress++;
        await this.ajaxBasket('recalculateAjax', body);
        await this.ajaxBasket('recalculateAjax', bodyQty);
        await this.updateOrder();
        this.data.updateInProgress--;

        $("#cart-item-edit-modal [data-modal-close]")[0].click();

        this.data.cartVersion++;
        $(document).trigger('refreshBasket');
    }

    gsvBasketSoa.prototype.isAvailable = function(offer) {
        return offer && (offer.AVAILABLE === 'Y' || offer.AVAILABLE === true);
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

        let result = await this.processOrder(false, profileChanged);

        if (this.data.needsUpdateOrder) {
            this.data.needsUpdateOrder = false;
            await this.updateOrder();
        } else if (updateNum == this.data.lastOrderUpdate) {

            if('SDEK' in result) {
                let temp = result.SDEK;
                delete result.SDEK;
                temp.order = result;

                if(window.IPOLSDEK_pvz) {
                    IPOLSDEK_pvz.onLoad();
                    IPOLSDEK_pvz.onLoad(temp);
                }
            }

            if(result.success === 'N') {
                if(result.redirect) {
                    window.location.href = result.redirect;
                } else {
                    window.location.reload(true);
                }
            }

            if(this.data.order.USER && this.data.order.USER.ACCOUNT
                && result.USER && !result.USER.ACCOUNT
                && this.data.order.USER.ID == result.USER.ID) {
                result.USER.ACCOUNT = Object.assign({}, this.data.order.USER.ACCOUNT);
            }

            this.data.order = this._jsData = result;
            this.data.model = this.prepareData(result);
            this.parseLocation(this.data.model, result);

            this.postreceiveOrder();
        } else {
            //Already updated. At least I hope so.
        }
        this.data.updateInProgress--;
    }

    gsvBasketSoa.prototype.onDeliveryApproved = async function ($event) {
        this.data.wasDeliveryApproved = true;
        this.presendOrder();
        if(this.isDeliveryEntered.value == false) {
            this.postreceiveOrder();
            return;
        }
        this.postreceiveOrder();
        this.data.step = 'payment';
    }

    gsvBasketSoa.prototype.onPaymentApproved = async function ($event) {
        this.data.wasPaymentApproved = true;
        this.presendOrder();
        if(this.isPaymentEntered.value == false) {
            this.postreceiveOrder();
            return;
        }
        this.postreceiveOrder();
        await this.onConfirmOrder($event);
    }

    gsvBasketSoa.prototype.onConfirmOrder = async function ($event) {
        this.data.wasConfirmed = true;

        this.preventDefault($event);

        if(this.isConfirming.value) {
            return;
        }

        this.presendOrder();
        if(Object.keys(this.requiredFields.value).length > 0) {
            this.postreceiveOrder();//Reverting the changes
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
        if(this.isSDEKDelivery.value) {
            let field = $(`[name="ORDER_PROP_${this.data.model.PROPERTIES.SDEK__ADDRESS__ID}"`);
            let address = field.length ? field.val() : '';
            this.data.model.PROPERTIES.ADDRESS = address;
            //this.data.model.PROPERTIES.ADDRESS_2 = '';
            if((this.data.model.PROPERTIES.ZIP || '').trim() == '') {
                this.data.model.PROPERTIES.ZIP = '-';
            }
        } else if(this.isSelfDelivery.value) {
            this.data.model.PROPERTIES.ADDRESS = '-';
            //this.data.model.PROPERTIES.ADDRESS_2 = '';
            if((this.data.model.PROPERTIES.ZIP || '').trim() == '') {
                this.data.model.PROPERTIES.ZIP = '-';
            }
        }
        if(this.isUsingDaData.value) {
            if(this.data.model.ADDRESS && this.data.model.ADDRESS.ID) {
                this.data.model.PROPERTIES.ADDRESS = this.data.model.ADDRESS.NAME;
                this.data.model.PROPERTIES.ZIP = this.data.model.ADDRESS.CODE;
            } else {
                this.data.model.PROPERTIES.ADDRESS = '';
                this.data.model.PROPERTIES.ZIP = '';
            }
        }
    }

    gsvBasketSoa.prototype.postreceiveOrder = function () {
        if(this.isSDEKDelivery.value) {
            let field = $(`[name="ORDER_PROP_${this.data.model.PROPERTIES.SDEK__ADDRESS__ID}"`);
            if(field.length) {
                let address = this.data.model.PROPERTIES.ADDRESS;
                field.val(address);
            }
            if(this.data.model.PROPERTIES.ZIP == '-') {
                this.data.model.PROPERTIES.ZIP = '';
            }
        } else if(this.isSelfDelivery.value) {
            this.data.model.PROPERTIES.ADDRESS = '';
            //this.data.model.PROPERTIES.ADDRESS_2 = '';
            if(this.data.model.PROPERTIES.ZIP == '-') {
                this.data.model.PROPERTIES.ZIP = '';
            }
        } else {
            if(this.data.model.PROPERTIES.ADDRESS == '-') {
                this.data.model.PROPERTIES.ADDRESS = '';
                //this.data.model.PROPERTIES.ADDRESS_2 = '';
            }
            if(this.data.model.PROPERTIES.ZIP == '-') {
                this.data.model.PROPERTIES.ZIP = '';
            }
        }
    }

    gsvBasketSoa.prototype.emptySdek = function () {
        let field = $(`[name="ORDER_PROP_${this.data.model.PROPERTIES.SDEK__ADDRESS__ID}"`);
        if(field.length) {
            field.val('');
        }
    }

    gsvBasketSoa.prototype._requiredFields = function () {

        let data = this.data;
        let requiredFields = {};

        if(data && (data.wasConfirmed || data.wasDeliveryApproved)) {
            let properties = data.model.PROPERTIES;
            for(let i in data.order.ORDER_PROP.properties) {
                let property = data.order.ORDER_PROP.properties[i];
                if(property.REQUIRED === 'Y' || property.REQUIRED === true) {
                    let code = property.CODE;
                    let val = properties[code];
                    val = (val || '').trim();
                    if(property.IS_PAYER === 'Y' || property.IS_PAYER === true || code == 'FIO') {
                        let firstName = this.firstName.value;
                        if(firstName.length == 0) {
                            requiredFields['FIRST_NAME'] = true;
                        }
                        let lastName = this.lastName.value;
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
                        if(!data.model.CITY || !data.model.CITY.ID) {
                            val = '';
                        }
                    } else if(code === 'COUNTRY') {
                        if(!data.model.COUNTRY || !data.model.COUNTRY.ID) {
                            val = '';
                        }
                    } else if(code === 'ADDRESS' || code === 'ZIP') {
                        if(this.isUsingDaData.value && !(this.data.model.ADDRESS && this.data.model.ADDRESS.ID
                            //HACK: W/o a ZIP
                            && this.data.model.ADDRESS.CODE)) {
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

    gsvBasketSoa.prototype.getTabStyles = function (step) {

        let styles = [];

        if(step == this.data.step) {
            styles.push('flex-grow-1');
            styles.push('flex-column');
        } else {
            styles.push('flex-grow-0');
            if(step > this.data.step) {
                styles.push('border-gray');
                styles.push('c-gray');
            } else {
                styles.push('flex-column');
            }
        }

        if(this.data.step != 'delivery' || step != 'payment') {
            styles.push('d-flex');
        }

        return styles;
    }

    gsvBasketSoa.prototype.isTabActive = function (step) {
        return (step == this.data.step);
    }

    gsvBasketSoa.prototype.getInputStyles = function (field) {

        let styles = [];

        if(field in this.requiredFields.value) {
            styles.push('input-error');
        }

        let val;
        if(field == 'FIRST_NAME') {
            val = this.firstName.value;
        } else if(field == 'LAST_NAME') {
            val = this.lastName.value;
        } else {
            val = this.data.model.PROPERTIES[field];
        }

        val = val || '';
        val = val.trim();
        if(val) {
            styles.push('input-filled');
        }

        return styles;
    }

    gsvBasketSoa.prototype.isMissing = function (name) {
        return (name in this.requiredFields.value);
    }

    gsvBasketSoa.prototype._isLoading = function() {
        return this.data.updateInProgress > 0;
    }

    gsvBasketSoa.prototype._isConfirming = function() {
        return this.data.confirmInProgress > 0;
    }

    gsvBasketSoa.prototype._isUpdatingCoupon = function() {
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
        //window.deltaLoading(val);
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

    gsvBasketSoa.prototype._isSDEKDelivery = function () {
        return this.isItSDEKDelivery(this.data.model.DELIVERY_ID);
    }

    gsvBasketSoa.prototype.isItSDEKDelivery = function (id) {
        if(window.IPOLSDEK_pvz) {
            for(var i in IPOLSDEK_pvz.deliveries) {
                for (var j in IPOLSDEK_pvz.deliveries[i]) {
                    if(id == j) {
                        return i || true;
                    }
                }
            }
        }
        return false;
    }

    gsvBasketSoa.prototype._isSelfDelivery = function () {
        if(this.isSDEKDelivery.value) {
            return true;
        }

        return this.isItSelfDelivery(this.data.model.DELIVERY_ID);
    }

    gsvBasketSoa.prototype.isItSelfDelivery = function (id) {

        let delivery = null;
        let model = this.data.model;
        for(let deliveryOption of model.DELIVERY) {
            if(deliveryOption.ID == id) {
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

    gsvBasketSoa.prototype._getFirstName = function(name) {
        if(this.data.model.PROPERTIES?.FIO) {
            let arr = this.data.model.PROPERTIES.FIO.split(' ');
            while (arr.length > 2) {
                arr.pop();
            }
            while (arr.length < 2) {
                arr.push('');
            }
            return arr[0];
        }
        return '';
    }

    gsvBasketSoa.prototype._getLastName = function(name) {
        if(this.data.model.PROPERTIES?.FIO) {
            let arr = this.data.model.PROPERTIES.FIO.split(' ');
            while (arr.length > 2) {
                arr.pop();
            }
            while (arr.length < 2) {
                arr.push('');
            }
            return arr[1];
        }
        return '';
    }

    gsvBasketSoa.prototype._setFirstName = function(name) {
        let arr = [];
        if(this.data.model.PROPERTIES?.FIO) {
            arr = this.data.model.PROPERTIES.FIO.split(' ');
            while(arr.length > 2) {
                arr.pop();
            }
            while (arr.length < 2) {
                arr.push('');
            }
            arr[0] = (name || '').replace(/\s+/g, '');
        }
        this.data.model.PROPERTIES.FIO = arr.join(' ');
    }

    gsvBasketSoa.prototype._setLastName = function(name) {
        let arr = [];
        if(this.data.model.PROPERTIES?.FIO) {
            arr = this.data.model.PROPERTIES.FIO.split(' ');
            while(arr.length > 2) {
                arr.pop();
            }
            while (arr.length < 2) {
                arr.push('');
            }
            arr[1] = (name || '').replace(/\s+/g, '');
        }
        this.data.model.PROPERTIES.FIO = arr.join(' ');
    }

    gsvBasketSoa.prototype._getUseBonuses = function() {
        if(this.data.model.PROPERTIES?.USE_BONUSES) {
            return this.data.model.PROPERTIES?.USE_BONUSES === 'Y';
        }
        return false;
    }

    gsvBasketSoa.prototype._setUseBonuses = function(use) {
        this.data.model.PROPERTIES.USE_BONUSES = use ? 'Y' : 'N';
    }

    gsvBasketSoa.prototype._getPaySystemSelected = function() {

        let paySystemId = this.data.paySystemSelectedId;
        for (let i in this.data.model.PAY_SYSTEM) {
            let paySystem = this.data.model.PAY_SYSTEM[i];
            if(paySystem.ID == paySystemId) {
                return paySystem;
            }
        }

        return null;
    }

    gsvBasketSoa.prototype.onEditItem = function(ROW) {

        let CURRENT = {
            PROPS: Object.assign({}, ROW.data.PROPS),
            QTY: ROW.data.QUANTITY,
        }

        this.data.edited = {
            ROW,
            CURRENT,
            QTY: ROW.data.AVAILABLE_QUANTITY,
            OFFERS: [],
            VARIANTS: {}
        };

    }

    gsvBasketSoa.prototype.watchOffers = function() {
        if(this.data.edited && this.data.edited.ROW.data.OFFERS) {
            let variants = {};
            let edited = this.data.edited;
            let offers = [...this.data.edited.ROW.data.OFFERS];
            for(let code of this.data.order.SKU_PROPS.PROP_CODES) {
                let res = [];
                for(let offer of offers) {
                    let v = offer.PROPS[code];
                    if(res.indexOf(v) < 0) {
                        res.push(v);
                    }
                }
                variants[code] = res;

                let found = false;
                for(let id of res) {
                    if(id == edited.CURRENT.PROPS[code]) {
                        found = true;
                    }
                }
                if(!found) {
                    for(let id of res) {
                        edited.CURRENT.PROPS[code] = id;
                        break;
                    }
                }

                let val = edited.CURRENT.PROPS[code];
                let newOffers = [];
                for(let offer of offers) {
                    let v = offer.PROPS[code];
                    if(v == val) {
                        newOffers.push(offer);
                    }
                }
                offers = newOffers;
            }
            edited.VARIANTS = variants;
            edited.OFFERS = offers;

            let maxQty = 0;
            for(let offer of offers) {
                if(maxQty < offer.QTY) {
                    maxQty = offer.QTY;
                }
            }
            edited.QTY = maxQty;
            if(edited.CURRENT.QTY > maxQty) {
                edited.CURRENT.QTY = maxQty;
            }
        }
    }

    gsvBasketSoa.prototype.getSkuPropEditedVals = function(edited) {
        let result =  [];
        //let edited = this.data.edited;
        if(edited) {
            for (let code of this.data.order.SKU_PROPS.PROP_CODES) {
                let prop = Object.assign({}, this.data.order.SKU_PROPS.PROP_VALS[code]);
                let vals = [];
                for (let val of prop.VALUES) {
                    //if (edited.VARIANTS[prop.CODE].indexOf(val.ID) >= 0) {
                    for(let variantId of edited.VARIANTS[prop.CODE]) {
                        if(variantId == val.ID) {
                            vals.push(val);
                            break;
                        }
                    }
                }
                prop.VALUES = vals;

                result.push(prop);
            }
        }

        return result;
    }

    gsvBasketSoa.prototype.onDeleteItem = async function(ROW) {

        let $basketTable = $(this.basketTable.value);
        let $basketLine = $basketTable.find(`[data-soa-basket-item-id="${ROW.data.ID}"]`);

        let answer = await gsvJsHelper.fancyAnswer($basketLine[0]);

        if(answer) {
            await this.removeQty(ROW.data);
        }
    }

    gsvBasketSoa.prototype.returnToStep = async function(step) {

        if(step != 'auth' && step < this.data.step) {
            this.data.step = step;
        }
    }

    gsvBasketSoa.prototype.onSelectPaySystem = async function(paySystem) {
        this.data.paySystemSelectedId = paySystem.ID;
    }

    gsvBasketSoa.prototype.onCheckPaySystem = async function(paySystem) {
        this.data.model.PAY_SYSTEM_ID = paySystem.ID;
        $("#payment-info-modal [data-modal-close]")[0].click();
    }

    gsvBasketSoa.prototype.updateBonuses = async function(no1C = false) {
        if(this.data.order.USER) {
            let account = await this.ajaxMe('updateBonuses', {
                NO_1C: !!no1C,
                CURRENCY: this.data.order.USER.CURRENCY,
            });

            this.data.order.USER.ACCOUNT = account;
        }
    }

    gsvBasketSoa.prototype._isRussia = function() {
        return this.data.model.COUNTRY?.NAME === 'Russia' || this.data.model.COUNTRY?.NAME === 'Russian Federation'
            || this.data.model.COUNTRY?.NAME === 'Россия' || this.data.model.COUNTRY?.NAME === 'Российская Федерация';
    }

    gsvBasketSoa.prototype._isUsingDaData = function() {
        return this._isRussia() && !this.isSelfDelivery.value;
    }

    return gsvBasketSoa;

}));

//HACK: Remove after fixing the file "https://mongokhto.ru/work/ushatava/html2/js/main.js"

//открытие модального окна
$(document).on("click", "[data-modal]", function (e) {
    e.preventDefault();
    const modalId = $(this).data("modal");
    const modal = $(modalId);

    //закрываем все меню и поиск
    if (window.menuManager) {
        window.menuManager.hideAll();
    }

    //если открывается подокно, не закрываем основное окно
    if (modal.hasClass("sub-modal")) {
        modal.addClass("show");
        modalStack.push(modal);
        return;
    }

    //если уже открыто какое-то окно, закрываем его (кроме подокон)
    if (modalStack.length && !modalStack[modalStack.length - 1].hasClass("sub-modal")) {
        closeModal(modalStack.pop());
    }

    $(".side-modal-overlay").addClass("show");
    modal.addClass("show");
    $("body").addClass("side-modal-opened");
    modalStack.push(modal);
});

//закрытие по клику на подложку (закрывает ВСЕ окна)
$(document).on("click", ".side-modal-overlay", function () {
    while (modalStack.length) {
        closeModal(modalStack.pop());
    }
});

//закрытие по клику на кнопку закрытия
$(document).on("click", "[data-modal-close]", function () {
    const modal = $(this).closest(".side-modal");
    closeModal(modal); //используем общую функцию закрытия
});