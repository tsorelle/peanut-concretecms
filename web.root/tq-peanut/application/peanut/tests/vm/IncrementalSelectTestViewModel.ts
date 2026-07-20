// required for all view models:
/// <reference path='../../../../pnut/core/ViewModelBase.ts' />
/// <reference path='../../../../typings/knockout/index.d.ts' />

// Module
namespace Peanut {
    export class IncrementalSelectTestViewModel extends Peanut.ViewModelBase {
        cities : ILookupItem[]  = [
            <ILookupItem>{
                code: 'AUS',
                description: 'We are wierd.',
                id: 1,
                name: 'Austin, Texas'
            },
            <ILookupItem>{
                code: 'HOU',
                description: 'Oil country',
                id: 2,
                name: 'Houston, Texas'
            },
            <ILookupItem>{
                code: 'DAL',
                description: 'Cowboy land',
                id: 3,
                name: 'Dallas, Texas'
            },
            <ILookupItem>{
                code: 'FW',
                description: 'Where the West Begins',
                id: 4,
                name: 'Fort Worth, Texas'
            },
            <ILookupItem>{
                code: 'WA',
                description: 'Mid Evangelical',
                id: 5,
                name: 'Waco, Texas'
            },
        ];

        cityList = ko.observableArray<ILookupItem>(
            this.cities
        );

        selectedCities = ko.observableArray<Peanut.ILookupItem>([this.cities[2]]);

        // observables

        init(successFunction?: () => void) {
            let me = this;
            Peanut.logger.write('IncrementalSelectTest Init');
            me.application.registerComponents('@pnut/incremental-select,@pnut/selected-list', () => {
                me.bindDefaultSection();
                successFunction();
            });
        }

        onCitySelect = (item: ILookupItem) => {
            alert(item.name + 'was sleected.');
        }
    }
}
