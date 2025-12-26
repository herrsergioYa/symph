(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(/*'vPopupJq', */['vue', 'jquery'], factory);
        // Or we can do that the 'global' way
        //define(/*'vPopupJq', */['vue', 'jquery'], function (Vue, jQuery) {
        //    return (root.vPopupJq = factory(Vue, jQuery));
        //});
    } else if (typeof module === 'object' && module.exports) {
        // Node. Does not work with strict CommonJS, but
        // only CommonJS-like environments that support module.exports,
        // like Node.
        module.exports = factory(require('vue'), require('jquery'));
    } else {
        // Browser globals
        root.vPopupJq = factory(root.Vue, root.jQuery);
    }
}(typeof self !== 'undefined' ? self : this, function (Vue, jQuery) {

    let { watch, nextTick, watchEffect, ref, onMounted, onUnmounted } = Vue || {};
    let $ = jQuery;

    let reinit = function () {
        //HACK: Against Bitrix's script reordering. ;-(
        //Doesn't always work, unfortunately.
        if (typeof window !== 'undefined') {
            $ = jQuery = jQuery || window.jQuery;

            Vue = Vue || window.Vue;
            ({ watch, nextTick, watchEffect, ref, onMounted, onUnmounted } = Vue || {});
        }
    }

    BX(reinit);
    reinit();
    document.addEventListener("DOMContentLoaded", reinit);

    const MAX_POPUP_SIZE = 10;

    let createPopupJq = function(desc) {

        /** desc = {
         * current()
         * select(item)
         * getUrl()
         * getLang()
         * watch
         * ?query(params)
         * }
         */

        let processResultImpl = function (response, objects) {
            let result = [];
            for (let item of Object.values(response)) {
                let data = {
                    value: item.text,
                    label: getLabel(item),
                }
                if(result.length < MAX_POPUP_SIZE) {
                    result.push(data);
                }
                objects[item.id] = item;
            }
            return result;
        }

        let ensureOption = function (value, objects) {
            if (value && value.id != 0 && !(value.id in objects)) {
                objects[value.id] = Object.assign({}, value);
            }
        }

        let selectOption = function ($el, id, objects, text) {
            if(id == 0) {
                $el.val(text || '');
            } else if (id in objects) {
                let text = objects[id].text;
                text = text || '';
                $el.val(text);
            } else {
                $el.val(text || '');
            }
        }

        let adapter = function (thenable) {
            return ({term}, resolv) => thenable(term).then(resolv);
        }
        let source = function (url, processResult) {
            return adapter(async function(q){
                let uri = url;
                if(typeof uri === 'function') {
                    uri = uri();
                }
                let params = {term: q};
                if(desc.query) {
                    params = desc.query(params);
                }
                let response = await fetch(uri + '&' + $.param(params) , {
                    headers: {
                        'X-Ajax': 'Y'
                    },
                });
                let result = await response.json();
                return processResult(result);
            });
        }

        let getChangedId = function (objects, text) {
            text = text.trim().toLowerCase();
            for(let id in objects) {
                let value = objects[id];
                value = getLabel(value);
                value = value.trim().toLowerCase();
                if(value == text) {
                    return id;
                }
            }
            return 0;
        }

        let getLabel = function (item) {
            if(item.group) {
                return item.text + ', ' + item.group;
            } else {
                return item.text;
            }
        }

        let onSelect = function (self, onChange, item, objects) {
            onChange(getChangedId(objects, item.label), item.value, objects);
        }

        let onAutocompleteChange = function (self, onChange, item, objects) {
            if(item) {
                onChange(getChangedId(objects, item.label), item.value, objects);
            } else {
                onDomChange(self, onChange, self.value, objects);
            }
        }

        let onDomChange = function (self, onChange, text, objects) {
            let value = (text || '').toLowerCase();
            let oldValue = desc.current().text.toLowerCase();
            if(value != oldValue) {
                onChange(0, text, objects);
            }
        }

        function onChange (newId, text, list) {
            if(newId != 0 && newId in list) {
                desc.select(list[newId]);
            } else {
                let temp = {
                    id: 0,
                    text,
                };
                desc.select(temp);
            }
        }

        let vPopup = {
            mounted(el, binding) {
                let $el = $(el);
                let list = {};
                let listSrc = source(
                    desc.getUrl(),
                    (response) => processResultImpl(response, list)
                );
                $el.autocomplete({
                    source: listSrc,
                    minLength: 0,
                    select: function( event, ui ) {
                        onSelect(this, onChange, ui.item, list);
                    },
                    change: function( event, ui ) {
                        onAutocompleteChange(this, onChange, ui.item, list);
                    },
                });
                $el.on('focus', function() {
                    $el.autocomplete('search');
                });
                $el.on("autocompleteselect", function( event, ui ) {
                    onSelect(this, onChange, ui.item, list);
                });
                $el.on("autocompletechange", function( event, ui ) {
                    onAutocompleteChange(this, onChange, ui.item, list);
                });
                $el.on("change", function(event) {
                    onDomChange(this, onChange, $el.val(), list);
                });
                let listener = () => {
                    let option = desc.current();
                    let id = option.id;
                    ensureOption(option, list);
                    selectOption($el, id, list, option.text);
                };
                desc.watch = listener;
                listener();
            },
            updated: function (el, binding) {

            },
            unmounted: function (el, binding) {
                let $el = $(el);
                $el.autocomplete('destroy');
            },
        }

        return vPopup;
    };

    return {
        createPopupJq,
    }
}));