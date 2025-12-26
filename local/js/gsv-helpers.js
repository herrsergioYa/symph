(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(/*'gsvJsHelper', */['jquery', 'fancybox'], factory);
        // Or we can do that the 'global' way
        //define(/*'gsvJsHelper', */['jquery', 'fancybox'], function (jquery, fancybox) {
        //    return (root.gsvJsHelper = factory(jquery, fancybox));
        //});
    } else if (typeof module === 'object' && module.exports) {
        // Node. Does not work with strict CommonJS, but
        // only CommonJS-like environments that support module.exports,
        // like Node.
        module.exports = factory(require('jquery'), require('fancybox'));
    } else {
        // Browser globals
        root.gsvJsHelper = factory(root.jQuery, root.Fancybox);
    }
}(typeof self !== 'undefined' ? self : this, function (jQuery, Fancybox) {

    let $ = jQuery;

    let fancyAnswer = function(target) {
        return new Promise(function (resolv) {

            let approveButton = null;

            let fancybox = Fancybox.fromNodes(
                [target], //Must have data-src="#true-target"
                {
                    on: {
                        done: (fancybox, slide) => {
                            let $el = $(slide.contentEl);
                            if (approveButton) {
                                //The documentation says it can be invoked multiple times
                                //though I didn't see it.
                                return;
                            }
                            approveButton = $el.find('[data-approve-fancybox]');
                            if (approveButton.length) {
                                approveButton.on('click', function () {
                                    resolv(true);
                                    fancybox.close();
                                })
                            } else {
                                approveButton = null;
                            }
                        },
                        close: (fancybox, slide) => {
                            if (approveButton) {
                                approveButton.off('click');
                            }
                            approveButton = null;
                            resolv(false);
                        },
                    },
                }
            );
        });
    }

    let renderMustacheBySelector = function(sel, data) {
        let element = document.querySelector(sel);
        return renderMustacheEl(element, data);
    }

    let renderMustache = function(el, data) {
        return typeof el === 'string' ? renderMustacheById(el, data) : renderMustacheEl(el, data);
    }

    let renderMustacheById = function(id, data) {
        let element = document.getElementById(id);//I hope it is faster than querySelector()
        return renderMustacheEl(element, data);
    }

    let renderMustacheEl = function(element, data) {
        if(!element) {
            return null;
        }
        let template = element.innerHTML;
        return renderMustacheTemplate(template, data);
    }

    let renderMustacheTemplate = function(template, data) {
        if(!('helpers' in data)) {
            data = Object.assign({}, data, {
                'helpers': mustacheHelpers,
            })
        }
        return Mustache.render(template, data);
    }

    let mustacheHelpers = {};
    mustacheHelpers.iterObj = function () {
        let ctx = [];
        for(let key in this) {
            if(this.hasOwnProperty(key)) {
                let value = this[key];
                ctx.push({key, value});
            }
        }
        return ctx;
    };
    mustacheHelpers.iterKeys = function () {
        return Object.keys(this);
    };
    mustacheHelpers.iterValues = function () {
        return Object.values(this);
    };

    let renderTemplateImpl = function (template, data) {
        let result = template;
        for(let key in data) {
            template = template.replaceAll(`#${key}#`, data[key]);
        }
        return result;
    }

    let renderTemplateBySelector = function(sel, data) {
        let element = document.querySelector(sel);
        return renderTemplateEl(element, data);
    }

    let renderTemplate = function(el, data) {
        return typeof el === 'string' ? renderTemplateById(el, data) : renderTemplateEl(el, data);
    }

    let renderTemplateById = function(id, data) {
        let element = document.getElementById(id);//I hope it is faster than querySelector()
        return renderTemplateEl(element, data);
    }

    let renderTemplateEl = function(element, data) {
        if(!element) {
            return null;
        }
        let template = element.innerHTML;
        return renderTemplateImpl(template, data);
    }

    let renderTwigBySelector = function(sel, data) {
        let element = document.querySelector(sel);
        return renderTwigEl(data);
    }

    let renderTwig = function(el, data) {
        return typeof el === 'string' ? renderTwigById(el, data) : renderTwigEl(el, data);
    }

    let renderTwigById = function(id, data) {
        let element = document.getElementById(id);//I hope it is faster than querySelector()
        return renderTwigEl(element, data);
    }

    let renderTwigEl = function(element, data) {
        if(!element) {
            return null;
        }
        let template = element.innerHTML;
        let id = element.dataset['twigId'];
        if (!id) {
            if(element.id) {
                id = '_twig_' + element.id;
            } else {
                id = '__twig__' + (new Date()).getTime() + '__' + Math.random();
            }
            element.dataset['twigId'] = id;
        }
        let params = {
            data: template,
            id,
        }
        let twigTemplate = Twig.twig(params);
        return twigTemplate.render(data);
    }

    let renderTwigTemplate = function(template, data) {
        let params = {
            data: template,
        }
        //params.id = 'some hash?';
        let twigTemplate = Twig.twig(params);
        return twigTemplate.render(data);
    }

    let handlebars__cache = {};

    let renderHandlebarsBySelector = function(sel, data) {
        let element = document.querySelector(sel);
        return renderHandlebarsEl(element, data);
    }

    let renderHandlebars = function(el, data) {
        return typeof el === 'string' ? renderHandlebarsById(el, data) : renderHandlebarsEl(el, data);
    }

    let renderHandlebarsById = function(id, data) {
        let element = document.getElementById(id);//I hope it is faster than querySelector()
        return renderHandlebarsEl(element, data);
    }

    let renderHandlebarsEl = function (element, data) {
        if(!element) {
            return null;
        }
        let id = element.dataset['handlebarsId'];
        if (!id) {
            if(element.id) {
                id = '_handlebars_' + element.id;
            } else {
                id = '__handlebars__' + (new Date()).getTime() + '__' + Math.random();
            }
            element.dataset['handlebarsId'] = id;
        }
        let template = handlebars__cache[id];
        if(!template) {
            template = element.innerHTML;
            template = Handlebars.compile(template);
            handlebars__cache[id] = template;
        }
        return template(data);
    }

    let renderHandlebarsTemplate = function(template, data) {
        template = Handlebars.compile(template);
        return template(data);
    }

    let callbackAdapter = function (thenable) {
        return (...args) => thenable(...args.slice(0, -1)).then(args[args.length - 1]);
    }

    let callbackAdapter2 = function (thenable) {
        return (...args) => thenable(...args.slice(0, -2)).then(args[args.length - 2], args[args.length - 1]);
    }

    let delay = async function(timeout) {
        await new Promise(function (resolv, reject) {
            setTimeout(resolv, timeout);
        });
    }

    let waitFor = function(condition = null) {
        let resolve = null;
        let rejected = null;
        let promise =  new Promise(function (resolv, reject) {
            resolve = resolv;
            rejected = reject;
        });
        let result = {
            onready: resolve,
            onfail: rejected,
            awaitable: promise,
        };
        if(condition) {
            result.oncheck = function (data) {
                if(condition(data)) {
                    resolve(data);
                }
            }
        } else {
            result.oncheck = function (data) {
                resolve(data);
            }
        }
        return result;
    }

    let pluralForm = function(number, item, lang = 'ru')
    {
        lang = (lang || '').toLowerCase();
        if(lang == 'ru' || lang == 'ua') {
            const cases = [2, 0, 1, 1, 1, 2];
            return item[(number % 100 > 4 && number % 100 < 20) ? 2 : cases[Math.min(number % 10, 5)]];
        } else if(lang == 'en' || lang == 'de' || lang == 'es') {
            //0 itemS <!--
            return number == 1 ? item[0] : item[1];
        } else {
            return null;
        }
    }

    let ready = function(fn) {
        if (document.readyState === "complete" || document.readyState === "interactive") {
            // call on next available tick
            setTimeout(fn, 1);
        } else {
            document.addEventListener("DOMContentLoaded", fn);
        }
    }

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

    let getCookie = function(name) {
        let cookiesList = getCookieList();

        if (name in cookiesList) {
            return cookiesList[name];
        }

        return undefined;
    }

    let setCookie = function(name, value, options = {}) {
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

    return {
        fancyAnswer,
        renderMustacheBySelector,
        renderMustache,
        renderMustacheById,
        renderMustacheEl,
        renderMustacheTemplate,
        mustacheHelpers,
        renderTemplateImpl,
        renderTemplateBySelector,
        renderTemplate,
        renderTemplateById,
        renderTemplateEl,
        renderTwigBySelector,
        renderTwig,
        renderTwigById,
        renderTwigEl,
        renderTwigTemplate,
        renderHandlebarsBySelector,
        renderHandlebars,
        renderHandlebarsById,
        renderHandlebarsEl,
        renderHandlebarsTemplate,
        callbackAdapter,
        callbackAdapter2,
        delay,
        waitFor,
        pluralForm,
        ready,
        getCookieList,
        getCookie,
        setCookie,
    }
}));