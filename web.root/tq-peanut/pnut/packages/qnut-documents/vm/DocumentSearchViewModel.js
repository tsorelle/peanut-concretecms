var QnutDocuments;
(function (QnutDocuments) {
    class DocumentSearchViewModel extends Peanut.ViewModelBase {
        constructor() {
            super(...arguments);
            this.documents = [];
            this.itemsPerPage = 6;
            this.searchResults = ko.observableArray([]);
            this.resultCount = ko.observable(0);
            this.textOption = ko.observable('keywords');
            this.fileTypes = ko.observableArray();
            this.selectedFileType = ko.observable();
            this.userAuthenticated = false;
            this.orders = [
                { Name: 'Date (descending)', Value: 2 },
                { Name: 'Date (ascending)', Value: 1 },
                { Name: 'Title', Value: 3 },
                { Name: 'No sort', Value: 0 }
            ];
            this.defaultOrder = this.orders.find((order) => order.Value === 2);
            this.sortOrders = ko.observableArray(this.orders);
            this.selectedOrder = ko.observable();
            this.committees = ko.observableArray();
            this.selectedCommittee = ko.observable();
            this.dateSearchModes = ko.observableArray([]);
            this.selectedDateSearchMode = ko.observable();
            this.showSecondDate = ko.observable(false);
            this.startDate = ko.observable('');
            this.endDate = ko.observable('');
            this.startDateVisible = ko.observable(false);
            this.endDateVisible = ko.observable(false);
            this.sortOrder = ko.observable(1);
            this.sortDescending = ko.observable(true);
            this.titleSearch = ko.observable('');
            this.textSearch = ko.observable('');
            this.fileNameSearch = ko.observable('');
            this.docIdSearch = ko.observable('');
            this.wideSearch = ko.observable(false);
            this.searchType = ko.observable('info');
            this.fullTextSupported = ko.observable(false);
            this.publicationDate = ko.observable('');
            this.searchResultMessage = ko.observable('');
            this.noSearchResultsText = '';
            this.searchResultsFormat = '';
            this.defaultLookupCaption = ko.observable('');
            this.searched = ko.observable(false);
            this.recordCount = ko.observable(0);
            this.currentPage = ko.observable(1);
            this.maxPages = ko.observable(2);
            this.newDocumentHref = ko.observable('#');
            this.docViewLinkTitle = ko.observable('View document');
            this.docDownloadLinkTitle = ko.observable('Download document');
            this.docEditLinkTitle = ko.observable('Edit document information');
            this.canEdit = ko.observable(false);
            this.clearForm = () => {
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
                this.selectedOrder(this.defaultOrder);
            };
            this.onDateModeChange = (selected) => {
                this.showSecondDate(selected && selected.Value == 4);
            };
            this.executeSearch = (isNew = true) => {
                let me = this;
                if (isNew) {
                    me.recordCount(0);
                    me.currentPage(1);
                }
                let sort = me.selectedOrder().Value;
                me.sortDescending(sort === 2);
                let request = {
                    searchType: me.searchType(),
                    searchText: me.textSearch() ? me.textSearch().trim() : '',
                    sortOrder: sort,
                    sortDescending: me.sortDescending(),
                    pageNumber: me.currentPage(),
                };
                if (me.searchType() == 'text') {
                    request.wideSearch = me.wideSearch();
                }
                if (me.searchType() == 'info') {
                    request.title = me.titleSearch() ? me.titleSearch().trim() : '';
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
                me.services.executeService('peanut.qnut-documents::FindDocuments', request, function (serviceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let response = serviceResponse.Value;
                        me.documents = response.searchResults;
                        me.loadPage();
                        if (isNew) {
                            let resultCount = response.searchResults.length;
                            me.recordCount(resultCount);
                            me.searchResultMessage(resultCount ? me.searchResultsFormat.replace('%s', resultCount.toString()) : me.noSearchResultsText);
                            let max = Math.ceil(resultCount / me.itemsPerPage);
                            me.maxPages(Math.ceil(resultCount / me.itemsPerPage));
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
            this.downloadDocument = (doc) => {
                window.location.href = doc.uri + '/download';
            };
            this.returnToSearchForm = () => {
                this.searched(false);
            };
            this.loadPage = (pageNumber = 1) => {
                let offset = (pageNumber - 1) * this.itemsPerPage;
                let page = [];
                let docCount = this.documents.length;
                if (offset <= docCount) {
                    let limit = offset + Math.min(this.itemsPerPage, docCount - offset);
                    for (let i = offset; i < limit; i++) {
                        page.push(this.documents[i]);
                    }
                }
                this.searchResults(page);
                this.currentPage(pageNumber);
            };
            this.changePage = (move) => {
                let current = this.currentPage() + move;
                if (current < 1) {
                    current = 1;
                }
                this.currentPage(current);
                this.loadPage(current);
            };
            this.setSearchTypeText = () => {
                this.searchType('text');
            };
            this.setSearchTypeInfo = () => {
                this.searchType('info');
            };
            this.setSearchTypeLookup = () => {
                this.searchType('lookup');
            };
        }
        init(successFunction) {
            let me = this;
            Peanut.logger.write('DocumentSearch Init');
            me.application.registerComponents('@pnut/entity-properties,@pnut/pager', () => {
                me.getInitializations(() => {
                    me.selectedOrder(me.sortOrders()[0]);
                    me.bindDefaultSection();
                    successFunction();
                });
            });
        }
        getInitializations(doneFunction) {
            let me = this;
            me.application.hideServiceMessages();
            me.showLoadWaiter();
            me.services.executeService('peanut.qnut-documents::InitDocumentSearch', null, function (serviceResponse) {
                if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                    let response = serviceResponse.Value;
                    me.fileTypes(response.fileTypes);
                    me.selectedFileType(null);
                    me.committees(response.committeeLookup);
                    me.selectedCommittee(null);
                    me.addTranslations(response.translations);
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
                        { Name: me.translate('date-search-mode-on'), Value: 1 },
                        { Name: me.translate('date-search-mode-before'), Value: 2 },
                        { Name: me.translate('date-search-mode-after'), Value: 3 },
                        { Name: me.translate('date-search-mode-between'), Value: 4 }
                    ]);
                    me.selectedDateSearchMode.subscribe(me.onDateModeChange);
                    let test = me.dateSearchModes();
                    let defaultLookupCaption = me.translate('document-search-dropdown-caption', '(any)');
                    me.propertiesController = new Peanut.entityPropertiesController(response.properties, response.propertyLookups, defaultLookupCaption, true);
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
    }
    QnutDocuments.DocumentSearchViewModel = DocumentSearchViewModel;
})(QnutDocuments || (QnutDocuments = {}));
//# sourceMappingURL=DocumentSearchViewModel.js.map