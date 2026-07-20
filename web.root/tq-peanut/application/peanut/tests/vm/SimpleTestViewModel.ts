/**
 * Created by Terry on 5/7/2017.
 */

// required for all view models:
/// <reference path='../../../../pnut/core/ViewModelBase.ts' />
/// <reference path='../../../../typings/knockout/index.d.ts' />

// Module
namespace Peanut {
    // SimpleTest view model
    export class SimpleTestViewModel  extends Peanut.ViewModelBase {
        messageText =
            ko.observable('This is a simple test, just to make sure all the foundational MVVM stuff is working.');

        // call this funtions at end of page
        init(successFunction?: () => void) {
            console.log('Init SimpleTest');
            let me = this;
            me.application.loadResources([
                '@lib:fontawesome'
            ], () => {
                me.bindDefaultSection();
                successFunction();
            });
        }


        onShowMessage = () => {
            this.application.showMessage('This is a message.');
           //  this.application.showError('This is an error.');
        };
        onShowError = () => {
            this.application.showError('This is an error.');
        };
    }
}
