/// <reference path='../../typings/knockout/index.d.ts' />
/// <reference path='../js/multiSelectObservable.ts' />
namespace Peanut {
    export class multiSelectComponent  implements IBaseTranslator {
        // note - lodash dependencies removed 1/22/2022.  Old version in backup directory
        errorMessage = ko.observable('');

        allItems : KnockoutObservableArray<ILookupItem>;
        selected : KnockoutObservableArray<ILookupItem>;
        options = ko.observableArray<ILookupItem>([]);

        selectedItem = ko.observable<ILookupItem>(null);
        label = ko.observable('');
        caption = ko.observable('Please select...');
        removeText = ko.observable('Remove');
        itemSubscription : any = null;
        selectionsSubscription : any = null;

        controlId = ko.observable('multi-select-field');
        lo : any;
        sortValue = 'name';

        constructor(params: any) {
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
                        me.allItems = ko.observableArray<ILookupItem>(params.items)
                    }
                }
                if (params.selected) {
                    if (ko.isObservable(params.selected)) {
                        me.selected = params.selected;
                    }
                    else {
                        me.selected = ko.observableArray<ILookupItem>(params.selected)
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

            let translator : IBaseTranslator = me;
            if (params.translator) {
                translator = <ViewModelBase>params.translator();
            }
            me.removeText(translator.translate('label-remove','Remove'));
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

        filterAvailable = () => {
            let me = this;
            let test = me.options();
            let selected = me.selected();
            let items = me.allItems();
            // no lodash version
            let result = items.filter((item: Peanut.ILookupItem) => {
                let existing = selected.find( (selectItem: Peanut.ILookupItem) => {
                    return selectItem.id == item.id;
                });
                return (!existing);
            });
            me.options(result);
        };

        addItem = (item: Peanut.ILookupItem) => {
            if (item) {
                this.moveSelectedItem(item, this.options, this.selected);
            }
        };

        removeItem = (item: Peanut.ILookupItem) => {
            this.moveSelectedItem(item,this.selected,this.options);

        };


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

        suspendSubscriptions = () => {
            if (this.itemSubscription !== null) {
                this.itemSubscription.dispose();
                this.itemSubscription = null;
            }
            if (this.selectionsSubscription !== null) {
                this.selectionsSubscription.dispose();
                this.selectionsSubscription = null;
            }
        };

        activateSubscriptions = () => {
            this.selectedItem(null);
            this.itemSubscription = this.selectedItem.subscribe(this.addItem);
            this.selectionsSubscription = this.selected.subscribe(this.filterAvailable)
        };

        // dummy translator
        translate(text: string, defaultText?:string) {
            return text ? text: defaultText;
        }
    }
}