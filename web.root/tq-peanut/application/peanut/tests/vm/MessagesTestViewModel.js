var Peanut;
(function (Peanut) {
    class MessagesTestViewModel extends Peanut.ViewModelBase {
        constructor() {
            super(...arguments);
            this.messageText = ko.observable('');
            this.itemName = ko.observable('');
            this.itemId = ko.observable(1);
            this.contextValue = ko.observable();
            this.processCancelled = false;
            this.onViewSelected = () => {
                let count = 1;
                alert(' Items Selected');
            };
            this.onProcessCancelled = () => {
                this.processCancelled = true;
                alert('Progress cancelled');
                Peanut.WaitMessage.hide();
            };
            this.onShowError = () => {
                this.application.showError("This is an error message.");
            };
            this.onShowMessage = () => {
                this.application.showMessage('This is a message.');
            };
            this.onShowProcessWaiter = () => {
                let me = this;
                let count = 0;
                let message = 'The process is running: ';
                me.processCancelled = false;
                Peanut.WaitMessage.show('This is a process waiter', 'process-waiter', this.onProcessCancelled);
                let t = window.setInterval(function () {
                    if (count > 20 || me.processCancelled) {
                        clearInterval(t);
                        Peanut.WaitMessage.hide();
                    }
                    else {
                        Peanut.WaitMessage.setMessage(message + ': Count ' + count);
                        Peanut.WaitMessage.setProgress(count, true);
                    }
                    count += 1;
                }, 100);
            };
        }
        init(successFunction) {
            let me = this;
            me.application.loadStyleSheets('test.css media=print');
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
            let msg = "Hello " + (new Date()).toISOString();
            Peanut.WaitMessage.show("Hello " + (new Date()).toISOString());
            let t = window.setInterval(function () {
                if (count > 50) {
                    clearInterval(t);
                    Peanut.WaitMessage.hide();
                }
                else {
                    Peanut.WaitMessage.setMessage(msg + ': Counting ' + count);
                }
                count += 1;
            }, 100);
        }
        onShowBannerWaiter() {
            let me = this;
            let count = 0;
            let message = "Hello " + (new Date()).toISOString();
            me.application.showBannerWaiter();
            let t = window.setInterval(function () {
                if (count > 20) {
                    clearInterval(t);
                    me.application.hideWaiter();
                }
                else {
                    Peanut.WaitMessage.setMessage(message + ': Counting ' + count);
                    Peanut.WaitMessage.setProgress(count, true);
                }
                count += 1;
            }, 100);
        }
        onShowActionWaiter() {
            let count = 0;
            let me = this;
            this.showActionWaiter('add', 'thing-plural');
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
            me.showWaitMessage();
            me.application.showBannerWaiter();
            let t = window.setInterval(function () {
                clearInterval(t);
                me.application.hideWaiter();
            }, 1000);
        }
        onShowProgressWaiter() {
            let me = this;
            me.processCancelled = false;
            let count = 0;
            let message = 'Doing something long running. ';
            Peanut.WaitMessage.showProgressWaiter(message, this.onProcessCancelled);
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
    }
    Peanut.MessagesTestViewModel = MessagesTestViewModel;
})(Peanut || (Peanut = {}));
//# sourceMappingURL=MessagesTestViewModel.js.map