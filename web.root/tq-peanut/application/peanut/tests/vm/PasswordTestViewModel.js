var Peanut;
(function (Peanut) {
    class PasswordTestViewModel extends Peanut.ViewModelBase {
        constructor() {
            super(...arguments);
            this.myPassword = ko.observable('');
            this.showPassword = () => {
                alert(this.myPassword());
            };
        }
        init(successFunction) {
            console.log('Init PasswordTest');
            let me = this;
            me.application.registerComponents('@pnut/change-password', () => {
                me.bindDefaultSection();
                successFunction();
            });
        }
    }
    Peanut.PasswordTestViewModel = PasswordTestViewModel;
})(Peanut || (Peanut = {}));
//# sourceMappingURL=PasswordTestViewModel.js.map