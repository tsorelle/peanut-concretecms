/**
 * Created by Terry on 5/7/2017.
 */

// required for all view models:
/// <reference path='../../../../pnut/core/ViewModelBase.ts' />
/// <reference path='../../../../typings/knockout/index.d.ts' />
/// <reference path='../../../../typings/bootstrap-5/js/dist/modal.d.ts' />

namespace Peanut {

    export class ServiceTestViewModel  extends Peanut.ViewModelBase {
        itemName = ko.observable('');
        itemId = ko.observable(1);
        contextValue = ko.observable();
        languageA = ko.observable('');
        languageB = ko.observable('');


        init(successFunction?: () => void) {
            console.log('Init ServiceTest');
            this.contextValue(this.getVmContext());
            let me = this;
            me.addTranslation('test', 'Un prueba de traducadora');
            me.addTranslation('thing-plural', 'thingies');
            me.addTranslation('save-modal-message', 'Do you want to save changes now?');

            me.bindDefaultSection();
            successFunction();
        }

        onService = () => {
            let me = this;
            let testerName = this.getPageVarialble('tester');
            me.showWaiter('Testing service');
            let request = {"tester" : testerName};
            me.services.executeService('PeanutTest::HelloWorld', request,
                function (serviceResponse: Peanut.IServiceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let response = serviceResponse.Value;
                        me.addTranslations(response.translations);
                        me.languageA(me.translate('hello','Hello'));
                        me.languageB(me.translate('world'));
                        alert(response.message);
                    }
                }
            ).fail(() => {
                alert('OOPS!!!!!!!!!!')
            }).always(() => {
                me.application.hideWaiter();
            });
        };
    }
}
