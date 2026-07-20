var PeanutMailings;
(function (PeanutMailings) {
    class MailingFormViewModel extends Peanut.ViewModelBase {
        constructor() {
            super(...arguments);
            this.sendRequest = null;
            this.messageSubject = ko.observable('');
            this.messageBody = ko.observable('');
            this.formVisible = ko.observable(false);
            this.bodyError = ko.observable('');
            this.subjectError = ko.observable('');
            this.mailingListSelectError = ko.observable('');
            this.confirmCaption = ko.observable('');
            this.confirmSendMessage = ko.observable('');
            this.confirmResendMessage = ko.observable('');
            this.sendTest = ko.observable(false);
            this.sendAddress = ko.observable('');
            this.queuePageSize = 10;
            this.currentQueuePage = ko.observable(1);
            this.maxQueuePages = ko.observable(1);
            this.refreshingQueue = ko.observable(false);
            this.mailingListLookup = ko.observableArray([]);
            this.mailingLists = ko.observableArray([]);
            this.mailboxList = ko.observableArray([]);
            this.selectedMailingList = ko.observable(null);
            this.defaultListCode = '';
            this.selectMailingListCaption = ko.observable('Select a mailing list');
            this.messasageFormats = ko.observableArray([
                { Name: 'Html', Value: 'html' },
                { Name: 'Plain text', Value: 'text' }
            ]);
            this.selectedMessageFormat = ko.observable(this.messasageFormats()[0]);
            this.editorView = ko.observable('html');
            this.tab = ko.observable('message');
            this.queueStatus = ko.observable('active');
            this.messageHistory = ko.observableArray([]);
            this.pausedUntil = ko.observable('');
            this.messageRemoveText = ko.observable('');
            this.messageRemoveHeader = ko.observable('');
            this.messageRemoveId = 0;
            this.messageEditForm = {
                messageId: 0,
                subject: ko.observable(''),
                template: ko.observable(''),
                messageText: ko.observable(''),
                bodyError: ko.observable(''),
                subjectError: ko.observable('')
            };
            this.listEditForm = {
                listId: ko.observable(0),
                mailboxCode: '',
                selectedMailbox: ko.observable(null),
                active: ko.observable(true),
                code: ko.observable(''),
                name: ko.observable(''),
                description: ko.observable(''),
                codeError: ko.observable(''),
                nameError: ko.observable(''),
                cansubscribe: ko.observable(true),
                adminonly: ko.observable(false)
            };
            this.previousMessage = { 'listId': -1, 'messageText': '' };
            this.currentModal = '';
            this.showConfirmation = (modalId) => {
                let me = this;
                me.currentModal = '#confirm-' + modalId + '-modal';
                Peanut.ui.helper.showModal(me.currentModal);
            };
            this.hideConfirmation = () => {
                let me = this;
                Peanut.ui.helper.hideModal(me.currentModal);
                me.currentModal = '';
            };
            this.getMailingLists = (doneFunction) => {
                let me = this;
                let request = null;
                me.application.hideServiceMessages();
                me.services.executeService('peanut.peanut-mailings::GetMailingLists', request, function (serviceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let response = serviceResponse.Value;
                        me.addTranslations(response.translations);
                        me.sendAddress(response.userEmail);
                        me.confirmCaption(me.translate('confirm-caption'));
                        me.confirmResendMessage(me.translate('mailing-confirm-resend'));
                        me.confirmSendMessage(me.translate('mailing-confirm-send'));
                        me.defaultListCode = response.defaultListCode;
                        me.assignEmailLists(response.emailLists);
                        me.formVisible(true);
                        me.selectedMessageFormat.subscribe(me.onFormatChange);
                    }
                    else {
                        me.formVisible(false);
                    }
                }).fail(() => {
                    let trace = me.services.getErrorInformation();
                }).always(() => {
                    if (doneFunction) {
                        doneFunction();
                    }
                });
            };
            this.assignEmailLists = (emailLists) => {
                let me = this;
                let defaultList = null;
                let lookup = emailLists.filter((item) => {
                    if (item.active == 1) {
                        if (item.code == me.defaultListCode || !defaultList) {
                            defaultList = item;
                        }
                        return true;
                    }
                    return false;
                });
                me.mailingListLookup(lookup);
                me.mailingLists(emailLists);
                me.selectedMailingList(defaultList);
            };
            this.onFormatChange = (format) => {
                let me = this;
                if (format.Value == 'text' && me.editorView() == 'html') {
                    me.changeEditMode('text');
                }
                else if (format.Value == 'html' && me.editorView() == 'text') {
                    me.changeEditMode('html');
                }
            };
            this.changeEditMode = (format) => {
                let me = this;
                me.updateMessageBody();
                me.editorView(format);
            };
            this.updateMessageBody = () => {
                this.htmlEditor.save();
                let messageHtml = document.getElementById('messagehtml');
                let value = messageHtml.value;
                this.messageBody(value);
            };
            this.createMessage = () => {
                let me = this;
                me.subjectError('');
                me.bodyError();
                if (me.editorView() == 'html') {
                    me.updateMessageBody();
                }
                let list = me.selectedMailingList();
                let listId = list ? list.id : 0;
                let message = {
                    listId: listId,
                    subject: me.messageSubject(),
                    messageText: me.messageBody(),
                    testAddress: me.sendTest() ? me.sendAddress().trim() : null,
                    contentType: me.selectedMessageFormat().Value,
                    sendTest: me.sendTest()
                };
                let valid = true;
                if (message.subject.trim() == '') {
                    me.subjectError(': ' + me.translate('form-error-email-subject-blank'));
                    valid = false;
                }
                if (message.messageText.trim() == '') {
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
                me.sendRequest = me.createMessage();
                if (me.sendRequest) {
                    if (me.sendRequest.listId) {
                        let modalId = (me.previousMessage.listId == me.sendRequest.listId &&
                            me.previousMessage.messageText == me.sendRequest.messageText) ? 'resend' : 'send';
                        me.showConfirmation(modalId);
                    }
                    else {
                        me.doSend();
                    }
                }
            };
            this.doSend = () => {
                let me = this;
                me.hideConfirmation();
                me.application.hideServiceMessages();
                me.showActionWaiterBanner('send', 'mailing-message-entity');
                me.services.executeService('peanut.peanut-mailings::SendMailingListMessage', me.sendRequest, function (serviceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                            if (me.sendRequest.listId) {
                                me.previousMessage = me.sendRequest;
                            }
                        }
                    }
                }).fail(function () {
                    let trace = me.services.getErrorInformation();
                }).always(function () {
                    me.application.hideWaiter();
                });
            };
            this.showMessageTab = () => {
                this.tab('message');
            };
            this.onQueuePaged = (moved) => {
                this.getMessageQueue(this.currentQueuePage() + moved);
            };
            this.refreshQueue = () => {
                let me = this;
                me.getMessageQueue(1);
            };
            this.getMessageQueue = (pageNumber) => {
                let me = this;
                me.refreshingQueue(true);
                if (pageNumber == 1) {
                    me.showWaiter('Getting email history');
                }
                let request = { pageSize: me.queuePageSize, pageNumber: pageNumber };
                me.services.executeService('peanut.peanut-mailings::GetEmailListHistory', request, function (serviceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                            let response = serviceResponse.Value;
                            me.currentQueuePage(pageNumber);
                            me.maxQueuePages(response.maxPages);
                            me.showQueueTab(response);
                        }
                    }
                }).fail(() => {
                    let trace = me.services.getErrorInformation();
                }).always(() => {
                    if (pageNumber == 1) {
                        me.hideWaiter();
                    }
                    me.refreshingQueue(false);
                });
            };
            this.controlQueue = (action) => {
                let me = this;
                me.application.showBannerWaiter('mailing-get-history');
                me.services.executeService('peanut.peanut-mailings::ControlMessageProcess', action, function (serviceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                            let response = serviceResponse.Value;
                            me.currentQueuePage(1);
                            me.showQueueTab(response);
                        }
                    }
                }).fail(() => {
                    let trace = me.services.getErrorInformation();
                }).always(() => {
                    me.application.hideWaiter();
                });
            };
            this.pauseQueue = () => {
                this.controlQueue('pause');
            };
            this.restartQueue = () => {
                this.controlQueue('start');
            };
            this.removeQueuedMessage = (item) => {
                let me = this;
                me.messageRemoveText(me.translate('mailing-remove-queue').replace('%s', item.subject));
                me.messageRemoveHeader(me.translate('mailing-remove-header'));
                me.messageRemoveId = item.messageId;
                Peanut.ui.helper.showModal("#confirm-remove-modal");
            };
            this.doRemoveMessage = () => {
                let me = this;
                Peanut.ui.helper.hideModal("#confirm-remove-modal");
                me.showActionWaiterBanner('remove', 'mailing-message-entity');
                me.services.executeService('peanut.peanut-mailings::RemoveQueuedMessage', me.messageRemoveId, function (serviceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                            let response = serviceResponse.Value;
                            me.currentQueuePage(1);
                            me.showQueueTab(response);
                        }
                    }
                }).fail(() => {
                    let trace = me.services.getErrorInformation();
                }).always(() => {
                    me.application.hideWaiter();
                });
            };
            this.editQueuedMessage = (item) => {
                let me = this;
                me.messageEditForm.messageId = item.messageId;
                me.messageEditForm.subject(item.subject);
                me.services.executeService('peanut.peanut-mailings::GetQueuedMessageText', item.messageId, function (serviceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                            let response = serviceResponse.Value;
                            me.messageEditForm.messageText(response);
                            Peanut.ui.helper.showModal('#edit-message-modal');
                        }
                    }
                }).fail(() => {
                    let trace = me.services.getErrorInformation();
                }).always(() => {
                });
            };
            this.updateQueuedMessage = () => {
                let me = this;
                Peanut.ui.helper.hideModal('#edit-message-modal');
                let request = {
                    messageId: me.messageEditForm.messageId,
                    subject: me.messageEditForm.subject(),
                    messageText: me.messageEditForm.messageText()
                };
                me.showActionWaiterBanner('update', 'mailing-message-entity');
                me.services.executeService('peanut.peanut-mailings::UpdateQueuedMessage', request, function (serviceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                            let response = serviceResponse.Value;
                            me.currentQueuePage(1);
                            me.showQueueTab(response);
                        }
                    }
                }).fail(() => {
                    let trace = me.services.getErrorInformation();
                }).always(() => {
                    me.application.hideWaiter();
                });
            };
            this.showQueueTab = (response) => {
                let me = this;
                me.queueStatus(response.status);
                me.messageHistory(response.items);
                me.pausedUntil(response.pausedUntil);
                me.tab('queue');
            };
            this.showLists = () => {
                let me = this;
                me.tab('lists');
            };
            this.editEmailList = (item) => {
                let me = this;
                me.showEmailListForm(item);
            };
            this.newEmailList = () => {
                let me = this;
                let item = {
                    id: 0,
                    name: '',
                    code: '',
                    active: 1,
                    description: '',
                    mailBox: '',
                    mailboxName: '',
                    cansubscribe: true,
                    adminonly: false
                };
                me.showEmailListForm(item);
            };
            this.valadateEmailList = (item) => {
                let me = this;
                if (item.name.trim() == '') {
                    me.listEditForm.nameError(me.translate('form-error-name-blank'));
                    return false;
                }
                if (item.code.trim() == '') {
                    me.listEditForm.codeError(me.translate('form-error-code-blank'));
                    return false;
                }
                if (item.description.trim() == '') {
                    item.description = item.name;
                }
                return true;
            };
            this.updateEmailList = () => {
                let me = this;
                let request = {
                    id: me.listEditForm.listId(),
                    name: me.listEditForm.name(),
                    code: me.listEditForm.code(),
                    active: me.listEditForm.active() ? 1 : 0,
                    description: me.listEditForm.description(),
                    mailBox: me.listEditForm.selectedMailbox().mailboxcode,
                    cansubscribe: me.listEditForm.cansubscribe() ? 1 : 0,
                    adminonly: me.listEditForm.adminonly() ? 1 : 0
                };
                if (me.valadateEmailList(request)) {
                    Peanut.ui.helper.hideModal('#edit-list-modal');
                    me.showActionWaiterBanner('update', 'mailing-list-entity');
                    me.services.executeService('peanut.peanut-mailings::UpdateMailingList', request, function (serviceResponse) {
                        if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                            if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                                let response = serviceResponse.Value;
                                me.assignEmailLists(response);
                            }
                        }
                    }).fail(() => {
                        let trace = me.services.getErrorInformation();
                    }).always(() => {
                        me.application.hideWaiter();
                    });
                }
            };
            this.showEmailListForm = (item) => {
                let me = this;
                me.application.hideServiceMessages();
                me.listEditForm.description(item.description);
                me.listEditForm.code(item.code);
                me.listEditForm.name(item.name);
                me.listEditForm.listId(item.id);
                me.listEditForm.active(item.active == 1);
                me.listEditForm.mailboxCode = item.mailBox;
                me.listEditForm.cansubscribe(!!item.cansubscribe);
                me.listEditForm.adminonly(!!item.adminonly);
                if (me.mailboxList().length == 0) {
                    me.mailboxes.subscribe(me.onMailboxListChanged);
                }
                me.mailboxes.getMailboxList(() => {
                    Peanut.ui.helper.showModal('#edit-list-modal');
                });
            };
            this.onMailboxListChanged = (mailboxes) => {
                let me = this;
                let filtered = mailboxes.filter((box) => {
                    return box.active == 1;
                });
                me.mailboxList(filtered);
                if (me.listEditForm.mailboxCode) {
                    let list = me.mailboxList();
                    let mailboxItem = list.find((mailbox) => {
                        return mailbox.mailboxcode == me.listEditForm.mailboxCode;
                    });
                    me.listEditForm.selectedMailbox(mailboxItem);
                }
                else {
                    me.listEditForm.selectedMailbox(null);
                }
            };
            this.showMailboxes = () => {
                let me = this;
                me.mailboxes.getMailboxList(() => {
                    me.tab('mailboxes');
                });
            };
            this.onTabChange = () => {
                this.application.hideServiceMessages();
            };
        }
        init(successFunction) {
            let me = this;
            Peanut.logger.write('MailingForm Init');
            me.tab.subscribe(me.onTabChange);
            me.showLoadWaiter();
            me.application.loadResources([
                '@lib:tinymce',
                '@pnut/ViewModelHelpers.js',
                '@pnut/htmlEditContainer',
                '@pkg/mailboxes/MailboxListObservable.js'
            ], () => {
                me.htmlEditor = new Peanut.htmlEditContainer(me);
                me.htmlEditor.includeDesignTools();
                me.htmlEditor.includeFileControls();
                me.mailboxes = new Mailboxes.MailboxListObservable(me);
                me.application.registerComponents([
                    '@pnut/modal-confirm',
                    '@pnut/clean-html',
                    '@pkg/mailboxes/mailbox-manager',
                    '@pnut/pager'
                ], () => {
                    me.getMailingLists(() => {
                        me.application.hideWaiter();
                        me.bindDefaultSection();
                        let startTab = me.getRequestVar('tab');
                        if (!startTab) {
                            startTab = me.getPageVarialble('start-tab', 'message');
                        }
                        switch (startTab) {
                            case 'queue':
                                me.getMessageQueue(1);
                                break;
                            case 'mailboxes':
                                me.showMailboxes();
                                break;
                            case 'lists':
                                me.showLists();
                                break;
                        }
                        me.htmlEditor.initEditor('messagehtml', () => {
                            me.htmlEditor.confirmEditorInit();
                            successFunction();
                        });
                    });
                });
            });
        }
        fetchEditorContent() {
            alert('fetchEditorContent');
        }
        saveEditorContent() {
            alert('saveEditorContent');
        }
    }
    PeanutMailings.MailingFormViewModel = MailingFormViewModel;
})(PeanutMailings || (PeanutMailings = {}));
//# sourceMappingURL=MailingFormViewModel.js.map