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

    $(reinit);
    reinit();

    let gsvCatalogElement = function(jsData, jsBasket) {
        this.sel = jsData.SEL || '#' + jsData.ID;
        this.jsData = jsData;
        this.jsBasket = jsBasket;
        this.loading = 0;

        this.init();
    }

    gsvCatalogElement.prototype.init = function () {
        this.$root = $(this.sel);

        let offerId = this.jsData.OFFER_ID_SELECTED;
        let props;
        if(offerId) {
            let offer = this.getOfferById(offerId);
            props = this.getSkuPropsFromOffer(offer);
        } else {
            props = {};
        }

        this.showProps(props);

        let self = this;
        this.$root.on('click', '[data-sku-prop] [data-sku-prop-val]', function () {
            self.selectSkuProp($(this));
        })
        this.$root.find('[data-btn-add-to-cart]').click(function () {
            self.addToBasket($(this));
        })

        /*this.$root.find('[data-change-color-action]').click(async function (e) {
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
        })*/

        $(document).on('basketRefreshed', (event, jsBasket) => {
            this.jsBasket = jsBasket;
            this.showBasketBtn();
        })

        $(document).on('wishlistRefreshed', (event, favList) => {
            /*let inWishlist = favList.indexOf(this.jsData.PRODUCT_ID) >= 0;
            let $wishlist = this.$root.find('.btn-wishlist');
            if(!inWishlist) {
                $wishlist.find('span').text($wishlist.data('add-wishlist-text'));
            } else {
                $wishlist.find('span').text($wishlist.data('in-wishlist-text'));
            }*/
        })

        this.initProductSlider();
    }

    gsvCatalogElement.prototype.getOfferById = function (id) {
        for(let i in this.jsData.OFFERS) {
            let offer = this.jsData.OFFERS[i];
            if(offer.ID == id) {
                return offer;
            }
        }

        return null;
    }

    gsvCatalogElement.prototype.selectSkuProp = function ($e) {
        if($e.attr('disabled')) {
            return;
        }

        let $prop = $e.closest('[data-sku-prop]');
        let $val = $e.closest('[data-sku-prop-val]');

        let propId = $prop.data('sku-prop');
        let valId = $val.data('sku-prop-val');

        let props = this.getSelectedSkuProps();
        props[propId] = valId;
        this.showProps(props);
    }

    gsvCatalogElement.prototype.getSelectedSkuProps = function () {
        let result = {};
        this.$root.find('[data-sku-prop]').each(function(i, e) {
            let $e = $(e);
            let res = null;
            //$e.find('[data-sku-prop-val].selected').each(function (i, e) {
            $e.find('[data-sku-prop-val].active').each(function (i, e) {
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
        for(let i of this.jsData.SKU_PROP_LIST) { //i - code
            let skuProp = this.jsData.SKU_PROPS[i];
            let id = skuProp.ID;
            let prop = 'PROP_' + id;
            if(prop in offer.TREE) {
                result[id] = offer.TREE[prop];
            }
        }
        return result;
    }

    gsvCatalogElement.prototype.showProps = function (selectedProps, checkAvailability = true) {
        let props = {};
        for(let i of this.jsData.SKU_PROP_LIST) { //i - code
            let skuProp = this.jsData.SKU_PROPS[i];
            for (let j in skuProp.VALUES) {
                let value = skuProp.VALUES[j];
                let valueId = value.ID;
                props[skuProp.ID] = valueId;
                let offers = this.getOffersBySkuProps(props);
                let $item = this.$root.find(`[data-sku-prop=${skuProp.ID}] [data-sku-prop-val=${valueId}]`);

                if(valueId == selectedProps[skuProp.ID]) {
                    //$item.addClass('selected');
                    value.SELECTED = true;
                    $item.addClass('active');
                } else {
                    //$item.removeClass('selected');
                    value.SELECTED = false;
                    $item.removeClass('active');
                }

                if (offers && offers.length) {
                    $item.removeClass('disabled');
                    value.DISABLED = false;
                } else {
                    $item.addClass('disabled');
                    value.DISABLED = true;
                    if(checkAvailability) {
                        if(valueId == selectedProps[skuProp.ID]) {
                            delete selectedProps[skuProp.ID];
                            value.SELECTED = false;
                        }
                        //$item.removeClass('selected');
                        $item.removeClass('active');
                    }
                }
            }
            if(skuProp.ID in selectedProps) {
                props[skuProp.ID] = selectedProps[skuProp.ID];
            } else {
                delete props[skuProp.ID];
            }
        }
        let {offers, selected} = this.getSelection(props);
        let $button = this.$root.find('[data-btn-add-to-cart]');
        if(offers && offers.length) {
            this.showOffer(offers[0].ID);
        } else {
            this.showOffer(0);
        }
        if(selected) {
            $button.data('btn-add-to-cart', offers[0].ID);
        } else {
            $button.data('btn-add-to-cart', 0);
        }
        this.showBasketBtn();
    }

    gsvCatalogElement.prototype.getSelection = function (props = null) {
        if (!props) {
            props = this.getSelectedSkuProps();
        }
        let offers = this.getOffersBySkuProps(props);
        if (!offers) {
            return {offers: [], selected: null, count: 0};
        } else if (offers.length == 1) {
            let selected = offers[0];
            let ok = true;
            for (let key in selected.TREE) {
                let id = +key.split('_')[1];
                let val = selected.TREE[key];
                if(!(id in props) || props[id] != val) {
                    ok = false;
                    break;
                }
            }
            if (!ok) {
                selected = null;
            }
            return {offers, selected, count: 1};
        } else {
            return {offers, selected: null, count: offers.length};
        }
    }

    gsvCatalogElement.prototype.getSelectedOffers = function (onlyAvailable = true) {
        return this.getOffersBySkuProps(this.getSelectedSkuProps(), onlyAvailable);
    }

    gsvCatalogElement.prototype.addToBasket = async function ($this) {
        let skuId = +$this.data('btn-add-to-cart');
        /*if(this.jsBasket[skuId]) {
            window.location.href = this.jsData.BASKET_URL;
            return;
        }*/
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
            openModalCart();
        } else {
            alert(result.MESSAGE);
        }
    }

    gsvCatalogElement.prototype.selectOffer = function (offerId) {
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

        jsData.ALL_ATTRIBUTES = [];
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
        }
    }

    gsvCatalogElement.prototype.showOffer = function (offerId) {
        this.selectOffer(offerId);
        this.$root.find('[data-product-area]').each((i, e) => {
            let $e = $(e);
            let code = $e.data('product-area');
            let $template = this.$root.find(`[data-product-area-tmpl="${code}"]`);
            let $html = $template.$hbs(this.jsData);
            $e.replaceWith($html);
        })
    }

    gsvCatalogElement.prototype.showBasketBtn = function () {
        let $this = this.$root.find('[data-btn-add-to-cart]');
        let skuId = +$this.data('btn-add-to-cart');
        let {offers, selected} = this.getSelection();
        let $productPage = this.$root.closest('.product-detail');
        let $productInfor = this.$root.find('.product-infor');
        let variantCount= offers.length;

        //if(variantCount != 1) {
        if(skuId == 0 || !selected) {
            $productPage.addClass('out-of-stock');
            if(variantCount <= 0) {
                $productInfor.addClass('style-out-of-stock');
            } else {
                $productInfor.removeClass('style-out-of-stock');
            }
            $this.attr('disabled', true);
            $this.addClass('disabled');
            $this.removeClass('border-black').addClass('border-line');

            if(variantCount > 0) {
                $this.text($this.data('choose-an-offer-text'));
            } else {
                $this.text($this.data('out-of-stock-text'));
            }
        } else {
            $productPage.removeClass('out-of-stock');
            $productInfor.removeClass('style-out-of-stock');
            $this.removeAttr('disabled');
            $this.removeClass('disabled');
            $this.addClass('border-black').removeClass('border-line');

            $this.text($this.data('add-to-cart-text'));
        }

        /*if(!this.jsBasket || this.jsBasket[skuId]) {
            $this.text($this.data('go-to-cart-text'));
        } else {
            $this.text($this.data('add-to-cart-text'));
        }*/
    }

    gsvCatalogElement.prototype.initProductSlider = function () {

        if(this.swiper) {
            return;
        }

        const descTabItem = document.querySelectorAll('.desc-tab .tab-item')
        const descItem = document.querySelectorAll('.desc-tab .desc-block .desc-item')

        descTabItem.forEach(tabItems => {
            const handleOpen = () => {
                //let dataItem = tabItems.innerHTML.replace(/\s+/g, '')
                let dataItem = tabItems.dataset['tabName'];

                descItem.forEach(item => {
                    if (item.getAttribute('data-item') === dataItem) {
                        item.classList.add('open')
                    } else {
                        item.classList.remove('open')
                    }
                })
            }

            if (tabItems.classList.contains('active')) {
                handleOpen()
            }

            tabItems.addEventListener('click', handleOpen)
        })

        const productDetail = document.querySelector('.product-detail')
        const popupImg = productDetail.querySelector('.featured-product .list-img .popup-img')

        // show, hide popup img
        const imgItems = productDetail.querySelectorAll('.list-img .popup-link>img')
        const closePopupBtn = productDetail.querySelector('.list-img .popup-img .close-popup-btn')

        imgItems.forEach((item, index) => {
            item.addEventListener("click", () => {

                popupImg.classList.add('open')

                // list-img popup
                var listPopupImg = new Swiper(".popup-img", {
                    loop: true,
                    clickable: true,
                    slidesPerView: 1,
                    spaceBetween: 0,
                    navigation: {
                        nextEl: ".swiper-button-next",
                        prevEl: ".swiper-button-prev",
                    },
                    initialSlide: index,
                });
            })
        })

        closePopupBtn.addEventListener('click', () => {
            popupImg.classList.remove('open')
        })

        this.swiper = true;

        /*

        this.swiper = new Swiper(".product-photo-slider", {
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
        });*/
    }

    gsvCatalogElement.prototype.startLoading = function () {
        //this.loading++;
        //this.checkLoading();
        window.startLoading();
    }

    gsvCatalogElement.prototype.endLoading = function () {
        //this.loading--;
        //this.checkLoading();
        window.endLoading();
    }

    return gsvCatalogElement;
}));