var Peanut;
(function (Peanut) {
    class ModalTestViewModel extends Peanut.ViewModelBase {
        constructor() {
            super(...arguments);
            this.showing = ko.observable(false);
            this.onShowClick = () => {
                if (!this.testModal) {
                    this.testModal = this.showModal('test-modal');
                }
                else {
                    this.showModal(this.testModal);
                }
            };
            this.onSaveChanges = () => {
                this.hideModal(this.testModal);
                alert('Save changes');
            };
            this.confirmTest = () => {
                this.showModal('#confirm-modal-test');
            };
            this.onConfirmTestOk = () => {
                this.hideModal('#confirm-modal-test');
                alert('OK Clicked');
            };
        }
        init(successFunction) {
            console.log('Init ModalTest');
            let me = this;
            me.application.registerComponents(['@pnut/modal-confirm'], () => {
                me.bindDefaultSection();
                successFunction();
            });
        }
    }
    Peanut.ModalTestViewModel = ModalTestViewModel;
})(Peanut || (Peanut = {}));
//# sourceMappingURL=ModalTestViewModel.js.map