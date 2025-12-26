(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(/*'gsvSaleBasketVue3Example', */['jquery', 'vue'], factory);
        // Or we can do that the 'global' way
        //define(/*'gsvSaleBasketVue3Example', */['jquery', 'vue'], function (jQuery, Vue) {
        //    return (root.gsvSaleBasketVue3Example = factory(jQuery, Vue));
        //});
    } else if (typeof module === 'object' && module.exports) {
        // Node. Does not work with strict CommonJS, but
        // only CommonJS-like environments that support module.exports,
        // like Node.
        module.exports = factory(require('jquery'), require('vue'));
    } else {
        // Browser globals
        root.gsvSaleBasketVue3Example = factory(root.jQuery, root.Vue);
    }
}(typeof self !== 'undefined' ? self : this, function (jQuery, Vue) {

    let $ = jQuery;
    let {reactive, ref, watch, watchEffect, computed, createApp} = Vue || {};

    let reinit = function () {
        //HACK: Against Bitrix's script reordering. ;-(
        //Doesn't always work, unfortunately.
        if (typeof window !== 'undefined') {
            $ = jQuery = jQuery || window.jQuery;
            Vue = Vue || window.Vue;
            ({reactive, ref, watch, watchEffect, computed, createApp} = Vue || {});
        }
    }

    BX.ready(reinit);

    let gsvSaleBasketVue3Example = function(id, data, opt) {

        reinit();

        this._data = data;
        this._opt = opt;

        this.app = {
            setup: () => this.init(),
        }
        this.vApp = createApp(this.app);
        this.vMountPoint = this.vApp.mount(`#${id}`);
    }

    gsvSaleBasketVue3Example.prototype.init = function () {

        let basket = reactive(this._data);
        let opt = reactive(this._opt);

        this.basket = basket;
        this.coupon = computed(() => this.getFirstCoupon());
        this.newCoupon = ref("");

        $(document).on('refreshBasket', (e, rootId) => this.onBasketUpdated(rootId));

        return {
            basket,
            opt,

            coupon: this.coupon,
            newCoupon: this.newCoupon,

            onPlus: (...args) => this.onPlus(...args),
            onMinus: (...args) => this.onMinus(...args),
            onUpdateQty: (...args) => this.onUpdateQty(...args),
            onDeleteItem: (...args) => this.onDeleteItem(...args),
            onApplyCoupon: (...args) => this.onApplyCoupon(...args),
            onRemoveCoupon: (...args) => this.onRemoveCoupon(...args),
            onUpdateBasket: (...args) => this.onUpdateBasket(...args),

            plus: (...args) => this.plus(...args),
            minus: (...args) => this.minus(...args),
            updateQty: (...args) => this.updateQty(...args),
            deleteItem: (...args) => this.deleteItem(...args),
            applyCoupon: (...args) => this.applyCoupon(...args),
            removeCoupon: (...args) => this.removeCoupon(...args),
            updateBasket: (...args) => this.updateBasket(...args),
        }
    }

    gsvSaleBasketVue3Example.prototype.onPlus = function (e, item) {
        if(e) {
            e.preventDefault();
        }
        return this.plus(item);
    }

    gsvSaleBasketVue3Example.prototype.onMinus = function (e, item) {
        if(e) {
            e.preventDefault();
        }
        return this.minus(item);
    }

    gsvSaleBasketVue3Example.prototype.onUpdateQty = function (e, item) {
        if(e) {
            e.preventDefault();
        }
        return this.updateQty(item);
    }

    gsvSaleBasketVue3Example.prototype.onDeleteItem = function (e, item) {
        if(e) {
            e.preventDefault();
        }
        return this.deleteItem(item);
    }

    gsvSaleBasketVue3Example.prototype.onApplyCoupon = function (e, coupon) {
        if(e) {
            e.preventDefault();
        }
        return this.applyCoupon(coupon);
    }

    gsvSaleBasketVue3Example.prototype.onRemoveCoupon = function (e, coupon) {
        if(e) {
            e.preventDefault();
        }
        return this.removeCoupon(coupon);
    }

    gsvSaleBasketVue3Example.prototype.onUpdateBasket = function (e) {
        if(e) {
            e.preventDefault();
        }
        return this.updateBasket();
    }


    gsvSaleBasketVue3Example.prototype.plus = async function (item) {
        item.QUANTITY++;
        await this.updateQty(item);
    }

    gsvSaleBasketVue3Example.prototype.minus = async function (item) {
        if(item.QUANTITY > 1) {
            item.QUANTITY--;
            await this.updateQty(item);
        }
    }

    gsvSaleBasketVue3Example.prototype.updateQty = async function (item) {

        if(item.QUANTITY < 1){
            item.QUANTITY = 1;
        }

        let result = await this.ajax('recalculateAjax', {
            basket: {
                ['QUANTITY_' + item.ID]: item.QUANTITY,
            }
        });

        this.applyBasketResult(result);

        await this.triggerBasketUpdate();
    }

    gsvSaleBasketVue3Example.prototype.deleteItem = async function (item) {

        let result = await this.ajax('recalculateAjax', {
            basket: {
                ['DELETE_' + item.ID]: 'Y',
            }
        });

        this.applyBasketResult(result);

        await this.triggerBasketUpdate();
    }

    gsvSaleBasketVue3Example.prototype.applyCoupon = async function (coupon) {

        let result = await this.ajax('recalculateAjax', {
            basket: {
                'coupon': (coupon || '').trim(),
            }
        });

        this.applyBasketResult(result);

        await this.triggerBasketUpdate();
    }

    gsvSaleBasketVue3Example.prototype.removeCoupon = async function (coupon) {

        let result = await this.ajax('recalculateAjax', {
            basket: {
                'delete_coupon': (coupon || '').trim(),
            }
        });

        this.applyBasketResult(result);

        await this.triggerBasketUpdate();
    }

    gsvSaleBasketVue3Example.prototype.ajax = async function (action, req = null) {
        let url = this._opt.AJAX_URL;
        let data = this.getAjaxReq(action);
        if(req) {
            data = Object.assign(data, req);
        }

        let result = await $.ajax(url, {
            'type': 'POST',
            'data': data,
            'dataType': 'json',
        });

        console.log(result);

        return result;
    }

    gsvSaleBasketVue3Example.prototype.getAjaxReq = function (action) {

        let data = {};

        data[this._opt.ACTION_VARIABLE] = action;
        data.via_ajax = 'Y';
        data.site_id = this._opt.siteId;
        data.site_template_id = this._opt.siteTemplateId;
        data.sessid = BX.bitrix_sessid();
        data.template = this._opt.template;
        data.signedParamsString = this._opt.signedParamsString;

        return data;
    }

    gsvSaleBasketVue3Example.prototype.applyBasketResult = function (result) {
        if(result.BASKET_DATA/*result.BASKET_REFRESHED*/) {
            this.applyBasketData(result.BASKET_DATA);
        }
        if(result.BASKET_DATA && result.BASKET_DATA.ERROR_MESSAGE) {
            alert(result.BASKET_DATA.ERROR_MESSAGE);
        }
    }

    gsvSaleBasketVue3Example.prototype.applyBasketData = function (basketData) {
        this.basket.BASKET_ITEM_RENDER_DATA = basketData.BASKET_ITEM_RENDER_DATA;
        this.basket.TOTAL_RENDER_DATA = basketData.TOTAL_RENDER_DATA;
        this.basket.EMPTY_BASKET = basketData.EMPTY_BASKET;
    }

    gsvSaleBasketVue3Example.prototype.getFirstCoupon = function () {
        let coupons = this.basket.TOTAL_RENDER_DATA.COUPON_LIST;
        coupons = Object.values(coupons);
        if(coupons.length) {
            return coupons[0];
        } else {
            return null;
        }
    }

    gsvSaleBasketVue3Example.prototype.updateBasket = async function () {
        let result = await this.ajax('refreshAjax', {});
        this.applyBasketResult(result);
    }

    gsvSaleBasketVue3Example.prototype.onBasketUpdated = async function (rootId) {
        if(rootId != this._opt.ROOT_ID) {
            await this.updateBasket();
        }
    }

    gsvSaleBasketVue3Example.prototype.triggerBasketUpdate = async function (selfToo = false) {
        $(document).trigger('refreshBasket', [selfToo ? '' : this._opt.ROOT_ID]);
    }

    // Just return a value to define the module export.
    // This example returns an object, but the module
    // can return a function as the exported value.
    return gsvSaleBasketVue3Example;
}));