var Peanut;
(function (Peanut) {
    class multiSelectObservable {
        constructor(optionsList = [], values = null) {
            this.allItems = ko.observableArray([]);
            this.selected = ko.observableArray([]);
            let me = this;
            me.allItems(optionsList);
            me.setValues(values);
        }
        setItems(items) {
            let me = this;
            me.allItems(items);
            me.selected([]);
        }
        setValues(values) {
            let me = this;
            if (values && values.length) {
                let all = me.allItems();
                let selected = all.filter((item) => {
                    return values.indexOf(item.id) !== -1;
                });
                me.selected(selected);
            }
            else {
                me.selected([]);
            }
        }
        getValues() {
            let me = this;
            let selection = me.selected();
            let result = [];
            for (let i = 0; i < selection.length; i++) {
                result.push(selection[i].id);
            }
            return result;
        }
    }
    Peanut.multiSelectObservable = multiSelectObservable;
})(Peanut || (Peanut = {}));
//# sourceMappingURL=multiSelectObservable.js.map