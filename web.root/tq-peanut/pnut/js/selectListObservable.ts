namespace Peanut {

    export class selectListObservable {
        private selectHandler: (selected: ILookupItem) => void;
        private subscription: any;
        private defaultValue;
        options = ko.observableArray<ILookupItem>();
        selected = ko.observable<ILookupItem>();

        public constructor(selectHandler: (selected: ILookupItem) => void = null,
                           optionsList: ILookupItem[] = [],
                           defaultValue: any = null) {
            let me = this;
            me.options(optionsList);
            me.defaultValue = defaultValue;
            me.setValue(defaultValue);
            me.selectHandler = selectHandler;
            me.subscription = null;
        }

        public static CreateLookup(
            selectHandler: (selected: ILookupItem) => void = null,
            optionsList: INameValuePair[],
            defaultValue: any = null) {
            let result = new selectListObservable(selectHandler,[],defaultValue);
            result.assignNameValueList(optionsList);
            return result;
        }

        public setSelectHandler = (handler: (selected: ILookupItem) => void) => {
            this.selectHandler = handler;
            this.subscribe();
        }

        public hasOptions = () => {
            let me = this;
            return (me.options().length > 0);
        }
        public setOptions(optionsList: ILookupItem[] = [],
                          defaultValue: any = null) {
            let me = this;
            if (optionsList == undefined) {
                console.error('Undefinded options list');
                optionsList = [];
            }
            me.options(optionsList);
            me.setValue(defaultValue);
        }

        public hasOption(value: any) {
            let me = this;
            let options = me.options();
            let option = options.find((item: ILookupItem) => {
                return item.id == value;
            });
            return (!!option);
        }

        public setValue(value: any) {
            let me = this;
            if (value == null) {
                if (me.selected()) {
                me.selected(null);
                }
                return;
            }
            let options = me.options();
            let option = options.find(function (item: ILookupItem) {
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

        public getOptions() {
            let me = this;
            return me.options();
        }

        public setDefaultValue() {
            let me = this;
            me.setValue(me.defaultValue);
        }

        public getValue(defaultValue: any = '') {
            let me = this;
            let selection = me.selected();
            return selection ? selection.id : defaultValue;
        }

        public getName(defaultName: string = '') {
            let me = this;
            let selection = me.selected();
            return selection ? selection.name : defaultName;
        }

        public restoreDefault() {
            let me = this;
            me.setValue(me.defaultValue);
        }

        public unsubscribe() {
            let me = this;
            if (me.subscription) {
                me.subscription.dispose();
                me.subscription = null;
            }
        }

        public subscribe() {
            let me = this;
            me.unsubscribe();
            if (me.selectHandler) {
            me.subscription = me.selected.subscribe(me.selectHandler);
            }
        }

        public assignNameValueList(lookupItems : Peanut.INameValuePair[]) {
            let me = this;
            let options : Peanut.ILookupItem[] = [];
            lookupItems.filter((item: Peanut.INameValuePair) => {
                options.push(
                    {
                        name: item.Name,
                        id: item.Value
                    }
                )
            });
            me.options(options);
        }

        nameText = ko.computed(() => {
            let me = this;
            if (me.selected && me.selected()) {
                let text = me.selected().name;
                if (text) {
                    return text;
                }
            }
            return 'Not assigned';
        }, this);
    }
}

