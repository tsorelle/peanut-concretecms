var QnutDocuments;
(function (QnutDocuments) {
    class documentObservable {
        constructor(owner, properties, lookups, selectText = 'Select') {
            this.id = ko.observable(0);
            this.title = ko.observable('');
            this.filename = ko.observable('');
            this.folder = ko.observable('');
            this.abstract = ko.observable('');
            this.protected = ko.observable(false);
            this.publicationDate = ko.observable('');
            this.selectedCommittees = ko.observableArray([]);
            this.selectedGroups = ko.observableArray([]);
            this.selectedAddendumType = ko.observable();
            this.addendumTypes = ko.observableArray([]);
            this.addendumDate = ko.observable('');
            this.addendumComment = ko.observable('');
            this.hasErrors = ko.observable(false);
            this.titleError = ko.observable(false);
            this.abstractError = ko.observable(false);
            this.publicationDateError = ko.observable(false);
            this.fileNameError = ko.observable(false);
            this.fileSelectError = ko.observable(false);
            this.addenda = ko.observableArray();
            this.clear = () => {
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
            this.clearErrors = () => {
                this.hasErrors(false);
                this.titleError(false);
                this.publicationDateError(false);
                this.abstractError(false);
                this.fileNameError(false);
                this.fileSelectError(false);
            };
            this.assign = (document) => {
                let me = this;
                this.clearErrors();
                this.id(document.id);
                this.title(document.title || '');
                this.filename(document.filename || '');
                this.folder(document.folder || '');
                this.abstract(document.abstract || '');
                this.protected(document.protected);
                this.publicationDate(this.owner.isoToShortDate(document.publicationDate));
                let items = this.owner.committeeLookup();
                let committees = items.filter((committeeItem) => {
                    return document.committees.indexOf(committeeItem.id) !== -1;
                });
                this.selectedCommittees(committees);
                let groupitems = this.owner.groupsLookup();
                let groups = groupitems.filter((groupItem) => {
                    return document.groups.indexOf(groupItem.id) !== -1;
                });
                this.selectedGroups(groups);
                this.addendumDate(document.addendumDate || null);
                if (document.addendumType) {
                    let addendumType = this.addendumTypes().find((type) => {
                        return type.id = document.addendumType;
                    });
                    this.selectedAddendumType(addendumType);
                }
                else {
                    this.selectedAddendumType(null);
                }
                this.addendumComment(document.addendumComment);
                if (document.addenda) {
                    this.addenda(document.addenda);
                }
                else {
                    this.addenda([]);
                }
                this.propertiesController.setValues(document.properties);
            };
            this.showFileSelectError = () => {
                this.fileSelectError(true);
                this.hasErrors(true);
            };
            this.showFileAssignError = () => {
                this.fileNameError(true);
                this.hasErrors(true);
            };
            this.getSelectedCommitteeIds = () => {
                let result = [];
                let selections = this.selectedCommittees();
                for (let i = 0; i < selections.length; i++) {
                    result.push(selections[i].id);
                }
                return result;
            };
            this.getSelectedGroupIds = () => {
                let result = [];
                let groups = this.selectedGroups();
                for (let i = 0; i < groups.length; i++) {
                    result.push(groups[i].id);
                }
                return result;
            };
            this.validate = () => {
                let valid = true;
                let document = {
                    id: this.id(),
                    title: this.title(),
                    filename: this.filename(),
                    folder: this.folder(),
                    abstract: this.abstract(),
                    protected: this.protected(),
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
            };
            this.owner = owner;
            this.propertiesController = new Peanut.entityPropertiesController(properties, lookups, selectText, true);
        }
    }
    QnutDocuments.documentObservable = documentObservable;
    class DocumentViewModel extends Peanut.ViewModelBase {
        constructor() {
            super(...arguments);
            this.test = ko.observable('DocumentViewModel loaded');
            this.defaultLookupCaption = ko.observable('');
            this.fileDisposition = ko.observable('none');
            this.tab = ko.observable('view');
            this.canEdit = ko.observable(false);
            this.documentUri = '';
            this.searchPage = ko.observable('');
            this.documentId = ko.observable(0);
            this.downloadHref = ko.observable('');
            this.viewPdfHref = ko.observable('');
            this.currentFileName = ko.observable('');
            this.replaceFile = ko.observable(false);
            this.currentDocument = null;
            this.conflicts = ko.observableArray([]);
            this.committeeLookup = ko.observableArray([]);
            this.groupsLookup = ko.observableArray([]);
            this.docViewLinkTitle = ko.observable('View document');
            this.docDownloadLinkTitle = ko.observable('Download document');
            this.docEditLinkTitle = ko.observable('Edit document information');
            this.whatIsThis = ko.observable("What's this?");
            this.handleDocumentResponse = (response) => {
                let me = this;
                me.loadDocument(response.document);
            };
            this.loadDocument = (document) => {
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
                me.replaceFile(false);
                me.currentDocument = document;
                me.documentForm.assign(document);
                me.tab('view');
            };
            this.editDocument = () => {
                if (this.canEdit()) {
                    this.tab('edit');
                }
                else {
                    this.showErrorPage();
                }
            };
            this.newDocument = () => {
                let me = this;
                me.currentDocument = null;
                if (me.canEdit()) {
                    me.documentId(0);
                    me.currentFileName('');
                    me.documentForm.clear();
                    this.setElementValue("#documentFile", "");
                    me.tab('edit');
                    me.fileDisposition('upload');
                }
                else {
                    this.showErrorPage('document-access-error');
                }
            };
            this.validateForm = (files) => {
                let valid = true;
                this.documentForm.clearErrors();
                let request = {
                    document: null,
                    fileDisposition: this.fileDisposition(),
                    propertyValues: []
                };
                switch (this.fileDisposition()) {
                    case 'none':
                        this.setElementValue("#documentFile", "");
                        break;
                    case 'assign':
                        this.setElementValue("#documentFile", "");
                        if (!this.documentForm.filename()) {
                            this.documentForm.showFileAssignError();
                            valid = false;
                        }
                        break;
                    case 'upload':
                    case 'replace':
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
            this.updateDocument = () => {
                let me = this;
                me.application.hideServiceMessages();
                let files = me.getFilesForUpload();
                let request = me.validateForm(files);
                if (request === false) {
                    return;
                }
                me.showActionWaiter(request.document.id ? 'update' : 'add', 'document');
                me.services.postForm('peanut.qnut-documents::UpdateDocument', request, files, null, function (serviceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let response = serviceResponse.Value;
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
            this.cancelEdit = () => {
                if (this.documentForm.id() == 0) {
                    this.documentForm.clear();
                }
                else if (this.currentDocument) {
                    this.documentForm.assign(this.currentDocument);
                }
                this.tab('view');
            };
            this.showConflictsPage = (conflicts) => {
                this.conflicts(conflicts);
                this.tab('conflicts');
            };
            this.showEditPage = () => {
                this.tab('edit');
            };
            this.showErrorPage = (message = '') => {
                if (message) {
                    this.application.showError(this.translate(message));
                }
                this.tab('error');
            };
            this.confirmDelete = () => {
                this.showModal("#confirm-delete-document-modal");
            };
            this.loadNewDocument = (document) => {
                let me = this;
                me.application.hideServiceMessages();
                me.showLoadWaiter();
                me.services.executeService('peanut.qnut-documents::GetDocumentProperties', document.id, function (serviceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let response = serviceResponse.Value;
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
            this.deleteDocument = () => {
                let me = this;
                this.hideModal("#confirm-delete-document-modal");
                me.application.hideServiceMessages();
                me.showLoadWaiter();
                me.services.executeService('peanut.qnut-documents::DeleteDocument', me.documentForm.id(), function (serviceResponse) {
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
            this.downloadDocument = (item) => {
            };
        }
        init(successFunction) {
            let me = this;
            Peanut.logger.write('Document vm Init');
            me.showLoadWaiter();
            me.application.loadResources([
                '@lib:jqueryui-css',
                '@lib:jqueryui-js',
                '@pnut/ViewModelHelpers.js'
            ], () => {
                me.application.registerComponents('@pnut/entity-properties,@pnut/multi-select', () => {
                    me.getInitializations(() => {
                        me.application.hideWaiter();
                        me.bindDefaultSection();
                        successFunction();
                    });
                });
            });
        }
        getInitializations(doneFunction) {
            let me = this;
            let documentId = Peanut.Helper.getRequestParam('id');
            me.downloadHref('');
            me.viewPdfHref('');
            me.application.hideServiceMessages();
            me.showLoadWaiter();
            me.services.executeService('peanut.qnut-documents::InitDocumentForm', documentId, function (serviceResponse) {
                if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                    let response = serviceResponse.Value;
                    me.canEdit(response.canEdit);
                    me.documentUri = response.documentsUri;
                    me.searchPage(response.searchPage);
                    me.addTranslations(response.translations);
                    me.committeeLookup(response.committeeLookup);
                    me.groupsLookup(response.groupLookup);
                    me.docViewLinkTitle(me.translate('document-icon-label-view'));
                    let defaultLookupCaption = me.translate('document-search-dropdown-caption', '(any)');
                    me.defaultLookupCaption(defaultLookupCaption);
                    me.documentForm = new documentObservable(me, response.properties, response.propertyLookups, defaultLookupCaption);
                    me.documentForm.addendumTypes(response.addendumTypesLookup);
                    if (response.document) {
                        me.loadDocument(response.document);
                    }
                    else {
                        me.newDocument();
                    }
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
    }
    QnutDocuments.DocumentViewModel = DocumentViewModel;
})(QnutDocuments || (QnutDocuments = {}));
//# sourceMappingURL=DocumentViewModel.js.map