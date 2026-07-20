var QnutDocuments;
(function (QnutDocuments) {
    class DocumentListViewModel extends Peanut.ViewModelBase {
        constructor() {
            super(...arguments);
            this.recordCount = ko.observable(0);
            this.documentList = ko.observableArray([]);
            this.currentPage = ko.observable(1);
            this.maxPages = ko.observable(2);
            this.resultCount = ko.observable(0);
            this.newDocumentHref = ko.observable('#');
            this.docViewLinkTitle = ko.observable('View document');
            this.docDownloadLinkTitle = ko.observable('Download document');
            this.docEditLinkTitle = ko.observable('Edit document information');
            this.searchResultMessage = ko.observable('');
            this.loadingDocumentList = ko.observable(true);
            this.loaded = ko.observable(false);
            this.errorMessage = ko.observable('');
            this.authenticated = ko.observable(false);
            this.noSearchResultsText = ko.observable('');
            this.searchResultsFormat = '';
            this.filter = null;
            this.getInitializations = (doneFunction) => {
                let me = this;
                me.application.hideServiceMessages();
                me.loadingDocumentList(true);
                let request = {
                    context: me.getVmContext(),
                };
                me.services.executeService('peanut.qnut-documents::GetDocumentList', request, function (serviceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        me.loaded(true);
                        let response = serviceResponse.Value;
                        me.addTranslations(response.translations);
                        let resultCount = response.recordCount;
                        me.documentList(response.documentList);
                        me.recordCount(resultCount);
                        me.authenticated(!response.filter.publicOnly);
                        me.searchResultsFormat = me.translate('document-search-found');
                        me.docViewLinkTitle(me.translate('document-icon-label-open-doc'));
                        me.docDownloadLinkTitle(me.translate('document-icon-label-download'));
                        me.docEditLinkTitle(me.translate('document-icon-label-info'));
                        if (resultCount == 0) {
                            me.searchResultMessage(response.filter.publicOnly ?
                                me.translate('document-search-hidden') :
                                me.translate('document-search-not-found'));
                        }
                        else {
                            me.searchResultMessage(me.searchResultsFormat.replace('%s', resultCount.toString()));
                        }
                        me.maxPages(Math.ceil(resultCount / response.filter.itemsPerPage));
                        me.filter = response.filter;
                    }
                    else {
                        me.errorMessage(serviceResponse.Value);
                    }
                })
                    .fail(function () {
                    let trace = me.services.getErrorInformation();
                })
                    .always(function () {
                    me.loadingDocumentList(false);
                    if (doneFunction) {
                        doneFunction();
                    }
                });
            };
            this.getDocuments = () => {
                let me = this;
                me.loadingDocumentList(true);
                me.loaded(false);
                me.application.hideServiceMessages();
                me.filter.pageNumber = me.currentPage();
                let request = {
                    filter: me.filter,
                    pageNumber: me.currentPage()
                };
                me.services.executeService('peanut.qnut-documents::GetDocumentList', request, function (serviceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let response = serviceResponse.Value;
                        me.loaded(true);
                        me.documentList(response.documentList);
                    }
                    else {
                    }
                })
                    .fail(function () {
                    let trace = me.services.getErrorInformation();
                })
                    .always(function () {
                    me.loadingDocumentList(false);
                });
            };
            this.changePage = (move) => {
                let current = this.currentPage() + move;
                if (current < 1) {
                    current = 1;
                }
                this.currentPage(current);
                this.getDocuments();
            };
            this.downloadDocument = (doc) => {
                window.location.href = doc.uri + '/download';
            };
        }
        init(successFunction) {
            let me = this;
            Peanut.logger.write('DocumentList Init');
            me.application.registerComponents('@pnut/pager', () => {
                me.getInitializations(() => {
                    me.bindDefaultSection();
                    successFunction();
                });
            });
        }
    }
    QnutDocuments.DocumentListViewModel = DocumentListViewModel;
})(QnutDocuments || (QnutDocuments = {}));
//# sourceMappingURL=DocumentListViewModel.js.map