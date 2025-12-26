$(document).ready(function() {
   $(document).on('click', '[data-smart-filter="sizes"] [data-name][data-value]', function() {
      let $this = $(this);
      $this.toggleClass('selected');
   });

   async function updatePage(url) {

       window.startLoading();

       let html = await $.ajax({
           type: 'GET',
           url: url,
           dataType: 'html',
       });

       let $html = $(html);

       $('[data-show-catalog-container]').each(function (i, e) {
           let $e = $(e);
           let tag = $e.data('show-catalog-container');

           let $new = $html.find(`[data-show-catalog-container="${tag}"]`);
           if(tag == 'smart-filter') {
                $e.empty();
                $e.append($new.children());
           } else if(tag == 'smart-filter-btn') {
               if($new.hasClass('filter-marker')) {
                   $e.addClass('filter-marker');
               } else {
                   $e.removeClass('filter-marker');
               }
           } else {
               $e.replaceWith($new);
           }
       });

       window.history.pushState({}, null, url);

       window.endLoading();
   }

   $(document).on('click', '[data-smart-filter-button]', async function() {
       let $this = $(this);
       let $root = $this.closest('[data-smart-filter-root]');

       let fields = {ajax: 'y'};
       let type = $this.data('smart-filter-button');

       window.startLoading();

       if(type == 'apply') {
           let $sizes = $root.find('[data-smart-filter="sizes"]');
           $sizes.find('[data-name][data-value].selected').each(function (i, e) {
               let $e = $(e);
               fields[$e.data('name')] = $e.data('value');
           });

           $root.find('[data-smart-filter] input[type=checkbox]:checked').each(function (i, e) {
               let $e = $(e);
               fields[$e.attr('name')] = $e.val();
           });
       }

       let url = $root.data('smart-filter-root');

       let result = await $.ajax({
           type: 'POST',
           url: url,
           dataType: 'json',
           data: fields,
       });

       if(result && result.FILTER_URL) {
           //window.location.href = result.FILTER_URL;

           await updatePage(result.FILTER_URL);
       }

       //TODO: Refactor! It should be here. The filter is in the file I cannot access
       if ($("body").hasClass("filter-opened")) {
           $(".btn-filter").removeClass("open");
           $("body").removeClass("filter-opened");
           $(".products-filter").slideToggle("fast");
       }

       window.endLoading();
   });


});