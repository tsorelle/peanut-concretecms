/// <reference path='../../../../pnut/core/ViewModelBase.ts' />
/// <reference path='../../../../typings/knockout/index.d.ts' />

namespace Peanut {

    export class PagerTestViewModel extends Peanut.ViewModelBase {
        currentPage = ko.observable(1);
        maxPages = ko.observable(10);
        changePage = (move: number) => {
            let current = this.currentPage() + move;
            this.currentPage(current);
        }


        init(successFunction?: () => void) {
            let me = this;
            Peanut.logger.write('PagerTest Init');
            me.application.registerComponents('@pnut/pager', () => {
                me.bindDefaultSection();
                successFunction();
            });
        }
    }
}
