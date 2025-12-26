(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(/*'returnExportsGlobal', */['jquery', 'vue'], factory);
        // Or we can do that the 'global' way
        //define(/*'returnExportsGlobal', */['jquery', 'vue'], function (jQuery, Vue) {
        //    return (root.returnExportsGlobal = factory(jQuery, Vue));
        //});
    } else if (typeof module === 'object' && module.exports) {
        // Node. Does not work with strict CommonJS, but
        // only CommonJS-like environments that support module.exports,
        // like Node.
        module.exports = /*root.returnExportsGlobal =*/ factory(require('jquery'), require('vue'));
    } else {
        // Browser globals
        root.returnExportsGlobal = factory(root.jQuery, root.Vue);
    }
}(typeof self !== 'undefined' ? self : this, function (jQuery, Vue) {
    // Use jQuery & Vue in some fashion.

    let $ = jQuery;
    let { reactive, ref, watch, watchEffect, computed, createApp, nextTick, onMounted, onUnmounted } = Vue || {};

    let reinit = function () {
        //HACK: Against Bitrix's script reordering. ;-(
        //Doesn't always work, unfortunately.
        if (typeof window !== 'undefined') {
            $ = jQuery = jQuery || window.jQuery;

            Vue = Vue || window.Vue;
            ({ reactive, ref, watch, watchEffect, computed, createApp, nextTick, onMounted, onUnmounted } = Vue || {});
        }
    }

    BX.ready(reinit);
    reinit();
    document.addEventListener("DOMContentLoaded", reinit);

    //https://emailregex.com/index.html
    let emailRegex = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

    // Just return a value to define the module export.
    // This example returns an object, but the module
    // can return a function as the exported value.
    return {};
}));