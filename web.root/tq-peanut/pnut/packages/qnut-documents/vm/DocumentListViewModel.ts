/// <reference path="../../../../pnut/core/ViewModelBase.ts" />
/// <reference path='../../../../typings/knockout/knockout.d.ts' />
/// <reference path='../../../../pnut/core/Peanut.d.ts' />
/// <reference path='./DocumentSearchViewModel.ts' />

namespace QnutDocuments {

    interface IDocumentListFilter {
        item: string;
        value: string;
        sortOrder: string;
        publicationDate: string; // earliest publicationDate as iso date
        itemsPerPage: number;
        pageNumber: number;
        publicOnly: any;
    }

    interface IGetDocumentListRequest {
        filter: IDocumentListFilter
    }

    interface IGetDocumentListResponse {
        documentList : IDocumentSearchResult[];
    }

    interface IInitDocumentListResponse extends IGetDocumentListResponse {
        filter: IDocumentListFilter
        recordCount: any;
        translations : any[];
    }

    export class DocumentListViewModel extends Peanut.ViewModelBase {
        // observables
        recordCount = ko.observable(0);
        documentList = ko.observableArray<IDocumentSearchResult>([]);
        currentPage = ko.observable(1);
        maxPages = ko.observable(2);
        resultCount = ko.observable(0);
        newDocumentHref = ko.observable('#');
        docViewLinkTitle = ko.observable('View document');
        docDownloadLinkTitle = ko.observable('Download document');
        docEditLinkTitle = ko.observable('Edit document information');
        searchResultMessage = ko.observable('');
        loadingDocumentList = ko.observable(true);
        loaded = ko.observable(false);
        errorMessage = ko.observable('');
        authenticated = ko.observable(false);

        noSearchResultsText = ko.observable('');
        searchResultsFormat = '';
        private filter : IDocumentListFilter = null;

        init(successFunction?: () => void) {
            let me = this;
            Peanut.logger.write('DocumentList Init');
            // me.application.registerComponents('@pnut/entity-properties,@pnut/pager', () => {
            me.application.registerComponents('@pnut/pager', () => {
                me.getInitializations(() => {
                    me.bindDefaultSection();
                    successFunction();
                });
            });
        }

        /**
         * Get initial document list etc.
         * @param doneFunction
         */
        getInitializations = (doneFunction?: () => void) => {
            let me = this;
            me.application.hideServiceMessages();
            me.loadingDocumentList(true);
            let request = {
                context: me.getVmContext(),
            };

            me.services.executeService('peanut.qnut-documents::GetDocumentList',request,
                function(serviceResponse: Peanut.IServiceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        me.loaded(true);
                        let response = <IInitDocumentListResponse>serviceResponse.Value;
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
                            me.searchResultMessage(
                                response.filter.publicOnly ?
                                    me.translate('document-search-hidden') :
                                    me.translate('document-search-not-found')
                            );
                        }
                        else {
                            me.searchResultMessage(
                                me.searchResultsFormat.replace('%s', resultCount.toString()));
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

        /**
         * Get next page of document listings
         */
        getDocuments = () => {
            let me = this;
            me.loadingDocumentList(true);
            me.loaded(false);
            me.application.hideServiceMessages();
            me.filter.pageNumber = me.currentPage();
            let request = <IGetDocumentListRequest>{
                filter: me.filter,
                pageNumber: me.currentPage()
            };
            me.services.executeService('peanut.qnut-documents::GetDocumentList',request,
                function(serviceResponse: Peanut.IServiceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let response = <IGetDocumentListResponse>serviceResponse.Value;
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

        changePage = (move: number) => {
            let current = this.currentPage() + move;
            if (current < 1) {
                current = 1;
            }
            this.currentPage(current);
            this.getDocuments();
        };

        downloadDocument = (doc : IDocumentSearchResult) => {
            window.location.href=doc.uri + '/download';
        };


    }
}
