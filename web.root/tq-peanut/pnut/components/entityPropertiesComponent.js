var Peanut;
(function (Peanut) {
    class entityPropertiesController {
        constructor(properties, lookups, selectText = 'Select', clearValues = false) {
            this.controls = [];
            this.defaults = [];
            this.clearValues = () => {
                this.setAssociatedValues(this.defaults);
            };
            this.getValue = (key) => {
                let item = this.controls[key];
                let value = item.selected();
                return value || null;
            };
            let me = this;
            for (let i = 0; i < properties.length; i++) {
                let property = properties[i];
                let lookup = lookups[property.lookup];
                let defaultItem = me.getLookupValue(property.defaultValue, lookup);
                me.controls[property.key] = {
                    lookup: lookup,
                    selected: ko.observable(defaultItem),
                    label: property.label,
                    caption: ((property.required && property.defaultValue) && !clearValues) ? null : selectText,
                    displayText: defaultItem ? defaultItem.name : ''
                };
                me.defaults[property.key] = property.defaultValue;
            }
        }
        getLookupValue(value, lookupList) {
            for (let i = 0; i < lookupList.length; i++) {
                let lookupItem = lookupList[i];
                if (lookupItem.id == value) {
                    return lookupItem;
                }
            }
            return null;
        }
        setValue(key, value) {
            let me = this;
            let control = me.controls[key];
            let item = me.getLookupValue(value, control.lookup);
            me.controls[key].displayText = item ? item.name : '';
            me.controls[key].selected(item);
        }
        setAssociatedValues(values) {
            let me = this;
            for (let key in values) {
                me.setValue(key, values[key]);
            }
        }
        setValues(values) {
            let me = this;
            for (let i = 0; i < values.length; i++) {
                me.setValue(values[i].Key, values[i].Value);
            }
        }
        getValues() {
            let me = this;
            let result = [];
            for (let key in me.controls) {
                let item = me.controls[key];
                let value = item.selected();
                if (value) {
                    result.push({
                        Key: key,
                        Value: value.id
                    });
                }
            }
            return result;
        }
    }
    Peanut.entityPropertiesController = entityPropertiesController;
    class entityPropertiesComponent {
        constructor(params) {
            this.propertyRows = ko.observableArray([]);
            this.readOnly = ko.observable(false);
            let me = this;
            if (!params) {
                console.error('entityPropertiesComponent: Params not defined in entityPropertiesComponent');
                return;
            }
            if (!params.controller) {
                console.error('entityPropertiesComponent: Parameter "controller" is required');
                return;
            }
            me.readOnly(params.readOnly == 1);
            let test = me.readOnly();
            let clearValues = params.clearValues;
            let columnCount = 3;
            let columnWidth = 'md';
            if (params.columns) {
                columnCount = params.columns;
            }
            if (params.colwidth) {
                columnWidth = params.colwidth;
            }
            let columnClass = 'col-' + columnWidth + '-' + (Math.floor(12 / columnCount));
            let rows = [];
            let controls = [];
            let i = 0;
            for (let key in params.controller.controls) {
                let control = params.controller.controls[key];
                let lookup = ko.observableArray(control.lookup);
                controls.push({
                    label: control.label,
                    lookup: lookup,
                    selected: control.selected,
                    caption: control.caption,
                    cssColumn: columnClass,
                    displayText: control.displayText
                });
                if (++i === columnCount) {
                    rows.push(ko.observableArray(controls));
                    controls = [];
                }
            }
            if (controls.length > 0) {
                rows.push(ko.observableArray(controls));
            }
            me.propertyRows(rows);
        }
    }
    Peanut.entityPropertiesComponent = entityPropertiesComponent;
})(Peanut || (Peanut = {}));
//# sourceMappingURL=entityPropertiesComponent.js.map