var Peanut;
(function (Peanut) {
    class lookupSelectComponent {
        constructor(params) {
            this.controlId = ko.observable('lookup-select');
            this.autocomplete = ko.observable(false);
            this.inline = ko.observable(false);
            let me = this;
            let valid = true;
            if (!params) {
                console.error('lookupSelectComponent: Params not defined in lookupSelectComponent');
                return;
            }
            if (params.controller) {
                me.options = params.controller.options;
                if (me.options == undefined) {
                    console.error('Options are not defined in controler.');
                }
                me.selectedItem = params.controller.selected;
            }
            else {
                if (params.items) {
                    if (ko.isObservable(params.items)) {
                        me.options = params.items;
                    }
                    else if (Array.isArray(params.items)) {
                        me.options = ko.observableArray(params.item);
                    }
                    if (params.selected) {
                        me.selectedItem = params.selected;
                    }
                }
            }
            if (typeof me.options == undefined) {
                console.error('lookupSelectComponent: Either parameter "controller" or parameter "items" is required');
                valid = false;
            }
            if (typeof me.selectedItem == undefined) {
                console.error('lookupSelectComponent: Either parameter "controller" or parameter "selected" is required');
                valid = false;
            }
            if (!valid) {
                return;
            }
            let test = me.options();
            let translator = me;
            if (params.translator) {
                translator = params.translator();
            }
            if (params.caption) {
                if (ko.isObservable(params.caption)) {
                    me.caption = params.caption;
                }
                else {
                    me.caption = ko.observable(translator.translate(params.caption));
                }
            }
            else {
                me.caption = ko.observable(null);
            }
            if (params.display) {
                if (params.display == 'inline') {
                    me.inline(true);
                }
            }
            if (params.id) {
                me.controlId(params.id);
            }
            if (params.label) {
                if (ko.isObservable(params.label)) {
                    me.label = params.label;
                }
                else {
                    me.label = ko.observable(translator.translate(params.label));
                }
            }
            else {
                me.label = ko.observable('');
            }
            if (params.title) {
                let title = null;
                if (ko.isObservable(params.title)) {
                    title = params.title;
                }
                else {
                    title = ko.observable(translator.translate(params.title));
                }
                if (params.label) {
                    me.title = ko.observable('');
                    me.labelTitle = title;
                }
                else {
                    me.title = title;
                    me.labelTitle = ko.observable('');
                }
            }
            else {
                me.title = ko.observable('');
                me.labelTitle = ko.observable('');
            }
        }
        translate(text, defaultText) {
            return text ? text : defaultText;
        }
    }
    Peanut.lookupSelectComponent = lookupSelectComponent;
})(Peanut || (Peanut = {}));
//# sourceMappingURL=lookupSelectComponent.js.map