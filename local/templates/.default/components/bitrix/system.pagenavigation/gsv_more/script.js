$(function() {
   $('body').on('click', '[data-show-more-button]', function (e) {

       var $this = $(this);
       var pagen = $this.data('show-more-button');

       var url = $this.data('show-more-url');
       var container = $('[data-show-more-container=' + pagen + ']');

       $this.each(function(i, e){
           e.style.setProperty("display", "none", "important");
       });

       $.ajax({
           type: 'GET',
           url: url,
           dataType: 'html',
           success: function (data) {
               var $data = $(data);
               var newItems = $data.find('[data-show-more-container=' + pagen + ']');
               var newButton = $data.find('[data-show-more-button=' + pagen + ']');
               container.append(newItems.children());
               $this.replaceWith(newButton);
               //$(document).trigger('catalog_updated');
           },
           error: function() {
               // $this.html(temp);
           }
       });
   })
});
