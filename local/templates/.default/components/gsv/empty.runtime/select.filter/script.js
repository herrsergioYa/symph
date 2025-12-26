BX(function() {
    $(document).on('change', '#select-filter, #filter-sale, #select-type', function() {
        let $this = $(this);
        let url = new URL(window.location.href);
        let query = new URLSearchParams(url.search);
        let name = $this.attr('name');
        let type = $this.is('input') ? $this.attr('type') : $this.get(0).tagName.toLowerCase();
        if(type == 'checkbox') {
            if(!$this.is(':checked')) {
                $this = null;//$this.siblings('input[type=hidden]');
            }
        } else if(type == 'radio') {
            //FIXME:?
        }
        let value = '';
        if($this && $this.length) {
            name = $this.attr('name');
            value = $this.val();
        }

        if(value) {
            query.set(name, value);
        } else {
            query.delete(name);
        }

        url.search = query.toString();
        //window.location.href = url.toString();
        $(document).trigger('refreshCatalog', [url.toString(), true]);
    })
})