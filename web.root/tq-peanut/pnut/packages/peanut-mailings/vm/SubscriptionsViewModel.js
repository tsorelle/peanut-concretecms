var PeanutMailings;
(function (PeanutMailings) {
    class SubscriptionsViewModel extends Peanut.ViewModelBase {
        init(successFunction) {
            let me = this;
            Peanut.logger.write('Subscriptions Init');
            me.bindDefaultSection();
            successFunction();
        }
    }
    PeanutMailings.SubscriptionsViewModel = SubscriptionsViewModel;
})(PeanutMailings || (PeanutMailings = {}));
//# sourceMappingURL=SubscriptionsViewModel.js.map