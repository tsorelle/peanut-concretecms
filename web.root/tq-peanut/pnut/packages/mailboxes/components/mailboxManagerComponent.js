var Mailboxes;
(function (Mailboxes) {
    class mailboxManagerComponent {
        constructor(params) {
            this.test = ko.observable('test');
            this.testList = ko.observableArray();
            this.onListChanged = (mailboxes) => {
            };
            this.insertId = 0;
            this.mailboxId = ko.observable('');
            this.mailboxCode = ko.observable('');
            this.mailboxName = ko.observable('');
            this.mailboxDescription = ko.observable('');
            this.mailboxEmail = ko.observable('');
            this.mailboxPublic = ko.observable(true);
            this.mailboxPublished = ko.observable(true);
            this.formHeading = ko.observable('');
            this.editMode = ko.observable('');
            this.mailboxDescriptionHasError = ko.observable(false);
            this.mailboxEmailHasError = ko.observable(false);
            this.mailboxNameHasError = ko.observable(false);
            this.mailboxCodeHasError = ko.observable(false);
            this.submitChanges = (box) => {
                let me = this;
                me.hideForm();
                me.application.hideServiceMessages();
                me.owner().showActionWaiter(me.editMode(), 'mailbox-entity');
                me.services.executeService('peanut.Mailboxes::UpdateMailbox', box, function (serviceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        me.mailboxes.setMailboxes(serviceResponse.Value);
                    }
                }).fail(function () {
                    let trace = me.services.getErrorInformation();
                }).always(function () {
                    me.application.hideWaiter();
                });
            };
            this.dropMailbox = (box) => {
                let me = this;
                me.hideForm();
                me.application.hideServiceMessages();
                me.owner().showActionWaiter('delete', 'mailbox-entity');
                me.services.executeService('peanut.Mailboxes::DeleteMailbox', box.mailboxcode, function (serviceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        me.mailboxes.setMailboxes(serviceResponse.Value);
                    }
                }).fail(function () {
                    let trace = me.services.getErrorInformation();
                }).always(function () {
                    me.application.hideWaiter();
                });
            };
            this.editMailbox = (box) => {
                let me = this;
                me.clearValidation();
                me.editMode('update');
                me.mailboxId(box.id);
                me.mailboxCode(box.mailboxcode);
                me.mailboxName(box.displaytext);
                me.mailboxEmail(box.address);
                me.mailboxPublic(box.public == '1');
                me.mailboxPublished(box.published == '1');
                me.mailboxDescription(box.description);
                me.formHeading("Edit mailbox: " + box.mailboxcode);
                me.showForm();
            };
            this.newMailbox = () => {
                let me = this;
                me.clearValidation();
                me.editMode('add');
                me.mailboxId('0');
                me.mailboxCode('');
                me.mailboxName('');
                me.mailboxEmail('');
                me.mailboxDescription('');
                me.mailboxPublic(true);
                me.mailboxPublished(false);
                me.formHeading('New mailbox');
                me.showForm();
            };
            this.clearValidation = () => {
                let me = this;
                me.mailboxCodeHasError(false);
                me.mailboxDescriptionHasError(false);
                me.mailboxEmailHasError(false);
                me.mailboxDescriptionHasError(false);
                me.mailboxNameHasError(false);
            };
            this.createMailboxDto = () => {
                let me = this;
                let valid = true;
                let box = {
                    id: me.mailboxId(),
                    mailboxcode: me.mailboxCode(),
                    displaytext: me.mailboxName(),
                    address: me.mailboxEmail(),
                    description: me.mailboxDescription(),
                    public: me.mailboxPublic() ? 1 : 0,
                    published: me.mailboxPublished() ? 1 : 0,
                    active: 1
                };
                if (box.mailboxcode == '') {
                    me.mailboxCodeHasError(true);
                    valid = false;
                }
                if (box.displaytext == '') {
                    me.mailboxNameHasError(true);
                    valid = false;
                }
                let emailOk = Peanut.Helper.endsWith(box.address, '@distribution.mail');
                if (!emailOk) {
                    emailOk = Peanut.Helper.ValidateEmail(box.address);
                }
                me.mailboxEmailHasError(!emailOk);
                if (!emailOk) {
                    valid = false;
                    me.mailboxEmailHasError(true);
                }
                if (valid) {
                    return box;
                }
                return null;
            };
            this.confirmRemoveMailbox = (box) => {
                let me = this;
                me.tempMailbox = box;
                me.mailboxCode(box.mailboxcode);
                me.showConfirmForm();
            };
            let me = this;
            if (!params) {
                console.error('maibox-manager: Params not defined in mailboxManagerComponent');
                return;
            }
            if (!params.owner) {
                console.error('maibox-manager: Owner parameter required for mailboxManagerComponent');
                return;
            }
            me.owner = params.owner;
            me.test('hello');
            let ownerVm = params.owner();
            me.application = ownerVm.getApplication();
            me.bootstrapVersion = ownerVm.bootstrapVersion;
            me.services = ownerVm.getServices();
            me.mailboxes = ownerVm.mailboxes;
            me.mailboxes.subscribe(this.onListChanged);
        }
        hideForm() {
            Peanut.ui.helper.hideModal("#mailbox-update-modal");
        }
        showForm() {
            let me = this;
            me.clearValidation();
            Peanut.ui.helper.showModal("#mailbox-update-modal");
        }
        hideConfirmForm() {
            Peanut.ui.helper.hideModal("#confirm-delete-modal");
        }
        showConfirmForm() {
            let me = this;
            Peanut.ui.helper.showModal("#confirm-delete-modal");
        }
        updateMailbox() {
            let me = this;
            let box = me.createMailboxDto();
            if (box) {
                me.submitChanges(box);
            }
        }
        removeMailbox() {
            let me = this;
            me.hideConfirmForm();
            me.dropMailbox(me.tempMailbox);
        }
    }
    Mailboxes.mailboxManagerComponent = mailboxManagerComponent;
})(Mailboxes || (Mailboxes = {}));
//# sourceMappingURL=mailboxManagerComponent.js.map