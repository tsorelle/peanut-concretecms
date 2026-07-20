var QnutDocuments;
(function (QnutDocuments) {
    class DocumentAddendaViewModel extends Peanut.ViewModelBase {
        constructor() {
            super(...arguments);
            this.id = ko.observable(0);
            this.title = ko.observable('');
            this.abstract = ko.observable('');
            this.errorMessage = ko.observable('');
            this.addenda = ko.observableArray();
            this.documentTitle = ko.observable('');
            this.documentPublicationDate = ko.observable('');
            this.documentDescription = ko.observable('');
            this.viewPdfHref = ko.observable('');
            this.downloadHref = ko.observable('');
            this.editHref = ko.observable('');
            this.docViewLinkTitle = ko.observable('View document');
            this.docDownloadLinkTitle = ko.observable('Download document');
            this.docEditLinkTitle = ko.observable('Edit document information');
            this.pageIntro = ko.observable('');
            this.downloadDocument = (item) => {
            };
        }
        init(successFunction) {
            let me = this;
            let documentId = this.getRequestVar('id');
            me.services.executeService('peanut.qnut-documents::GetDocumentAddenda', documentId, function (serviceResponse) {
                if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                    let response = serviceResponse.Value;
                    me.addTranslations(response.translations);
                    me.docViewLinkTitle(me.translate('document-icon-label-view'));
                    me.documentTitle(response.document.title);
                    me.documentPublicationDate(response.document.publicationDate);
                    me.documentDescription(response.document.description);
                    me.viewPdfHref = ko.observable(response.document.viewUrl);
                    me.downloadHref(response.document.downloadUrl);
                    me.editHref(response.document.editUrl);
                    me.addenda(response.addenda);
                    if (response.pageTitle) {
                        me.setPageHeading(response.pageTitle);
                    }
                    me.pageIntro(response.pageIntro);
                }
            })
                .fail(function () {
                let trace = me.services.getErrorInformation();
            })
                .always(function () {
                me.application.hideWaiter();
                me.bindDefaultSection();
                successFunction();
            });
        }
    }
    QnutDocuments.DocumentAddendaViewModel = DocumentAddendaViewModel;
})(QnutDocuments || (QnutDocuments = {}));
//# sourceMappingURL=DocumentAddendaViewModel.js.map