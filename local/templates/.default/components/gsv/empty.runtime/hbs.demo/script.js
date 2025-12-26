(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(/*'gsvHbsDemo', */['jquery'], factory);
        // Or we can do that the 'global' way
        //define(/*'gsvHbsDemo', */['jquery'], function (jQuery) {
        //    return (root.gsvHbsDemo = factory(jQuery));
        //});
    } else if (typeof module === 'object' && module.exports) {
        // Node. Does not work with strict CommonJS, but
        // only CommonJS-like environments that support module.exports,
        // like Node.
        module.exports = /*root.gsvHbsDemo =*/ factory(require('jquery'));
    } else {
        // Browser globals
        root.gsvHbsDemo = factory(root.jQuery);
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
    reinit();
    document.addEventListener("DOMContentLoaded", reinit);

     let ret = function (id, data) {
        this.data = data;
        this.id = id;
        this.$root = $('#' + this.id);

        this.init();
    }

    ret.prototype.init = function () {
         this.rerender();
    }

    ret.prototype.rerender = function () {
        let data = this.data;
        let $template = this.$root.find('.container-area_tmpl');
        let $area = this.$root.find('.container-area');

        //As an html-text
        let html = $template.hbs(data);
        $area.html(html);
        //or
        $area.empty();
        $area.append(html);

        //OR

        //As an jq-object
        let $html = $template.$hbs(html);
        $area.empty();
        $html.appendTo($area);
        //or
        $area.empty();
        $area.append($html);
    }

    return ret;
}));