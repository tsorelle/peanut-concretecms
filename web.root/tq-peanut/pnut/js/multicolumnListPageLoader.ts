/// <reference path='../../typings/knockout/index.d.ts' />
namespace Peanut {

    export class multicolumnListPageLoader {
        items = [];
        column = [];
        itemsPerPage = 30;
        columnCount = 3;
        columnsize = 10;

        recordCount = ko.observable(0);
        currentPageNumber = ko.observable(1);
        maxPages = ko.observable(2);
        onPageChange : (change: number) => void;
        public constructor(itemsPerPage : number = 75,
                           columnCount = 3) {
            let me = this;
            this.itemsPerPage = itemsPerPage;
            this.columnCount = columnCount;
            this.columnsize = Math.floor(itemsPerPage / columnCount);
            for(let i=0; i<columnCount; i++) {
                me.column.push(ko.observableArray());
            }
        }

        setItems = (items : []) => {
            this.items = items;
            this.recordCount(items.length);
            this.currentPageNumber(1);
            this.maxPages(Math.ceil(items.length / this.itemsPerPage));
            this.loadPage(1);
        }

        pageStart :number = 0;
        pageEnd :number = 0;

        loadPage = (pageNumber: any = 1) => {
            let offset = (pageNumber-1) * this.itemsPerPage;
            this.pageStart = offset;
            let page = [];
            let count = this.items.length;
            if (offset <= count) {
                let limit = offset + Math.min(this.itemsPerPage, count - offset );
                page = this.items.slice(offset,limit);
                this.pageEnd = offset + page.length -1
            }
            for (let i = 0; i<this.columnCount; i++) {
                let start = this.columnsize * i;
                let end = this.columnsize + start;
                let c = page.slice(start, end);
                this.column[i](c);
            }
            this.currentPageNumber(pageNumber);
        };

        changePage = (move: number) => {
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

        gotoPage = (pageNo: number) => {
            this.currentPageNumber(pageNo);
            this.loadPage(pageNo);
        }

        gotoLast = () => {
            let pageNo = this.maxPages();
            this.currentPageNumber(pageNo);
            this.loadPage(pageNo);
        }

        gotoIndex = (idx : number) => {
            let pageNo = Math.ceil((idx+1) / this.itemsPerPage);
            if (pageNo != this.currentPageNumber()) {
                this.currentPageNumber(pageNo);
                this.loadPage(pageNo);
            }
        }




    }
}