(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(/*'gsvSaleBasketAjaxExample', */['jquery'], factory);
        // Or we can do that the 'global' way
        //define(/*'gsvSaleBasketAjaxExample', */['jquery'], function (jQuery) {
        //    return (root.gsvSaleBasketAjaxExample = factory(jQuery));
        //});
    } else if (typeof module === 'object' && module.exports) {
        // Node. Does not work with strict CommonJS, but
        // only CommonJS-like environments that support module.exports,
        // like Node.
        module.exports = factory(require('jquery'));
    } else {
        // Browser globals
        root.gsvSaleBasketAjaxExample = factory(root.jQuery);
    }
}(typeof self !== 'undefined' ? self : this, function (jQuery) {

    let $ = jQuery;

    let reinit = function () {
        //HACK: Against Bitrix's script reordering. ;-(
        //Doesn't always work, unfortunately.
        if (typeof window !== 'undefined') {
            $ = jQuery = jQuery || window.jQuery;
        }
    }

    BX.ready(reinit);

    let gsvSaleBasketAjaxExample = function(id, data, opt) {

        reinit();

        this.id = id;
        this.data = data;
        this.opt = opt;

        this.initBasket();
        this.initDocument();
    }

    gsvSaleBasketAjaxExample.prototype.initBasket = function () {
        this.$el = $(`#${this.id}`);

        let self = this;

        this.$el.find('.mini-cart-item a.remove').on('click', function(e) {
            self.onRemove(e, $(this));
        })
    }

    gsvSaleBasketAjaxExample.prototype.initDocument = function () {
        $(document).on('refreshBasket', (e, rootId) => this.onBasketUpdated(rootId));
    }

    gsvSaleBasketAjaxExample.prototype.onPlus = function (e, $this) {
        e.preventDefault();

        let $item = $this.closest('[data-basket-item-id]');
        let id = +$item.attr('data-basket-item-id');
        let quantity = +$item.attr('data-basket-quantity');

        quantity += 1;
        this.updateQty(id, quantity);
    }

    gsvSaleBasketAjaxExample.prototype.onMinus = function (e, $this) {
        e.preventDefault();

        let $item = $this.closest('[data-basket-item-id]');
        let id = +$item.attr('data-basket-item-id');
        let quantity = +$item.attr('data-basket-quantity');

        if(quantity > 1) {
            quantity--;
            this.updateQty(id, quantity);
        }
    }

    gsvSaleBasketAjaxExample.prototype.onRemove = function (e, $this) {
        e.preventDefault();

        let $item = $this.closest('[data-basket-item-id]');
        let id = +$item.attr('data-basket-item-id');

        this.deleteItem(id);
    }

    gsvSaleBasketAjaxExample.prototype.onApplyCoupon = function (e, coupon) {
        e.preventDefault();

        this.applyCoupon(coupon);
    }

    gsvSaleBasketAjaxExample.prototype.onRemoveCoupon = function (e, coupon) {
        e.preventDefault();

        this.removeCoupon(coupon);
    }

    gsvSaleBasketAjaxExample.prototype.updateQty = async function (id, quantity) {

        if(quantity < 1){
            quantity = 1;
        }

        let result = await this.ajax('recalculate', {
            //     basket: {
            ['QUANTITY_' + id]: quantity,
            //   }
        });

        await this.triggerBasketUpdate(true);
    }

    gsvSaleBasketAjaxExample.prototype.deleteItem = async function (id) {

        let result = await this.ajax('recalculate', {
            // basket: {
            ['DELETE_' + id]: 'Y',
            //}
        });

        await this.triggerBasketUpdate(true);
    }

    gsvSaleBasketAjaxExample.prototype.applyCoupon = async function (coupon) {

        let result = await this.ajax('recalculate', {
            // basket: {
            'coupon': (coupon || '').trim(),
            // }
        });

        await this.triggerBasketUpdate(true);
    }

    gsvSaleBasketAjaxExample.prototype.removeCoupon = async function (coupon) {

        let result = await this.ajax('recalculate', {
            //    basket: {
            'delete_coupon': (coupon || '').trim(),
            //  }
        });

        await this.triggerBasketUpdate(true);
    }

    gsvSaleBasketAjaxExample.prototype.ajax = async function (action = '', req = null) {
        let url = this.opt.AJAX_URL;
        let data = this.getAjaxReq(action);
        if(req) {
            data = Object.assign(data, req);
        }

        let result = await $.ajax(url, {
            'type': 'POST',
            'data': data,
            //'dataType': 'json',
        });

        return result;
    }

    gsvSaleBasketAjaxExample.prototype.getAjaxReq = function (action) {

        let data = {};

        if(action) {
            data[this.opt.ACTION_VARIABLE] = action;
        }
        data.via_ajax = 'Y';
        data.site_id = this.opt.siteId;
        data.site_template_id = this.opt.siteTemplateId;
        data.sessid = BX.bitrix_sessid();
        data.template = this.opt.template;
        data.signedParamsString = this.opt.signedParamsString;

        return data;
    }

    gsvSaleBasketAjaxExample.prototype.updateBasket = async function () {
        let html = await this.ajax();

        let $html = $(html);

        this.$el.find('[data-basket-update]').each(function(i, e) {
            let $e = $(e);
            let key = $e.attr('data-basket-update');
            $e.html($html.find(`[data-basket-update="${key}"]`).html());
        });

        this.initBasket();
    }

    gsvSaleBasketAjaxExample.prototype.onBasketUpdated = async function (rootId) {
        if(rootId != this.opt.ROOT_ID) {
            await this.updateBasket();
        }
    }

    gsvSaleBasketAjaxExample.prototype.triggerBasketUpdate = async function (selfToo = false) {
        $(document).trigger('refreshBasket', [selfToo ? '' : this.opt.ROOT_ID]);
    }

    // Just return a value to define the module export.
    // This example returns an object, but the module
    // can return a function as the exported value.
    return gsvSaleBasketAjaxExample;
}));