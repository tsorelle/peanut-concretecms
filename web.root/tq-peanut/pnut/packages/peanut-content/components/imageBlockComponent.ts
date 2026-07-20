/// <reference path="../../../../pnut/core/ViewModelBase.ts" />
/// <reference path='../../../../typings/knockout/index.d.ts' />
/// <reference path='../../../../pnut/core/Peanut.d.ts' />
/// <reference path='../../../../typings/tinymce/tinymce.d.ts' />
/// <reference path='../../../../pnut/js/ViewModelHelpers.ts' />
/// <reference path="../js/peanutcontent.d.ts" />
namespace PeanutContent {

    /*** Required in owning ViewModel init() ***
     *	me.application.loadResources([
     *		'@pnut/ViewModelHelpers.js'], () => {
     *		me.application.registerComponents([
     *   			'@pkg/peanut-content/image-block'
     *   			], () => {
     *			me.bindDefaultSection();
     *			successFunction();
     *		});
     *	});
     ***/

    export class imageBlockComponent {
        imageSrc = ko.observable('');
        imagePath : string;
        contentId: KnockoutObservable<string>;
        imageName: KnockoutObservable<string>;
        // imageFileName : KnockoutObservable<string>;
        useUploadFileName = true;
        editing = ko.observable(false);
        canedit : KnockoutObservable<boolean>;

        editorModal : any;
        editorTitle = ko.observable('Upload or replace');
        editorModalId = ko.observable('modal-image');
        imageUploadId = ko.observable('upload-image');
        selected = ko.observable(false);
        invalidFile = ko.observable(false);
        newimage = ko.observable(false);
        hideimage = ko.observable(false);
        noimage = ko.observable(false);

        owner: IImageComponentOwner = null;

        constructor(params: any) {
            let me = this;

            if (!params) {
                console.error('imageBlockComponent: Params not defined');
                return;
            }
            if (!params.imagepath) {
                console.error('imageBlockComponent: Parameter "imagepath" is required');
                return;
            }

            if (!(params.imagename || params.contentId)) {
                console.error('imageBlockComponent: Parameter "imagename" or "contentId" is required');
                return;
            }

            if (params.imagename && !ko.isObservable(params.imagename)) {
                console.error('imageBlockComponent: Parameter "imagename" must be an observable');
                return;
            }

            if (params.id) {
                me.editorModalId('modal-'+ params.id);
                me.imageUploadId('upload-'+params.id);
            }

            me.imagePath = (ko.isObservable(params.imagepath)) ?
                params.imagepath() : params.imagepath;

            me.imagePath = (me.imagePath === null) ? '/assets/img' : me.imagePath.trim();
            if (me.imagePath.length == 0) {
                me.imagePath = '/assets/img';
            }

            if (params.contentId) {
                me.imageName =
                    ko.isObservable(params.contentId) ?
                        params.contentId : ko.observable(params.contentId)
                this.useUploadFileName = false;
            }
            else if (params.imagename) {
                me.imageName =
                    ko.isObservable(params.imagename) ?
                        params.imagename : ko.observable(params.imagename)
            }

            if (!me.imageName) {
                throw 'Image name not assigned.';
            }

            let imageName = me.imageName().trim();
            me.imageName(imageName);
            if (imageName.length > 0) {
                let imgFile = me.getImageFilename();
                me.imageSrc(me.imagePath + '/' + imgFile);
            }

            if (params.owner) {
                me.owner = params.owner();
            }

            if (params.canedit && params.owner) {
                me.canedit = params.canedit;
            }
            else {
                me.canedit = ko.observable(false);
            }

            if (params.title) {
                me.editorTitle(
                    'Upload or replace: ' + params.title)
            }
        }

        getImageFilename = () => {
            let imageName = this.imageName();
            if (!imageName) {
                return '';
            }
            let ext =  imageName.indexOf('.') === -1 ? null : imageName.split('.').pop();
            if (!ext) {
                imageName += '.jpg';
            }
            return imageName;
        }

        save = () => {
            let filelist = Peanut.Helper.getSelectedFiles(this.imageUploadId());
            if (filelist.length) {
                if (this.useUploadFileName) {
                    let file = filelist[0];
                    this.imageName(file.name);
                }
                let imageFile = this.getImageFilename();
                if (imageFile.length == 0) {
                    alert (
                        this.useUploadFileName ? "No image name assigned." : "No content id assigned"
                    );
                }
                this.owner.onFileSelected(filelist,this.imagePath,this.getImageFilename());
                this.imageSrc(this.imagePath + '/' + imageFile);
            }
            this.editorModal.hide();
            this.newimage(true);
       }

        edit = () => {
            this.showModal();
            this.editing(true);
        }

        cancel = () => {
            this.editorModal.hide();
        }
        showModal = ()  => {
            if (!this.editorModal) {
                let id = this.editorModalId();
                let modalElement = document.getElementById(id);
                modalElement.addEventListener('hidden.bs.modal',this.cancel);
                this.editorModal = new bootstrap.Modal(document.getElementById(id));
            }
            this.editorModal.show();
        }

        validateSelectedFile = () => {
            let element = <HTMLInputElement> document.getElementById(this.imageUploadId());
            if (element && element.value) {
                let imageName = element.value;
                let ext =  imageName.indexOf('.') === -1 ? null : imageName.split('.').pop();

                switch (ext.toLowerCase()) {
                    case 'jpg':
                        return 1;
                    case 'jpeg':
                        return 1;
                    case 'png':
                        return 1;
                    case 'gif':
                        return 1;
                    default:
                        return -1;
                }
            }
            return 0;
        }

        onFileSelection = () => {
            let validation = this.validateSelectedFile();
            this.selected(validation === 1);
            this.invalidFile(validation === -1);
        }
    }
}