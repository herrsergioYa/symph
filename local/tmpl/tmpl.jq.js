(function($, gsvJsHelper) {
    $.tmpl = function (template, d) {
        return gsvJsHelper.renderTemplateImpl(template, d);
    };
    $.$tmpl = function (template, d) {
        return $($.tmpl(template, d));
    };

    $.fn.tmpl = function (d) {
        var template = $.trim($(this).html());
        var output = $.tmpl(template, d);
        return output;
    };
    $.fn.$tmpl = function (d) {
        var output = $(this).tmpl(d);
        return $(output);//.get();
    };
})(jQuery, gsvJsHelper);