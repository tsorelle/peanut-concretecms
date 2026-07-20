/// <reference path='../../typings/knockout/index.d.ts' />
/// <reference path='../js/multiSelectObservable.ts' />
namespace Peanut {
    export class incrementalSelectComponent implements IBaseTranslator {

        // todo: parameter support
        // todo: move styling to skin
        // todo: selectedTableComponent
        // todo: generic support for other object types

        // param selected
        selectedItems : KnockoutObservableArray<ILookupItem>;

        // parameter items
        allItems : KnockoutObservableArray<ILookupItem>;
        
        // parameter label
        label = ko.observable(''); // optional

        // parameter id
        controlId = ko.observable('increment-select-field'); // optional


        // assign from observable. parameter error message
        errorMessage = ko.observable(''); // optional
        // assign from parameter sort
        sortValue = 'name';

        // displays availiable items on dropdown
        availableItems = ko.observableArray<ILookupItem>([]);
        caption = ko.observable('Please enter search value and select...');
        searchValue = ko.observable();
        listVisible = ko.observable();

        onSelectEvent : (item: ILookupItem) => void;

        // filterAvailable() when selected list changes
        selectedUpdateSubscription : KnockoutSubscription = null;
        // onSearchChange on user keypress
        keypressSubscription: KnockoutSubscription = null;

        constructor(params: any) {
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
                me.allItems = (<IMultiSelectObservable>params.controller).allItems;
                me.selectedItems = (<IMultiSelectObservable>params.controller).selected;
            }
            else {
                if (ko.isObservable(params.items)) {
                    me.allItems = params.items;                    
                }
                else {
                    if (Array.isArray(params.items)) {
                        me.allItems = ko.observableArray<Peanut.ILookupItem>(params.items);
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
                            me.selectedItems = ko.observableArray<Peanut.ILookupItem>(params.selected);
                        } else {
                            console.error('incrementalSelectComponent: Parameter "selected" must be array or observable array.');
                        }
                    }
                }
            }
           me.availableItems(me.allItems());

           let translator : IBaseTranslator = me;
           if (params.translator) {
               translator = <ViewModelBase>params.translator();
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


        sortItems(collection: any[]) {
            let me = this;
            return collection.sort((itemA,itemB) => {
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

        onSearchEvent = (_data: any) => {
            // fires
            this.clearSearch();
        }

        onSearchChange = (value: string) => {
            let me = this;
            if (value) {
                me.availableItems([]);
                value = value.toLowerCase();
                let newlist = me.allItems().filter((item: Peanut.ILookupItem) => {
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


        onSelected = (item: ILookupItem) => {
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

        showList = () => {
            let current = this.listVisible();
            if (current) {
                this.clearSearch();
            }
            this.listVisible(!current);

            //todo:implemtn showList
        };

        clearSearch = () => {
            let me=this;
            me.suspendSubscriptions();
            me.listVisible(false);
            me.searchValue('');
            me.filterAvailable();
            me.activateSubscriptions();
        };

        activateSubscriptions = () => {
            if (this.selectedItems) {
                this.selectedUpdateSubscription = this.selectedItems.subscribe(this.filterAvailable)
            }
            this.keypressSubscription = this.searchValue.subscribe(this.onSearchChange);

        };

        suspendSubscriptions = () => {
            if (this.selectedUpdateSubscription !== null) {
                this.selectedUpdateSubscription.dispose();
                this.selectedUpdateSubscription = null;
            }
            if (this.keypressSubscription !== null) {
                this.keypressSubscription.dispose();
                this.keypressSubscription = null;
            }
        };

        filterAvailable = () => {
            let me = this;
            if (me.selectedItems) {
                let selected = me.selectedItems();
                let items = me.allItems();
                // me.availableItems();
                let result = items.filter((item: Peanut.ILookupItem) => {
                    let existing = selected.find((selectItem: Peanut.ILookupItem) => {
                        return selectItem.id == item.id;
                    });
                    return (!existing);
                });
                me.availableItems(
                    me.sortItems(result)
                );
            }
        };

        moveSelectedItem = (item: Peanut.ILookupItem,
                            source: KnockoutObservableArray<Peanut.ILookupItem>,
                            target: KnockoutObservableArray<Peanut.ILookupItem>) => {
            let me = this;
            me.suspendSubscriptions();
            let sourceItems = source();
            let targetItems = target();
            let remaining =  sourceItems.filter((sourceItem: Peanut.ILookupItem) => {
                return sourceItem.id != item.id;
            });
            remaining = me.sortItems(remaining);
            target.push(item);
            targetItems = me.sortItems(targetItems)
            source(remaining);
            target(targetItems);
            me.activateSubscriptions();
        };

        // dummy translator
        translate(text: string, defaultText?:string) {
            return text ? text: defaultText;
        }

    }
}