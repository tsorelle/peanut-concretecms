var Peanut;
(function (Peanut) {
    class BootstrapFiveFAUiHelper {
        constructor() {
            this.modals = {};
            this.showMessage = (message, id, container, modal = true) => {
                Peanut.WaitMessage.show(message, id);
            };
            this.hideMessage = (container) => {
                Peanut.WaitMessage.hide();
            };
            this.getModal = (modal) => {
                if (typeof modal === 'string' || modal instanceof String) {
                    if (modal.charAt(0) === '#') {
                        modal = modal.substring(1);
                    }
                    let obj = this.modals[modal];
                    if (!obj) {
                        let ele = document.getElementById(modal);
                        if (ele === null) {
                            console.log('Error: cannot find element ' + modal);
                        }
                        else {
                            obj = new bootstrap.Modal(ele, {
                                backdrop: 'static',
                                keyboard: false
                            });
                            this.modals[modal] = obj;
                        }
                    }
                    return obj;
                }
                return modal;
            };
            this.showModal = (modal) => {
                modal = this.getModal(modal);
                if (!modal) {
                    return null;
                }
                modal.show();
                return modal;
            };
            this.hideModal = (modal) => {
                modal = this.getModal(modal);
                modal.hide();
            };
            this.getResourceList = () => {
                return ['@lib:fontawesome'];
            };
            this.getFramework = () => {
                return 'Bootstrap';
            };
            this.getVersion = () => {
                return 5;
            };
            this.getFontSet = () => {
                return 'FA';
            };
        }
    }
    Peanut.BootstrapFiveFAUiHelper = BootstrapFiveFAUiHelper;
})(Peanut || (Peanut = {}));
//# sourceMappingURL=BootstrapFiveFAUiHelper.js.map