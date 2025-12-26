$(function() {
    $('body').on('submit', 'form[data-popup-form]', function() {

        var $this = $(this);

        var data = $this.serialize();

        $.ajax({
            'type' : 'POST',
            'url' : window.location.href,
            'data' : data,
            'success' : function (data) {
                console.log(data);
            },
            'error' : function (a, b, c) {
                console.log(a, b, c);
            }
        });

       return false;
    });
});