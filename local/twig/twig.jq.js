(function($) {
    $.twig = function (template, view) {
        let twigTemplate = Twig.twig({data:template});
        let output = twigTemplate.render(view);
        return output;
    };

    $.$twig = function (template, view) {
        return $($.twig(template, view));
    };

    $.fn.twig = function (view) {
        let $elem = $(this);
        let id = $elem.attr('id');
        var template = $elem.html();
        let params = {
            data: template,
        }
        if (id) {
            params.id = 'twig__html__' + id;
        }
        let twigTemplate = Twig.twig(params);
        var output = twigTemplate.render(view);
        return output;
    };

    $.fn.$twig = function (view) {
        let output = $(this).twig(view);
        return $(output);
    }
})(jQuery);