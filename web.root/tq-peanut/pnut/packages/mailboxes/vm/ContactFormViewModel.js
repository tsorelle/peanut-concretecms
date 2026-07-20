var Mailboxes;
(function (Mailboxes) {
    class ContactFormViewModel extends Peanut.ViewModelBase {
        constructor() {
            super(...arguments);
            this.headerMessage = ko.observable('Send a Message');
            this.fromAddress = ko.observable('');
            this.fromName = ko.observable('');
            this.messageSubject = ko.observable('');
            this.messageBody = ko.observable('');
            this.formVisible = ko.observable(false);
            this.mailboxList = ko.observableArray([]);
            this.mailboxSelectSubscription = null;
            this.selectedMailbox = ko.observable(null);
            this.subjectError = ko.observable('');
            this.bodyError = ko.observable('');
            this.fromNameError = ko.observable('');
            this.fromAddressError = ko.observable('');
            this.mailboxSelectError = ko.observable('');
            this.selectRecipientCaption = ko.observable('');
            this.enabled = ko.observable(true);
            this.userIsAnonymous = ko.observable(false);
            this.messageForm = {
                subjectError: ko.observable(false),
                bodyError: ko.observable(false),
                subject: ko.observable(''),
                editor: null
            };
            this.getMailbox = (doneFunction) => {
                let me = this;
                me.application.hideServiceMessages();
                let request = {
                    mailbox: me.mailboxCode,
                    context: me.getVmContext()
                };
                me.services.executeService('peanut.Mailboxes::GetContactForm', request, function (serviceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                            let response = serviceResponse.Value;
                            me.addTranslations(response.translations);
                            me.selectRecipientCaption(response.translations['mail-select-recipient']);
                            me.fromAddress(response.fromAddress);
                            me.fromName(response.fromName);
                            me.userIsAnonymous(response.fromAddress.trim() == '');
                            if (me.mailboxSelectSubscription !== null) {
                                me.mailboxSelectSubscription.dispose();
                                me.mailboxSelectSubscription = null;
                            }
                            if (response.mailboxList.length > 1) {
                                me.mailboxCode = 'all';
                                let inquiries = response.mailboxList.find((box) => {
                                    return box.mailboxcode == 'inquiries';
                                });
                                inquiries = inquiries || null;
                                me.selectedMailbox(inquiries);
                                me.mailboxSelectSubscription = me.selectedMailbox.subscribe(me.onMailBoxSelected);
                                me.headerMessage(response.translations['mail-header-select']);
                            }
                            else {
                                let mailbox = response.mailboxList.pop();
                                me.mailboxCode = mailbox.mailboxcode;
                                me.selectedMailbox(mailbox);
                                me.headerMessage(response.translations['mail-header-send'] + ': ' + mailbox.displaytext);
                            }
                            me.mailboxList(response.mailboxList);
                            me.formVisible(true);
                        }
                        else {
                            me.formVisible(false);
                        }
                    }
                }).fail(() => {
                    let trace = me.services.getErrorInformation();
                }).always(() => {
                    if (doneFunction) {
                        doneFunction();
                    }
                });
            };
            this.createMessage = (token = null) => {
                let me = this;
                if (!me.protector.likelyHuman()) {
                    return null;
                }
                me.mailboxSelectError('');
                me.subjectError('');
                me.bodyError('');
                me.fromAddressError('');
                me.fromNameError('');
                if (me.mailboxCode === 'all') {
                    let box = this.selectedMailbox();
                    if (!box) {
                        me.mailboxSelectError(': ' + me.translate('mail-error-recipient'));
                        return false;
                    }
                    me.mailboxCode = box.mailboxcode;
                }
                let message = {
                    toName: '',
                    mailboxCode: me.mailboxCode,
                    fromName: me.fromName(),
                    fromAddress: me.fromAddress(),
                    subject: me.messageSubject(),
                    body: me.userIsAnonymous() ? me.messageBody() : me.messageForm.editor.getContent(),
                    token: token
                };
                let valid = true;
                if (message.fromAddress.trim() == '') {
                    me.fromAddressError(': ' + me.translate('form-error-your-email-blank'));
                    valid = false;
                }
                else {
                    let fromAddressOk = Peanut.Helper.ValidateEmail(message.fromAddress);
                    if (!fromAddressOk) {
                        me.fromAddressError(': ' + me.translate('form-error-email-invalid'));
                        valid = false;
                    }
                }
                if (message.fromName.trim() == '') {
                    me.fromNameError(': ' + me.translate('form-error-your-name-blank'));
                    valid = false;
                }
                if (message.subject.trim() == '') {
                    me.subjectError(': ' + me.translate('form-error-email-subject-blank'));
                    valid = false;
                }
                if (message.body.trim() == '') {
                    me.bodyError(': ' + me.translate('form-error-email-message-blank'));
                    valid = false;
                }
                if (valid) {
                    return message;
                }
                return null;
            };
            this.sendMessage = () => {
                let me = this;
                let message = me.createMessage();
                if (message) {
                    me.application.hideServiceMessages();
                    me.application.showWaiter(me.translate('wait-sending-message'));
                    me.services.executeService('peanut.Mailboxes::SendContactMessage', message, function (serviceResponse) {
                        if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                            me.headerMessage(me.translate('mail-thanks-message'));
                        }
                        else if (serviceResponse.Value == 'denied') {
                            me.headerMessage('Not able to send your message. Please sign in if you have an account');
                        }
                        me.formVisible(false);
                        window.scrollTo(0, 0);
                    }).fail(function () {
                        let trace = me.services.getErrorInformation();
                    }).always(function () {
                        me.application.hideWaiter();
                        me.protector.start();
                    });
                }
            };
            this.onMailBoxSelected = (selected) => {
                let me = this;
                if (selected) {
                    me.headerMessage(me.translate('mail-header-send') + ':  ' + selected.displaytext);
                }
                else {
                    me.headerMessage(me.translate('mail-header-select'));
                }
            };
        }
        init(successFunction) {
            let me = this;
            Peanut.logger.write('ContactForm Init');
            me.mailboxCode = me.getRequestVar('box', 'all');
            if (me.mailboxCode == 'inqueries') {
                me.mailboxCode = 'inquiries';
            }
            me.showLoadWaiter();
            me.application.loadResources([
                '@pnut/ViewModelHelpers.js',
                '@pnut/htmlEditContainer'
            ], () => {
                me.protector = new Peanut.formProtector();
                me.getMailbox(() => {
                    me.application.hideWaiter();
                    if (me.userIsAnonymous()) {
                        me.protector.start();
                        me.bindDefaultSection();
                        successFunction();
                    }
                    else {
                        me.protector.setEnabled(false);
                        me.messageForm.editor = new Peanut.htmlEditContainer(me);
                        me.messageForm.editor.initialize('messagehtml', () => {
                            me.bindDefaultSection();
                            successFunction();
                        });
                    }
                });
            });
        }
    }
    Mailboxes.ContactFormViewModel = ContactFormViewModel;
})(Mailboxes || (Mailboxes = {}));
//# sourceMappingURL=ContactFormViewModel.js.map