var PeanutContent;
(function (PeanutContent) {
    class contentBlockComponent {
        constructor(params) {
            this.controller = null;
            this.isHtml = ko.observable(true);
            this.editing = ko.observable(false);
            this.editorTitle = ko.observable('');
            this.textBuffer = ko.observable('');
            this.editorModalId = ko.observable('');
            this.htmlEditorId = ko.observable('');
            this.textEditorId = ko.observable('');
            this.codeEditorId = ko.observable('');
            this.codeView = ko.observable(false);
            this.codeSource = ko.observable('');
            this.editorInitialized = false;
            this.editHtml = () => {
                this.showModal();
            };
            this.edit = () => {
                if (this.isHtml()) {
                    let id = this.htmlEditorId();
                    let editor = tinymce.get(id);
                    let content = this.contentSource() === null ? '' : this.contentSource();
                    editor.setContent(content);
                }
                else {
                    let text = this.contentSource();
                    this.textBuffer(text);
                }
                this.showModal();
                if (this.controller) {
                    this.controller.sendNotification(this.contentId, 'edit');
                }
                this.editing(true);
            };
            this.cancel = () => {
                if (this.editing()) {
                    if (this.controller) {
                        this.controller.sendNotification(this.contentId, 'cancelled');
                    }
                    this.editorModal.hide();
                    this.editing(false);
                }
            };
            this.open = (contentObservable) => {
                this.contentSource = contentObservable;
                if (this.controller) {
                    this.controller.sendNotification(this.contentId, 'opened');
                }
            };
            this.save = () => {
                this.postContent();
                if (this.controller) {
                    this.controller.sendNotification(this.contentId, 'saved');
                }
                this.editorModal.hide();
                this.editing(false);
            };
            this.getEditorContent = () => {
                tinymce.triggerSave();
                let element = document.getElementById(this.htmlEditorId());
                return element ? element.value : '';
            };
            this.postContent = () => {
                let me = this;
                if (this.isHtml()) {
                    tinymce.triggerSave();
                    let content = me.getEditorContent();
                    me.contentSource(content);
                }
                else {
                    let text = this.textBuffer();
                    this.contentSource(text);
                }
            };
            this.showModal = () => {
                if (!this.editorModal) {
                    let id = this.editorModalId();
                    let modalElement = document.getElementById(id);
                    modalElement.addEventListener('hidden.bs.modal', this.cancel);
                    this.editorModal = new bootstrap.Modal(document.getElementById(id));
                }
                this.codeView(false);
                this.editorModal.show();
            };
            this.viewCode = () => {
                let content = this.getEditorContent();
                this.codeSource(content);
                this.codeView(true);
            };
            this.hideCode = () => {
                let id = this.htmlEditorId();
                let editor = tinymce.get(id);
                let content = this.codeSource();
                editor.setContent(content);
                this.codeView(false);
            };
            this.cancelCode = () => {
                this.codeView(false);
            };
            this.initEditor = () => {
                if (!this.isHtml()) {
                    return;
                }
                this.editorInitialized = true;
                let mh = Math.floor((Math.max(document.documentElement.clientHeight || 0, window.innerHeight || 0)) / 100) * 50;
                let id = this.htmlEditorId();
                tinymce.init({
                    selector: '#' + id,
                    toolbar: "undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link | image",
                    plugins: "image imagetools link lists code paste",
                    min_height: mh,
                    default_link_target: "_blank",
                    document_base_url: Peanut.Helper.getHostUrl() + '/',
                    branding: false,
                    paste_word_valid_elements: "b,strong,i,em,h1,h2,h3,p,a,ul,li",
                    relative_urls: false,
                    convert_urls: false,
                    remove_script_host: false
                });
            };
            let me = this;
            if (!params) {
                console.error('contentBlockComponent: Params not defined');
                return;
            }
            if (!params.source) {
                console.error('contentBlockComponent: Parameter "source" is required');
                return;
            }
            if (!ko.isObservable(params.source)) {
                console.error('contentBlockComponent: Parameter "source" must be a knowckout obscrvable');
                return;
            }
            me.contentSource = params.source;
            if ((params.canedit) && ko.isObservable(params.canedit)) {
                me.canedit = params.canedit;
            }
            else {
                me.canedit = ko.observable(false);
            }
            if (!params.id) {
                console.error('contentBlockComponent: Parameter "id" is required');
                return;
            }
            me.contentId = params.id;
            if (params.controller) {
                me.controller = params.controller;
                me.controller.register(me.contentId, me);
            }
            if (params.contenttype) {
                me.isHtml(params.contenttype === 'html');
            }
            if (params.title) {
                me.editorTitle(params.title);
            }
            let editorId = me.contentId + '-html';
            me.htmlEditorId(editorId);
            me.textEditorId(me.contentId + '-text');
            me.editorModalId(me.contentId + '-modal');
            me.codeEditorId(me.contentId + '-code');
        }
    }
    PeanutContent.contentBlockComponent = contentBlockComponent;
})(PeanutContent || (PeanutContent = {}));
//# sourceMappingURL=contentBlockComponent.js.map