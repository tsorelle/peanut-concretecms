var PeanutContent;
(function (PeanutContent) {
    class imageBlockComponent {
        constructor(params) {
            this.imageSrc = ko.observable('');
            this.useUploadFileName = true;
            this.editing = ko.observable(false);
            this.editorTitle = ko.observable('Upload or replace');
            this.editorModalId = ko.observable('modal-image');
            this.imageUploadId = ko.observable('upload-image');
            this.selected = ko.observable(false);
            this.invalidFile = ko.observable(false);
            this.newimage = ko.observable(false);
            this.hideimage = ko.observable(false);
            this.noimage = ko.observable(false);
            this.owner = null;
            this.getImageFilename = () => {
                let imageName = this.imageName();
                if (!imageName) {
                    return '';
                }
                let ext = imageName.indexOf('.') === -1 ? null : imageName.split('.').pop();
                if (!ext) {
                    imageName += '.jpg';
                }
                return imageName;
            };
            this.save = () => {
                let filelist = Peanut.Helper.getSelectedFiles(this.imageUploadId());
                if (filelist.length) {
                    if (this.useUploadFileName) {
                        let file = filelist[0];
                        this.imageName(file.name);
                    }
                    let imageFile = this.getImageFilename();
                    if (imageFile.length == 0) {
                        alert(this.useUploadFileName ? "No image name assigned." : "No content id assigned");
                    }
                    this.owner.onFileSelected(filelist, this.imagePath, this.getImageFilename());
                    this.imageSrc(this.imagePath + '/' + imageFile);
                }
                this.editorModal.hide();
                this.newimage(true);
            };
            this.edit = () => {
                this.showModal();
                this.editing(true);
            };
            this.cancel = () => {
                this.editorModal.hide();
            };
            this.showModal = () => {
                if (!this.editorModal) {
                    let id = this.editorModalId();
                    let modalElement = document.getElementById(id);
                    modalElement.addEventListener('hidden.bs.modal', this.cancel);
                    this.editorModal = new bootstrap.Modal(document.getElementById(id));
                }
                this.editorModal.show();
            };
            this.validateSelectedFile = () => {
                let element = document.getElementById(this.imageUploadId());
                if (element && element.value) {
                    let imageName = element.value;
                    let ext = imageName.indexOf('.') === -1 ? null : imageName.split('.').pop();
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
            };
            this.onFileSelection = () => {
                let validation = this.validateSelectedFile();
                this.selected(validation === 1);
                this.invalidFile(validation === -1);
            };
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
                me.editorModalId('modal-' + params.id);
                me.imageUploadId('upload-' + params.id);
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
                        params.contentId : ko.observable(params.contentId);
                this.useUploadFileName = false;
            }
            else if (params.imagename) {
                me.imageName =
                    ko.isObservable(params.imagename) ?
                        params.imagename : ko.observable(params.imagename);
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
                me.editorTitle('Upload or replace: ' + params.title);
            }
        }
    }
    PeanutContent.imageBlockComponent = imageBlockComponent;
})(PeanutContent || (PeanutContent = {}));
//# sourceMappingURL=imageBlockComponent.js.map