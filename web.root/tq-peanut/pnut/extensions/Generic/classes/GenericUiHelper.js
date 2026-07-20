var Peanut;
(function (Peanut) {
    class GenericUiHelper {
        constructor() {
            this.modals = {};
            this.showMessage = (message, id, container, modal = true) => {
                Peanut.WaitMessage.show(message, id);
            };
            this.hideMessage = (container) => {
                Peanut.WaitMessage.hide();
            };
            this.getResourceList = () => {
                return [];
            };
            this.getFramework = () => {
                return 'Generic';
            };
            this.getVersion = () => {
                return 1;
            };
            this.getFontSet = () => {
                return 'peanut';
            };
        }
    }
    Peanut.GenericUiHelper = GenericUiHelper;
})(Peanut || (Peanut = {}));
//# sourceMappingURL=GenericUiHelper.js.map