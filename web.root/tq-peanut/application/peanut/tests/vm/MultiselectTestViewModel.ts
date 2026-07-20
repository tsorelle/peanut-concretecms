/// <reference path='../../../../pnut/core/ViewModelBase.ts' />
/// <reference path='../../../../typings/knockout/index.d.ts' />

namespace Peanut {
    // MultiselectTest view model
    export class MultiselectTestViewModel  extends Peanut.ViewModelBase {
        messageText = ko.observable('This is a Multiselect test vm');

        multiSelectTestItems : ILookupItem[] = [
            <ILookupItem>{
                code: 'thing1',
                description: 'Thing number 1',
                id: 1,
                name: 'Thing one'
            },
            <ILookupItem>{
                code: 'thing2',
                description: 'Thing number 2',
                id: 2,
                name: 'Thing two'
            },
            <ILookupItem>{
                code: 'thing3',
                description: 'Thing number 2',
                id: 3,
                name: 'Thing three'
            },
            <ILookupItem>{
                code: 'thing4',
                description: 'Thing number 4',
                id: 4,
                name: 'Thing four'
            },
        ]

        multiSelectListTestSelected : ILookupItem[] = [
            <ILookupItem>{
                code: 'thing2',
                description: 'Thing number 2',
                id: 2,
                name: 'Thing two'
            },
            <ILookupItem>{
                code: 'thing3',
                description: 'Thing number 2',
                id: 3,
                name: 'Thing three'
            },
        ];

        // selectedItems = ko.observableArray<ILookupItem>();

        multiSelectTestController : IMultiSelectObservable;



        // call this funtions at end of page
        init(successFunction?: () => void) {
            let me = this;
            console.log('Init MultiselectTest');
            me.application.registerComponents('@pnut/selected-list,@pnut/multi-select', () => {
                    me.application.loadResources([
                        '@pnut/multiSelectObservable'
                    ], () => {
                        me.multiSelectTestController = new multiSelectObservable(me.multiSelectTestItems,[2,3]);
                        me.bindDefaultSection();
                        successFunction();
                });
            });
        }
    }
}
