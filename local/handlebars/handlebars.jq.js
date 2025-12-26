(function($) {
    //These function can be overridden independently
    $.handlebars = $.hbs = function (source, model) {
        /*if(!('helpers' in model)) {
            model = Object.assign({}, model, {
                'helpers': handlebarsHelpers,
            })
        }*/
        var template = Handlebars.compile(source);
        return template(model);
    };
    $.$handlebars = function (source, model) {
        return $($.handlebars(source, model));
    };
    $.$hbs = function (source, model) {
        return $($.hbs(source, model));
    };

    let handlebarsHelpers = {};
    /*handlebarsHelpers.iterObj = function () {
        let ctx = [];
        for(let key in this) {
            if(this.hasOwnProperty(key)) {
                let value = this[key];
                ctx.push({key, value});
            }
        }
        return ctx;
    };
    handlebarsHelpers.iterKeys = function () {
        return Object.keys(this);
    };
    handlebarsHelpers.iterValues = function () {
        return Object.values(this);
    };*/
    $.handlebars.helpers = $.$handlebars.helpers = $.hbs.helpers = $.$hbs.helpers = handlebarsHelpers;

    $.fn.handlebars = $.fn.hbs = function (model) {
        let $elem = $(this);
        var template = $elem.data('handlebars-template');
        if (!template) {
            var source = $elem.html();
            template = Handlebars.compile(source);
            $elem.data('handlebars-template', template);
        }
        return template(model);
    };

    $.fn.$handlebars = function (model) {
        let output = $(this).handlebars(model);
        return $(output);
    }
    $.fn.$hbs = function (model) {
        let output = $(this).hbs(model);
        return $(output);
    }
})(jQuery);