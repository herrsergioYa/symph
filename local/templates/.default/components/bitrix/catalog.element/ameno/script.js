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
        module.exports = /*root.gsvCatalogElement =*/ factory(require('jquery'), require('fancybox'));
    } else {
        // Browser globals
        root.gsvCatalogElement = factory(root.jQuery, root.Fancybox);
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

    BX.ready(reinit);
    reinit();

    let gsvCatalogElement = function(jsData, jsBasket) {
        this.sel = jsData.SEL;
        this.jsData = jsData;
        this.jsBasket = jsBasket;
        this.loading = 0;

        this.init();
    }

    gsvCatalogElement.prototype.init = function () {
        this.$root = $(this.sel);

        let offerId = this.jsData.SELECTED_ID_OFFER;
        let props;
        if(offerId) {
            let offer = this.jsData.OFFERS[offerId];
            props = this.getSkuPropsFromOffer(offer);
        } else {
            props = {};
        }

        this.showProps(props);

        let self = this;
        this.$root.find('[data-sku-prop] [data-sku-prop-val]').click(function () {
            self.selectSkuProp($(this));
        })
        this.$root.find('[data-btn-add-to-cart]').click(function () {
            self.addToBasket($(this));
        })
        this.$root.find('[data-change-color-action]').click(async function (e) {
            e.preventDefault();

            self.startLoading();

            let $this = $(this);
            let url = $this.attr('href');
            let response = await fetch(url);
            let html = await response.text();
            let $html = $(html);

            self.$root.find('[data-catalog-element-holder]').each(function(i, e) {
               let $e = $(e);
               let key = $e.data('catalog-element-holder');
               let $newEl = $html.find(`[data-catalog-element-holder=${key}]`);
               $e.replaceWith($newEl);
            });

            window.history.pushState({}, null, url);

            self.endLoading();
        })

        $(document).on('basketRefreshed', (event, jsBasket) => {
            this.jsBasket = jsBasket;
            this.showBasketBtn();
        })

        this.initProductSlider();
    }

    gsvCatalogElement.prototype.selectSkuProp = function ($e) {
        let $prop = $e.parent('[data-sku-prop]');

        let propId = $prop.data('sku-prop');
        let valId = $e.data('sku-prop-val');

        let props = this.getSelectedSkuProps();
        props[propId] = valId;
        this.showProps(props);
    }

    gsvCatalogElement.prototype.getSelectedSkuProps = function () {
        let result = {};
        this.$root.find('[data-sku-prop]').each(function(i, e) {
            let $e = $(e);
            let res = null;
            $e.find('[data-sku-prop-val].selected').each(function (i, e) {
                let $e = $(e);
                res = $e.data('sku-prop-val');
            });
            if(res !== null) {
                result[$e.data('sku-prop')] = res;
            }
        })
        return result;
    }

    gsvCatalogElement.prototype.getOffersBySkuProps = function (skuProps, onlyAvailable = true) {
        let result = [];
        for (let offer of this.jsData.OFFERS) {
            if(onlyAvailable && !offer.CAN_BUY) {
                continue;
            }
            let ok = true;
            for(let propId in skuProps) {
                let prop = 'PROP_' + propId;
                if(offer.TREE[prop] != skuProps[propId]) {
                    ok = false;
                    break;
                }
            }
            if(ok) {
                result.push(offer);
            }
        }
        return result;
    }

    gsvCatalogElement.prototype.getSkuPropsFromOffer = function (offer) {

        let result = {};
        for(let skuProp of this.jsData.SKU_PROPS) {
            let id = skuProp.ID;
            let prop = 'PROP_' + id;
            if(prop in offer.TREE) {
                result[id] = offer.TREE[id];
            }
        }
        return result;
    }

    gsvCatalogElement.prototype.showProps = function (selectedProps) {
        let skuProps = this.jsData.SKU_PROPS;
        //let selected = true;
        let props = {};
        for(let i = 0; i < skuProps.length; i++) {
            let skuProp = skuProps[i];
            for (let valueId in skuProp.VALUES) {
                props[skuProps[i].ID] = valueId;
                let offers = /*selected ?*/ this.getOffersBySkuProps(props);// : false;
                let $item = this.$root.find(`[data-sku-prop=${skuProps[i].ID}] [data-sku-prop-val=${valueId}]`);
                if (offers && offers.length) {
                    $item.removeClass('disabled');
                    if(valueId == selectedProps[skuProps[i].ID]) {
                        $item.addClass('selected');
                    } else {
                        $item.removeClass('selected');
                    }
                } else {
                    if(valueId == selectedProps[skuProps[i].ID]) {
                        delete selectedProps[skuProps[i].ID];
                    }
                    $item.addClass('disabled');
                    $item.removeClass('selected');
                }
            }
            if(!(skuProps[i].ID in selectedProps)) {
                //selected = false;
                delete props[skuProps[i].ID];
            } else /*if(selected)*/ {
                props[skuProps[i].ID] = selectedProps[skuProps[i].ID]
            } /*else {debugger;//Impossible condition now?
                delete props[skuProps[i].ID];
            }*/
        }
        let selected = Object.values(props).length >= skuProps.length;

        if(Object.keys(props).length) {
            $(".product-sizes").removeClass("d-none");
            $(".product-sizes-wrap .notice").addClass("d-none");
        }
        let $button = this.$root.find('[data-btn-add-to-cart]');
        let offers = selected ? this.getOffersBySkuProps(props)/*getSelectedOffers()*/ : false;
        if(offers && offers.length) {
            $button.data('btn-add-to-cart', offers[0].ID);
        } else {
            $button.data('btn-add-to-cart', 0);
        }
        this.showBasketBtn();
    }

    gsvCatalogElement.prototype.getSelectedOffers = function (onlyAvailable = true) {
        return this.getOffersBySkuProps(this.getSelectedSkuProps(), onlyAvailable);
    }

    gsvCatalogElement.prototype.addToBasket = async function ($this) {
        let skuId = +$this.data('btn-add-to-cart');
        if(this.jsBasket[skuId]) {
            window.location.href = this.jsData.BASKET_URL;
            return;
        }
        if(skuId <= 0) {
            Fancybox.fromSelector('[data-btn-add-to-cart]');
            return;
        }


        let url = $this.data('href');
        url = url.replace('#ID#', skuId);
        if(url.indexOf('?') < 0) {
            url += '?';
        } else {
            url += '&';
        }
        url += 'ajax_basket=Y';

        let response = await fetch(
            url,
            {
                method: 'GET',
            }
        )

        let result = await response.json();

        if(result.STATUS == 'OK') {
            if(this.jsBasket) {   //TODO: Impossible for it to be false?
                if (skuId in this.jsBasket) {
                    this.jsBasket[skuId]++;
                } else {
                    this.jsBasket[skuId] = 1;
                }
            }
            $(document).trigger('refreshBasket');
            this.showBasketBtn();
        } else {
            alert(result.MESSAGE);
        }
    }

    gsvCatalogElement.prototype.showBasketBtn = function () {
        let $this = this.$root.find('[data-btn-add-to-cart]');
        let skuId = +$this.data('btn-add-to-cart');
        let selected = Object.values(this.getSelectedSkuProps()).length
            >= this.jsData.SKU_PROPS.length;

        if(skuId == 0 && selected || !this.jsBasket) {
            $this.attr('disabled', true);
            $this.addClass('disabled');
        } else {
            $this.removeAttr('disabled');
            $this.removeClass('disabled');
        }

        if(!this.jsBasket || this.jsBasket[skuId]) {
            $this.text($this.data('go-to-cart-text'));
        } else {
            $this.text($this.data('add-to-cart-text'));
        }
    }

    gsvCatalogElement.prototype.initProductSlider = function () {

        if(this.swiper || this.$root.find(".product-photo-slider-gsv").hasClass('swiper-initialized')) {
            return;
        }

        this.swiper = new Swiper(".product-photo-slider-gsv", {
            watchSlidesProgress: true,
            slidesPerView: "auto",
            loop: true,
            //grabCursor: true,
            spaceBetween: 0,
            zoom: {
                maxRatio: 5,
                minRation: 1
            },
            // autoplay: {
            // delay: 5000,
            // pauseOnMouseEnter: true,
            // disableOnInteraction: false,
            // },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            breakpoints: {
                768: {
                    slidesPerView: 2
                },
                992: {
                    slidesPerView: 3
                },
                1360: {
                    slidesPerView: 4
                }
            },
        });
    }

    gsvCatalogElement.prototype.checkLoading = function () {
        let isLoading = this.loading > 0;
        let $loader = $('.preloader');
        if(isLoading) {
            $loader.removeClass('d-none');
        } else {
            $loader.addClass('d-none');
        }
    }

    gsvCatalogElement.prototype.startLoading = function () {
        this.loading++;
        this.checkLoading();
    }

    gsvCatalogElement.prototype.endLoading = function () {
        this.loading--;
        this.checkLoading();
    }

    return gsvCatalogElement;
}));