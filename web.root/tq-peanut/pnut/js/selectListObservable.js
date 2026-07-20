var Peanut;
(function (Peanut) {
    class selectListObservable {
        constructor(selectHandler = null, optionsList = [], defaultValue = null) {
            this.options = ko.observableArray();
            this.selected = ko.observable();
            this.setSelectHandler = (handler) => {
                this.selectHandler = handler;
                this.subscribe();
            };
            this.hasOptions = () => {
                let me = this;
                return (me.options().length > 0);
            };
            this.nameText = ko.computed(() => {
                let me = this;
                if (me.selected && me.selected()) {
                    let text = me.selected().name;
                    if (text) {
                        return text;
                    }
                }
                return 'Not assigned';
            }, this);
            let me = this;
            me.options(optionsList);
            me.defaultValue = defaultValue;
            me.setValue(defaultValue);
            me.selectHandler = selectHandler;
            me.subscription = null;
        }
        static CreateLookup(selectHandler = null, optionsList, defaultValue = null) {
            let result = new selectListObservable(selectHandler, [], defaultValue);
            result.assignNameValueList(optionsList);
            return result;
        }
        setOptions(optionsList = [], defaultValue = null) {
            let me = this;
            if (optionsList == undefined) {
                console.error('Undefinded options list');
                optionsList = [];
            }
            me.options(optionsList);
            me.setValue(defaultValue);
        }
        hasOption(value) {
            let me = this;
            let options = me.options();
            let option = options.find((item) => {
                return item.id == value;
            });
            return (!!option);
        }
        setValue(value) {
            let me = this;
            if (value == null) {
                if (me.selected()) {
                    me.selected(null);
                }
                return;
            }
            let options = me.options();
            let option = options.find(function (item) {
                return item.id == value;
            });
            let current = me.selected();
            if (option) {
                if (option !== current) {
                    me.selected(option);
                }
            }
            else {
                if (current !== null) {
                    me.selected(null);
                }
            }
        }
        getOptions() {
            let me = this;
            return me.options();
        }
        setDefaultValue() {
            let me = this;
            me.setValue(me.defaultValue);
        }
        getValue(defaultValue = '') {
            let me = this;
            let selection = me.selected();
            return selection ? selection.id : defaultValue;
        }
        getName(defaultName = '') {
            let me = this;
            let selection = me.selected();
            return selection ? selection.name : defaultName;
        }
        restoreDefault() {
            let me = this;
            me.setValue(me.defaultValue);
        }
        unsubscribe() {
            let me = this;
            if (me.subscription) {
                me.subscription.dispose();
                me.subscription = null;
            }
        }
        subscribe() {
            let me = this;
            me.unsubscribe();
            if (me.selectHandler) {
                me.subscription = me.selected.subscribe(me.selectHandler);
            }
        }
        assignNameValueList(lookupItems) {
            let me = this;
            let options = [];
            lookupItems.filter((item) => {
                options.push({
                    name: item.Name,
                    id: item.Value
                });
            });
            me.options(options);
        }
    }
    Peanut.selectListObservable = selectListObservable;
})(Peanut || (Peanut = {}));
//# sourceMappingURL=selectListObservable.js.map