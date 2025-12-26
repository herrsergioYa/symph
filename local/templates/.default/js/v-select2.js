(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(/*'vSelect2', */['vue', 'jquery'], factory);
        // Or we can do that the 'global' way
        //define(/*'vSelect2', */['vue', 'jquery'], function (Vue, jQuery) {
        //    return (root.vSelect2 = factory(Vue, jQuery));
        //});
    } else if (typeof module === 'object' && module.exports) {
        // Node. Does not work with strict CommonJS, but
        // only CommonJS-like environments that support module.exports,
        // like Node.
        module.exports = factory(require('vue'), require('jquery'));
    } else {
        // Browser globals
        root.vSelect2 = factory(root.Vue, root.jQuery);
    }
}(typeof self !== 'undefined' ? self : this, function (Vue, jQuery) {

    let { watch, nextTick, watchEffect } = Vue;
    let $ = jQuery;

    let reinit = function () {
        //HACK: Against Bitrix's script reordering. ;-(
        //Doesn't always work, unfortunately.
        if (typeof window !== 'undefined') {
            $ = jQuery = jQuery || window.jQuery;

            Vue = Vue || window.Vue;
            ({ watch, nextTick, watchEffect } = Vue || {});
        }
    }

    BX(reinit);
    reinit();
    document.addEventListener("DOMContentLoaded", reinit);

    let processResultImpl = function (response, objects) {
        let result = [];
        let groups = {};
        let groupList = []; //To preserve sorting...
        for (let i in response) {
            let item = response[i];
            let data = {
                id: item.id,
                text: item.text,
            }
            let group = item.group || '';
            if(!(group in groups)) {
                groups[group] = {
                    text: group,
                    children: [],
                };
                groupList.push(groups[group]);
            }
            groups[group].children.push(data);
            result.push(data);
            objects[item.id] = item;
        }
        if(groupList.length > 1) {
            result = groupList;
        }
        return {
            results: result,
        };
    }

    let createPopup = function(desc) {

        /** desc = {
         * current()
         * select(item)
         * getUrl()
         * getLang()
         * watch
         * ?query(params)
         * }
         */

        let ensureOption = function (id, option, objects) {
            if (id != 0 && !(id in objects)) {
                option.id = id;
                objects[id] = option;
            }
        }

        let selectOption = function ($el, id, objects) {
            if(id == 0) {
                id = 0; // 0 in objects IST VERBOTEN!!
                //let newOption = new Option('', 0, true, true);
                // Append it to the select?
                $el.val('0').trigger('change');
            }else if ($el.find("option[value='" + id + "']").length) {
                $el.val(id).trigger('change');
            } else if (id in objects) {
                // Create a DOM Option and pre-select by default
                let text = objects[id].text || '';
                let newOption = new Option(text, id, true, true);
                // Append it to the select
                $el.append(newOption).trigger('change');
            } else {
                //let it be...
                selectOption($el, 0, objects);
            }
        }

        let vPopup = {
            mounted(el, binding) {
                let $el = $(el);
                let list = {};
                $el.select2({
                    ajax: {
                        url: desc.getUrl(),
                        dataType: 'json',
                        data: function (params) {
                            return desc.query ? desc.query(params) : params;
                        },

                        processResults: (response) => processResultImpl(response, list),
                        delay: 500,
                        cache: true,
                    },
                    language: desc.getLang(),
                });
                $el.on('change', function () {
                    let newId = $el.val();
                    desc.select(list[newId]);
                });
                let listener = () => {
                    let option = desc.current();
                    let id = option.id;
                    ensureOption(id, option, list);
                    selectOption($el, id, list);
                };
                desc.watch = listener;
                listener();
            },
            updated: function (el, binding) {

            },
            unmounted: function (el, binding) {
                let $el = $(el);
                $el.select2('destroy');
            },
        }

        return vPopup;
    };

    return {
        createPopup,
    };

}));