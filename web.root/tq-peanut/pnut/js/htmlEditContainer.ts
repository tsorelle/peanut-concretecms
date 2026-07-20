/// <reference path='../../pnut/core/Peanut.d.ts' />
/// <reference path='../../pnut/js/ViewModelHelpers.ts' />
/// <reference path='../../typings/tinymce/tinymce.d.ts' />

namespace Peanut {
    export class htmlEditContainer implements Peanut.IContentEditor
    {
        readonly configEmail = 1;
        readonly configContent = 2;
        private application : IPeanutClient;
        public editorInitialized = false;
        private selector : string;

        private configurationType = this.configEmail;
        private showCodeButton = false;
        private showImageTools = false;
        private showColorTools = false;
        private showTableTools = false;
        private showFontSizes  = false;
        private showFileControls  = false;
        private showHr = false;
        private additionalOptions : { [key: string]: any } = null;
        private services: ServiceBroker;
        private editController : IEditController;

        constructor(owner?: any)
        {
            let me = this;

            if (owner) {
                me.services = owner.getServiceBroker();
                me.application = owner.getApplication();
            }
        }

        setEditController(controller: IEditController) : void {
            let me = this;
            me.editController = controller;
        }


        /**
         * Set up the editor for use in composing email messages.
         * URLs are expanded to full URL, e.g. https://mydomain.com/path/to/whatever
         */
        // public configureForEmail = (includeDesignTools = false) => {
        public configureForEmail = () : void => {
            this.configurationType = this.configEmail;
        }

        /**
         * Set up editor to compose content for use on the current web site.
         * URLs are explanded to full path on the local server, e.g. /path/to/whatever
         */
        public configureForContentEditing = () => {
            this.configurationType = this.configContent
            this.includeDesignTools();
        }

        /**
         * Hide 'code' button
         */
        public hideHtml = ()=> {
            this.showCodeButton = false;
        }

        /**
         * Include fore and background color buttons
         */
        public showColorButtons  = ()=> {
            this.showColorTools = true;
        }

        /**
         * Include
         */
        public showImageButtons  = ()=> {
            this.showImageTools = true;
        }

        public includeFontSizing = () => {
            this.showFontSizes = true;
        }

        public includeTableTools = () => {
            this.showTableTools = true;
        }

        /**
         * Add design tools to menu: Image tools, Color tools, code button
         */
        public includeDesignTools = () => {
            this.showImageTools = true;
            this.showColorTools = true;
            this.showCodeButton = true;
            this.showTableTools = true;
            this.showFontSizes  = true;
            this.showHr = true;
        }

        public includeFileControls = () => {
            this.showFileControls = true;
        }

        /**
         * Add or overwrite any TinyMce options,  See:
         * https://www.tiny.cloud/docs/tinymce/5/configure/
         *
         * @param newOptions
         */
        public addOptions = (newOptions : { [key: string]: any }) => {
            if (newOptions.selector !== undefined) {
                console.log("WARNING: Cannot assign selector in custorm options. Use InitEditor()");
                delete newOptions.selector;
            }
            if (newOptions.setup !== undefined) {
                console.log("WARNING: Cannot assign setup in custorm options. Use InitEditor()");
                delete newOptions.setup;
            }

            if (newOptions.toolbar !== undefined && newOptions.plugins === undefined) {
                console.log('WARNING: If toolbar is changed you must also provide the plugins option.');
                delete newOptions.toolbar;
            }

            if (newOptions.toolbar === undefined && newOptions.plugins !== undefined) {
                console.log('WARNING: If plugins is changed you must also provide the toolbar option.');
                delete newOptions.plugins;
            }

            this.additionalOptions = newOptions;
        }


        /**
         * Initialize the obervable object by loading script dependencies and optionally call followup function
         *
         * @param selector
         *      String ID of textarea used by Tiny mce if NULL skip editor initialization and
         *      use initEditor() later. Delayed initalization if sometimes required.
         * @param onInitialized
         *      Function to invoke on successful editor initialization. If used, should include the statement,
         *      instance.confirmEditorInit()  where instance is an instance of htmlEditor to test for success.
         */
        initialize = (selector: string, onInitialized?: () => void) => {
            let me = this;
            me.selector = selector
            me.application.loadResources([
                '@lib:tinymce',
                '@pnut/ViewModelHelpers.js'
            ], () => {
                if (selector) {
                    me.initEditor(selector, onInitialized);
                }
                else if (onInitialized) {
                    onInitialized();
                }
            });
        }

        /**
         * Called as "editor.on" function after editor initialization.  Test for success.
         */
        confirmEditorInit = () => {
            let ed = tinymce.get(this.selector);
            let me = this;
            me.editorInitialized = (ed !== null);
        }

        isEmpty() : boolean {
            let me = this;
            let content = me.getContent();
            content = content
                .replace(/<[^>]*>/g, "")
                .replace(/\s/g, "").
                replace(/&nbsp;/g , "");
            return content == '';
        }
        hasUnsavedChanges = () => {
            let me = this;
            let dirty = tinymce.get(me.selector).isDirty() ;
            if (dirty) {
                dirty = !me.isEmpty();
            }
            return dirty;
        }

        setDirty(enabled: boolean) : void {
            let me = this;
            tinymce.get(me.selector).setDirty(enabled);
        }

        enableUnsavedWarning = () => {
            let me = this;
            window.addEventListener('beforeunload', function (event) {
                if (me.hasUnsavedChanges()) {
                    event.preventDefault();
                    /* event.returnValue = '';  for older browsers not suppoert on modern browsers*/
                    // noinspection JSDeprecatedSymbols
                    event.returnValue = 'WARNING: You have unsaved changes.';
                }
            });
/*
            window.onbeforeunload = function () {
                if (me.hasUnsavedChanges()) {
                    // most modern browsers insert their own message instead.
                    return "**** WARNING: You have unsaved changes. If you reload or leave this page your changes will be lost. ****";
                }
            };
*/
        }


        onSave = (editor: any) => {
            if (editor) {
                this.editController.saveDocument();

            }
        }
        onSaveAs = (editor: any) => {
            if (editor) {
                this.editController.saveDocumentAs();

            }
        }
        onFetchContent = (editor ) => {
            if (editor) {
                this.editController.openDocument();
            }
        }

        onSetUp = (editor) => {
            // editor.on('init', onInitialized);

            let me = this;
            editor.ui.registry.addMenuItem('getcontent', {
                text: 'Load content...',
                onAction:  me.onFetchContent
            });
        }

        onNewDocument = (editor) => {
            let me = this;
            if (editor) {
                me.editController.newDocument();
            }
        }

        initEditor = (selector: string, onInitialized?: () => void) => {
            let me = this;
            me.selector = selector;
            if (!onInitialized) {
                onInitialized = me.confirmEditorInit;
            }

            let options = {
                // required options
                selector: '#' + selector,
                setup: function (editor: any) {
                    editor.on('init', onInitialized);

                    if (me.showFileControls) {
                        editor.on('ExecCommand', function (e) {
                            if (e.command === 'mceNewDocument') {
                                // Runs before TinyMCE clears the editor content
                                me.onNewDocument(editor);

                                // Your custom function here
                                // myNewDocumentHandler(editor);
                            }
                        });

                        editor.addMenuItem('fileopen', {
                            text: 'Open...',
                            context: 'file',
                            onclick: function () {
                                me.onFetchContent(editor);
                            }
                        });

                        editor.addMenuItem('filesave', {
                            text: 'Save as...',
                            context: 'file',
                            onclick: function () {
                                me.onSaveAs(editor);
                            }
                        });

                    }
                },

                // todo: initialization method to alter menubar
                menubar: 'file edit insert format',
                // these options can be overridden
                document_base_url : Peanut.Helper.getHostUrl() + '/',
                branding: false,
                paste_word_valid_elements: "b,strong,i,em,h1,h2,h3,p,a,ul,li"

            }

            if (me.configurationType == this.configEmail) {
                // set defaults for email
                options['relative_urls'] = false;
                options['remove_script_host'] = false;
            }
            else {
                // set defaults for contentEditing
                options['relative_urls'] = false;
                options['default_link_target'] = '_blank'
                options['remove_script_host']  = true;
            }

            let customToolbar = false;
            if (this.additionalOptions) {
                for (const prop in this.additionalOptions) {
                    if (prop == 'toolbar') {
                        customToolbar = true;
                    }
                    options[prop] = this.additionalOptions[prop]
                }
            }

            if (!customToolbar) {
                // let toolbar = 'undo redo | styleselect | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent ';
                let toolbar = 'undo redo | styleselect | bold italic underline';
                // let blockControls =' | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent';
                // private plugins = "image imagetools link lists code paste textcolor";
                let plugins = "link lists paste";

                if (me.showColorTools) {
                    toolbar += ' | forecolor backcolor';
                    plugins += ' textcolor'
                }

                if (me.showFontSizes) {
                    toolbar += ' | fontsizeselect'
                    options['fontsize_formats'] = '8pt 10pt 12pt 14pt 18pt 24pt 36pt';
                }

                // block controls
                toolbar += ' | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent';

                if (me.showHr) {
                    toolbar += ' | hr';
                    plugins += ' hr';
                }

                if (me.showTableTools) {
                    toolbar += ' | table';
                        // ' | table tabledelete | tableprops tablerowprops tablecellprops | tableinsertrowbefore '+
                        // 'tableinsertrowafter tabledeleterow | tableinsertcolbefore tableinsertcolafter tabledeletecol'
                    plugins += ' table';
                }

                if (me.showImageTools) {
                    toolbar += ' | image';
                    plugins += ' image imagetools ' + plugins;
                }

                toolbar += ' | link'

                if (me.showCodeButton) {
                    toolbar += ' | code'
                    plugins += ' code'
                }

                if (me.showFileControls) {
                    toolbar += ' | save';
                    plugins += ' save';
                    options['save_onsavecallback'] = this.onSave;


                    options['menu'] =
                    {
                        file: {
                            title: 'File',
                                items: 'newdocument fileopen filesave'
                        },
                        edit: {
                            title: 'Edit',
                                items: 'undo redo | cut copy paste pastetext | selectall'
                        },
                        insert: {
                            title: 'Insert',
                                items: 'link'
                        },
                        format: {
                            title: 'Format',
                                items: 'bold italic underline | formats'
                        }
                    }


                }

                options['toolbar'] = toolbar;
                options['plugins'] = plugins;
            }

            tinymce.init(options);
        };

        /**
         * Set Editor content
         * @param content
         *      html markup
         */
        setContent = (content: string): void => {
            let editor = tinymce.get(this.selector);
            if (!editor) {
                console.log('Cannot access editor')
            } else {
                editor.setContent(content);
            }
        }

        /**
         * Extract content (HTML text) from editor
         */
        getContent = () => {
            let me = this;
            tinymce.triggerSave();
            let editor = tinymce.get(me.selector);
            if (!editor) {
                console.log('Cannot access editor')
                return null;
            }
            return editor.getContent();
        }

        save = () => {
            // let me = this;
            tinymce.triggerSave();
        }

        /**
         * Return instance of editor
         */
        getEditor = () => {
            let me = this;
            tinymce.triggerSave();
            return tinymce.get(me.selector);
        }

        doGetContent = () => {
            let me = this;

            tinymce.triggerSave();
            let editor = tinymce.get(me.selector);
            if (!editor) {
                console.log('Cannot access editor')
                return null;
            }
            return editor.getContent();
        }

    }
}