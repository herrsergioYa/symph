$(document).ready(function() {

   async function updatePage(url) {

       window.startLoading();

       let html = await $.ajax({
           type: 'GET',
           url: url,
           dataType: 'html',
       });

       let $html = $(html);

       $('[data-catalog-area]').each(function (i, e) {
           let $e = $(e);
           let tag = $e.data('catalog-area');

           let $new = $html.find(`[data-catalog-area="${tag}"]`);
           if(tag == 'smart-filter' || tag.startsWith('smart-filter_')) {
                $e.empty();
                $e.append($new.children());
           } else if(tag == 'smart-filter-btn') {
               let $cnt = $e.find('[data-smart-filter-count]');
               let $newCnt = $new.find('[data-smart-filter-count]');
               $cnt.html($newCnt.html());
           } else {
               $e.replaceWith($new);
           }
       });

       window.history.pushState({}, null, url);

       window.endLoading();

       $(document).trigger('catalog_updated');
   }

   $(document).on('click', '[data-smart-filter] [data-smart-filter-button]', async function () {
       await refresh($(this));
   });

    $(document).on('change', '[data-smart-filter] input[type=checkbox]', async function () {
        await refresh($(this));
    });

   let updateNum = 0;

   async function refresh($this) {
      // let $this = $(this);
       let $root = $this.closest('[data-smart-filter]');

       let fields = {ajax: 'y'};
       let type = $this.data('smart-filter-button');
       let full = !!$this.is('[data-smart-filter-button]');

       if(type == 'apply' || !full) {
           $root.find('input[type=checkbox]:checked').each(function (i, e) {
               let $e = $(e);
               fields[$e.attr('name')] = $e.val();
           });
       }

       let myNum = ++updateNum;

        if(full) {
            window.startLoading();
        }

       let url = $root.data('smart-filter');

       let result = await $.ajax({
           type: 'POST',
           url: url,
           dataType: 'json',
           data: fields,
       });

       if(myNum == updateNum) {

           $root.find('[data-catalog-area="smart-filter-btn"]').each(function (i, e) {
               let $e = $(e);

               let $cnt = $e.find('[data-smart-filter-count]');
               $cnt.html('(' + result.ELEMENT_COUNT + ')');
           });

           if(full) {
               if (result && result.FILTER_URL) {
                   //window.location.href = result.FILTER_URL;

                   let obUrl = new URL(result.FILTER_URL, window.location.href);
                   let query = new URLSearchParams(obUrl.search);
                   let sort = '';
                   $root.find('input[type=radio][name=sort]:checked').each(function (i, e) {
                       let $e = $(e);
                       sort = $e.val();
                   });
                   if(sort) {
                       query.set('sort', sort);
                   } else {
                       query.delete('sort')
                   }
                   obUrl.search = query;
                   result.FILTER_URL = obUrl.toString();

                   await updatePage(result.FILTER_URL, full);
               }
           }
       }

       if(full) {
           window.endLoading();
       }
   }
});