var Mailboxes;
(function (Mailboxes) {
    class MailboxesViewModel extends Peanut.ViewModelBase {
        init(successFunction) {
            let me = this;
            Peanut.logger.write('Mailboxes form Init');
            me.application.loadResources([
                '@pnut/ViewModelHelpers.js',
                '@pkg/mailboxes/MailboxListObservable.js'
            ], () => {
                me.mailboxes = new Mailboxes.MailboxListObservable(me);
                me.application.registerComponents(['@pnut/modal-confirm', '@pkg/mailboxes/mailbox-manager'], () => {
                    me.mailboxes.getMailboxListWithTranslations(() => {
                        me.bindDefaultSection();
                        successFunction();
                    });
                });
            });
        }
    }
    Mailboxes.MailboxesViewModel = MailboxesViewModel;
})(Mailboxes || (Mailboxes = {}));
//# sourceMappingURL=MailboxesViewModel.js.map