(function($) {
    $.mustache = function (template, view, partials) {
        if(!('helpers' in view)) {
            view = Object.assign({}, view, {
                'helpers': mustacheHelpers,
            })
        }
        return Mustache.render(template, view, partials);
    };
    $.$mustache = function (template, view, partials) {
        return $($.mustache(template, view, partials));
    };

    let mustacheHelpers = {};
    mustacheHelpers.iterObj = function () {
        let ctx = [];
        for(let key in this) {
            if(this.hasOwnProperty(key)) {
                let value = this[key];
                ctx.push({key, value});
            }
        }
        return ctx;
    };
    mustacheHelpers.iterKeys = function () {
        return Object.keys(this);
    };
    mustacheHelpers.iterValues = function () {
        return Object.values(this);
    };
    $.mustache.helpers = $.$mustache.helpers = mustacheHelpers;

    $.fn.mustache = function (view, partials) {
        var template = $.trim($(this).html());
        var output = $.mustache(template, view, partials);
        return output;
    };
    $.fn.$mustache = function (view, partials) {
        var output = $(this).mustache(view, partials);
        return $(output);//.get();
    };
})(jQuery);