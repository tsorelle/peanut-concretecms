/// <reference path="./peanutcontent.d.ts" />
namespace PeanutContent {
    export class contentController implements IContentController {
        contentOwner: PeanutContent.IContentOwner;
        components: IContentComponent[] = [];

        constructor(owner: IContentOwner) {
            this.contentOwner = owner;
        }

        initialize = () => {
            this.components.forEach((component: IContentComponent) => {
                component.initEditor();
            } )
        }

        register = (contentId: string, component: PeanutContent.IContentComponent) => {
            this.components.push(component);
        }

        cancel(contentId: string) {
            let component = this.getComponent(contentId);
            component.cancel();
        }

        cancelAll = () => {
            this.components.forEach((component: IContentComponent) => {
                component.cancel();
            } )
        }

        getComponent = (contentId) => {
            return this.components.find((component: IContentComponent) => {
                return component.contentId == contentId;
            });
        }

        save = (contentId: string) => {
            let component = this.getComponent(contentId);
            component.save();
        }

        saveAll() {
            this.components.forEach((component: IContentComponent) => {
                component.save();
            } )
        }
        sendNotification = (contentId: string, message: string) => {
            this.contentOwner.handleContentNotification(contentId,message);
        }

    }
}