/// <reference path="../../../../pnut/core/ViewModelBase.ts" />
/// <reference path='../../../../typings/knockout/index.d.ts' />
/// <reference path='../../../../pnut/core/Peanut.d.ts' />

namespace PeanutUsers {

    interface IContactItem {
        id : any;
        fullname : string;
        email : string;
        phone : string;
        listingtypeId : any;
        sortkey : string;
        notes : string;
        uid : any;
        accountId : any;
        active : any;
        subscriptions : number[] | null;
        roles: number[] | null;
    }

    interface IContactInitResponse {
        contacts: IContactItem[];
        emailLists: Peanut.ILookupItem[];
        listingTypes: Peanut.ILookupItem[];
        roles: Peanut.ILookupItem[];
    }

    interface IGetContactDetailsReponse {
        subscriptions: number[];
    }

    class accountFormObservable {
        constructor(roles: Peanut.ILookupItem[]) {
            this.rolesController = new Peanut.multiSelectObservable(roles);
        }
        rolesController : Peanut.multiSelectObservable;
        username = ko.observable('');
        password = ko.observable('');
        errorMessage = ko.observable('');

        clear = () => {
            this.username('');
            this.password('');
            this.rolesController.setValues([]);
        }

        getRequest = () => {
            this.errorMessage('');
            let result = {
                username: this.username().trim(),
                password: this.password().trim(),
                roles : this.rolesController.getValues(),
                contactId: null
            }
            if (!result.username) {
                this.errorMessage('Username is required');
                return false;
            }
            if (result.password.length < 5) {
                this.errorMessage('Password must be 5 or more characters long');
                return false;
            }

            return result;
        }
    }

    class contactFormObservable {
        // todo: suppoert for listing type
        constructor(emailLists: Peanut.ILookupItem[]) {
            this.subscriptionsController = new Peanut.multiSelectObservable(emailLists);
        }

        id = ko.observable(0);
        fullname= ko.observable('');
        email = ko.observable('');
        errorMessage = ko.observable('')
        accountId = ko.observable(0);
        notes = ko.observable('');
        active = ko.observable(true);
        sortkey = ko.observable('');
        phone = ko.observable('');

        subscriptionsController : Peanut.multiSelectObservable;

        assign = (contact: IContactItem) => {
            this.id(contact.id);
            let accountId = parseInt(contact.accountId);
            if (isNaN(accountId)) {
                accountId = 0;
            }
            this.accountId(isNaN(accountId) ? 0 : accountId)
            this.fullname(contact.fullname ?? '');
            this.email(contact.email ?? '');
            this.errorMessage('');
            this.active(parseInt(contact.active) === 1);
            this.notes(contact.notes ?? '');
            this.subscriptionsController.setValues(contact.subscriptions);
            this.sortkey(contact.sortkey ?? '');
            this.phone(contact.phone ?? '');
        }

        clear = () => {
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
        }

        getContact = () => {
            let contact = {
                id: this.id(),
                fullname: this.fullname().trim(),
                email : this.email().trim(),
                phone: this.phone().trim(),
                // include listing type id when supported
                sortkey: this.sortkey().trim().toLowerCase(),
                notes: this.notes(),
                accountId: this.accountId(),
                active: this.active() ? 1 : 0,
                subscriptions: this.subscriptionsController.getValues()
            }
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
            }
        }
    }



    export class ContactsViewModel extends Peanut.ViewModelBase {
        contacts : IContactItem[] = [];
        contactForm : contactFormObservable;
        contactList = ko.observableArray<IContactItem>([]);
        selectedContact : IContactItem;
        pageview = ko.observable('list');
        currentPage = ko.observable(1);
        maxPages = ko.observable(10);
        emailLists = ko.observableArray<Peanut.ILookupItem>();
        listingTypes = ko.observableArray<Peanut.ILookupItem>();
        itemsPerPage = 10;
        filterValue = ko.observable('');

        accountForm : accountFormObservable;

        init(successFunction?: () => void) {
            let me = this;
            Peanut.logger.write('Contacts Init');
            me.application.registerComponents(
                '@pnut/pager,@pnut/selected-list,@pnut/multi-select,@pnut/change-password', () => {
                me.application.loadResources([
                    '@pnut/multiSelectObservable',
                    '@pnut/ViewModelHelpers'
                ], () => {
                    me.services.executeService('Peanut.contacts::InitContactsPage',null,
                    function(serviceResponse: Peanut.IServiceResponse) {
                        if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                            let response = <IContactInitResponse>serviceResponse.Value;
                            me.contactForm = new contactFormObservable(response.emailLists);
                            me.accountForm = new accountFormObservable(response.roles);
                            me.setContactList(response.contacts);
                            me.bindDefaultSection();
                            successFunction();
                        }
                    }
                    ).fail(function () {
                        me.pageview('error');
                    });
                });
            });
        }

        setContactList(contacts : IContactItem[]) {
            this.contacts = contacts;
            let count = contacts.length;
            let max =  Math.ceil(count / this.itemsPerPage);
            this.maxPages(max);
            this.currentPage(1);
            this.changePage();
            this.scrollToList();
        }

        changePage = (move: number = 0) => {
            let current = this.currentPage() + move;
            let start = this.itemsPerPage * (current - 1)
            let end = start + this.itemsPerPage;
            let pageSet = this.contacts.slice(start,end);
            if (pageSet.length > 0) {
                this.contactList(pageSet);
                this.currentPage(current);
                this.selectContact(pageSet[0]);
            }
            this.pageview('view');
            Peanut.Helper.ScrollToTop();
        }

        newContact = () => {
            this.contactForm.clear();
            this.displayPageView('edit');
        }

        editContact = () => {
            this.displayPageView('edit');
        }

        newAccount = () => {
            this.accountForm.clear();
            this.displayPageView('account')
        }
        createAccount = () => {
            let me = this;
            let request = me.accountForm.getRequest();
            if (!request) {
                return;
            }
            request.contactId = me.selectedContact.id;
            me.pageview('wait');
            me.services.executeService('Peanut.contacts::CreateContactAccount',request,
                function(serviceResponse: Peanut.IServiceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        me.selectedContact.accountId = serviceResponse.Value;
                        me.pageview('view');
                    }
                }
            ).fail(function () {
                // let trace = me.services.getErrorInformation();
                me.pageview('error');
            });

        }

        saveChanges  = () => {
            let me = this;
            let request = this.contactForm.getContact();
            if (!request) {
                return;
            }
            me.pageview('wait');
            me.services.executeService('Peanut.contacts::UpdateContact',request,
                function(serviceResponse: Peanut.IServiceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let response = <IContactItem[]>serviceResponse.Value;
                        me.filterValue('')
                        me.setContactList(response);
                    }
                }
            ).fail(function () {
                me.pageview('error');
               // let trace = me.services.getErrorInformation();
                me.application.hideWaiter();
            });

        }

        cancelChanges = () => {
            if (this.selectedContact) {
                this.contactForm.assign(this.selectedContact);
                this.displayPageView('view');
            }
            else {
                this.contactForm.clear();
                this.pageview('blank');
            }
        }

        displayPageView = (viewName: string) => {
            this.pageview(viewName);
            let div = document.getElementById(viewName+'-page');
            div.scrollIntoView({behavior: "smooth"});
        }

        scrollToList = () => {
            let div = document.getElementById('contact-list');
            div.scrollIntoView(true);
        }

        clearFilter = () => {
            let previous = this.filterValue().trim();
            this.filterValue('');
            if (previous !== '') {
                this.doSearch();
            }
        }

        getContactDetails = (contact : IContactItem) => {
            let me = this;
            me.services.executeService('Peanut.contacts::GetContactDetails',contact.id,
                function(serviceResponse: Peanut.IServiceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let response = <IGetContactDetailsReponse>serviceResponse.Value;
                        contact.subscriptions = response.subscriptions;
                        me.showContact(contact);
                    }
                }
            ).fail(function () {
                me.pageview('blank');
                // let trace = me.services.getErrorInformation();
                me.application.hideWaiter();
            });

        }

        showContact =(contact : IContactItem) => {
            this.selectedContact = contact;
            this.contactForm.assign(contact);
            this.displayPageView('view');
        }

        selectContact = (contact : IContactItem) => {
            if (!!contact.subscriptions) {
                this.showContact(contact);
            }
            else {
                this.getContactDetails(contact);
            }
        }

        doSearch  = () => {
            let me = this;
            me.pageview('wait');
            let request = {
                searchtype: 'fullname',
                searchvalue: me.filterValue()
            }
            me.services.executeService('Peanut.contacts::GetContactList',request,
                function(serviceResponse: Peanut.IServiceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let response = <IContactInitResponse>serviceResponse.Value;
                        me.setContactList(response.contacts);
                    }
                }
            ).fail(function () {
                me.pageview('blank');
                // let trace = me.services.getErrorInformation();
                me.application.hideWaiter();
            });

        }
    }
}
