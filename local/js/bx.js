(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define('BX', ['jquery'], function (jQuery) {
            return (root.BX = root.BX || factory(jQuery));
        });
    } else if (typeof module === 'object' && module.exports) {
        // Node. Does not work with strict CommonJS, but
        // only CommonJS-like environments that support module.exports,
        // like Node.
        module.exports = root.BX = root.BX || factory(require('jquery'));
    } else {
        // Browser globals
        root.BX = root.BX || factory(root.jQuery);
    }
}(typeof self !== 'undefined' ? self : this, function (jQuery) {

    let $ = jQuery;
    let reinit = function () {
        //HACK: Against Bitrix's script reordering. ;-(
        //Doesn't always work, unfortunately.
        if (typeof window !== 'undefined') {
            $ = jQuery = jQuery || window.jQuery;
        }
    }

    reinit();
    document.addEventListener("DOMContentLoaded", reinit);

    let BX = function (param) {
        if(typeof param === 'string') {
            return document.getElementById(param);
        } else if(typeof param === 'function') {
            BX.ready(param);
        } else {
            return param;
        }
    }

    BX.ready = function(fn) {
        if (document.readyState === "complete" || document.readyState === "interactive") {
            // call on next available tick
            setTimeout(fn, 1);
        } else {
            document.addEventListener("DOMContentLoaded", fn);
        }
    }

    let __gsv_messages = {};

    //HACK: Exported for sessid's update
    BX.__gsv_update_sessid = function (sessid) {
        __gsv_messages['bitrix_sessid'] = sessid;
    }

    BX.message = function (param) {
        if(typeof param === 'string') {
            return __gsv_messages[param];
        } else if(typeof param === 'object' || typeof param === 'function') {
            Object.assign(__gsv_messages, param);
        }
    }

    BX.bitrix_sessid = function () {
        return BX.message('bitrix_sessid');
    }

    let defaultConfig = {
        method: 'GET', // request method: GET|POST
        dataType: 'html', // type of data loading: html|json|script
        timeout: 0, // request timeout in seconds. 0 for browser-default
        async: true, // whether request is asynchronous or not
        processData: true, // any data processing is disabled if false, only callback call
        scriptsRunFirst: false, // whether to run _all_ found scripts before onsuccess call. script tag can have an attribute "bxrunfirst" to turn  this flag on only for itself
        emulateOnload: true,
        skipAuthCheck: false, // whether to check authorization failure (SHOUD be set to true for CORS requests)
        start: true, // send request immediately (if false, request can be started manually via XMLHttpRequest object returned)
        cache: true, // whether NOT to add random addition to URL
        preparePost: true, // whether set Content-Type x-www-form-urlencoded in POST
        headers: false, // add additional headers, example: [{'name': 'If-Modified-Since', 'value': 'Wed, 15 Aug 2012 08:59:08 GMT'}, {'name': 'If-None-Match', 'value': '0'}]
        lsTimeout: 30, //local storage data TTL. useless without lsId.
        lsForce: false, //wheter to force query instead of using localStorage data. useless without lsId.
    };

    var getLastContentTypeHeader = function (headers) {
        if (!Array.isArray(headers))
        {
            return null;
        }
        var lastHeader = headers
            .filter(function (header) {
                return header.name === 'Content-Type';
            })
            .pop();

        return lastHeader ? lastHeader.value : null;
    };

    BX.ajax = function(config)
    {
        var status;

        if (!config || !config.url || typeof config.url !== 'string')
        {
            return false;
        }

        for (let i in defaultConfig)
            if (typeof (config[i]) == "undefined")
                config[i] = defaultConfig[i];

        config.method = config.method.toUpperCase();

        /*if (BX.browser.IsIE())
        {
            var result = r.url_utf.exec(config.url);
            if (result)
            {
                do
                {
                    config.url = config.url.replace(result, BX.util.urlencode(result));
                    result = r.url_utf.exec(config.url);
                } while (result);
            }
        }*/

        if(config.dataType == 'json')
            config.emulateOnload = false;

        if (config.method == 'POST')
        {
            if (config.preparePost)
            {
                config.data = BX.ajax.prepareData(config.data);
            }
            else if (getLastContentTypeHeader(config.headers) === 'application/json')
            {
                /*const isJson = (
                    BX.Type.isPlainObject(config.data)
                    || BX.Type.isString(config.data)
                    || BX.Type.isNumber(config.data)
                    || BX.Type.isBoolean(config.data)
                    || BX.Type.isArray(config.data)
                );

                if (isJson)
                {*/
                    config.data = JSON.stringify(config.data);
                /*}*/
            }
        }

        var bXHR = true;

        if (bXHR)
        {
            config.xhr = BX.ajax.xhr();
            if (!config.xhr)
                return;

            if (typeof  (config.onprogress) === 'function')
            {
                //BX.bind(config.xhr, 'progress', config.onprogress);
            }

            if (typeof  (config.onprogressupload) === 'function' && config.xhr.upload)
            {
                //BX.bind(config.xhr.upload, 'progress', config.onprogressupload);
            }

            config.xhr.open(config.method, config.url, config.async);

            /*if (!config.skipBxHeader && !BX.ajax.isCrossDomain(config.url))
            {
                config.xhr.setRequestHeader('Bx-ajax', 'true');
            }*/

            if (config.method == 'POST' && config.preparePost)
            {
                config.xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            }
            if (typeof(config.headers) == "object")
            {
                for (i = 0; i < config.headers.length; i++)
                    config.xhr.setRequestHeader(config.headers[i].name, config.headers[i].value);
            }

            var bRequestCompleted = false;
            var onreadystatechange = config.xhr.onreadystatechange = function(additional)
            {
                if (bRequestCompleted)
                    return;

                if (additional === 'timeout')
                {
                    if (config.onfailure)
                    {
                        config.onfailure('timeout', '', config);
                    }

                    //BX.onCustomEvent(config.xhr, 'onAjaxFailure', ['timeout', '', config]);

                    config.xhr.onreadystatechange = BX.DoNothing;
                    config.xhr.abort();

                    if (config.async)
                    {
                        config.xhr = null;
                    }
                }
                else
                {
                    if (config.xhr.readyState == 4 || additional == 'run')
                    {
                        status = BX.ajax.xhrSuccess(config.xhr) ? "success" : "error";
                        bRequestCompleted = true;
                        config.xhr.onreadystatechange = BX.DoNothing;

                        if (status == 'success')
                        {
                            var authHeader = (!!config.skipAuthCheck || BX.ajax.isCrossDomain(config.url))
                                ? false
                                : config.xhr.getResponseHeader('X-Bitrix-Ajax-Status');

                            if(!!authHeader && authHeader == 'Authorize')
                            {
                                if (config.onfailure)
                                {
                                    config.onfailure('auth', config.xhr.status, config);
                                }

                                BX.onCustomEvent(config.xhr, 'onAjaxFailure', ['auth', config.xhr.status, config]);
                            }
                            else
                            {
                                var data = config.xhr.responseText;

                                if (config.lsId)
                                {
                                    BX.localStorage.set('ajax-' + config.lsId, data, config.lsTimeout);
                                }

                                BX.ajax.__run(config, data);
                            }
                        }
                        else
                        {
                            if (config.onfailure)
                            {
                                config.onfailure('status', config.xhr.status, config);
                            }

                            BX.onCustomEvent(config.xhr, 'onAjaxFailure', ['status', config.xhr.status, config]);
                        }

                        if (config.async)
                        {
                            config.xhr = null;
                        }
                    }
                }
            };

            if (config.async && config.timeout > 0)
            {
                setTimeout(function() {
                    if (config.xhr && !bRequestCompleted)
                    {
                        onreadystatechange("timeout");
                    }
                }, config.timeout * 1000);
            }

            if (config.start)
            {
                config.xhr.send(config.data);

                if (!config.async)
                {
                    onreadystatechange('run');
                }
            }

            return config.xhr;
        }
        else
        {
            //TODO: Use fetch!
        }
    };

    BX.DoNothing = function()
    {

    }

    BX.ajax.xhr = function()
    {
        if (window.XMLHttpRequest)
        {
            try {return new XMLHttpRequest();} catch(e){}
        }
        else if (window.ActiveXObject)
        {
            try { return new window.ActiveXObject("Msxml2.XMLHTTP.6.0"); }
            catch(e) {}
            try { return new window.ActiveXObject("Msxml2.XMLHTTP.3.0"); }
            catch(e) {}
            try { return new window.ActiveXObject("Msxml2.XMLHTTP"); }
            catch(e) {}
            try { return new window.ActiveXObject("Microsoft.XMLHTTP"); }
            catch(e) {}
            throw new Error("This browser does not support XMLHttpRequest.");
        }

        return null;
    };

    let prepareData = function(arData)
    {
        var data = {};
        if (null != arData)
        {
            if(typeof arData == 'object')
            {
                for(var i in arData)
                {
                    if (arData.hasOwnProperty(i))
                    {
                        var name = encodeURIComponent(i);
                        if(typeof arData[i] == 'object' && arData[i]["file"] !== true)
                            data[name] = prepareData(arData[i]);
                        else if (arData[i]["file"] === true)
                            data[name] = arData[i]["value"];
                        else
                            data[name] = encodeURIComponent(arData[i]);
                    }
                }
            }
            else
                data = encodeURIComponent(arData);
        }
        return data;
    };

    BX.ajax.prepareData = function(arData, prefix)
    {
        var data = '';
        if (typeof (arData) === 'string')
            data = arData;
        else if (null != arData)
        {
            for(var i in arData)
            {
                if (arData.hasOwnProperty(i))
                {
                    if (data.length > 0)
                        data += '&';
                    var name = encodeURIComponent(i);
                    if(prefix)
                        name = prefix + '[' + name + ']';
                    if(typeof arData[i] == 'object')
                        data += BX.ajax.prepareData(arData[i], name);
                    else
                        data += name + '=' + encodeURIComponent(arData[i]);
                }
            }
        }
        return data;
    };

    BX.ajax.promise = function(config)
    {
        var result = new Promise(function (fulfill, reject) {

            config.onsuccess = function (data) {
                fulfill(data);
            };
            config.onfailure = function (reason, httpStatus, config) {
                reject({
                    reason: reason,
                    data: httpStatus,
                    ajaxConfig: config,
                    xhr: config.xhr
                });
            };

            var xhr = BX.ajax(config);
            if (xhr) {
                if (typeof config.onrequeststart === 'function') {
                    config.onrequeststart(xhr);
                }
            } else {
                reject({
                    reason: "init",
                    data: false
                });
            }
        });

        return result;
    };

    let getCookieList = function() {
        return document.cookie
            .split(';')
            .map(item => item.split('='))
            .map(item => item.map(subItem => subItem.trim()))
            .reduce((acc, item) => {
                const [key, value] = item;
                acc[decodeURIComponent(key)] = (
                    decodeURIComponent(value)
                );
                return acc;
            }, {});
    }

    BX.getCookie = function(name) {
        let cookiesList = getCookieList();

        if (name in cookiesList) {
            return cookiesList[name];
        }

        return undefined;
    }

    BX.setCookie = function(name, value, options = {}) {
        const attributes = {
            expires: '',
            path: '/',//HACK: otherwise there are problems
            ...options,
        };

        if (!Number.isNaN(attributes.expires) && typeof attributes.expires === 'number') {
            const now = (+new Date());
            const days = attributes.expires;
            const dayInMs = 864e+5;
            attributes.expires = new Date(now + days * dayInMs);
        }

        if (typeof attributes.expires == 'object'
            && attributes.expires instanceof Date && !isNaN(attributes.expires.getTime())) {
            attributes.expires = attributes.expires.toUTCString();
        }

        const safeName = decodeURIComponent(String(name))
            .replace(/%(23|24|26|2B|5E|60|7C)/g, decodeURIComponent)
            .replace(/[()]/g, escape);

        const safeValue = encodeURIComponent(String(value))
            .replace(/%(23|24|26|2B|3A|3C|3E|3D|2F|3F|40|5B|5D|5E|60|7B|7D|7C)/g, decodeURIComponent);

        const stringifiedAttributes = Object.keys(attributes)
            .reduce((acc, key) => {
                const attributeValue = attributes[key];

                if (!attributeValue) {
                    return acc;
                }

                if (attributeValue === true) {
                    return `${acc}; ${key}`;
                }

                /**
                 * Considers RFC 6265 section 5.2:
                 * ...
                 * 3. If the remaining unparsed-attributes contains a %x3B (';')
                 * character:
                 * Consume the characters of the unparsed-attributes up to,
                 * not including, the first %x3B (';') character.
                 */
                return `${acc}; ${key}=${attributeValue.split(';')[0]}`;
            }, '');

        document.cookie = `${safeName}=${safeValue}${stringifiedAttributes}`;
    }

    return BX;
}));