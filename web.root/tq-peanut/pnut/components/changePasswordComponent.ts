/// <reference path='../../typings/knockout/index.d.ts' />
/// <reference path='../js/multiSelectObservable.ts' />
namespace Peanut {
    export class changePasswordComponent {
        // observables
        errorMessage = ko.observable('');
        pwdvisible = ko.observable(false);
        controlIdShow = ko.observable('change-password-visible');
        controlIdHide = ko.observable('change-password-hidden');
        label = ko.observable('');

        password : KnockoutObservable<string>;

        // include constructor if any params used
        constructor(params: any) {
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
                me.controlIdHide(params.id+'-hidden');
                me.controlIdShow(params.id+'-visible');
            }
            if (params.label) {
                me.label(params.label);
            }

            me.password('');
            me.pwdvisible(false);
        }

        toggleVisibility = () => {
            let state = this.pwdvisible();
            state = !state;
            this.pwdvisible(state);
        }
    }
}