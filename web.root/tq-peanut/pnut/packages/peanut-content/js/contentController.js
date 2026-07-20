var PeanutContent;
(function (PeanutContent) {
    class contentController {
        constructor(owner) {
            this.components = [];
            this.initialize = () => {
                this.components.forEach((component) => {
                    component.initEditor();
                });
            };
            this.register = (contentId, component) => {
                this.components.push(component);
            };
            this.cancelAll = () => {
                this.components.forEach((component) => {
                    component.cancel();
                });
            };
            this.getComponent = (contentId) => {
                return this.components.find((component) => {
                    return component.contentId == contentId;
                });
            };
            this.save = (contentId) => {
                let component = this.getComponent(contentId);
                component.save();
            };
            this.sendNotification = (contentId, message) => {
                this.contentOwner.handleContentNotification(contentId, message);
            };
            this.contentOwner = owner;
        }
        cancel(contentId) {
            let component = this.getComponent(contentId);
            component.cancel();
        }
        saveAll() {
            this.components.forEach((component) => {
                component.save();
            });
        }
    }
    PeanutContent.contentController = contentController;
})(PeanutContent || (PeanutContent = {}));
//# sourceMappingURL=contentController.js.map