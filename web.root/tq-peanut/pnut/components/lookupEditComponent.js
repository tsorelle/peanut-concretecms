var Peanut;
(function (Peanut) {
    class lookupEditComponent {
        constructor(params) {
            this.id = ko.observable();
            this.name = ko.observable('');
            this.code = ko.observable('');
            this.description = ko.observable('');
            this.active = ko.observable(true);
            this.items = ko.observableArray();
            this.itemName = ko.observable('item');
            this.itemNameUC = ko.observable('');
            this.newItemTitle = ko.observable('');
            this.itemsPlural = ko.observable('');
            this.activeOnly = ko.observable(true);
            this.nameError = ko.observable(false);
            this.codeError = ko.observable(false);
            this.showActive = ko.observable(false);
            this.viewState = ko.observable('view');
            this.newItem = ko.observable(false);
            this.dataChange = null;
            this.filterChange = null;
            this.subscribe = () => {
                this.dataChange = this.allitems.subscribe(this.filterActive);
                this.filterChange = this.activeOnly.subscribe(this.filterActive);
            };
            this.unSubscribe = () => {
                this.dataChange.dispose();
                this.filterChange.dispose();
            };
            this.assign = (item) => {
                this.newItem(false);
                this.id(item.id);
                this.name(item.name);
                this.code(item.code);
                this.description(item.description);
                this.active(item.active == 1);
                this.clearErrors();
            };
            this.createItem = () => {
                this.clear();
                this.newItem(true);
                this.showForm('edit');
            };
            this.showForm = (state = 'view') => {
                this.viewState(state);
                Peanut.ui.helper.showModal('#item-form');
            };
            this.clear = () => {
                this.id(0);
                this.name('');
                this.code('');
                this.description('');
                this.active(true);
                this.clearErrors();
            };
            this.clearErrors = () => {
                this.nameError(false);
                this.codeError(false);
            };
            this.showDetails = (item) => {
                this.assign(item);
                this.showForm();
            };
            this.validate = () => {
                let request = {
                    id: this.id(),
                    name: this.name().trim(),
                    code: this.code().trim(),
                    description: this.description().trim(),
                    active: this.active() ? 1 : 0
                };
                if (request.active) {
                    let valid = true;
                    if (!request.name) {
                        this.nameError(true);
                        valid = false;
                    }
                    if (!request.code) {
                        this.codeError(true);
                        valid = false;
                    }
                    if (request.id == 0) {
                        let existing = this.allitems().find((codeitem) => {
                            return codeitem.code == request.code;
                        });
                        if (existing) {
                            alert('Duplicate code try another.');
                            this.codeError(true);
                            valid = false;
                        }
                    }
                    if (!valid) {
                        return false;
                    }
                }
                return request;
            };
            this.cancelEdit = () => {
                Peanut.ui.helper.hideModal('#item-form');
            };
            this.updateItem = () => {
                let item = this.validate();
                if (item !== false) {
                    Peanut.ui.helper.hideModal('#item-form');
                    this.updateEvent(item);
                }
            };
            this.editItem = (item) => {
                this.viewState('edit');
            };
            this.filterActive = () => {
                let all = this.allitems();
                if (this.activeOnly()) {
                    if (all) {
                        let filtered = (all.filter((i) => {
                            return i.active == 1;
                        }));
                        this.items(filtered);
                    }
                    else {
                        this.items([]);
                    }
                }
                else {
                    this.items(all);
                }
            };
            let me = this;
            let valid = true;
            if (!params) {
                console.error('lookupEditComponent: Params not defined in lookupEditComponent');
                return;
            }
            if (params.items) {
                if (ko.isObservable(params.items)) {
                    me.allitems = params.items;
                    me.filterActive();
                }
                else {
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
            let translator = me;
            if (params.translator) {
                translator = params.translator();
            }
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
            Peanut.PeanutLoader.loadUiHelper(() => {
                this.subscribe();
            });
        }
        capitalize(s) {
            if (typeof s !== 'string')
                return '';
            return s.charAt(0).toUpperCase() + s.slice(1);
        }
        translate(text, defaultText) {
            return text ? text : defaultText;
        }
    }
    Peanut.lookupEditComponent = lookupEditComponent;
})(Peanut || (Peanut = {}));
//# sourceMappingURL=lookupEditComponent.js.map