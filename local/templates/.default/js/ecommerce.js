

async function sendEcommerceEvent(event) {
    window.dataLayer = window.dataLayer || [];
    dataLayer.push(event);
}

function getEcommerceList(el) {
    let list = el.closest('[data-ecommerce-list]');
    if(list) {
        let listInfo = list.dataset.ecommerceList;
        if(typeof listInfo === 'string') {
            listInfo = JSON.parse(listInfo);
        }
        if(listInfo) {
            return listInfo;
        }
    }
    return null;
}

function getEcommerceInfo(el) {
    let info = getEcommerceDataLayer(el);
    if(!info) {
        return null;
    }
    let listInfo = getEcommerceList(el);
    if(listInfo) {
        //The listInfo can override some default values from the info!
        Object.assign(info, listInfo);
    }

    return info;
}

function getEcommerceCurrency(el) {
    let currency = el ? el.closest('[data-ecommerce-currency]') : document.querySelector('[data-ecommerce-currency]');
    if(currency) {
        let currencyInfo = currency.dataset.ecommerceCurrency;
        if(currencyInfo) {
            return currencyInfo;
        }
    }
    return null;
}

function getEcommerceDataLayer(el) {
    let info = el.closest('[data-ecommerce-data-layer]')?.dataset['ecommerceDataLayer'];
    if(!info) {
        return ;
    }
    if(typeof info === 'string') {
        info = JSON.parse(info);
    } else {
        info = Object.assign({}, info);
    }

    if(info) {
        return info;
    }

    return null;
}

$(document).ready(function () {
    const observerForViewList = new IntersectionObserver(
        (entries, observer) => {
            let visible = [];
            let currency = '';
            for(let entry of entries) {
                if(entry.isIntersecting) {
                    if(entry.target.dataset['ecommerceSeen']) {
                        continue;
                    }
                    entry.target.dataset['ecommerceSeen'] = 'true';
                    let info = getEcommerceInfo(entry.target);
                    if(!info) {
                        continue;
                    }
                    currency = getEcommerceCurrency(entry.target) || currency;

                    visible.push(info);
                }
            }

            if(visible.length) {
                sendEcommerceEvent({
                    "ecommerce": {
                        "currencyCode": currency,
                        "impressions": {
                            //"actionField": '',
                            "products": visible,
                        },
                    },
                });
            }
        },
        {
            root: null,
            rootMargin: "30px",
            threshold: 0.5,
        }
    );

    function bindObservables() {
        let arrObservedNodes = document.querySelectorAll("[data-ecommerce-observable]:not([data-ecommerce-observed])");
        arrObservedNodes.forEach((el) => {
            observerForViewList.observe(el);
            el.dataset['ecommerceObserved'] = 'true';
        });
    }

    const cookieTtlSec = 10;

    function loadEcommerceEvents() {

        let events = localStorage.getItem('gsv_ecommerceEvent');
        if(typeof events === "string") {
            try {
                events = JSON.parse(events);
                if(events && events.events && events.ttl && +events.ttl >= +new Date()) {
                    events = events.events;
                } else {
                    events = [];
                }
            } catch(e) {
                events = [];
            }
        } else {
            events = [];
        }

        return events;
    }

    function saveEcommerceEvents(events) {

        events = {
            ttl: +new Date() + cookieTtlSec * 1000,
            events,
        }

        events = JSON.stringify(events);

        localStorage.setItem('gsv_ecommerceEvent', events);
    }

    function clearEcommerceEvents() {
        localStorage.removeItem('gsv_ecommerceEvent');
    }

    function preserveEcommerceEvent(event) {
        let events = loadEcommerceEvents();
        events.push(event);
        saveEcommerceEvents(events);
    }

    async function executeEcommerceEvents() {
        let events = loadEcommerceEvents();

        let event = null;
        let result = [];
        while (event = events.shift()) {
            //We can be interrupted so ...
            saveEcommerceEvents(events);

            /*await*/ executeEcommerceEvent(event).then(() => {});
            result.push(event);

            //Redundant? Can they change now!? w/o await?
            //events = loadEcommerceEvents();
        }

        clearEcommerceEvents();

        return result;
    }

    async function fetchEcommerceEvents() {

        let url = '/local/ajax/ecommerce.php';
        url += '?action=getPendingEvents';
        url += '&sessid=' + encodeURIComponent(BX.bitrix_sessid());

        let body = {
        };

        body = new URLSearchParams(body);

        let response = await fetch(
            url,
            {
                method: 'POST',
                body,
            }
        )

        let events = await response.json();

        let event = null;
        let result = [];
        while (event = events.shift()) {
            /*await*/ executeEcommerceEvent(event).then(() => {});
            result.push(event);
        }

        return result;
    }

    async function executeEcommerceEvent(event) {
        return await sendEcommerceEvent(event);
    }

    $('body').on('click', '[data-ecommerce-clickable]', function(e) {
        let info = getEcommerceInfo(this);
        if(info) {
            let currency = getEcommerceCurrency(this);

            let event = {
                "ecommerce": {
                    "currencyCode": currency,
                    "click": {
                        //"actionField": '',
                        "products": [info],
                    },
                },
            };

            preserveEcommerceEvent(event);
        }
    });

    $(document).on('rebindEcommerceObservables', bindObservables);
    //$(document).on('catalog_updated', bindObservables);
    bindObservables();

    $(document).on('onEcommerceServerEvent', fetchEcommerceEvents);
    //$(document).on('refreshBasket', fetchEcommerceEvents);
    executeEcommerceEvents().then(fetchEcommerceEvents);
});