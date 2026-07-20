///<reference path="../../typings/jquery/jquery.d.ts"/>
namespace Peanut {
    export interface IUpdateLookupItemResponse extends ILookupItem {
        error?: string;
    }

    export interface ILookupItemInitRequest  {
        table: string,
        activeOnly?: any;
    }

    export interface ILookupItemUpdateRequest  {
        item: ILookupItem;
        table: string,
    }


    export interface ILookupItemInitResponse {
        items: ILookupItem[];
        canEdit: any;
    }

    export interface ILookupItemUpdateResponse {
        updateResult: ILookupItem[] | string;
    }

    export class lookupEditComponent  implements IBaseTranslator {
        id = ko.observable();
        name = ko.observable('');
        code = ko.observable('');
        description = ko.observable('');
        active = ko.observable(true);

        updateEvent : (item: ILookupItem) => void;
        items = ko.observableArray<Peanut.ILookupItem>();
        itemName = ko.observable('item');
        itemNameUC = ko.observable('');
        newItemTitle = ko.observable('');
        itemsPlural = ko.observable('');
        allitems: KnockoutObservableArray<ILookupItem>;
        userCanEdit : KnockoutObservable<boolean>;

        activeOnly = ko.observable(true);
        nameError = ko.observable(false);
        codeError = ko.observable(false);
        showActive = ko.observable(false);
        viewState = ko.observable('view');

        newItem = ko.observable(false);

        dataChange : KnockoutSubscription = null;
        filterChange : KnockoutSubscription = null;

        constructor(params: any) {
            let me = this;
            let valid = true;

            if (!params) {
                console.error('lookupEditComponent: Params not defined in lookupEditComponent');
                return;
            }

            // required parameters
            if (params.items) {
                if (ko.isObservable(params.items)) {
                    me.allitems = params.items;
                    me.filterActive();
                } else {
                    console.error('lookupEditComponent: items or invalid in lookupEditComponent');
                    return;
                }
            }
            if (params.onUpdate) {
                me.updateEvent = params.onUpdate;
            }
            else {
                console.error('lookupEditComponent: onUpdate method is required');
            }

            // todo: add translation later
            let translator : IBaseTranslator = me;
            if (params.translator) {
                translator = <ViewModelBase>params.translator();
            }

            // optional parameters
            let itemName = 'item';
            if (params.name) {
                itemName = params.name;
            }
            let itemNameUC = me.capitalize(itemName);
            me.itemName(itemName);
            me.itemNameUC(itemNameUC);
            if (params.plural) {
                me.itemsPlural(params.plural);
            }
            else {
                me.itemsPlural(itemName + 's');
            }

            me.newItemTitle('Create a new ' + me.itemName);

            if (params.canEdit) {
                if (ko.isObservable(params.canEdit)) {
                    me.userCanEdit = params.canEdit;
                }
                else {
                    me.userCanEdit = ko.observable(params.canEdit == 'yes');
                }
            }
            else {
                me.userCanEdit = ko.observable(false);
            }

            PeanutLoader.loadUiHelper(() => {
                this.subscribe();
            });
        }

        subscribe = () => {
           this.dataChange = this.allitems.subscribe(this.filterActive);
           this.filterChange = this.activeOnly.subscribe(this.filterActive);
        }

        unSubscribe = () => {
            this.dataChange.dispose();
            this.filterChange.dispose();
        }

        assign = (item: ILookupItem) => {
            this.newItem(false);
            this.id(item.id);
            this.name(item.name);
            this.code(item.code);
            this.description(item.description);
            this.active(item.active == 1);
            this.clearErrors();
        }

        createItem = () => {
            this.clear();
            this.newItem(true);
            this.showForm('edit');
        }

        showForm = (state = 'view') => {
            this.viewState(state);
            // jQuery('#item-form').modal('show');
            Peanut.ui.helper.showModal('#item-form')
        }
        
        clear = () => {
            this.id(0);
            this.name('');
            this.code('');
            this.description('');
            this.active(true);
            this.clearErrors();
        }

        clearErrors = () => {
            this.nameError(false);
            this.codeError(false);
        }

        showDetails = (item: ILookupItem) => {
            this.assign(item);
            this.showForm();
        }
        
        validate = () => {
            let request = <ILookupItem> {
                id : this.id(),
                name : this.name().trim(),
                code : this.code().trim(),
                description: this.description().trim(),
                active: this.active() ? 1 : 0
            }
            if (request.active) {
                let valid = true;
                if (!request.name) {
                    this.nameError(true)
                    valid = false;
                }
                if (!request.code) {
                    this.codeError(true);
                    valid = false;
                }

                if (request.id == 0) {
                    let existing = this.allitems().find((codeitem: ILookupItem) => {
                        return codeitem.code == request.code;
                    })
                    if (existing) {
                        alert('Duplicate code try another.')
                        this.codeError(true);
                        valid = false;
                    }
                }

                if (!valid) {
                    return false;
                }
            }
            return request;
        }

        cancelEdit = () => {
            // jQuery('#item-form').modal('hide');
            Peanut.ui.helper.hideModal('#item-form')
        }

        updateItem = () => {
            let item = this.validate();
            if (item !== false) {
                // jQuery('#item-form').modal('hide');
                Peanut.ui.helper.hideModal('#item-form')
                this.updateEvent(item);
            }
        }
        editItem = (item: ILookupItem) => {
            this.viewState('edit');
        }

        filterActive = ()=> {
            let all = this.allitems();
            if (this.activeOnly()) {
                if (all) {
                    let filtered = (all.filter((i: ILookupItem) => {
                        return i.active == 1;
                    }))
                    this.items(filtered);
                } else {
                    this.items([]);
                }
            }
            else {
                this.items(all);
            }
        }

        capitalize(s: string) {
            if (typeof s !== 'string') return ''
            return s.charAt(0).toUpperCase() + s.slice(1)
        }

        // dummy translator
        translate(text: string, defaultText?:string) {
            return text ? text: defaultText;
        }
    }
}