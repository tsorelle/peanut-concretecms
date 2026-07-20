/// <reference path="../../../../pnut/core/ViewModelBase.ts" />
/// <reference path='../../../../typings/knockout/index.d.ts' />
/// <reference path='../../../../pnut/core/Peanut.d.ts' />
/// <reference path='../js/contentController.ts' />

namespace PeanutContent {

    interface IContentItem {
        id : any;
        description : string;
        format : string;
        content : string;
        active: any;
    }

    export class ContentManagerViewModel extends Peanut.ViewModelBase
        implements IContentOwner, IImageComponentOwner {
        // observables
        content = ko.observable('');
        canedit = ko.observable(true);
        controller: PeanutContent.contentController;

        init(successFunction?: () => void) {
            let me = this;
            Peanut.logger.write('Content Manager Init');

            me.application.loadResources([
                '@pkg/peanut-content/contentController.js',
                '@lib:tinymce',
                '@pnut/ViewModelHelpers.js'], () => {
                me.application.registerComponents([
                    '@pnut/modal-confirm',
                    '@pnut/clean-html',
                    '@pkg/peanut-content/content-block',
                    '@pkg/peanut-content/image-block'
                ], () => {
                    me.controller = new PeanutContent.contentController(me);
                    me.getContent(1,()=> {
                        me.bindDefaultSection();
                        successFunction();
                    });
                });
            });
        }

        getContent =  (id : any, successFunction?: () => void) => {
            let me = this;
            me.application.hideServiceMessages();
            me.application.showWaiter('Getting content...');
            // single statement example
            me.services.executeService('Peanut.content::GetContent',id,
                function(serviceResponse: Peanut.IServiceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        me.application.hideWaiter();
                        let contentItem = <IContentItem>serviceResponse.Value;
                        me.content(contentItem.content);
                        if (successFunction) {
                            successFunction()
                        }
                    }

                }
            ).fail(function () {
                me.services.getErrorInformation();
                me.application.hideWaiter();
            });

        }
        handleContentNotification(contentId: string, message: string) {
            // console.log(contentId = ': '+message);
        }

        afterDatabind = () => {
            this.controller.initialize();
        }
        onFileSelected(files: any, imagePath: string, imageName: string) {
            // alert('File selected: ' + imagePath + '/' + imageName);
            let me=this;
            let request : IImageUploadRequest = {
                imageurl: imagePath,
                filename: imageName
            }
            me.showWaitMessage('Uploading image');
            me.services.postForm( 'peanut.content::UploadImage', request, files, null,
                function (serviceResponse: Peanut.IServiceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {

                    }
                    else {
                    }
                }).fail(() => {
                   //  let trace = me.services.getErrorInformation();
                }).always(() => {
                    me.application.hideWaiter();
                });


        }
    }
}
