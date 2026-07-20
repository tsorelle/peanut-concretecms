/// <reference path='../../typings/knockout/index.d.ts' />
/// <reference path='../js/selectListObservable.ts' />

namespace Peanut {
    export class lookupSelectComponent  implements IBaseTranslator {
        // observables
        options: KnockoutObservableArray<ILookupItem>; // param items or controller
        selectedItem: KnockoutObservable<ILookupItem>; // param selected or controller
        caption : KnockoutObservable<string>; // param default - optional
        label  : KnockoutObservable<string>; // param label - optional
        labelTitle  : KnockoutObservable<string>; // param label - optional
        title  : KnockoutObservable<string>; // param title - optiona
        controlId = ko.observable<string>('lookup-select'); // param id
        autocomplete = ko.observable(false);
        inline = ko.observable(false);

        constructor(params: any) {
            let me = this;
            let valid = true;

            if (!params) {
                console.error('lookupSelectComponent: Params not defined in lookupSelectComponent');
                return;
            }

            // required parameters
            if (params.controller) {
                me.options = (<selectListObservable>params.controller).options;
                if (me.options == undefined) {
                    console.error('Options are not defined in controler.');
                }
                me.selectedItem = (<selectListObservable>params.controller).selected;
            } else {
                if (params.items) {
                    if (ko.isObservable(params.items)) {
                        me.options = params.items;
                    } else if (Array.isArray(params.items)) {
                        me.options = ko.observableArray<ILookupItem>(params.item);
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

            // optional parameters

            let translator : IBaseTranslator = me;
            if (params.translator) {
                translator = <ViewModelBase>params.translator();
            }

            // me.caption 	 = ko.observable('test cap'); // param default - optional
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

            if(params.display) {
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

                // title should go on the label, if present; otherwise on the select
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

        // dummy translator
        translate(text: string, defaultText?:string) {
            return text ? text: defaultText;
        }



    }
}
