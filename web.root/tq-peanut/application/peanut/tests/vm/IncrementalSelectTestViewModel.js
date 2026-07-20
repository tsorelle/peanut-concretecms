var Peanut;
(function (Peanut) {
    class IncrementalSelectTestViewModel extends Peanut.ViewModelBase {
        constructor() {
            super(...arguments);
            this.cities = [
                {
                    code: 'AUS',
                    description: 'We are wierd.',
                    id: 1,
                    name: 'Austin, Texas'
                },
                {
                    code: 'HOU',
                    description: 'Oil country',
                    id: 2,
                    name: 'Houston, Texas'
                },
                {
                    code: 'DAL',
                    description: 'Cowboy land',
                    id: 3,
                    name: 'Dallas, Texas'
                },
                {
                    code: 'FW',
                    description: 'Where the West Begins',
                    id: 4,
                    name: 'Fort Worth, Texas'
                },
                {
                    code: 'WA',
                    description: 'Mid Evangelical',
                    id: 5,
                    name: 'Waco, Texas'
                },
            ];
            this.cityList = ko.observableArray(this.cities);
            this.selectedCities = ko.observableArray([this.cities[2]]);
            this.onCitySelect = (item) => {
                alert(item.name + 'was sleected.');
            };
        }
        init(successFunction) {
            let me = this;
            Peanut.logger.write('IncrementalSelectTest Init');
            me.application.registerComponents('@pnut/incremental-select,@pnut/selected-list', () => {
                me.bindDefaultSection();
                successFunction();
            });
        }
    }
    Peanut.IncrementalSelectTestViewModel = IncrementalSelectTestViewModel;
})(Peanut || (Peanut = {}));
//# sourceMappingURL=IncrementalSelectTestViewModel.js.map