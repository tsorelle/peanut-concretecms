var Peanut;
(function (Peanut) {
    class SimpleTestViewModel extends Peanut.ViewModelBase {
        constructor() {
            super(...arguments);
            this.messageText = ko.observable('This is a simple test, just to make sure all the foundational MVVM stuff is working.');
            this.onShowMessage = () => {
                this.application.showMessage('This is a message.');
            };
            this.onShowError = () => {
                this.application.showError('This is an error.');
            };
        }
        init(successFunction) {
            console.log('Init SimpleTest');
            let me = this;
            me.application.loadResources([
                '@lib:fontawesome'
            ], () => {
                me.bindDefaultSection();
                successFunction();
            });
        }
    }
    Peanut.SimpleTestViewModel = SimpleTestViewModel;
})(Peanut || (Peanut = {}));
//# sourceMappingURL=SimpleTestViewModel.js.map