var Peanut;
(function (Peanut) {
    class searchListObservable {
        constructor(columnCount, maxInColumn) {
            this.searchValue = ko.observable('');
            this.selectionCount = ko.observable(0);
            this.hasMore = ko.observable(false);
            this.hasPrevious = ko.observable(false);
            this.selectionList = [];
            this.currentPage = 1;
            this.foundCount = ko.observable(0);
            this.nextPage = () => {
                let me = this;
                if (me.currentPage < me.lastPage) {
                    me.currentPage = me.currentPage + 1;
                }
                me.hasMore(me.lastPage > me.currentPage);
                me.hasPrevious(me.currentPage > 1);
                me.parseColumns();
            };
            this.previousPage = () => {
                let me = this;
                if (me.currentPage > 1) {
                    me.currentPage = me.currentPage - 1;
                }
                me.hasMore(me.lastPage > me.currentPage);
                me.hasPrevious(me.currentPage > 1);
                me.parseColumns();
            };
            let me = this;
            me.columnCount = columnCount;
            me.maxInColumn = maxInColumn;
            me.itemsPerPage = columnCount * maxInColumn;
            for (let i = 1; i <= columnCount; i++) {
                me.selectionList[i] = ko.observableArray([]);
            }
        }
        reset() {
            let me = this;
            me.searchValue('');
            me.selectionCount(0);
            me.foundCount(0);
        }
        setList(list) {
            let me = this;
            me.itemList = list;
            let itemCount = list.length;
            if (itemCount == 1 && list[0].Value === null) {
                me.foundCount(itemCount - 1);
            }
            else {
                me.foundCount(itemCount);
            }
            me.selectionCount(itemCount);
            me.currentPage = 1;
            me.lastPage = Math.ceil(itemCount / me.itemsPerPage);
            me.hasMore(me.lastPage > 1);
            me.hasPrevious(false);
            me.parseColumns();
        }
        parseColumns() {
            let me = this;
            let columns = [];
            let currentColumn = 0;
            let startIndex = me.itemsPerPage * (me.currentPage - 1);
            let lastIndex = startIndex + me.itemsPerPage;
            if (lastIndex >= me.itemList.length) {
                lastIndex = me.itemList.length - 1;
            }
            columns[0] = [];
            let j = 0;
            for (let i = startIndex; i <= lastIndex; i++) {
                columns[currentColumn][j] = me.itemList[i];
                if ((i + 1) % me.maxInColumn == 0) {
                    ++currentColumn;
                    columns[currentColumn] = [];
                    j = 0;
                }
                else {
                    j = j + 1;
                }
            }
            for (let i = 0; i < me.columnCount; i++) {
                me.selectionList[i + 1](columns[i]);
            }
        }
    }
    Peanut.searchListObservable = searchListObservable;
})(Peanut || (Peanut = {}));
//# sourceMappingURL=searchListObservable.js.map