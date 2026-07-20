var Peanut;
(function (Peanut) {
    class multicolumnListPageLoader {
        constructor(itemsPerPage = 75, columnCount = 3) {
            this.items = [];
            this.column = [];
            this.itemsPerPage = 30;
            this.columnCount = 3;
            this.columnsize = 10;
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
            this.pageStart = 0;
            this.pageEnd = 0;
            this.loadPage = (pageNumber = 1) => {
                let offset = (pageNumber - 1) * this.itemsPerPage;
                this.pageStart = offset;
                let page = [];
                let count = this.items.length;
                if (offset <= count) {
                    let limit = offset + Math.min(this.itemsPerPage, count - offset);
                    page = this.items.slice(offset, limit);
                    this.pageEnd = offset + page.length - 1;
                }
                for (let i = 0; i < this.columnCount; i++) {
                    let start = this.columnsize * i;
                    let end = this.columnsize + start;
                    let c = page.slice(start, end);
                    this.column[i](c);
                }
                this.currentPageNumber(pageNumber);
            };
            this.changePage = (move) => {
                let current = this.currentPageNumber() + move;
                if (current < 1) {
                    current = 1;
                }
                this.currentPageNumber(current);
                this.loadPage(current);
                if (this.onPageChange) {
                    this.onPageChange(move);
                }
            };
            this.gotoPage = (pageNo) => {
                this.currentPageNumber(pageNo);
                this.loadPage(pageNo);
            };
            this.gotoLast = () => {
                let pageNo = this.maxPages();
                this.currentPageNumber(pageNo);
                this.loadPage(pageNo);
            };
            this.gotoIndex = (idx) => {
                let pageNo = Math.ceil((idx + 1) / this.itemsPerPage);
                if (pageNo != this.currentPageNumber()) {
                    this.currentPageNumber(pageNo);
                    this.loadPage(pageNo);
                }
            };
            let me = this;
            this.itemsPerPage = itemsPerPage;
            this.columnCount = columnCount;
            this.columnsize = Math.floor(itemsPerPage / columnCount);
            for (let i = 0; i < columnCount; i++) {
                me.column.push(ko.observableArray());
            }
        }
    }
    Peanut.multicolumnListPageLoader = multicolumnListPageLoader;
})(Peanut || (Peanut = {}));
//# sourceMappingURL=multicolumnListPageLoader.js.map