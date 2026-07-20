var Peanut;
(function (Peanut) {
    class listPageLoader {
        constructor(itemsPerPage = 6) {
            this.list = ko.observableArray([]);
            this.itemsPerPage = 6;
            this.recordCount = ko.observable(0);
            this.currentPageNumber = ko.observable(1);
            this.maxPages = ko.observable(2);
            this.setItems = (items) => {
                this.items = items;
                this.recordCount(items.length);
                this.currentPageNumber(1);
                this.maxPages(Math.ceil(items.length / this.itemsPerPage));
                this.loadPage(1);
            };
            this.loadPage = (pageNumber = 1) => {
                let offset = (pageNumber - 1) * this.itemsPerPage;
                let page = [];
                let docCount = this.items.length;
                if (offset <= docCount) {
                    let limit = offset + Math.min(this.itemsPerPage, docCount - offset);
                    for (let i = offset; i < limit; i++) {
                        page.push(this.items[i]);
                    }
                }
                this.list(page);
                this.currentPageNumber(pageNumber);
            };
            this.changePage = (move) => {
                let current = this.currentPageNumber() + move;
                if (current < 1) {
                    current = 1;
                }
                this.currentPageNumber(current);
                this.loadPage(current);
            };
            this.itemsPerPage = itemsPerPage;
        }
    }
    Peanut.listPageLoader = listPageLoader;
})(Peanut || (Peanut = {}));
//# sourceMappingURL=listPageLoader.js.map