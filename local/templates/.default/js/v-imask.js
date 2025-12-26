(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(/*'vIMask', */['vue', 'imask', 'jquery'], factory);
        // Or we can do that the 'global' way
        //define(/*'vIMask', */['vue', 'imask', 'jquery'], function (Vue, IMask, jQuery) {
        //    return (root.vIMask = factory(Vue, IMask, jQuery));
        //});
    } else if (typeof module === 'object' && module.exports) {
        // Node. Does not work with strict CommonJS, but
        // only CommonJS-like environments that support module.exports,
        // like Node.
        module.exports = factory(require('vue'), require('imask'), require('jquery'));
    } else {
        // Browser globals
        root.vIMask = factory(root.Vue, root.IMask, root.jQuery);
    }
}(typeof self !== 'undefined' ? self : this, function (Vue, IMask, jQuery) {

    let { watch, nextTick, watchEffect, ref, onMounted, onUnmounted } = Vue || {};
    let $ = jQuery;

    let reinit = function () {
        //HACK: Against Bitrix's script reordering. ;-(
        //Doesn't always work, unfortunately.
        if (typeof window !== 'undefined') {
            $ = jQuery = jQuery || window.jQuery;

            Vue = Vue || window.Vue;
            ({ watch, nextTick, watchEffect, ref, onMounted, onUnmounted } = Vue || {});
        }
    }

    BX(reinit);
    reinit();
    document.addEventListener("DOMContentLoaded", reinit);

    let vIMask = {
        mounted(el, binding) {
            let mask = binding.value;
            let imask = IMask(el, {mask});
            let $el = $(el);
            $el.data('imask', imask);
            $el.data('old-value', $el.val());
        },
        updated(el, binding) {
            let $el = $(el);
            let val = $el.val();
            let oldVal = $el.data('old-value');
            if(val != oldVal) {
                $el.data('old-value', val);
                let imask = $(el).data('imask');
                imask.updateValue();
            }
        },
    }

    let vMask = {
        props: {
            'modelValue': {
                type: String,
                default: '',
            },
            'mask': {
                type: String,
                default: '+{7} 000 000 00 00',
            },
        },
        emits: ['update:modelValue', 'inputted'],
        setup(props, ctx) {

            const mask = props.mask;

            const inp = ref(null);

            let imask = null;

            onMounted(function() {
                //inp.value = props.modelValue;
                let el = inp.value;
                imask = IMask(el, {mask})
                imask.unmaskedValue/*value*/ = props.modelValue;
                imask.on('accept', onValueChanged);
                imask.on('complete', onValueChanged);
            })

            onUnmounted(function () {
                if(imask) {
                    imask.off('accept', onValueChanged);
                    imask.off('complete', onValueChanged);
                    imask.destroy();
                }
                imask = null;
            })

            watch(() => props.modelValue, function() {
                imask.unmaskedValue/*value*/ = props.modelValue;
                ctx.emit('inputted', imask.unmaskedValue);
            });

            let onValueChanged = function() {
                let value = imask.unmaskedValue/*value*/;
                ctx.emit('update:modelValue', value);
            }

            return {
                inp,
                onValueChanged,
            }
        },
        template: '<input class="form-control" type="tel" ref="inp" required @change="onValueChanged()">',
    }

    return {
        vIMask,
        vMask,
    }
}));