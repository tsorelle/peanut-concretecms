/// <reference path='../../typings/knockout/index.d.ts' />
namespace Peanut {
    export class listPageLoader {
        items : [];
        list = ko.observableArray([]);
        itemsPerPage = 6;
        recordCount = ko.observable(0);
        currentPageNumber = ko.observable(1);
        maxPages = ko.observable(2);

        public constructor(itemsPerPage : number = 6) {
            this.itemsPerPage = itemsPerPage;
        }

        setItems = (items: []) => {
            this.items = items;
            this.recordCount(items.length);
            this.currentPageNumber(1);
            this.maxPages(Math.ceil(items.length / this.itemsPerPage));
            this.loadPage(1);
        }

        loadPage = (pageNumber: any = 1) => {
            let offset = (pageNumber-1) * this.itemsPerPage;
            let page = [];
            let docCount = this.items.length;
            if (offset <= docCount) {
                let limit = offset + Math.min(this.itemsPerPage, docCount - offset );
                for (let i=offset;i<limit;i++) {
                    page.push(this.items[i]);
                }
            }
            this.list(page);
            this.currentPageNumber(pageNumber);
        };

        changePage = (move: number) => {
            let current = this.currentPageNumber() + move;
            if (current < 1) {
                current = 1;
            }
            this.currentPageNumber(current);
            this.loadPage(current);
        };

    }
}