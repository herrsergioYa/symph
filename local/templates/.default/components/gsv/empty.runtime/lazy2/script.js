BX.ready(function(){
    const $targets = $('[data-lazy-block]');

    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {

                let $e = $(entry.target);
                let block = entry.target.dataset.lazyBlock;

                if(entry.target.dataset.inProgress) {
                    return;
                }
                entry.target.dataset.inProgress = 'true';

                function isNext() {
                    return $('[data-lazy-block]').index($e) <= 0;
                }

                (async function () {

                    if(!isNext()) {
                        await new Promise(resolve => {
                            let timer = setInterval(function () {
                                if (isNext()) {
                                    clearInterval(timer);
                                    resolve();
                                }
                            }, 200);
                        });
                    }

                    let url = BX.message('SITE_DIR');
                    url += 'api/' + block + '.html';
                    //url += '?SITE_ID=' + BX.message('SITE_ID');
                    //url += '&SITE_TEMPLATE_ID=' + BX.message('SITE_TEMPLATE_ID');

                    let query = entry.target.dataset.lazyParams;
                    if(query && query.length) {
                        url += '?' + query;
                    }

                    let response = await fetch(url);
                    let html = await response.text();

                    $e.replaceWith(html);

                    $(document).trigger('catalog_updated');
                })();

                observer.unobserve(entry.target);
            }
        });
    }, {
        root: null,          // null = viewport
        rootMargin: '0px 0px 200px 0px',   // отступы вокруг viewport
        threshold: 0.15       // 50% элемента должно быть видно
    });

    $targets.each((i, target) => observer.observe(target));
});