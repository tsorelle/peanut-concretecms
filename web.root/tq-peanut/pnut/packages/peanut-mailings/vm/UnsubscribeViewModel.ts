/// <reference path="../../../../pnut/core/ViewModelBase.ts" />
/// <reference path='../../../../typings/knockout/knockout.d.ts' />
/// <reference path='../../../../pnut/core/Peanut.d.ts' />

namespace PeanutMailings {

    interface IUnsubscribeResponse {
        removed : any;
        personName : string;
        listName : string;
        subscriptionsLink : string;
        message: string;
        translations: any;
    }

    export class UnsubscribeViewModel extends Peanut.ViewModelBase {
        // observables
        message = ko.observable('');
        subscriptionsLink = ko.observable('');
        init(successFunction?: () => void) {
            let me = this;
            let uid = this.getRequestVar('uid');
            let listId = this.getRequestVar('listId');
            me.showLoadWaiter();
            me.services.executeService('peanut.peanut-mailings::UnsubscribeList',{uid: uid,listId: listId},
                function(serviceResponse: Peanut.IServiceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let response = <IUnsubscribeResponse>serviceResponse.Value;
                        me.addTranslations(response.translations);
                        me.message(response.message);
                        me.subscriptionsLink(response.subscriptionsLink + '?uid='+uid);
                    }
                })
                .fail(function () {
                    let trace = me.services.getErrorInformation();
                })
                .always(function () {
                    me.application.hideWaiter();
                    me.bindDefaultSection();
                });
            successFunction();
        }
    }
}
