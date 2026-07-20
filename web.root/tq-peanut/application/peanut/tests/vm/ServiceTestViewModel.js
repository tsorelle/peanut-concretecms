var Peanut;
(function (Peanut) {
    class ServiceTestViewModel extends Peanut.ViewModelBase {
        constructor() {
            super(...arguments);
            this.itemName = ko.observable('');
            this.itemId = ko.observable(1);
            this.contextValue = ko.observable();
            this.languageA = ko.observable('');
            this.languageB = ko.observable('');
            this.onService = () => {
                let me = this;
                let testerName = this.getPageVarialble('tester');
                me.showWaiter('Testing service');
                let request = { "tester": testerName };
                me.services.executeService('PeanutTest::HelloWorld', request, function (serviceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let response = serviceResponse.Value;
                        me.addTranslations(response.translations);
                        me.languageA(me.translate('hello', 'Hello'));
                        me.languageB(me.translate('world'));
                        alert(response.message);
                    }
                }).fail(() => {
                    alert('OOPS!!!!!!!!!!');
                }).always(() => {
                    me.application.hideWaiter();
                });
            };
        }
        init(successFunction) {
            console.log('Init ServiceTest');
            this.contextValue(this.getVmContext());
            let me = this;
            me.addTranslation('test', 'Un prueba de traducadora');
            me.addTranslation('thing-plural', 'thingies');
            me.addTranslation('save-modal-message', 'Do you want to save changes now?');
            me.bindDefaultSection();
            successFunction();
        }
    }
    Peanut.ServiceTestViewModel = ServiceTestViewModel;
})(Peanut || (Peanut = {}));
//# sourceMappingURL=ServiceTestViewModel.js.map