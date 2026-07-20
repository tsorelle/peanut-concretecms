/// <reference path="../../../../pnut/core/ViewModelBase.ts" />
/// <reference path='../../../../typings/knockout/knockout.d.ts' />
/// <reference path='../../../../pnut/core/Peanut.d.ts' />
/// <reference path='../../../../pnut/components/entityPropertiesComponent.ts' />
/// <reference path='../js/document.d.ts' />



namespace QnutDocuments {

    import INameValuePair = Peanut.INameValuePair;
    import ILookupItem = Peanut.ILookupItem;
    import repeatInfoEditor = QnutCalendar.repeatInfoEditor;

    interface IDocumentSearchInitResponse {
        properties : Peanut.IPropertyDefinition[];
        propertyLookups: Peanut.ILookupItem[];
        fileTypes: ILookupItem[];
        committeeLookup: ILookupItem[];
        fullTextSupported : boolean;
        translations : any[];
        newDocumentHref: string;
        userCanEdit: any;
        userIsAuthenticated: boolean;
    }

    export interface IDocumentSearchResponse {
        searchResults: any;
        recordCount: any;
    }

    interface IDocumentSearchRequest {
        searchType: string,
        searchText: string,
        sortOrder: any,
        sortDescending: boolean,
        pageNumber: any,
        itemsPerPage: any,
        recordCount: any,
        wideSearch?: boolean
    }

    interface IDocumentInfoSearchRequest extends IDocumentSearchRequest{
        title: string,
        fileType: string,
        committeeId: any,
        literal: boolean,
        dateSearchMode: any,
        firstDate: any,
        secondDate: any,
        properties: string[],
    }

    export class DocumentSearchViewModel extends Peanut.ViewModelBase {
        // observables
        documents : IDocumentSearchResult[] = [];
        itemsPerPage = 6;
        searchResults = ko.observableArray<IDocumentSearchResult>([]);
        resultCount = ko.observable(0);
        propertiesController : Peanut.entityPropertiesController;

        textOption = ko.observable('keywords');
        fileTypes = ko.observableArray<ILookupItem>();
        selectedFileType = ko.observable<ILookupItem>();
        userAuthenticated : boolean = false;


        orders =             [
            {Name:'Date (descending)',Value: 2},
            {Name:'Date (ascending)',Value: 1},
            {Name:'Title',Value: 3},
            {Name:'No sort', Value: 0}
        ]
        defaultOrder =  this.orders.find((order) => order.Value === 2);
        sortOrders = ko.observableArray<INameValuePair>(
            this.orders
        );
        selectedOrder = ko.observable<INameValuePair>();

        committees = ko.observableArray<ILookupItem>();
        selectedCommittee = ko.observable<ILookupItem>();
        dateSearchModes = ko.observableArray<Peanut.INameValuePair>([]);
        selectedDateSearchMode = ko.observable<Peanut.INameValuePair>();
        showSecondDate = ko.observable(false);
        startDate = ko.observable('');
        endDate = ko.observable('');
        startDateVisible = ko.observable(false);
        endDateVisible = ko.observable(false);
        sortOrder = ko.observable(1);
        sortDescending = ko.observable(true);
        titleSearch = ko.observable('');
        textSearch = ko.observable('');
        fileNameSearch = ko.observable('');
        docIdSearch = ko.observable('');
        wideSearch = ko.observable(false);

        searchType = ko.observable('info');

        // fullTextSearch = ko.observable(false);
        fullTextSupported = ko.observable(false);
        publicationDate = ko.observable('');
        searchResultMessage = ko.observable('');
        noSearchResultsText = '';
        searchResultsFormat = '';
        defaultLookupCaption = ko.observable('');
        searched = ko.observable(false);

        recordCount = ko.observable(0);
        currentPage = ko.observable(1);
        maxPages = ko.observable(2);
        newDocumentHref = ko.observable('#');
        docViewLinkTitle = ko.observable('View document');
        docDownloadLinkTitle = ko.observable('Download document');
        docEditLinkTitle = ko.observable('Edit document information');
        canEdit = ko.observable(false);


        init(successFunction?: () => void) {
            let me = this;
            Peanut.logger.write('DocumentSearch Init');

            /*
            // use load loadResources if jqueryui used
            me.application.loadResources([
                '@lib:jqueryui-css',
                '@lib:jqueryui-js',
                // ,'@pnut/ViewModelHelpers'
            ], () => {
                // initialize date popups
                jQuery(function () {
                    jQuery(".datepicker").datepicker();
                });
             */

                me.application.registerComponents('@pnut/entity-properties,@pnut/pager', () => {
                    me.getInitializations(() => {
                        me.selectedOrder(me.sortOrders()[0])
                        me.bindDefaultSection();
                        successFunction();
                    });
                });
            // });
        }

        getInitializations(doneFunction?: () => void) {
            let me = this;
            me.application.hideServiceMessages();
            me.showLoadWaiter();
            me.services.executeService('peanut.qnut-documents::InitDocumentSearch',null,
                function(serviceResponse: Peanut.IServiceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let response = <IDocumentSearchInitResponse>serviceResponse.Value;
                        me.fileTypes(response.fileTypes);
                        me.selectedFileType(null);
                        me.committees(response.committeeLookup);
                        me.selectedCommittee(null);
                        me.addTranslations(response.translations);
                        // me.searchResultsFormat = me.translate('document-search-found');
                        if (response.userIsAuthenticated) {
                            me.searchResultsFormat = '%s documents found.';
                            me.noSearchResultsText = me.translate('document-search-not-found');
                        }
                        else {
                            me.noSearchResultsText = 'No public documents found. Sign in to search all documents.';
                            me.searchResultsFormat = '%s public documents found. Sign in to search all documents.';
                        }

                        me.fullTextSupported(response.fullTextSupported);

                        me.dateSearchModes([
                            // {Name: me.defaultLookupCaption(), Value: 1},
                            {Name: me.translate('date-search-mode-on'), Value: 1},
                            {Name: me.translate('date-search-mode-before'), Value: 2},
                            {Name: me.translate('date-search-mode-after'), Value: 3},
                            {Name: me.translate('date-search-mode-between'), Value: 4}
                        ]);
                        me.selectedDateSearchMode.subscribe(me.onDateModeChange);
                        let test = me.dateSearchModes();
                        let defaultLookupCaption = me.translate('document-search-dropdown-caption','(any)');
                        me.propertiesController = new Peanut.entityPropertiesController(response.properties,
                            response.propertyLookups,defaultLookupCaption,true);
                        me.defaultLookupCaption(defaultLookupCaption);
                        me.docViewLinkTitle(me.translate('document-icon-label-view'));
                        me.docDownloadLinkTitle(me.translate('document-icon-label-download'));
                        me.docEditLinkTitle(me.translate('document-icon-label-open'));

                        me.newDocumentHref(response.newDocumentHref);
                        me.canEdit(response.userCanEdit);

                    }
                    else {
                    }
                })
                .fail(function () {
                    let trace = me.services.getErrorInformation();
                })
                .always(function () {
                    me.application.hideWaiter();
                    if (doneFunction) {
                        doneFunction();
                    }
                });
        }

        clearForm = () => {
            this.searchType('info');
            this.selectedFileType(null);
            this.titleSearch('');
            this.textSearch('');
            this.selectedDateSearchMode(null);
            this.publicationDate('');
            this.docIdSearch('');
            this.fileNameSearch('');
            this.propertiesController.clearValues();
            this.selectedCommittee(null);
            this.documents = [];
            this.searchResults([]);
            this.recordCount(0);
            this.currentPage(1);
            this.maxPages(0);
            this.wideSearch(false);
            this.selectedOrder(this.defaultOrder)
        };

        onDateModeChange = (selected: INameValuePair) => {
            this.showSecondDate(selected && selected.Value == 4);
        };

        executeSearch = (isNew = true) => {
            let me = this;
            if (isNew) {
                me.recordCount(0);
                me.currentPage(1);
            }

            let sort = me.selectedOrder().Value;
/*
            if (sort == 2) {
                this.sortDescending(true);
            }
            else {
                this.sortDescending(false);
            }
*/

            // me.sortOrder(sort == 3 ? 2 : 1);
            me.sortDescending(sort === 2);

            let request : any = {
                searchType : me.searchType(),
                searchText : me.textSearch() ? me.textSearch().trim() : '',
                sortOrder:  sort,
                sortDescending: me.sortDescending(),
                pageNumber: me.currentPage(),
                // itemsPerPage: 4,
                // recordCount: me.recordCount()
            };
            if (me.searchType() =='text' ) {
                request.wideSearch =  me.wideSearch();
            }
            if (me.searchType() == 'info') {
                request.title =  me.titleSearch() ? me.titleSearch().trim() : '';
                request.fileType = me.selectedFileType() ? me.selectedFileType().code : null;
                let test = me.selectedCommittee();
                request.committeeId = me.selectedCommittee() ? me.selectedCommittee().id : null;
                request.literal = (me.textOption() == 'literal');
                request.dateSearchMode = me.selectedDateSearchMode() ? me.selectedDateSearchMode().Value : null;
                request.firstDate = me.startDate();
                request.secondDate = me.endDate();
                request.properties = this.propertiesController.getValues();
            }
            else if (me.searchType() == 'lookup') {
                request.documentId = this.docIdSearch();
                request.filename = this.fileNameSearch();
            }

            me.application.hideServiceMessages();
            me.showLoadWaiter();
            me.services.executeService('peanut.qnut-documents::FindDocuments',request,
                function(serviceResponse: Peanut.IServiceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {

                        let response = <IDocumentSearchResponse>serviceResponse.Value;
                        me.documents = response.searchResults;
                        // me.searchResults(response.searchResults);
                        me.loadPage();
                        if (isNew) {
                            let resultCount = response.searchResults.length;// response.recordCount;
                            me.recordCount(resultCount);
                            me.searchResultMessage(
                                resultCount ? me.searchResultsFormat.replace('%s', resultCount.toString()) : me.noSearchResultsText
                            );
                            let max = Math.ceil(resultCount / me.itemsPerPage);
                            me.maxPages(Math.ceil(resultCount / me.itemsPerPage));
                            // me.maxPages(100);
                        }
                        me.searched(true);
                    }
                    else {
                    }
                })
                .fail(function () {
                    let trace = me.services.getErrorInformation();
                })
                .always(function () {
                    me.application.hideWaiter();
                });
        };

        downloadDocument = (doc : IDocumentSearchResult) => {
            window.location.href=doc.uri + '/download';
        };

        returnToSearchForm = () => {
            this.searched(false);
        };

        loadPage = (pageNumber: any = 1) => {
            let offset = (pageNumber-1) * this.itemsPerPage;
            let page = [];
            let docCount = this.documents.length;
            if (offset <= docCount) {
                let limit = offset + Math.min(this.itemsPerPage, docCount - offset );
                for (let i=offset;i<limit;i++) {
                    page.push(this.documents[i]);
                }
            }
            this.searchResults(page);
            this.currentPage(pageNumber);

        };

        changePage = (move: number) => {
            let current = this.currentPage() + move;
            if (current < 1) {
                current = 1;
            }
            this.currentPage(current);
            // this.executeSearch(false);
            this.loadPage(current);
        };

        setSearchTypeText = () => {
            this.searchType('text');
        };
        setSearchTypeInfo = () => {
            this.searchType('info');

        };
        setSearchTypeLookup = () => {
            this.searchType('lookup');
        };
    }
}