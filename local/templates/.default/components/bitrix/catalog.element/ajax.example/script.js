(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(/*'gsvCatalogElementAjaxExample', */['jquery'], factory);
        // Or we can do that the 'global' way
        //define(/*'gsvCatalogElementAjaxExample', */['jquery'], function (jQuery) {
        //    return (root.gsvCatalogElementAjaxExample = factory(jQuery));
        //});
    } else if (typeof module === 'object' && module.exports) {
        // Node. Does not work with strict CommonJS, but
        // only CommonJS-like environments that support module.exports,
        // like Node.
        module.exports = factory(require('jquery'));
    } else {
        // Browser globals
        root.gsvCatalogElementAjaxExample = factory(root.jQuery);
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

    let ret = function (jsData) {
        this.data = jsData;
        reinit();
    }

    ret.prototype.onAddToCart = async function (e) {
        e.preventDefault();

        let id = +this.$cartOfferId.attr('data-offer-id');
        let quantity = +this.$cartQty.val();

        this.$cartBtn.addClass('loading');

        let ok = await this.addToCart(id, quantity);

        this.$cartBtn.removeClass('loading');

        if (ok) {
            if (id in IN_BASKET)
                IN_BASKET[id] += quantity;
            else
                IN_BASKET[id] = quantity;
            this.setCartBtn(IN_BASKET[id] > 0);

            $(document).trigger('refreshBasket');
        }
    }

    ret.prototype.addToCart = async function (id, quantity) {

        let url = this.data.BASKET.ADD_URL_TEMPLATE.replace('#ID#', id);
        let data = {
            'ajax_basket': 'Y',
            [this.data.BASKET.QUANTITY]: quantity,
        };

        let response = await fetch(url, {
            method: 'POST',
            body: new URLSearchParams(data),
        });

        let result = await response.json();

        window.showMessage(result.MESSAGE);

        return (result.STATUS == 'OK');
    }
}));