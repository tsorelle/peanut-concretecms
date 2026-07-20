var Peanut;
(function (Peanut) {
    class multiSelectComponent {
        constructor(params) {
            this.errorMessage = ko.observable('');
            this.options = ko.observableArray([]);
            this.selectedItem = ko.observable(null);
            this.label = ko.observable('');
            this.caption = ko.observable('Please select...');
            this.removeText = ko.observable('Remove');
            this.itemSubscription = null;
            this.selectionsSubscription = null;
            this.controlId = ko.observable('multi-select-field');
            this.sortValue = 'name';
            this.filterAvailable = () => {
                let me = this;
                let test = me.options();
                let selected = me.selected();
                let items = me.allItems();
                let result = items.filter((item) => {
                    let existing = selected.find((selectItem) => {
                        return selectItem.id == item.id;
                    });
                    return (!existing);
                });
                me.options(result);
            };
            this.addItem = (item) => {
                if (item) {
                    this.moveSelectedItem(item, this.options, this.selected);
                }
            };
            this.removeItem = (item) => {
                this.moveSelectedItem(item, this.selected, this.options);
            };
            this.moveSelectedItem = (item, source, target) => {
                let me = this;
                me.suspendSubscriptions();
                let sourceItems = source();
                let targetItems = target();
                let remaining = sourceItems.filter((sourceItem) => {
                    return sourceItem.id != item.id;
                });
                remaining = me.sortItems(remaining);
                target.push(item);
                targetItems = me.sortItems(targetItems);
                source(remaining);
                target(targetItems);
                me.activateSubscriptions();
            };
            this.suspendSubscriptions = () => {
                if (this.itemSubscription !== null) {
                    this.itemSubscription.dispose();
                    this.itemSubscription = null;
                }
                if (this.selectionsSubscription !== null) {
                    this.selectionsSubscription.dispose();
                    this.selectionsSubscription = null;
                }
            };
            this.activateSubscriptions = () => {
                this.selectedItem(null);
                this.itemSubscription = this.selectedItem.subscribe(this.addItem);
                this.selectionsSubscription = this.selected.subscribe(this.filterAvailable);
            };
            let me = this;
            me.errorMessage('Cannot load multi-select');
            if (!params) {
                console.error('multiSelectComponent: Params not defined in multi-select');
                return;
            }
            let valid = true;
            if (params.controller) {
                me.allItems = params.controller.allItems;
                me.selected = params.controller.selected;
            }
            else {
                if (params.items) {
                    if (ko.isObservable(params.items)) {
                        me.allItems = params.items;
                    }
                    else {
                        me.allItems = ko.observableArray(params.items);
                    }
                }
                if (params.selected) {
                    if (ko.isObservable(params.selected)) {
                        me.selected = params.selected;
                    }
                    else {
                        me.selected = ko.observableArray(params.selected);
                    }
                }
            }
            if (!me.allItems) {
                console.error('multiSelectComponent: Parameter "items" or "controller" is required');
                valid = false;
            }
            if (!me.selected) {
                console.error('multiSelectComponent: Parameter "selected" or "controller" is required');
                valid = false;
            }
            if (!valid) {
                return;
            }
            me.errorMessage('');
            let translator = me;
            if (params.translator) {
                translator = params.translator();
            }
            me.removeText(translator.translate('label-remove', 'Remove'));
            if (params.label) {
                me.label(translator.translate(params.label));
            }
            if (params.caption) {
                me.caption(translator.translate(params.caption));
            }
            if (params.sort) {
                me.sortValue = params.sort;
            }
            if (params.id) {
                me.controlId(params.id);
            }
            me.filterAvailable();
            me.activateSubscriptions();
        }
        sortItems(collection) {
            let me = this;
            return collection.sort((itemA, itemB) => {
                let a = itemA[me.sortValue];
                let b = itemB[me.sortValue];
                if (a < b) {
                    return -1;
                }
                if (a > b) {
                    return 1;
                }
                return 0;
            });
        }
        translate(text, defaultText) {
            return text ? text : defaultText;
        }
    }
    Peanut.multiSelectComponent = multiSelectComponent;
})(Peanut || (Peanut = {}));
//# sourceMappingURL=multiSelectComponent.js.map