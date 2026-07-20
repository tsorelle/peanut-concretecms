var PeanutContacts;
(function (PeanutContacts) {
    class accountFormObservable {
        constructor(roles) {
            this.username = ko.observable('');
            this.password = ko.observable('');
            this.errorMessage = ko.observable('');
            this.clear = () => {
                this.username('');
                this.password('');
                this.rolesController.setValues([]);
            };
            this.getRequest = () => {
                this.errorMessage('');
                let result = {
                    username: this.username().trim(),
                    password: this.password().trim(),
                    roles: this.rolesController.getValues(),
                    contactId: null
                };
                if (!result.username) {
                    this.errorMessage('Username is required');
                    return false;
                }
                if (result.password.length < 5) {
                    this.errorMessage('Password must be 5 or more characters long');
                    return false;
                }
                return result;
            };
            this.rolesController = new Peanut.multiSelectObservable(roles);
        }
    }
    class contactFormObservable {
        constructor(emailLists) {
            this.id = ko.observable(0);
            this.fullname = ko.observable('');
            this.email = ko.observable('');
            this.errorMessage = ko.observable('');
            this.accountId = ko.observable(0);
            this.notes = ko.observable('');
            this.active = ko.observable(true);
            this.sortkey = ko.observable('');
            this.phone = ko.observable('');
            this.assign = (contact) => {
                var _a, _b, _c, _d, _e;
                this.id(contact.id);
                let accountId = parseInt(contact.accountId);
                if (isNaN(accountId)) {
                    accountId = 0;
                }
                this.accountId(isNaN(accountId) ? 0 : accountId);
                this.fullname((_a = contact.fullname) !== null && _a !== void 0 ? _a : '');
                this.email((_b = contact.email) !== null && _b !== void 0 ? _b : '');
                this.errorMessage('');
                this.active(parseInt(contact.active) === 1);
                this.notes((_c = contact.notes) !== null && _c !== void 0 ? _c : '');
                this.subscriptionsController.setValues(contact.subscriptions);
                this.sortkey((_d = contact.sortkey) !== null && _d !== void 0 ? _d : '');
                this.phone((_e = contact.phone) !== null && _e !== void 0 ? _e : '');
            };
            this.clear = () => {
                this.accountId(0);
                this.id(0);
                this.fullname('');
                this.email('');
                this.phone('');
                this.errorMessage('');
                this.notes('');
                this.sortkey('');
                this.active(true);
                this.subscriptionsController.setValues([]);
            };
            this.getContact = () => {
                let contact = {
                    id: this.id(),
                    fullname: this.fullname().trim(),
                    email: this.email().trim(),
                    phone: this.phone().trim(),
                    sortkey: this.sortkey().trim().toLowerCase(),
                    notes: this.notes(),
                    accountId: this.accountId(),
                    active: this.active() ? 1 : 0,
                    subscriptions: this.subscriptionsController.getValues()
                };
                this.errorMessage('');
                if (!contact.fullname) {
                    this.errorMessage('Full name is required.');
                    return false;
                }
                if (!Peanut.Helper.ValidateEmail(contact.email)) {
                    this.errorMessage('Invalid email address');
                    return false;
                }
                return {
                    contact: contact,
                    subscriptions: this.subscriptionsController.getValues()
                };
            };
            this.subscriptionsController = new Peanut.multiSelectObservable(emailLists);
        }
    }
    class ContactsViewModel extends Peanut.ViewModelBase {
        constructor() {
            super(...arguments);
            this.contacts = [];
            this.contactList = ko.observableArray([]);
            this.pageview = ko.observable('list');
            this.currentPage = ko.observable(1);
            this.maxPages = ko.observable(10);
            this.emailLists = ko.observableArray();
            this.listingTypes = ko.observableArray();
            this.itemsPerPage = 10;
            this.filterValue = ko.observable('');
            this.changePage = (move = 0) => {
                let current = this.currentPage() + move;
                let start = this.itemsPerPage * (current - 1);
                let end = start + this.itemsPerPage;
                let pageSet = this.contacts.slice(start, end);
                if (pageSet.length > 0) {
                    this.contactList(pageSet);
                    this.currentPage(current);
                    this.selectContact(pageSet[0]);
                }
                this.pageview('view');
                Peanut.Helper.ScrollToTop();
            };
            this.newContact = () => {
                this.contactForm.clear();
                this.displayPageView('edit');
            };
            this.editContact = () => {
                this.displayPageView('edit');
            };
            this.newAccount = () => {
                this.accountForm.clear();
                this.displayPageView('account');
            };
            this.createAccount = () => {
                let me = this;
                let request = me.accountForm.getRequest();
                if (!request) {
                    return;
                }
                request.contactId = me.selectedContact.id;
                me.pageview('wait');
                me.services.executeService('Peanut.contacts::CreateContactAccount', request, function (serviceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        me.selectedContact.accountId = serviceResponse.Value;
                        me.pageview('view');
                    }
                }).fail(function () {
                    me.pageview('error');
                });
            };
            this.saveChanges = () => {
                let me = this;
                let request = this.contactForm.getContact();
                if (!request) {
                    return;
                }
                me.pageview('wait');
                me.services.executeService('Peanut.contacts::UpdateContact', request, function (serviceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let response = serviceResponse.Value;
                        me.filterValue('');
                        me.setContactList(response);
                    }
                }).fail(function () {
                    me.pageview('error');
                    me.application.hideWaiter();
                });
            };
            this.cancelChanges = () => {
                if (this.selectedContact) {
                    this.contactForm.assign(this.selectedContact);
                    this.displayPageView('view');
                }
                else {
                    this.contactForm.clear();
                    this.pageview('blank');
                }
            };
            this.displayPageView = (viewName) => {
                this.pageview(viewName);
                let div = document.getElementById(viewName + '-page');
                div.scrollIntoView({ behavior: "smooth" });
            };
            this.scrollToList = () => {
                let div = document.getElementById('contact-list');
                div.scrollIntoView(true);
            };
            this.clearFilter = () => {
                let previous = this.filterValue().trim();
                this.filterValue('');
                if (previous !== '') {
                    this.doSearch();
                }
            };
            this.getContactDetails = (contact) => {
                let me = this;
                me.services.executeService('Peanut.contacts::GetContactDetails', contact.id, function (serviceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let response = serviceResponse.Value;
                        contact.subscriptions = response.subscriptions;
                        me.showContact(contact);
                    }
                }).fail(function () {
                    me.pageview('blank');
                    me.application.hideWaiter();
                });
            };
            this.showContact = (contact) => {
                this.selectedContact = contact;
                this.contactForm.assign(contact);
                this.displayPageView('view');
            };
            this.selectContact = (contact) => {
                if (!!contact.subscriptions) {
                    this.showContact(contact);
                }
                else {
                    this.getContactDetails(contact);
                }
            };
            this.doSearch = () => {
                let me = this;
                me.pageview('wait');
                let request = {
                    searchtype: 'fullname',
                    searchvalue: me.filterValue()
                };
                me.services.executeService('Peanut.contacts::GetContactList', request, function (serviceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let response = serviceResponse.Value;
                        me.setContactList(response.contacts);
                    }
                }).fail(function () {
                    me.pageview('blank');
                    me.application.hideWaiter();
                });
            };
        }
        init(successFunction) {
            let me = this;
            Peanut.logger.write('Contacts Init');
            me.application.registerComponents('@pnut/pager,@pnut/selected-list,@pnut/multi-select,@pnut/change-password', () => {
                me.application.loadResources([
                    '@pnut/multiSelectObservable',
                    '@pnut/ViewModelHelpers'
                ], () => {
                    me.services.executeService('Peanut.contacts::InitContactsPage', null, function (serviceResponse) {
                        if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                            let response = serviceResponse.Value;
                            me.contactForm = new contactFormObservable(response.emailLists);
                            me.accountForm = new accountFormObservable(response.roles);
                            me.setContactList(response.contacts);
                            me.bindDefaultSection();
                            successFunction();
                        }
                    }).fail(function () {
                        me.pageview('error');
                    });
                });
            });
        }
        setContactList(contacts) {
            this.contacts = contacts;
            let count = contacts.length;
            let max = Math.ceil(count / this.itemsPerPage);
            this.maxPages(max);
            this.currentPage(1);
            this.changePage();
            this.scrollToList();
        }
    }
    PeanutContacts.ContactsViewModel = ContactsViewModel;
})(PeanutContacts || (PeanutContacts = {}));
//# sourceMappingURL=ContactsViewModel.js.map