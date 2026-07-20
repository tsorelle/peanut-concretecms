var Peanut;
(function (Peanut) {
    class changePasswordComponent {
        constructor(params) {
            this.errorMessage = ko.observable('');
            this.pwdvisible = ko.observable(false);
            this.controlIdShow = ko.observable('change-password-visible');
            this.controlIdHide = ko.observable('change-password-hidden');
            this.label = ko.observable('');
            this.toggleVisibility = () => {
                let state = this.pwdvisible();
                state = !state;
                this.pwdvisible(state);
            };
            let me = this;
            if (!params) {
                console.error('changePasswordComponent: Params not defined');
                return;
            }
            if (!params.password) {
                console.error('changePasswordComponent: Parameter "password" is required');
                return;
            }
            me.password = params.password;
            if (params.id) {
                me.controlIdHide(params.id + '-hidden');
                me.controlIdShow(params.id + '-visible');
            }
            if (params.label) {
                me.label(params.label);
            }
            me.password('');
            me.pwdvisible(false);
        }
    }
    Peanut.changePasswordComponent = changePasswordComponent;
})(Peanut || (Peanut = {}));
//# sourceMappingURL=changePasswordComponent.js.map