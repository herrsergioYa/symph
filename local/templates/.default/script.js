
window.showMessage = function (msg) {
    // Display message

    let $ = window.jQuery;

    if(window.showMessage.timer) {
        window.showMessage.queue.push(msg);
        return;
    }

    $('body').append('<div class="added-message">' + msg + '</div>');
    window.showMessage.timer = setTimeout(function() {
        $('.added-message').remove();
        window.showMessage.timer = setTimeout(function() {
            window.showMessage.timer = null;
            if (window.showMessage.queue.length) {
                let msg = window.showMessage.queue.shift();
                window.showMessage(msg);
            }
        }, 1000);
    }, 2000);
}
window.showMessage.queue = [];