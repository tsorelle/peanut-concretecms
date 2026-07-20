/// <reference path='../../typings/knockout/index.d.ts' />
namespace Peanut {
    export class selectedListComponent  implements IBaseTranslator {
        // observables
        selectedItems : KnockoutObservableArray<ILookupItem>;
        removeText = ko.observable('Remove');
        readonly = ko.observable(false);
        emptymessage = ko.observable('');
        label = ko.observable('');

        // variables.


        // include constructor if any params used
        constructor(params: any) {
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
                if (Array.isArray(params.source))
                {
                    me.selectedItems = ko.observableArray<ILookupItem>(params.source);
                }
                else
                {
                    me.selectedItems = params.source.selected; // assume it is a controller
                }
            }

            let test = this.selectedItems();

            if (params.readonly) {
                me.readonly(true);
            }

            let translator : IBaseTranslator = me;
            if (params.translator) {
                translator = <ViewModelBase>params.translator();
            }


            if (params.emptymessage) {
                me.emptymessage(translator.translate(params.emptymessage));
            }

            if (params.label) {
                me.label(translator.translate(params.label));
            }
        }

        removeItem = (item: Peanut.ILookupItem) => {
            let me = this;
            let sourceItems = me.selectedItems();
            let remaining =  sourceItems.filter((sourceItem: Peanut.ILookupItem) => {
                return sourceItem.id != item.id;
            });
            me.selectedItems(remaining);
        };

        // dummy translator
        translate(text: string, defaultText?:string) {
            return text ? text: defaultText;
        }

    }
}