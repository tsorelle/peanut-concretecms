/// <reference path="../../../../pnut/core/ViewModelBase.ts" />
/// <reference path='../../../../typings/knockout/index.d.ts' />
/// <reference path='../../../../pnut/core/Peanut.d.ts' />
/// <reference path='../../../../typings/tinymce/tinymce.d.ts' />
/// <reference path='../../../../pnut/js/ViewModelHelpers.ts' />
/// <reference path="../js/peanutcontent.d.ts" />
namespace PeanutContent {

    // noinspection JSUnusedGlobalSymbols
    /*** Required in owning ViewModel init() ***
     *	me.application.loadResources([
     *		'@lib:tinymce',
     *		'@pnut/ViewModelHelpers.js'], () => {
     *		me.application.registerComponents([
     *   			'@pnut/modal-confirm',
     *   			'@pnut/clean-html'
     *   			'@pkg/peanut-content/content-block'
     *   			], () => {
     *			me.bindDefaultSection();
     *			successFunction();
     *		});
     *	});
     ***/

    export class contentBlockComponent implements PeanutContent.IContentComponent {
        contentId: string;
        contentSource: KnockoutObservable<string>;
        controller: IContentController = null;

        isHtml = ko.observable(true);
        editing = ko.observable(false);
        canedit : KnockoutObservable<boolean>;

        editorModal : any;
        editorTitle = ko.observable('');
        textBuffer = ko.observable('');

        editorModalId = ko.observable('');
        htmlEditorId = ko.observable('');
        textEditorId = ko.observable('')
        codeEditorId = ko.observable('');

        codeView = ko.observable(false);
        codeSource = ko.observable('');

        editorInitialized = false;

        constructor(params: any) {
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
                me.controller.register(me.contentId,me);
            }

            if (params.contenttype) {
                me.isHtml(params.contenttype === 'html')
            }

            if (params.title) {
                me.editorTitle(params.title)
            }

            let editorId = me.contentId+'-html';
            me.htmlEditorId(editorId);
            me.textEditorId(me.contentId+'-text');
            me.editorModalId(me.contentId+'-modal');
            me.codeEditorId(me.contentId+'-code')
        }

        editHtml = () => {
            this.showModal();

        }


        edit = () => {
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
                this.controller.sendNotification(this.contentId,'edit');
            }
            this.editing(true);
        }

        cancel = () => {
            if (this.editing()) {
                if (this.controller) {
                    this.controller.sendNotification(this.contentId, 'cancelled');
                }
                this.editorModal.hide();
                this.editing(false);
            }
        }

        open = (contentObservable: KnockoutObservable<string>) => {
            this.contentSource = contentObservable;
            if (this.controller) {
                this.controller.sendNotification(this.contentId,'opened');
            }
        }

        save = () => {
            this.postContent();
            if (this.controller) {
                this.controller.sendNotification(this.contentId,'saved');
            }
            this.editorModal.hide()
            this.editing(false);
        }

        getEditorContent = () => {
            tinymce.triggerSave();
            let element = <HTMLInputElement> document.getElementById(this.htmlEditorId());
            return element ? element.value : '';
        }

        postContent = () => {
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
        }

        showModal = ()  => {
            if (!this.editorModal) {
                let id = this.editorModalId();
                let modalElement = document.getElementById(id);
                modalElement.addEventListener('hidden.bs.modal',this.cancel);
                this.editorModal = new bootstrap.Modal(document.getElementById(id));
            }
            this.codeView(false);
            this.editorModal.show();
        }

        viewCode = () => {
            let content = this.getEditorContent();
            this.codeSource(content);
            this.codeView(true);
        }

        hideCode = () => {
            let id = this.htmlEditorId();
            let editor = tinymce.get(id);
            let content = this.codeSource();
            editor.setContent(content);
            this.codeView(false);
        }
        cancelCode = () => {
            this.codeView(false);
        }

        initEditor = () => {
            if (!this.isHtml()) {
                return;
            }
            // calculate min_hight as 50% of view port height
            this.editorInitialized = true;
            let mh = Math.floor((Math.max(document.documentElement.clientHeight || 0, window.innerHeight || 0)) / 100) * 50;
            let id = this.htmlEditorId();
            tinymce.init({
                selector: '#' + id,
                toolbar: "undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link | image",
                plugins: "image imagetools link lists code paste",
                min_height: mh,
                default_link_target: "_blank",
                document_base_url : Peanut.Helper.getHostUrl() + '/',
                branding: false,
                paste_word_valid_elements: "b,strong,i,em,h1,h2,h3,p,a,ul,li",
                relative_urls : false,
                convert_urls: false,
                remove_script_host : false
            });
        }
    }
}