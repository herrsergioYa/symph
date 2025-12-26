(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(/*'gsvCatalogElement', */['jquery', 'fancybox'], factory);
        // Or we can do that the 'global' way
        //define(/*'gsvCatalogElement', */['jquery', 'fancybox'], function (jQuery, Fancybox) {
        //    return (root.gsvCatalogElement = factory(jQuery, Fancybox));
        //});
    } else if (typeof module === 'object' && module.exports) {
        // Node. Does not work with strict CommonJS, but
        // only CommonJS-like environments that support module.exports,
        // like Node.
        module.exports = /*root.gsvGiftCard =*/ factory(require('jquery'), require('fancybox'));
    } else {
        // Browser globals
        root.gsvGiftCard = factory(root.jQuery, root.Fancybox);
    }
}(typeof self !== 'undefined' ? self : this, function (jQuery, Fancybox) {

    let $ = jQuery;
    let reinit = function () {
        //HACK: Against Bitrix's script reordering. ;-(
        //Doesn't always work, unfortunately.
        if (typeof window !== 'undefined') {
            $ = jQuery = jQuery || window.jQuery;
            Fancybox = Fancybox || window.Fancybox;
        }
    }

    BX(reinit);
    reinit();

    let gsvGiftCard = function(jsData, jsBasket) {
        this.sel = jsData.SEL || '#' + jsData.ROOT_ID;
        this.jsData = jsData;
        this.jsBasket = jsBasket;
        this.loading = 0;

        this.init();
    }

    gsvGiftCard.prototype.init = function () {
        this.$root = $(this.sel);

        let offerId = this.jsData.OFFER_ID_SELECTED;
        if(offerId) {
            let offer = this.getOfferById(offerId);
            offerId = offer.ID;
        }

        this.showOffer(offerId);

        let self = this;
        this.$root.on('change', '[data-product-sel] input[type=radio]', function () {
            debugger;
            self.showOffer($(this).val());
        })
        this.$root.find('[data-btn-add-to-cart]').click(function () {
            self.addToBasket($(this));
        })

        $(document).on('basketRefreshed', (event, jsBasket) => {
            this.jsBasket = jsBasket;
            this.showBasketBtn();
        })

        $(document).on('wishlistRefreshed', (event, favList) => {

        })

        //this.updateProduct().then(() => this.showProps(this.getSelectedSkuProps()));
    }

    gsvGiftCard.prototype.getOfferById = function (id) {
        for(let i in this.jsData.OFFERS) {
            let offer = this.jsData.OFFERS[i];
            if(offer.ID == id) {
                return offer;
            }
        }

        return null;
    }

    gsvGiftCard.prototype.getSelection = function (skuId = null) {
        if (!skuId) {
            let $btn = this.$root.find('[data-btn-add-to-cart]');
            skuId = +$btn.data('btn-add-to-cart');
        }
        let selected = this.getOfferById(skuId);
        if(selected) {
            if(!selected.CAN_BUY) {
                selected = null;
            }
        }
        return selected;
    }

    gsvGiftCard.prototype.addToBasket = async function ($this) {
        let skuId = +$this.data('btn-add-to-cart');
        if(this.jsBasket && this.jsBasket[skuId]) {
            //window.location.href = this.jsData.BASKET_URL;
            $(".btn-cart[data-modal=\"#cart-modal\"]")[0].click();
            return;
        }
        if($this.hasClass('btn-loading')) {
            return;
        }
        if(skuId <= 0) {
            //Fancybox.fromSelector('[data-btn-add-to-cart]');
            return;
        }
        $this.addClass('btn-loading');

        let url = $this.data('href');
        url = url.replace('#ID#', skuId);
        if(url.indexOf('?') < 0) {
            url += '?';
        } else {
            url += '&';
        }
        url += 'ajax_basket=Y';

        let quantity = 1;
        let $quantity = this.$root.find('.quantity-block .quantity')
        if($quantity.length) {
            quantity = +$quantity.text();
            if(quantity <= 0) {
                quantity = 1;
            }
        }
        if(this.jsData.QUANTITY_VAR) {
            url += '&' + encodeURIComponent(this.jsData.QUANTITY_VAR) + '=' + quantity;
        }

        let response = await fetch(
            url,
            {
                method: 'GET',
            }
        )

        let result = await response.json();

        $this.removeClass('btn-loading');

        if(result.STATUS == 'OK') {
            if(this.jsBasket) {   //TODO: Impossible for it to be false?
                if (skuId in this.jsBasket) {
                    this.jsBasket[skuId] += quantity;
                } else {
                    this.jsBasket[skuId] = quantity;
                }
            }
            $(document).trigger('refreshBasket');
            this.showBasketBtn();
            //openModalCart();
        } else {
            alert(result.MESSAGE);
        }
    }

    gsvGiftCard.prototype.selectOffer = function (offerId) {
        let jsData = this.jsData;
        jsData.OFFER_ID_SELECTED = 0;
        jsData.OFFER_SELECTED = null;

        for (let i in this.jsData.OFFERS) {
            let offer = this.jsData.OFFERS[i];
            if(offer.ID == offerId) {
                jsData.OFFER_ID_SELECTED = offerId;
                jsData.OFFER_SELECTED = offer;
                offer.SELECTED = true;
            } else {
                offer.SELECTED = false;
            }
        }

        /*jsData.ALL_ATTRIBUTES = [];
        let even = false;
        if(jsData.ATTRIBUTES) {
            for(let attr of jsData.ATTRIBUTES) {
                attr.EVEN = even;
                even = ! even;
                jsData.ALL_ATTRIBUTES.push(attr);
            }
        }
        if(jsData.OFFER_SELECTED?.ATTRIBUTES) {
            for(let attr of jsData.OFFER_SELECTED.ATTRIBUTES) {
                attr.EVEN = even;
                even = ! even;
                jsData.ALL_ATTRIBUTES.push(attr);
            }
        }*/
    }

    gsvGiftCard.prototype.showOffer = function (offerId) {
        this.selectOffer(offerId);
        this.$root.find('[data-product-area]').each((i, e) => {
            let $e = $(e);
            let code = $e.data('product-area');
            let $template = this.$root.find(`[data-product-area-tmpl="${code}"]`);
            let $html = $template.$hbs(this.jsData);
            $e.replaceWith($html);
        })
        this.$root.find(`[data-product-sel] input[type=radio][value=${offerId}]`).prop('checked', true);
        this.$root.find('[data-btn-add-to-cart]').data('btn-add-to-cart', offerId);
        this.showBasketBtn();
    }

    gsvGiftCard.prototype.showBasketBtn = function () {
        let $this = this.$root.find('[data-btn-add-to-cart]');
        let skuId = +$this.data('btn-add-to-cart');
        let selected = this.getSelection(skuId);

        if(skuId == 0 || !selected) {
            $this.attr('disabled', true);
            $this.addClass('disabled');
            $this.addClass('btn-disabled');

            if(skuId == 0) {
                $this.text($this.data('choose-an-offer-text'));
            } else {
                $this.text($this.data('out-of-stock-text'));
            }
        } else {
            $this.removeAttr('disabled');
            $this.removeClass('disabled');
            $this.removeClass('btn-disabled');

            if(this.jsBasket && this.jsBasket[skuId]) {
                $this.text($this.data('go-to-cart-text'));
            } else {
                $this.text($this.data('add-to-cart-text'));
            }
        }
    }

    gsvGiftCard.prototype.startLoading = function () {
        //this.loading++;
        //this.checkLoading();
        window.startLoading();
    }

    gsvGiftCard.prototype.endLoading = function () {
        //this.loading--;
        //this.checkLoading();
        window.endLoading();
    }

    return gsvGiftCard;
}));

