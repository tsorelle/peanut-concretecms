var Mailboxes;
(function (Mailboxes) {
    class MailboxListObservable {
        constructor(client) {
            this.list = ko.observableArray([]);
            this.callbacks = [];
            this.subscriptions = [];
            this.downloadMailboxList = (all = true, translations = false, doneFunction) => {
                let me = this;
                let request = {
                    filter: all ? 'all' : false,
                    translations: translations,
                    context: me.owner.getVmContext()
                };
                me.application.hideServiceMessages();
                let translated = (me.owner.translate('mailbox-entity-plural') !== 'mailbox-entity-plural');
                if (translated) {
                    me.owner.showActionWaiter('load', 'mailbox-entity-plural');
                }
                me.services.executeService('peanut.Mailboxes::GetMailboxList', request, function (serviceResponse) {
                    if (translated) {
                        me.application.hideWaiter();
                    }
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let response = serviceResponse.Value;
                        me.owner.addTranslations(response.translations);
                        me.setMailboxes(response.list);
                    }
                }).fail(function () {
                    let trace = me.services.getErrorInformation();
                }).always(function () {
                    if (translated) {
                        me.application.hideWaiter();
                    }
                    if (doneFunction) {
                        doneFunction();
                    }
                });
            };
            this.getUpdatedMailboxList = (doneFunction) => {
                this.downloadMailboxList(true, false);
            };
            this.refreshList = (doneFunction) => {
                let me = this;
                let list = me.list();
                me.suspendSubscriptions();
                me.list([]);
                me.restoreSubscriptions();
                me.list(list);
                doneFunction();
            };
            this.getMailboxList = (doneFunction) => {
                let me = this;
                if (me.list().length == 0) {
                    me.downloadMailboxList(true, false, doneFunction);
                }
                else {
                    me.refreshList(doneFunction);
                }
            };
            this.getMailboxListWithTranslations = (doneFunction) => {
                this.downloadMailboxList(true, false, doneFunction);
            };
            this.setMailboxes = (mailboxes) => {
                let me = this;
                let list = Peanut.Helper.SortByAlpha(mailboxes, 'displaytext');
                me.list(list);
            };
            let me = this;
            me.application = client.getApplication();
            me.services = client.getServices();
            me.owner = client;
        }
        subscribe(callback) {
            let me = this;
            me.callbacks.push(callback);
            let subscription = me.list.subscribe(callback);
            me.subscriptions.push(subscription);
        }
        suspendSubscriptions() {
            let me = this;
            for (let i = 0; i < me.subscriptions.length; i++) {
                me.subscriptions[i].dispose();
            }
            me.subscriptions = [];
        }
        restoreSubscriptions() {
            let me = this;
            for (let i = 0; i < me.callbacks.length; i++) {
                let subscription = me.list.subscribe(me.callbacks[i]);
                me.subscriptions.push(subscription);
            }
        }
    }
    Mailboxes.MailboxListObservable = MailboxListObservable;
})(Mailboxes || (Mailboxes = {}));
//# sourceMappingURL=MailboxListObservable.js.map