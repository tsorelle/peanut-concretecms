var Peanut;
(function (Peanut) {
    class BootstrapFAUiHelper {
        constructor() {
            this.showMessage = (message, id, container, modal = true) => {
                let span = container.find('#' + id);
                span.text(message);
                this.showModal(container);
            };
            this.hideMessage = (container) => {
                this.hideModal(container);
            };
            this.showModal = (container) => {
                if (navigator.appName == 'Microsoft Internet Explorer') {
                    container.removeClass('fade');
                }
                container.modal('show');
            };
            this.hideModal = (container) => {
                container.modal('hide');
            };
            this.getResourceList = () => {
                return ['@lib:fontawesome'];
            };
            this.getFramework = () => {
                return 'Bootstrap';
            };
            this.getVersion = () => {
                return 3;
            };
            this.getFontSet = () => {
                return 'FA';
            };
        }
    }
    Peanut.BootstrapFAUiHelper = BootstrapFAUiHelper;
})(Peanut || (Peanut = {}));
//# sourceMappingURL=BootstrapFAUiHelper.js.map