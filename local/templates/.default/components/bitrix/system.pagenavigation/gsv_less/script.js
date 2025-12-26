$(function() {

    function bindToPager (unused, el) {
        var $window = $(window);
        var isVisible = function() {
            return $window.scrollTop() + $window.height() >= $el.offset().top && $window.scrollTop() <= $el.offset().top + $el.height()
        };

        var $el = $(el);

        var loading, visible, pagen, url, container;

        function lazyLoad() {
            if(loading)
                return;
            loading = true;
            $.ajax({
                type: 'GET',
                url: url,
                dataType: 'html',
                success: function (data) {
                    var $data = $(data);
                    var newItems = $data.find('[data-show-more-container=' + pagen + ']');
                    var newButton = $data.find('[data-show-more-anchor=' + pagen + ']');
                    container.append(newItems.children());
                    $el.replaceWith(newButton);//??
                    $el = $('[data-show-more-anchor=' + pagen + ']');
                    loading = false;
                    postLoad();
                    //$(document).trigger('catalog_updated');
                },
                error: function () {
                    // $this.html(temp);
                    loading = false;
                    postLoad();
                }
            });
        }

        function postLoad(force = false) {
            if(force && pagen != null) {
                $el = $('[data-show-more-anchor=' + pagen + ']');
            } else {
                pagen = $el.data('show-more-anchor');
            }
            url = $el.data('show-more-url');
            container = $('[data-show-more-container=' + pagen + ']');
            visible = isVisible();
        }

        postLoad();

        //$(document).on('catalog_updated', () => postLoad(true))

        // $this.each(function(i, e){
        //     e.style.setProperty("display", "none", "important");
        // });


        if(visible) {
            lazyLoad();
        }
        $window.scroll(function() {
            if(visible != isVisible()) {
                visible = !visible;
                if(visible && url) {
                    //Shown! Must do lazy load
                    lazyLoad();
                }
            }
        });
    }

   $('[data-show-more-anchor]').each(bindToPager);

});
