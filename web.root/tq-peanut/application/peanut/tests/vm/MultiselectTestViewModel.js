var Peanut;
(function (Peanut) {
    class MultiselectTestViewModel extends Peanut.ViewModelBase {
        constructor() {
            super(...arguments);
            this.messageText = ko.observable('This is a Multiselect test vm');
            this.multiSelectTestItems = [
                {
                    code: 'thing1',
                    description: 'Thing number 1',
                    id: 1,
                    name: 'Thing one'
                },
                {
                    code: 'thing2',
                    description: 'Thing number 2',
                    id: 2,
                    name: 'Thing two'
                },
                {
                    code: 'thing3',
                    description: 'Thing number 2',
                    id: 3,
                    name: 'Thing three'
                },
                {
                    code: 'thing4',
                    description: 'Thing number 4',
                    id: 4,
                    name: 'Thing four'
                },
            ];
            this.multiSelectListTestSelected = [
                {
                    code: 'thing2',
                    description: 'Thing number 2',
                    id: 2,
                    name: 'Thing two'
                },
                {
                    code: 'thing3',
                    description: 'Thing number 2',
                    id: 3,
                    name: 'Thing three'
                },
            ];
        }
        init(successFunction) {
            let me = this;
            console.log('Init MultiselectTest');
            me.application.registerComponents('@pnut/selected-list,@pnut/multi-select', () => {
                me.application.loadResources([
                    '@pnut/multiSelectObservable'
                ], () => {
                    me.multiSelectTestController = new Peanut.multiSelectObservable(me.multiSelectTestItems, [2, 3]);
                    me.bindDefaultSection();
                    successFunction();
                });
            });
        }
    }
    Peanut.MultiselectTestViewModel = MultiselectTestViewModel;
})(Peanut || (Peanut = {}));
//# sourceMappingURL=MultiselectTestViewModel.js.map