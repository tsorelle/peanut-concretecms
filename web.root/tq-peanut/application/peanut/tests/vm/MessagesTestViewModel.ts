/**
 * Created by Terry on 5/7/2017.
 */

// required for all view models:
/// <reference path='../../../../pnut/core/ViewModelBase.ts' />
/// <reference path='../../../../typings/knockout/index.d.ts' />

// Module
namespace Peanut {
    // TestPage view model
    export class MessagesTestViewModel  extends Peanut.ViewModelBase {
        messageText = ko.observable('');

        itemName = ko.observable('');
        itemId = ko.observable(1);
        contextValue = ko.observable();
        processCancelled = false;

        // call this funtions at end of page
        init(successFunction?: () => void) {

            let me = this;
            //  let jsver = me.checkJsVersion();
            // alert('Javascript version: ' + jsver);
            me.application.loadStyleSheets('test.css media=print');// ,'print');
            me.application.loadResources([
                '@pnut/ViewModelHelpers'
            ], () => {
                me.bindDefaultSection();
                successFunction();
            });
        }

        onAddMessageClick() {
            let me = this;
            let msg = me.messageText();
            me.application.showMessage(msg);
            me.messageText('');
        }

        onAddErrorMessageClick() {
            let me = this;
            let msg = me.messageText();
            me.application.showError(msg);
            me.messageText('');
        }

        onAddWarningMessageClick() {
            let me = this;
            let msg = me.messageText();
            me.application.showWarning(msg);
            me.messageText('');
        }

        onShowSpinWaiter() {
            let me = this;
            let count = 0;
            let msg = "Hello " + (new Date()).toISOString()
            Peanut.WaitMessage.show("Hello " + (new Date()).toISOString());
            let t = window.setInterval(function () {
                if (count > 50) {
                    clearInterval(t);
                    // me.application.hideWaiter();
                    // alert('done');
                    Peanut.WaitMessage.hide();
                }
                else {
                    Peanut.WaitMessage.setMessage(msg +': Counting ' + count);
                    // Peanut.WaitMessage.setProgress(count,true);
                }
                count += 1;
            }, 100);

        }

        onShowBannerWaiter() {
            let me = this;
            let count = 0;
            let message = "Hello " + (new Date()).toISOString()
            // Peanut.WaitMessage.show(message,'banner-waiter');
            me.application.showBannerWaiter()

            let t = window.setInterval(function () {
                // if (count > 100) {
                if (count > 20) {
                    clearInterval(t);
                    me.application.hideWaiter();
                    // Peanut.WaitMessage.hide();
                }
                else {
                    Peanut.WaitMessage.setMessage(message +': Counting ' + count);
                    Peanut.WaitMessage.setProgress(count,true);
                }
                count += 1;
            }, 100);

        }

        onShowActionWaiter() {
            let count = 0;
            let me = this;
            this.showActionWaiter('add','thing-plural');
            // Peanut.WaitMessage.show(message,'banner-waiter');
            let t = window.setInterval(function () {
                if (count > 50) {
                    clearInterval(t);
                    me.hideWaiter();
                }
                count += 1;
            }, 100);

        }


        onShowWaiter() {
            let me = this;
            me.showWaitMessage()
            // these deprecated alternatives all do the same thing
            // me.application.showWaiter();
            me.application.showBannerWaiter()
            let t = window.setInterval(function () {
                clearInterval(t);
               //  me.hideWaiter();
                me.application.hideWaiter();
            }, 1000);

        }


        onViewSelected = () => {
            let count = 1;
            // alert(count + ' Items Selected');
            alert(' Items Selected');
        };

        onProcessCancelled = () => {
            this.processCancelled = true;
            alert('Progress cancelled');
            WaitMessage.hide();
        }
        onShowProgressWaiter() {
            let me = this;
            me.processCancelled = false;
            let count = 0;
            let message = 'Doing something long running. '
            Peanut.WaitMessage.showProgressWaiter(message,this.onProcessCancelled);
            let t = window.setInterval(function () {
                if (count > 100 || me.processCancelled) {
                    clearInterval(t);
                    Peanut.WaitMessage.hide();
                }
                else {
                    if (count == 25) {
                        Peanut.WaitMessage.setMessage(message + ' Still working...');
                    }
                    else if (count == 50) {
                        Peanut.WaitMessage.setMessage(message + ' Half way there...');
                    }
                    else if (count == 70) {
                        Peanut.WaitMessage.setMessage(message + ' Keep going...');
                    }
                    else if (count == 90) {
                        Peanut.WaitMessage.setMessage(message + 'Almost done!');
                    }
                    Peanut.WaitMessage.setProgress(count, true);
                }
                count += 1;
            }, 100);
        }

        onHideWaiter() {
            Peanut.WaitMessage.hide();
        }

        onShowError = () => {
            this.application.showError("This is an error message.");
        };

        onShowMessage = () => {
            this.application.showMessage('This is a message.');
            //  this.application.showError('This is an error.');
        };

        onShowProcessWaiter = () => {
            let me = this;
            let count = 0;
            let message = 'The process is running: ';
            me.processCancelled = false;
            WaitMessage.show('This is a process waiter','process-waiter',this.onProcessCancelled)

            let t = window.setInterval(function () {
                // if (count > 100) {
                if (count > 20 || me.processCancelled) {
                    clearInterval(t);
                    Peanut.WaitMessage.hide();
                }
                else {
                    Peanut.WaitMessage.setMessage(message +': Count ' + count);
                    Peanut.WaitMessage.setProgress(count,true);
                }
                count += 1;
            }, 100);

        }
    }


}
