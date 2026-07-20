/// <reference path="../../../../pnut/core/ViewModelBase.ts" />
/// <reference path='../../../../typings/knockout/index.d.ts' />
/// <reference path='../../../../pnut/core/Peanut.d.ts' />

namespace PeanutUsers {

    export class SigninViewModel extends Peanut.ViewModelBase {
        // observables
        username = ko.observable('');
        password = ko.observable('');
        status = ko.observable('ready');
        userfullname= ko.observable('');
        redirectlink = ko.observable('/');
        failed = ko.observable(false);
        errormessage = ko.observable('');
        redirect = '/';

        init(successFunction?: () => void) {
            let me = this;
            Peanut.logger.write('Signin Init');
            const redirect = me.getRequestVar('return') || '/';
            me.redirectlink(redirect);
            me.application.registerComponents('@pnut/change-password', () => {
                me.bindDefaultSection();
                successFunction();
            });
        }

        onSigninRequest = () => {
            let me = this;
            me.failed(false);
            me.status('signing');

            let request = {
                password: this.password().trim(),
                username: this.username().trim()
            }

            if (request.password && request.username) {
                me.services.executeService('peanut.users::Signin', request,
                    function (serviceResponse: Peanut.IServiceResponse) {
                        if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                            let response = serviceResponse.Value;
                            me.status(response.status == 'failed' ? 'ready': response.status)
                            switch (response.status) {
                                case 'ok':
                                    window.location.replace('/');
                                    window.location.href = me.redirectlink();
                                    me.redirectlink(response.redirectlink);
                                    me.userfullname(response.userfullname);
                                    break;
                                case 'failed' :
                                    me.failed(true);
                                    me.username('');
                                    me.password('');
                                    break;
                                case 'error' :
                                    me.errormessage(response.errormessage ? response.errormessage : 'Unknown error');
                                    break;
                            }
                        }
                        else {
                            me.errormessage('Unknown error')
                            me.status('error');
                        }
                    }).fail(() => {
                        me.errormessage('Unknown error')
                        me.status('error');
                        me.services.getErrorInformation();
                    }).always(() => {
                });

            }
            else {
                this.status('ready');
                this.failed(true);
            }
        }

    }
}
