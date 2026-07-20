var PeanutContent;
(function (PeanutContent) {
    class ContentManagerViewModel extends Peanut.ViewModelBase {
        constructor() {
            super(...arguments);
            this.content = ko.observable('');
            this.canedit = ko.observable(true);
            this.getContent = (id, successFunction) => {
                let me = this;
                me.application.hideServiceMessages();
                me.application.showWaiter('Getting content...');
                me.services.executeService('Peanut.content::GetContent', id, function (serviceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        me.application.hideWaiter();
                        let contentItem = serviceResponse.Value;
                        me.content(contentItem.content);
                        if (successFunction) {
                            successFunction();
                        }
                    }
                }).fail(function () {
                    me.services.getErrorInformation();
                    me.application.hideWaiter();
                });
            };
            this.afterDatabind = () => {
                this.controller.initialize();
            };
        }
        init(successFunction) {
            let me = this;
            Peanut.logger.write('Content Manager Init');
            me.application.loadResources([
                '@pkg/peanut-content/contentController.js',
                '@lib:tinymce',
                '@pnut/ViewModelHelpers.js'
            ], () => {
                me.application.registerComponents([
                    '@pnut/modal-confirm',
                    '@pnut/clean-html',
                    '@pkg/peanut-content/content-block',
                    '@pkg/peanut-content/image-block'
                ], () => {
                    me.controller = new PeanutContent.contentController(me);
                    me.getContent(1, () => {
                        me.bindDefaultSection();
                        successFunction();
                    });
                });
            });
        }
        handleContentNotification(contentId, message) {
        }
        onFileSelected(files, imagePath, imageName) {
            let me = this;
            let request = {
                imageurl: imagePath,
                filename: imageName
            };
            me.showWaitMessage('Uploading image');
            me.services.postForm('peanut.content::UploadImage', request, files, null, function (serviceResponse) {
                if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                }
                else {
                }
            }).fail(() => {
            }).always(() => {
                me.application.hideWaiter();
            });
        }
    }
    PeanutContent.ContentManagerViewModel = ContentManagerViewModel;
})(PeanutContent || (PeanutContent = {}));
//# sourceMappingURL=ContentManagerViewModel.js.map