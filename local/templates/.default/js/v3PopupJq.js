(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(/*'v3PopupJq', */['vue'/*, 'v3-popupJq'*/], factory);
        // Or we can do that the 'global' way
        //define(/*'v3PopupJq', */['vue'/*, 'v3-popupJq'*/], function (Vue/*, vPopupJq*/) {
        //    return (root.v3PopupJq = factory(Vue/*, vPopupJq*/));
        //});
    } else if (typeof module === 'object' && module.exports) {
        // Node. Does not work with strict CommonJS, but
        // only CommonJS-like environments that support module.exports,
        // like Node.
        module.exports = factory(require('vue')/*, require('v3-popupJq')*/);
    } else {
        // Browser globals
        root.v3PopupJq = factory(root.Vue/*, root.vPopupJq*/);
    }
}(typeof self !== 'undefined' ? self : this, function (Vue/*, vPopupJq*/) {

    let { watch, nextTick, watchEffect, ref, onMounted, onUnmounted } = Vue || {};

    let reinit = function () {
        //HACK: Against Bitrix's script reordering. ;-(
        //Doesn't always work, unfortunately.
        if (typeof window !== 'undefined') {
            Vue = Vue || window.Vue;
            ({ watch, nextTick, watchEffect, ref, onMounted, onUnmounted } = Vue || {});
        }
    }

    BX(reinit);
    reinit();
    document.addEventListener("DOMContentLoaded", reinit);

    const MAX_POPUP_SIZE = 10;

    let setup = function (props, ctx) {
        let popup = new v3PopupJq(props, ctx);
        popup.setup();
        return popup.getExports();
    }

    let v3PopupJq = function(props, ctx) {
        this.props = props;
        this.ctx = ctx;

        this.desc = this.createDesc();
    }

    v3PopupJq.prototype.createDesc = function() {

        let self = this;

        /** desc = {
         * current()
         * select(id)
         * getUrl()
         * getLang()
         * watch
         * ?query(params)
         * }
         */
        let desc = {
            current: () => {
                if (self.props.modelValue) {
                    return {
                        'id': self.props.modelValue.ID,
                        'text': self.props.modelValue.NAME,
                        'code': self.props.modelValue.CODE,
                    }
                } else {
                    return {
                        'id': 0,
                        'text': self.props.text,
                        'code': '',
                    }
                }
            },
            select: (item) => {
                if (item && item.id) {
                    let val = {
                        ID: item.id,
                        NAME: item.text,
                        CODE: item.code,
                    }
                    self.props.modelValue = val;
                    self.props.text = item.text;
                    self.ctx.emit('update:modelValue', val);
                    self.ctx.emit('update:text', item.text);
                } else {
                    self.props.modelValue = false;
                    self.props.text = item.text;
                    self.ctx.emit('update:modelValue', false);
                    self.ctx.emit('update:text', item.text);
                }
            },
            getUrl: () => typeof self.props.url === 'function' ? self.props.url() : self.props.url,
            getLang: () => typeof self.props.lang === 'function' ? self.props.lang() : self.props.lang,
            watch: () => {
            },
            query: (params) => typeof self.props.query === 'function' ? self.props.query(params) :
                {
                    q: params.term || '',
                },
        };

        return desc;
    }

    v3PopupJq.prototype.setup = function () {
        watch(
            () => [this.props.modelValue, this.props.modelValue?.ID, this.props.text],
            () => this.desc.watch()
        );
    }

    v3PopupJq.prototype.getExports = function () {
        return {
            desc: this.desc,
        };
    }

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
    let source = function (desc, processResult) {
        return adapter(async function(q){
            let uri = desc.getUrl();
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

    let onSelect = function (desc, self, onChange, item, objects) {
        onChange(desc, getChangedId(objects, item.label), item.value, objects);
    }

    let onAutocompleteChange = function (desc, self, onChange, item, objects) {
        if(item) {
            onChange(desc, getChangedId(objects, item.label), item.value, objects);
        } else {
            onDomChange(desc, self, onChange, self.value, objects);
        }
    }

    let onDomChange = function (desc, self, onChange, text, objects) {
        let value = (text || '').toLowerCase();
        let oldValue = desc.current().text.toLowerCase();
        if(value != oldValue) {
            onChange(desc, 0, text, objects);
        }
    }

    function onChange (desc, newId, text, list) {
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

    let vPopupJq = {
        mounted(el, binding) {
            let desc = binding.value;//!?
            let $el = $(el);
            let list = {};
            let listSrc = source(
                desc,
                (response) => processResultImpl(response, list)
            );
            $el.autocomplete({
                source: listSrc,
                minLength: 0,
                select: function( event, ui ) {
                    onSelect(desc, this, onChange, ui.item, list);
                },
                change: function( event, ui ) {
                    onAutocompleteChange(desc, this, onChange, ui.item, list);
                },
            });
            $el.on('focus', function() {
                $el.autocomplete('search');
            });
            $el.on("autocompleteselect", function( event, ui ) {
                onSelect(desc, this, onChange, ui.item, list);
            });
            $el.on("autocompletechange", function( event, ui ) {
                onAutocompleteChange(desc, this, onChange, ui.item, list);
            });
            $el.on("change", function(event) {
                onDomChange(desc, this, onChange, $el.val(), list);
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

    return {
        setup,
        props: {
            'modelValue': {
                type: Object,
                required: true,
            },
            'text': {
                type: String,
                required: true,
            },

            url: {
                required: true,
            },
            lang: {},
            query: {},
        },
        emits: ['update:modelValue', 'update:text'],
        directives: {
            'popup-jq': vPopupJq,
        },
        template: '<input v-popup-jq="desc" type="text" val="">',
    }
}));