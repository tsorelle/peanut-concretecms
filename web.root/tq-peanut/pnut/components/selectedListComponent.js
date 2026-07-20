var Peanut;
(function (Peanut) {
    class selectedListComponent {
        constructor(params) {
            this.removeText = ko.observable('Remove');
            this.readonly = ko.observable(false);
            this.emptymessage = ko.observable('');
            this.label = ko.observable('');
            this.removeItem = (item) => {
                let me = this;
                let sourceItems = me.selectedItems();
                let remaining = sourceItems.filter((sourceItem) => {
                    return sourceItem.id != item.id;
                });
                me.selectedItems(remaining);
            };
            let me = this;
            if (!params) {
                console.error('selectedListComponent: Params not defined in selectedListComponent');
                return;
            }
            if (params.source === undefined) {
                console.error('selectedListComponent: Parameter "source" is required');
                return;
            }
            if (ko.isObservable(params.source)) {
                me.selectedItems = params.source;
            }
            else {
                if (Array.isArray(params.source)) {
                    me.selectedItems = ko.observableArray(params.source);
                }
                else {
                    me.selectedItems = params.source.selected;
                }
            }
            let test = this.selectedItems();
            if (params.readonly) {
                me.readonly(true);
            }
            let translator = me;
            if (params.translator) {
                translator = params.translator();
            }
            if (params.emptymessage) {
                me.emptymessage(translator.translate(params.emptymessage));
            }
            if (params.label) {
                me.label(translator.translate(params.label));
            }
        }
        translate(text, defaultText) {
            return text ? text : defaultText;
        }
    }
    Peanut.selectedListComponent = selectedListComponent;
})(Peanut || (Peanut = {}));
//# sourceMappingURL=selectedListComponent.js.map