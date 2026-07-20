/// <reference path='../../pnut/core/Peanut.d.ts' />
/// <reference path='../../typings/knockout/index.d.ts' />
// web.root/packages/knockout_view/pnut/js/multiSelectObservable.ts
namespace Peanut {

    export interface IMultiSelectObservable {
        allItems: KnockoutObservableArray<ILookupItem>;
        selected: KnockoutObservableArray<ILookupItem>
    }

    export class multiSelectObservable {
        allItems = ko.observableArray<ILookupItem>([]);
        selected = ko.observableArray<ILookupItem>([]);

        // todo: need sort routine

        public constructor(optionsList: ILookupItem[] = [],
                           values: any[] = null) {
            let me = this;
            me.allItems(optionsList);
            me.setValues(values)
        }

        public setItems(items: ILookupItem[]) {
            let me = this;
            me.allItems(items);
            me.selected([]);
        }

        public setValues(values: any[]) {
            let me = this;
            if (values && values.length) {
                let all = me.allItems();
                let selected = all.filter((item: Peanut.ILookupItem) => {
                    return values.indexOf(item.id) !== -1;
                });
                me.selected(selected);
            }
            else {
                me.selected([])
            }
        }
        
        public getValues() {
            let me = this;
            let selection = me.selected();
            let result = [];
            for(let i=0;i<selection.length; i++) {
                result.push(selection[i].id)
            }
            return result;
        }
    }
}


