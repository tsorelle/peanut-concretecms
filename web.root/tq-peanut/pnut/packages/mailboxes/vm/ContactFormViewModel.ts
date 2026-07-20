/// <reference path="../../../../pnut/core/ViewModelBase.ts" />
/// <reference path='../../../../typings/knockout/knockout.d.ts' />
/// <reference path='../../../../pnut/core/Peanut.d.ts' />
/// <reference path='../../../../pnut/js/ViewModelHelpers.ts' />
/// <reference path='mailboxes.d.ts' />
// <reference path='../../../../pnut/js/Recaptcha.ts' />


namespace Mailboxes {

    /**
     *   Link example:
     *   https://www.austinquakers.org/about-us/contact-fma?box=(mailbox code)
     *   e.g. https://www.austinquakers.org/about-us/contact-fma?box=calendar
     */

    export class ContactFormViewModel extends Peanut.ViewModelBase {
        // observables
        headerMessage = ko.observable('Send a Message');
        fromAddress = ko.observable('');
        fromName = ko.observable('');
        messageSubject = ko.observable('');
        messageBody = ko.observable('');
        formVisible = ko.observable(false);
        mailboxList = ko.observableArray<IMailBox>([]);
        mailboxSelectSubscription = null;
        selectedMailbox = ko.observable<IMailBox>(null);
        subjectError = ko.observable('');
        bodyError = ko.observable('');
        fromNameError = ko.observable('');
        fromAddressError = ko.observable('');
        mailboxSelectError = ko.observable('');
        selectRecipientCaption = ko.observable('');


        enabled = ko.observable(true);

        userIsAnonymous = ko.observable(false);

        mailboxCode: string;
        // recaptcha: Peanut.Recaptcha;
        protector: Peanut.formProtector;

        messageForm = {
            subjectError: ko.observable(false),
            bodyError: ko.observable(false),
            subject: ko.observable(''),
            editor: null
        };


        init(successFunction?: () => void) {
            let me = this;
            Peanut.logger.write('ContactForm Init');
            me.mailboxCode = me.getRequestVar('box', 'all');
            if (me.mailboxCode == 'inqueries') {
                // oops, correct spelling error!
                me.mailboxCode = 'inquiries';
            }
            me.showLoadWaiter();
            me.application.loadResources([
                '@pnut/ViewModelHelpers.js',
                '@pnut/htmlEditContainer'
                // ,'@pnut/Recaptcha.js'
            ], () => {
                me.protector = new Peanut.formProtector();
                // me.recaptcha = new Peanut.Recaptcha();
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
                        })

                    }

                });
            });
        }

        getMailbox = (doneFunction?: () => void) => {
            let me = this;

            me.application.hideServiceMessages();

            let request = {
                mailbox: me.mailboxCode,
                context: me.getVmContext()
            };
            me.services.executeService('peanut.Mailboxes::GetContactForm', request,
                function (serviceResponse: Peanut.IServiceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                            let response = <IGetContactFormResponse>serviceResponse.Value;
                            // me.recaptcha.setSitekey(response.grsitekey);
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
                                /*
                                                                if (me.userIsAnonymous) {
                                                                    me.selectedMailbox(response.mailboxList[0]);
                                                                }
                                                                else {
                                                                    me.selectedMailbox(null);
                                                                }
                                */
                                let inquiries = response.mailboxList.find((box : IMailBox) => {
                                        return box.mailboxcode == 'inquiries';
                                    }
                                );

                                inquiries = inquiries || null;

                                me.selectedMailbox(inquiries);
                                me.mailboxSelectSubscription = me.selectedMailbox.subscribe(me.onMailBoxSelected);
                                me.headerMessage(response.translations['mail-header-select']);
                            } else {
                                let mailbox = response.mailboxList.pop();
                                me.mailboxCode = mailbox.mailboxcode;
                                me.selectedMailbox(mailbox);
                                me.headerMessage(response.translations['mail-header-send'] + ': ' + mailbox.displaytext);
                            }
                            me.mailboxList(response.mailboxList);
                            me.formVisible(true);
                        } else {
                            me.formVisible(false);
                        }
                    }
                }).fail(() => {
                /** @noinspection PhpUnusedLocalVariableInspection */
                let trace = me.services.getErrorInformation();
            }).always(() => {
                if (doneFunction) {
                    doneFunction();
                }
            });
        };
        createMessage = (token = null) => {
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


            let message = <IMailMessage>{
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
            } else {
                let fromAddressOk = Peanut.Helper.ValidateEmail(message.fromAddress);
                if (!fromAddressOk) {
                    me.fromAddressError(': ' + me.translate('form-error-email-invalid'));
                    valid = false;
                }
            }

            if (message.fromName.trim() == '') {
                me.fromNameError(': ' + me.translate('form-error-your-name-blank')); //
                valid = false;
            }

            if (message.subject.trim() == '') {
                me.subjectError(': ' + me.translate('form-error-email-subject-blank')); //A subject is required
                valid = false;
            }

            if (message.body.trim() == '') {
                me.bodyError(': ' + me.translate('form-error-email-message-blank')); // Message text is required.);
                valid = false;
            }

            if (valid) {
                return message;
            }
            return null;
        };

        sendMessage = () => {
            let me = this;
            let message = me.createMessage();
            if (message) {
                        // message.token = token;
                        me.application.hideServiceMessages();
                        me.application.showWaiter(me.translate('wait-sending-message')); //'Sending message...');
                        me.services.executeService('peanut.Mailboxes::SendContactMessage', message,
                            function (serviceResponse: Peanut.IServiceResponse) {
                                if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                                    me.headerMessage(me.translate('mail-thanks-message'));//'Thanks for your message.')
                                } else if (serviceResponse.Value == 'denied') {
                                    me.headerMessage('Not able to send your message. Please sign in if you have an account');
                                }
                                me.formVisible(false);
                                window.scrollTo(0, 0);
                            }
                        ).fail(function () {
                            /** @noinspection PhpUnusedLocalVariableInspection */
                            let trace = me.services.getErrorInformation();
                        }).always(function () {
                            me.application.hideWaiter();
                            me.protector.start();
                        });
                    }


        }

        onMailBoxSelected = (selected: IMailBox) => {
            let me = this;
            if (selected) {
                me.headerMessage(me.translate('mail-header-send') + ':  ' + selected.displaytext); // Send a message to
            }
            else {
                me.headerMessage(me.translate('mail-header-select')); //'Send a message: (please select recipient)');
            }
        }
    }
}

