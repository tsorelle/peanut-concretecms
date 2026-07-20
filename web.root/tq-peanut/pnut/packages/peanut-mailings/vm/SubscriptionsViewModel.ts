/// <reference path="../../../../pnut/core/ViewModelBase.ts" />
/// <reference path='../../../../typings/knockout/knockout.d.ts' />
/// <reference path='../../../../pnut/core/Peanut.d.ts' />

namespace PeanutMailings {

    export class SubscriptionsViewModel extends Peanut.ViewModelBase {
        // observables

        init(successFunction?: () => void) {
            let me = this;
            Peanut.logger.write('Subscriptions Init');

			me.bindDefaultSection();
            successFunction();
        }
    }
}
