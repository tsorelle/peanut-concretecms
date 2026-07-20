var Peanut;
(function (Peanut) {
    class incrementalSelectComponent {
        constructor(params) {
            this.label = ko.observable('');
            this.controlId = ko.observable('increment-select-field');
            this.errorMessage = ko.observable('');
            this.sortValue = 'name';
            this.availableItems = ko.observableArray([]);
            this.caption = ko.observable('Please enter search value and select...');
            this.searchValue = ko.observable();
            this.listVisible = ko.observable();
            this.selectedUpdateSubscription = null;
            this.keypressSubscription = null;
            this.onSearchEvent = (_data) => {
                this.clearSearch();
            };
            this.onSearchChange = (value) => {
                let me = this;
                if (value) {
                    me.availableItems([]);
                    value = value.toLowerCase();
                    let newlist = me.allItems().filter((item) => {
                        return (item.name.toLowerCase().indexOf(value) >= 0);
                    });
                    me.availableItems(newlist);
                    me.listVisible(newlist.length > 0);
                }
                else {
                    me.listVisible(false);
                    me.availableItems(me.allItems());
                }
            };
            this.onSelected = (item) => {
                let me = this;
                if (item) {
                    if (me.onSelectEvent) {
                        me.onSelectEvent(item);
                    }
                    else {
                        me.moveSelectedItem(item, this.availableItems, this.selectedItems);
                    }
                }
                me.clearSearch();
            };
            this.showList = () => {
                let current = this.listVisible();
                if (current) {
                    this.clearSearch();
                }
                this.listVisible(!current);
            };
            this.clearSearch = () => {
                let me = this;
                me.suspendSubscriptions();
                me.listVisible(false);
                me.searchValue('');
                me.filterAvailable();
                me.activateSubscriptions();
            };
            this.activateSubscriptions = () => {
                if (this.selectedItems) {
                    this.selectedUpdateSubscription = this.selectedItems.subscribe(this.filterAvailable);
                }
                this.keypressSubscription = this.searchValue.subscribe(this.onSearchChange);
            };
            this.suspendSubscriptions = () => {
                if (this.selectedUpdateSubscription !== null) {
                    this.selectedUpdateSubscription.dispose();
                    this.selectedUpdateSubscription = null;
                }
                if (this.keypressSubscription !== null) {
                    this.keypressSubscription.dispose();
                    this.keypressSubscription = null;
                }
            };
            this.filterAvailable = () => {
                let me = this;
                if (me.selectedItems) {
                    let selected = me.selectedItems();
                    let items = me.allItems();
                    let result = items.filter((item) => {
                        let existing = selected.find((selectItem) => {
                            return selectItem.id == item.id;
                        });
                        return (!existing);
                    });
                    me.availableItems(me.sortItems(result));
                }
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
            let me = this;
            me.errorMessage('Cannot load incremental-select');
            if (!params) {
                console.error('incrementalSelectComponentSelectComponent: Params not defined in multi-select');
                return;
            }
            let valid = true;
            if (!params.controller) {
                if (!params.items) {
                    console.error('incrementalSelectComponent: Parameter "items" is required');
                    valid = false;
                }
                if (!(params.selected)) {
                    console.error('incrementalSelectComponent: Parameter "selected" is required');
                    valid = false;
                }
            }
            if (!valid) {
                return;
            }
            me.errorMessage('');
            if (params.controller) {
                me.allItems = params.controller.allItems;
                me.selectedItems = params.controller.selected;
            }
            else {
                if (ko.isObservable(params.items)) {
                    me.allItems = params.items;
                }
                else {
                    if (Array.isArray(params.items)) {
                        me.allItems = ko.observableArray(params.items);
                    }
                    else {
                        console.error('incrementalSelectComponent: Parameter "items" must be array or observable array.');
                    }
                }
                if (ko.isObservable(params.selected)) {
                    me.selectedItems = params.selected;
                }
                else {
                    if (typeof params.selected === 'function') {
                        me.onSelectEvent = params.selected;
                    }
                    else {
                        if (Array.isArray(params.selected)) {
                            me.selectedItems = ko.observableArray(params.selected);
                        }
                        else {
                            console.error('incrementalSelectComponent: Parameter "selected" must be array or observable array.');
                        }
                    }
                }
            }
            me.availableItems(me.allItems());
            let translator = me;
            if (params.translator) {
                translator = params.translator();
            }
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
            me.searchValue('');
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
    Peanut.incrementalSelectComponent = incrementalSelectComponent;
})(Peanut || (Peanut = {}));
//# sourceMappingURL=incrementalSelectComponent.js.map