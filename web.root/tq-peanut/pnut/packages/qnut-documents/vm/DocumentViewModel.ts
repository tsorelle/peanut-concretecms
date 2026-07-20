/// <reference path="../../../../pnut/core/ViewModelBase.ts" />
/// <reference path='../../../../typings/knockout/knockout.d.ts' />
/// <reference path='../../../../pnut/core/Peanut.d.ts' />

namespace QnutDocuments {


    import IKeyValuePair = Peanut.IKeyValuePair;
    import IViewModel = Peanut.IViewModel;
    import ViewModelBase = Peanut.ViewModelBase;
    import ILookupItem = Peanut.ILookupItem;
    import INameValuePair = Peanut.INameValuePair;


    interface IDocumentRecord {
        id : any;
        title : string;
        filename : string;
        folder : string;
        abstract : string;
        protected : any;
        publicationDate : string;
        properties : Peanut.IKeyValuePair[];
        addendumType: any;
        addendumDate: any;
        addendumComment: string;
        committees : any[];
        groups: any[];
        addenda?: IDocumentReference[];
    }

    interface IDocumentInitResponse {
        properties : Peanut.IPropertyDefinition[];
        propertyLookups: Peanut.ILookupItem[];
        committeeLookup: Peanut.ILookupItem[];
        groupLookup: Peanut.ILookupItem[];
        addendumTypesLookup: Peanut.ILookupItem[];
        translations : any[];
        canEdit: boolean;
        maxFileSize: any;
        documentsUri: string;
        searchPage: string,
        document: IDocumentRecord;
    }

    interface IDocumentUpdateRequest {
        document: IDocumentRecord;
        fileDisposition: string;
        propertyValues : Peanut.IKeyValuePair[];
    }

    interface IGetPropertiesResponse {
        properties: Peanut.IKeyValuePair[];
        committees: any[];
        groups: any[];
    }

    export interface IDocumentReference {
        id: any,
        title: string,
        publicationDate: any,
        documentType: string,
        viewUrl: string,
        downloadUrl: string;
        editUrl: string;
        description: string;
    }

    export class documentObservable {
        public constructor(owner: Peanut.ViewModelBase,
                           properties: Peanut.IPropertyDefinition[],lookups : any[], selectText : string = 'Select') {
            this.owner = <DocumentViewModel>owner;
            this.propertiesController =  new Peanut.entityPropertiesController(properties,lookups,selectText,true);
        }
        owner : DocumentViewModel;
        id = ko.observable(0);
        title = ko.observable('');
        filename = ko.observable('');
        folder = ko.observable('');
        abstract = ko.observable('');
        protected = ko.observable(false);
        publicationDate = ko.observable('');
        propertiesController : Peanut.entityPropertiesController;
        selectedCommittees = ko.observableArray<ILookupItem>([]);
        selectedGroups = ko.observableArray<ILookupItem>([]);

        selectedAddendumType = ko.observable<ILookupItem>();
        addendumTypes = ko.observableArray<ILookupItem>([]);
        addendumDate = ko.observable('');
        addendumComment = ko.observable('');


        hasErrors = ko.observable(false);
        titleError = ko.observable(false);
        abstractError = ko.observable(false);
        publicationDateError = ko.observable(false);
        fileNameError = ko.observable(false);
        fileSelectError=ko.observable(false);

        addenda=ko.observableArray<IDocumentReference>();


        // lo : any = null; // lodash reference

        public clear = () => {
            this.id(0);
            this.title('');
            this.filename('');
            this.folder('');
            this.abstract('');
            this.protected(false);
            this.publicationDate(this.owner.getTodayString());
            this.propertiesController.clearValues();
            this.selectedCommittees([]);
            this.selectedGroups([]);
            this.selectedAddendumType(null);
            this.addendumComment('');
            this.clearErrors();
        };

        public clearErrors = () => {
            this.hasErrors(false);
            this.titleError(false);
            this.publicationDateError(false);
            this.abstractError(false);
            this.fileNameError(false);
            this.fileSelectError(false);
        };

        public assign = (document : IDocumentRecord) => {
            let me = this;
            this.clearErrors();
            this.id(document.id);
            this.title(document.title || '');
            this.filename(document.filename || '');
            this.folder(document.folder || '');
            this.abstract(document.abstract || '');
            this.protected(document.protected);
            this.publicationDate(this.owner.isoToShortDate(document.publicationDate));
/*
            let committees = this.lo.filter(this.owner.committeeLookup(),(committeeItem: Peanut.ILookupItem) => {
                return document.committees.indexOf(committeeItem.id) !== -1;
            });
*/
            let items = this.owner.committeeLookup();
            let committees = items.filter((committeeItem: Peanut.ILookupItem) => {
                return document.committees.indexOf(committeeItem.id) !== -1;
            });

            this.selectedCommittees(committees);

            let groupitems = this.owner.groupsLookup();
            let groups = groupitems.filter((groupItem: Peanut.ILookupItem) => {
                return document.groups.indexOf(groupItem.id) !== -1;
            });
            this.selectedGroups(groups);

            this.addendumDate(document.addendumDate || null);
            if (document.addendumType) {
                let addendumType = this.addendumTypes().find((type: ILookupItem) => {
                    return type.id = document.addendumType;
                });
                this.selectedAddendumType(addendumType);
            }
            else {
                this.selectedAddendumType(null);
            }

            this.addendumComment(document.addendumComment)

            if (document.addenda) {
                this.addenda(document.addenda);
            }
            else {
                this.addenda([]);
            }

            this.propertiesController.setValues(document.properties);
        };

        public showFileSelectError = () =>  {
            this.fileSelectError(true);
            this.hasErrors(true);
        };

        public showFileAssignError = () =>  {
            this.fileNameError(true);
            this.hasErrors(true);
        };

        private getSelectedCommitteeIds = () => {
            let result = [];
            let selections = this.selectedCommittees();
            // this.lo.forEach(this.selectedCommittees(), function(selected: ILookupItem) {
            for (let i = 0; i < selections.length; i++) {
                result.push(selections[i].id);
            }
            return result;
        };

        private getSelectedGroupIds = () => {
            let result = [];
            let groups = this.selectedGroups();
/*
            this.lo.forEach(this.selectedGroups(), function(selected: ILookupItem) {
                result.push(selected.id);
            });
*/
            for(let i=0; i < groups.length; i++) {
                result.push(groups[i].id);
            }

            return result;
        };

        public validate = () => {
            let valid = true;
            // this.clearErrors();
            // assume errors cleared
            let document = <IDocumentRecord>{
                id: this.id(),
                title: this.title(),
                filename: this.filename(),
                folder: this.folder(),
                abstract: this.abstract(),
                protected: this.protected(), // ? 1 : 0,
                publicationDate: this.owner.shortDateToIso(this.publicationDate()),
                properties: this.propertiesController.getValues(),
                committees: this.getSelectedCommitteeIds(),
                groups: this.getSelectedGroupIds(),
                addendumType: this.selectedAddendumType() ? this.selectedAddendumType().id : null,
                addendumDate: this.selectedAddendumType() ? this.owner.shortDateToIso(this.addendumDate()) : null,
                addendumComment: this.selectedAddendumType() ? this.addendumComment() : null
            };

            if (!document.title) {
                this.titleError(true);
                valid = false;
            }
            if (!document.abstract) {
                this.abstractError(true);
                valid = false;
            }

            if (!document.publicationDate) {
                this.publicationDateError(true);
                valid = false;
            }

            this.hasErrors(!valid);
            return valid ? document : null;
        }

    }

    export class DocumentViewModel extends Peanut.ViewModelBase {
        // observables
        test = ko.observable('DocumentViewModel loaded');
        defaultLookupCaption = ko.observable('');
        fileDisposition = ko.observable('none'); // 'upload','replace','none'
        tab = ko.observable('view'); // 'view','edit','error'
        canEdit = ko.observable(false);
        documentUri : string = '';
        searchPage = ko.observable('');
        documentId = ko.observable<any>(0);
        documentForm : documentObservable;
        downloadHref = ko.observable('');
        viewPdfHref = ko.observable('');
        currentFileName = ko.observable('');
        replaceFile = ko.observable(false);
        currentDocument : IDocumentRecord = null;
        conflicts = ko.observableArray<IDocumentRecord>([]);
        committeeLookup = ko.observableArray<ILookupItem>([]);
        groupsLookup = ko.observableArray<ILookupItem>([]);

        docViewLinkTitle = ko.observable('View document');
        docDownloadLinkTitle = ko.observable('Download document');
        docEditLinkTitle = ko.observable('Edit document information');

        whatIsThis = ko.observable("What's this?");


        init(successFunction?: () => void) {
            let me = this;
            Peanut.logger.write('Document vm Init');
            me.showLoadWaiter();
            me.application.loadResources([
                '@lib:jqueryui-css',
                '@lib:jqueryui-js',
                // '@lib:lodash',
                '@pnut/ViewModelHelpers.js'
            ], () => {
                // initialize date popups
                /* replce with html
                jQuery(function () {
                    jQuery(".datepicker").datepicker();
                });
                */
                 /*
                 There dont seem to be any popovers in Document.html. Obsolete?

                const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]')
                const popoverList = [...popoverTriggerList].map(
                    popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl))
                // jQuery('[data-bs-toggle="popover"]').popover();

                  */
                me.application.registerComponents('@pnut/entity-properties,@pnut/multi-select', () => {
                    me.getInitializations(() => {
                        me.application.hideWaiter();
                        me.bindDefaultSection();
                        successFunction();
                    });
                });
            });
        }

        getInitializations(doneFunction?: () => void) {
            let me = this;
            let documentId = Peanut.Helper.getRequestParam('id');
            me.downloadHref('');
            me.viewPdfHref('');
            me.application.hideServiceMessages();
            me.showLoadWaiter();
            me.services.executeService('peanut.qnut-documents::InitDocumentForm',documentId,
                function(serviceResponse: Peanut.IServiceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let response = <IDocumentInitResponse>serviceResponse.Value;
                        me.canEdit(response.canEdit);
                        me.documentUri = response.documentsUri;
                        me.searchPage(response.searchPage);
                        me.addTranslations(response.translations);
                        me.committeeLookup(response.committeeLookup);
                        me.groupsLookup(response.groupLookup);
                        me.docViewLinkTitle(me.translate('document-icon-label-view'));
                        let defaultLookupCaption = me.translate('document-search-dropdown-caption','(any)');
                        me.defaultLookupCaption(defaultLookupCaption);
                        me.documentForm = new documentObservable(me,response.properties,
                            response.propertyLookups,defaultLookupCaption);
                        me.documentForm.addendumTypes(
                            response.addendumTypesLookup
                        );
                        if (response.document) {
                            me.loadDocument(response.document);
                        }
                        else {
                            me.newDocument();
                        }

                        // me.docViewLinkTitle(me.translate('document-icon-label-view'));
                        // me.docDownloadLinkTitle(me.translate('document-icon-label-download'));
                        // me.docEditLinkTitle(me.translate('document-icon-label-edit'));
                    }
                    else {
                        me.tab('error');
                    }
                })
                .fail(function () {
                    me.tab('error');
                    let trace = me.services.getErrorInformation();
                })
                .always(function () {
                    me.application.hideWaiter();
                    if (doneFunction) {
                        doneFunction();
                    }
                });
        }

        private handleDocumentResponse = (response) => {
            let me = this;
            me.loadDocument(response.document);
        };

        loadDocument = (document: IDocumentRecord) => {
            let me = this;
            let href = me.documentUri + document.id;

            let filename = document.filename || '';
            let p = filename.lastIndexOf('.');
            let ext = p >= 0 ? filename.substring(p + 1, filename.length) : '';
            me.viewPdfHref(ext == 'pdf' ? href : '');
            me.downloadHref(href + '/download');

            me.documentId(document.id);
            me.fileDisposition(filename ? 'none' : 'upload');
            me.currentFileName(document.filename);
            // me.currentFileName('');
            me.replaceFile(false);
            me.currentDocument = document;
            me.documentForm.assign(document);
            me.tab('view');
        };

        editDocument = () => {
            if (this.canEdit()) {
                this.tab('edit');
            }
            else {
                this.showErrorPage()
            }
        };

        newDocument = () => {
            let me = this;
            me.currentDocument = null;
            if (me.canEdit()) {
                me.documentId(0);
                me.currentFileName('');
                me.documentForm.clear();
                // $("#documentFile").val("");
                this.setElementValue("#documentFile","");
                me.tab('edit');
                me.fileDisposition('upload');
            }
            else {
                this.showErrorPage('document-access-error');
            }
        };

        getFilesForUpload() {
            let disposition = this.fileDisposition();
            let files = null;
            if (disposition === 'upload' || disposition == 'assign') {
                files = Peanut.Helper.getSelectedFiles('#documentFile');
                if (!files) {
                    return false;
                }
            }
            return files;
        }

        validateForm = (files) => {
            let valid = true;
            this.documentForm.clearErrors();
            let request = <IDocumentUpdateRequest>{
                document: null,
                fileDisposition: this.fileDisposition(),
                propertyValues: []
            };

            switch (this.fileDisposition()) {
                case 'none':
                    this.setElementValue("#documentFile","");
                    break;
                case 'assign' :
                    this.setElementValue("#documentFile","");
                    if (!this.documentForm.filename()) {
                        this.documentForm.showFileAssignError();
                        valid = false;
                    }
                    break;
                case 'upload' :
                case 'replace' :
                    if (!files) {
                        this.documentForm.showFileSelectError();
                        valid = false;
                    }
            }

            request.document = this.documentForm.validate();

            if (!request.document) {
                valid = false;
            }

            if (valid) {
                request.propertyValues = this.documentForm.propertiesController.getValues();
                return request;
            }

            return false;
        };

        updateDocument = () => {
            let me = this;
            me.application.hideServiceMessages();
            let files = me.getFilesForUpload();
            let request = me.validateForm(files);
            if (request === false) {
                return;
            }

            me.showActionWaiter( request.document.id ? 'update' : 'add','document');
            me.services.postForm( 'peanut.qnut-documents::UpdateDocument', request, files, null,
                function (serviceResponse: Peanut.IServiceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let response = <IDocumentRecord>serviceResponse.Value;
                        me.loadDocument(response);
                    }
                    else {
                        if (serviceResponse.Value && serviceResponse.Value.conflicts) {
                            me.showConflictsPage(serviceResponse.Value.conflicts);
                        }
                        else {

                            me.showErrorPage();
                        }
                    }
                    }).fail(() => {
                        let trace = me.services.getErrorInformation();
                    }).always(() => {
                        me.application.hideWaiter();
                    });
        };

        cancelEdit = () => {
            if (this.documentForm.id() == 0) {
                this.documentForm.clear();
            }
            else if (this.currentDocument) {
                this.documentForm.assign(this.currentDocument);
            }

            this.tab('view');
        };

        showConflictsPage = (conflicts: any[]) => {
            this.conflicts(conflicts);
            this.tab('conflicts');
        };

        showEditPage = () => {
            this.tab('edit');
        };

        showErrorPage = (message = '') => {
            if (message) {
                this.application.showError(this.translate(message));
            }
            this.tab('error');
        };

        confirmDelete = () => {
            this.showModal("#confirm-delete-document-modal");
            // jQuery("#confirm-delete-document-modal").modal('show');
        };

        loadNewDocument = (document: IDocumentRecord) => {
            let me = this;
            me.application.hideServiceMessages();
            me.showLoadWaiter();
            me.services.executeService('peanut.qnut-documents::GetDocumentProperties',document.id,
                function(serviceResponse: Peanut.IServiceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let response =  <IGetPropertiesResponse>serviceResponse.Value;
                        document.properties = response.properties;
                        document.committees = response.committees;
                        document.groups = response.groups;

                        me.loadDocument(document);
                    }
                    else {
                        me.tab('error');
                    }
                })
                .fail(function () {
                    me.tab('error');
                    let trace = me.services.getErrorInformation();
                })
                .always(function () {
                    me.application.hideWaiter();
                });

        };

        deleteDocument = () => {
            let me = this;
            this.hideModal("#confirm-delete-document-modal");

            me.application.hideServiceMessages();
            me.showLoadWaiter();
            me.services.executeService('peanut.qnut-documents::DeleteDocument',me.documentForm.id(),
                function(serviceResponse: Peanut.IServiceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        window.location.assign(me.searchPage());
                    }
                    else {
                        me.tab('error');
                    }
                })
                .fail(function () {
                    me.tab('error');
                    let trace = me.services.getErrorInformation();
                })
                .always(function () {
                    me.application.hideWaiter();
                });
        };
        downloadDocument = (item: any) => {

        }


    }

}