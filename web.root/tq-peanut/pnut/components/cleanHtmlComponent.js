var Peanut;
(function (Peanut) {
    class cleanHtmlComponent {
        constructor(params) {
            this.translator = null;
            this.removeBlanks = ko.observable(true);
            this.convertHeadings = ko.observable('h2');
            this.cleaning = ko.observable(false);
            this.showModal = () => {
                this.cleaning(false);
                if (!this.editor) {
                    this.editor = tinymce.get(this.editorId);
                    if (!this.editor) {
                        console.error('Invalid editor id ' + this.editorId);
                        return;
                    }
                }
                this.content = this.editor.getContent();
                if (!this.content.trim()) {
                    alert('No text to clean.');
                    return;
                }
                Peanut.ui.helper.showModal("#html-cleanup-modal");
            };
            this.doCleanup = () => {
                let me = this;
                me.cleaning(true);
                let lines = me.content.split("\n");
                if (me.removeBlanks()) {
                    lines = lines.filter((item) => {
                        if (item == '<p>&nbsp;</p>' || item == '<p><strong>&nbsp;</strong></p>') {
                            return false;
                        }
                        return true;
                    });
                }
                if (me.convertHeadings()) {
                    let conversion = me.convertHeadings();
                    let h1 = conversion.substr(0, 2);
                    let h2 = conversion.length > 2 ? 'h' + conversion.substr(-1) : h1;
                    let hStart = '<' + h1 + '>';
                    let hEnd = '</' + h1 + '>';
                    let pStart = '<p><strong>';
                    let pEnd = '</strong></p>';
                    let count = lines.length;
                    for (let i = 0; i < count; i++) {
                        let item = lines[i];
                        if (item.substr(0, 11) == pStart && item.substr(-13) == pEnd) {
                            lines[i] = item.replace(pStart, hStart).replace(pEnd, hEnd);
                            hStart = '<' + h2 + '>';
                            hEnd = '</' + h2 + '>';
                        }
                    }
                }
                me.editor.setContent(lines.join("\n"));
                me.cleaning(false);
                Peanut.ui.helper.hideModal("#html-cleanup-modal");
            };
            let me = this;
            if (!params) {
                console.error('Params not defined in cleanHtmlComponent');
                throw ('Cannot initialize cleanHtmlComponent');
            }
            if (!params.editorId) {
                console.error('cleanupHtml component requires editorId parameter giving ID of TinyMCE target text area.');
                throw ('Cannot initialize cleanHtmlComponent');
            }
            me.editorId = params.editorId;
            if (params.translator) {
            }
            me.bootstrapVersion = ko.observable(3);
            Peanut.PeanutLoader.loadUiHelper(() => {
                me.bootstrapVersion(Peanut.ui.helper.getVersion());
            });
        }
    }
    Peanut.cleanHtmlComponent = cleanHtmlComponent;
})(Peanut || (Peanut = {}));
//# sourceMappingURL=cleanHtmlComponent.js.map