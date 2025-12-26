//jQuery is ready later...
BX.ready(function() {

    updateFields();

    async function apply($el) {
        let $root = $el.closest('.subscribe-form-wrap');
        let $form = $root.find('form');

        $form.find('input[name=sessid]').val(BX.bitrix_sessid());

        let result = await $.ajax({
            type: $form.attr('method'),
            url: $form.attr("action"),
            data: $form.serialize(),
        });

        let $result = $('<div>' + result + '</div>');

        $root.empty();
        $result.find('.subscribe-form-wrap').children().appendTo($root);

        updateFields();
    }

    function updateFields() {
        $('.subscribe-form-wrap .subscribe-form form input').trigger('blur');
    }

   $('body').on('submit', '.subscribe-form-wrap .subscribe-form form', async function (e) {
       e.preventDefault();

       let $form = $(this);
       await apply($form);
   })

    $('body').on('input', '.subscribe-form-wrap .subscribe-form form input', async function (e) {
        let $el = $(this);
        let $root = $el.closest('.subscribe-form-wrap');
        $root.find('.subscribe-form-success,.subscribe-form-failure').remove();
    })
});