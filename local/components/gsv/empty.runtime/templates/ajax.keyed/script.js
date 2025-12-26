BX.ready(function() {
    $('body').on('click', 'form[data-ajax-key] button', async function () {
        let $this = $(this);
		debugger;
        let $form = $this.closest('form');
        let url = $form.attr('action');
        let ajaxKey = $form.data('ajax-key');
        let body = $form.serialize();
        body += '&AJAX_KEY=' + ajaxKey;

        let response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body,
        });

        let result = await response.json();
		
    })
})