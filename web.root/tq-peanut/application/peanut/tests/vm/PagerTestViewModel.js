var Peanut;
(function (Peanut) {
    class PagerTestViewModel extends Peanut.ViewModelBase {
        constructor() {
            super(...arguments);
            this.currentPage = ko.observable(1);
            this.maxPages = ko.observable(10);
            this.changePage = (move) => {
                let current = this.currentPage() + move;
                this.currentPage(current);
            };
        }
        init(successFunction) {
            let me = this;
            Peanut.logger.write('PagerTest Init');
            me.application.registerComponents('@pnut/pager', () => {
                me.bindDefaultSection();
                successFunction();
            });
        }
    }
    Peanut.PagerTestViewModel = PagerTestViewModel;
})(Peanut || (Peanut = {}));
//# sourceMappingURL=PagerTestViewModel.js.map