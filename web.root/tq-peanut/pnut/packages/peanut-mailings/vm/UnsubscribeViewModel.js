var PeanutMailings;
(function (PeanutMailings) {
    class UnsubscribeViewModel extends Peanut.ViewModelBase {
        constructor() {
            super(...arguments);
            this.message = ko.observable('');
            this.subscriptionsLink = ko.observable('');
        }
        init(successFunction) {
            let me = this;
            let uid = this.getRequestVar('uid');
            let listId = this.getRequestVar('listId');
            me.showLoadWaiter();
            me.services.executeService('peanut.peanut-mailings::UnsubscribeList', { uid: uid, listId: listId }, function (serviceResponse) {
                if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                    let response = serviceResponse.Value;
                    me.addTranslations(response.translations);
                    me.message(response.message);
                    me.subscriptionsLink(response.subscriptionsLink + '?uid=' + uid);
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
    PeanutMailings.UnsubscribeViewModel = UnsubscribeViewModel;
})(PeanutMailings || (PeanutMailings = {}));
//# sourceMappingURL=UnsubscribeViewModel.js.map