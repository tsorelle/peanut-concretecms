/// <reference path="../../../../pnut/core/ViewModelBase.ts" />
/// <reference path='../../../../typings/knockout/knockout.d.ts' />
/// <reference path='../../../../pnut/core/Peanut.d.ts' />
/// <reference path='DocumentViewModel.ts' />

namespace QnutDocuments {

    import IDocumentReference = QnutDocuments.IDocumentReference;

    interface IDocumentAddendaInitReaponse {
        document: IDocumentReference;
        addenda: IDocumentReference[];
        pageTitle: string;
        pageIntro: string;
        translations: any[];
    }

    export class DocumentAddendaViewModel extends Peanut.ViewModelBase {
        // observables
        id = ko.observable(0);
        title = ko.observable('');
        abstract = ko.observable('');
        errorMessage = ko.observable('');
        addenda=ko.observableArray<IDocumentReference>();
        documentTitle = ko.observable('');
        documentPublicationDate = ko.observable('');
        documentDescription=ko.observable('');
        viewPdfHref = ko.observable('');
        downloadHref = ko.observable('');
        editHref = ko.observable('');
        docViewLinkTitle = ko.observable('View document');
        docDownloadLinkTitle = ko.observable('Download document');
        docEditLinkTitle = ko.observable('Edit document information');
        pageIntro = ko.observable('');

        init(successFunction?: () => void) {
            let me = this;
            let documentId = this.getRequestVar('id');
            me.services.executeService('peanut.qnut-documents::GetDocumentAddenda',documentId,
                function(serviceResponse: Peanut.IServiceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let response = <IDocumentAddendaInitReaponse>serviceResponse.Value;
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

        downloadDocument = (item: IDocumentReference) => {

        }
    }
}
