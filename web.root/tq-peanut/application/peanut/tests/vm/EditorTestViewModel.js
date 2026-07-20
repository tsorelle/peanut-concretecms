var Peanut;
(function (Peanut) {
    class EditorTestViewModel extends Peanut.ViewModelBase {
        constructor() {
            super(...arguments);
            this.i = 1;
            this.getHtmlContent = () => {
                let content = this.htmlEditor.getContent();
                alert('Got content! "' + content.slice(0, 20) + '..."');
            };
            this.setHtmlContent = () => {
                this.htmlEditor.setContent('<h1>Hello World ' + this.i + '</h1>');
                this.i++;
            };
            this.checkDirty = () => {
                let me = this;
                let dirty = me.htmlEditor.hasUnsavedChanges();
                alert(dirty ? 'Dirty' : 'Clean');
            };
            this.toggleDirty = () => {
                let me = this;
                let dirty = me.htmlEditor.hasUnsavedChanges();
                me.htmlEditor.setDirty(!dirty);
            };
        }
        init(successFunction) {
            let me = this;
            Peanut.logger.write('VmName Init');
            me.application.loadResources([
                '@pnut/htmlEditContainer'
            ], () => {
                me.application.registerComponents(['@pkg/peanut-content/content-controller'], () => {
                    me.htmlEditor = new Peanut.htmlEditContainer(me);
                    me.htmlEditor.enableUnsavedWarning();
                    me.htmlEditor.addOptions({ height: '50ex' });
                    me.htmlEditor.includeDesignTools();
                    me.htmlEditor.includeFileControls();
                    me.htmlEditor.initialize('test-editor', () => {
                        me.bindDefaultSection();
                        successFunction();
                    });
                });
            });
        }
        getContent() {
            let me = this;
            return me.htmlEditor.getContent();
        }
        setContent(content) {
            let me = this;
            me.htmlEditor.setContent(content);
        }
    }
    Peanut.EditorTestViewModel = EditorTestViewModel;
})(Peanut || (Peanut = {}));
//# sourceMappingURL=EditorTestViewModel.js.map