function initSearch(INPUT_ID, CONTAINER_ID) {

    let update = 'none';
    let updateSearchResults = async function($this) {

        if(update != 'none') {
            update = 'pending';
            return;
        }

        update = 'in_progress';
        await new Promise(function (resolve) {
            setTimeout(resolve, 500);
        });
        update = 'in_progress';//Accumulating...

        let q = $this.val() || '';
        q = q.trim();

        let minLength = $this.data('min-query-len');

        if(q.length < minLength) {
            clearResults($this);
            update = 'none';
            return;
        }

        let url = $this.data('ajax-url');
        let body = {
            'ajax_call': 'y',
            'INPUT_ID': INPUT_ID,
            'q': q,
        };

        let response = await fetch(
            url,
            {
                method: 'POST',
                body: new URLSearchParams(body),
            }
        );

        let newQ = $this.val() || '';
        newQ = newQ.trim();
        if(newQ == q) {
            let html = await response.text();
            $('#' + CONTAINER_ID).html(html);
        }

        if(update == 'pending') {
            update = 'none';
            updateSearchResults($this);
        } else {
            update = 'none';
        }
    }

    let clearResults = function ($this) {
        $('#' + CONTAINER_ID).html('');
    }

    $(document).on('input', '#' + INPUT_ID, async function () {
        let $this = $(this);
        updateSearchResults($this);
    })

    $(document).on('reset', 'form:has(#' + INPUT_ID + ')', async function () {
        let $this = $(this).find('#' + INPUT_ID);
        $this.val('');
        clearResults($this);
        //updateSearchResults($this);
    })
}