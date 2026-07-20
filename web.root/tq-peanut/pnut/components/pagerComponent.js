var Peanut;
(function (Peanut) {
    class pagerComponent {
        constructor(params) {
            this.forwardLabel = ko.observable('Next');
            this.backwardLabel = ko.observable('Previous');
            this.pageLabel = ko.observable('Page');
            this.ofLabel = ko.observable('of');
            this.pagerFormat = 'Page %s of %s';
            this.nextPage = () => {
                this.onClick(1);
            };
            this.prevPage = () => {
                this.onClick(-1);
            };
            let me = this;
            if (!params) {
                console.error('pagerComponent: Params not defined in translateComponent');
                return;
            }
            if (!params.click) {
                console.error('pagerComponent: Parameter "click" is required');
                return;
            }
            me.onClick = params.click;
            if (!params.page) {
                console.error('pagerComponent: Parameter "page" is required');
                return;
            }
            me.currentPage = params.page;
            if (!params.max) {
                console.error('pagerComponent: Parameter "max" is required');
                return;
            }
            me.maxPages = params.max;
            if (params.waiter) {
                me.showSpinner = params.waiter;
            }
            else {
                me.showSpinner = ko.observable(false);
            }
            if (params.owner) {
                let translator = params.owner();
                me.forwardLabel(translator.translate('label-next', 'Next'));
                me.backwardLabel(translator.translate('label-previous', 'Previous'));
                me.pageLabel(translator.translate('label-page', 'Page'));
                me.ofLabel(translator.translate('label-of', 'of'));
            }
        }
    }
    Peanut.pagerComponent = pagerComponent;
})(Peanut || (Peanut = {}));
//# sourceMappingURL=pagerComponent.js.map