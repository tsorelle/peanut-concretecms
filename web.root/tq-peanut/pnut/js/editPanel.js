var Peanut;
(function (Peanut) {
    class editPanel {
        constructor(owner) {
            this.viewState = ko.observable('');
            this.hasErrors = ko.observable(false);
            this.isAssigned = false;
            this.relationId = null;
            this.translate = (code, defaultText = null) => {
                return this.owner.translate(code, defaultText);
            };
            let me = this;
            me.owner = owner;
        }
        edit(relationId = null) {
            let me = this;
            me.viewState('edit');
            me.relationId = relationId;
        }
        close() {
            let me = this;
            me.viewState('closed');
        }
        search() {
            let me = this;
            me.viewState('search');
        }
        empty() {
            let me = this;
            me.viewState('empty');
        }
        view() {
            let me = this;
            if (me.isAssigned) {
                me.viewState('view');
            }
            else {
                me.viewState('empty');
            }
        }
        setViewState(state = 'view') {
            let me = this;
            me.viewState(state);
        }
    }
    Peanut.editPanel = editPanel;
})(Peanut || (Peanut = {}));
//# sourceMappingURL=editPanel.js.map