var Peanut;
(function (Peanut) {
    class htmlEditContainer {
        constructor(owner) {
            this.configEmail = 1;
            this.configContent = 2;
            this.editorInitialized = false;
            this.configurationType = this.configEmail;
            this.showCodeButton = false;
            this.showImageTools = false;
            this.showColorTools = false;
            this.showTableTools = false;
            this.showFontSizes = false;
            this.showFileControls = false;
            this.showHr = false;
            this.additionalOptions = null;
            this.configureForEmail = () => {
                this.configurationType = this.configEmail;
            };
            this.configureForContentEditing = () => {
                this.configurationType = this.configContent;
                this.includeDesignTools();
            };
            this.hideHtml = () => {
                this.showCodeButton = false;
            };
            this.showColorButtons = () => {
                this.showColorTools = true;
            };
            this.showImageButtons = () => {
                this.showImageTools = true;
            };
            this.includeFontSizing = () => {
                this.showFontSizes = true;
            };
            this.includeTableTools = () => {
                this.showTableTools = true;
            };
            this.includeDesignTools = () => {
                this.showImageTools = true;
                this.showColorTools = true;
                this.showCodeButton = true;
                this.showTableTools = true;
                this.showFontSizes = true;
                this.showHr = true;
            };
            this.includeFileControls = () => {
                this.showFileControls = true;
            };
            this.addOptions = (newOptions) => {
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
            };
            this.initialize = (selector, onInitialized) => {
                let me = this;
                me.selector = selector;
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
            };
            this.confirmEditorInit = () => {
                let ed = tinymce.get(this.selector);
                let me = this;
                me.editorInitialized = (ed !== null);
            };
            this.hasUnsavedChanges = () => {
                let me = this;
                let dirty = tinymce.get(me.selector).isDirty();
                if (dirty) {
                    dirty = !me.isEmpty();
                }
                return dirty;
            };
            this.enableUnsavedWarning = () => {
                let me = this;
                window.addEventListener('beforeunload', function (event) {
                    if (me.hasUnsavedChanges()) {
                        event.preventDefault();
                        event.returnValue = 'WARNING: You have unsaved changes.';
                    }
                });
            };
            this.onSave = (editor) => {
                if (editor) {
                    this.editController.saveDocument();
                }
            };
            this.onSaveAs = (editor) => {
                if (editor) {
                    this.editController.saveDocumentAs();
                }
            };
            this.onFetchContent = (editor) => {
                if (editor) {
                    this.editController.openDocument();
                }
            };
            this.onSetUp = (editor) => {
                let me = this;
                editor.ui.registry.addMenuItem('getcontent', {
                    text: 'Load content...',
                    onAction: me.onFetchContent
                });
            };
            this.onNewDocument = (editor) => {
                let me = this;
                if (editor) {
                    me.editController.newDocument();
                }
            };
            this.initEditor = (selector, onInitialized) => {
                let me = this;
                me.selector = selector;
                if (!onInitialized) {
                    onInitialized = me.confirmEditorInit;
                }
                let options = {
                    selector: '#' + selector,
                    setup: function (editor) {
                        editor.on('init', onInitialized);
                        if (me.showFileControls) {
                            editor.on('ExecCommand', function (e) {
                                if (e.command === 'mceNewDocument') {
                                    me.onNewDocument(editor);
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
                    menubar: 'file edit insert format',
                    document_base_url: Peanut.Helper.getHostUrl() + '/',
                    branding: false,
                    paste_word_valid_elements: "b,strong,i,em,h1,h2,h3,p,a,ul,li"
                };
                if (me.configurationType == this.configEmail) {
                    options['relative_urls'] = false;
                    options['remove_script_host'] = false;
                }
                else {
                    options['relative_urls'] = false;
                    options['default_link_target'] = '_blank';
                    options['remove_script_host'] = true;
                }
                let customToolbar = false;
                if (this.additionalOptions) {
                    for (const prop in this.additionalOptions) {
                        if (prop == 'toolbar') {
                            customToolbar = true;
                        }
                        options[prop] = this.additionalOptions[prop];
                    }
                }
                if (!customToolbar) {
                    let toolbar = 'undo redo | styleselect | bold italic underline';
                    let plugins = "link lists paste";
                    if (me.showColorTools) {
                        toolbar += ' | forecolor backcolor';
                        plugins += ' textcolor';
                    }
                    if (me.showFontSizes) {
                        toolbar += ' | fontsizeselect';
                        options['fontsize_formats'] = '8pt 10pt 12pt 14pt 18pt 24pt 36pt';
                    }
                    toolbar += ' | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent';
                    if (me.showHr) {
                        toolbar += ' | hr';
                        plugins += ' hr';
                    }
                    if (me.showTableTools) {
                        toolbar += ' | table';
                        plugins += ' table';
                    }
                    if (me.showImageTools) {
                        toolbar += ' | image';
                        plugins += ' image imagetools ' + plugins;
                    }
                    toolbar += ' | link';
                    if (me.showCodeButton) {
                        toolbar += ' | code';
                        plugins += ' code';
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
                            };
                    }
                    options['toolbar'] = toolbar;
                    options['plugins'] = plugins;
                }
                tinymce.init(options);
            };
            this.setContent = (content) => {
                let editor = tinymce.get(this.selector);
                if (!editor) {
                    console.log('Cannot access editor');
                }
                else {
                    editor.setContent(content);
                }
            };
            this.getContent = () => {
                let me = this;
                tinymce.triggerSave();
                let editor = tinymce.get(me.selector);
                if (!editor) {
                    console.log('Cannot access editor');
                    return null;
                }
                return editor.getContent();
            };
            this.save = () => {
                tinymce.triggerSave();
            };
            this.getEditor = () => {
                let me = this;
                tinymce.triggerSave();
                return tinymce.get(me.selector);
            };
            this.doGetContent = () => {
                let me = this;
                tinymce.triggerSave();
                let editor = tinymce.get(me.selector);
                if (!editor) {
                    console.log('Cannot access editor');
                    return null;
                }
                return editor.getContent();
            };
            let me = this;
            if (owner) {
                me.services = owner.getServiceBroker();
                me.application = owner.getApplication();
            }
        }
        setEditController(controller) {
            let me = this;
            me.editController = controller;
        }
        isEmpty() {
            let me = this;
            let content = me.getContent();
            content = content
                .replace(/<[^>]*>/g, "")
                .replace(/\s/g, "").
                replace(/&nbsp;/g, "");
            return content == '';
        }
        setDirty(enabled) {
            let me = this;
            tinymce.get(me.selector).setDirty(enabled);
        }
    }
    Peanut.htmlEditContainer = htmlEditContainer;
})(Peanut || (Peanut = {}));
//# sourceMappingURL=htmlEditContainer.js.map