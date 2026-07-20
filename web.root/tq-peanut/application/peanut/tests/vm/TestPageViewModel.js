var Peanut;
(function (Peanut) {
    class TestPageViewModel extends Peanut.ViewModelBase {
        constructor() {
            super(...arguments);
            this.messageText = ko.observable('');
            this.itemName = ko.observable('');
            this.itemId = ko.observable(1);
            this.contextValue = ko.observable();
            this.messagePanel = ko.observable('button');
            this.messageFormVisible = ko.observable(false);
            this.messageButtonVisible = ko.observable(true);
            this.languageA = ko.observable('');
            this.languageB = ko.observable('');
            this.itemList = ko.observableArray([
                {
                    code: 'thing1',
                    description: 'Thing number 1',
                    id: 1,
                    name: 'Thing one'
                },
                {
                    code: 'thing2',
                    description: 'Thing number 2',
                    id: 2,
                    name: 'Thing two'
                },
                {
                    code: 'thing3',
                    description: 'Thing number 2',
                    id: 3,
                    name: 'Thing three'
                },
                {
                    code: 'thing4',
                    description: 'Thing number 4',
                    id: 4,
                    name: 'Thing four'
                },
            ]);
            this.selectedItems = ko.observableArray([
                {
                    code: 'thing2',
                    description: 'Thing number 2',
                    id: 2,
                    name: 'Thing two'
                },
                {
                    code: 'thing3',
                    description: 'Thing number 2',
                    id: 3,
                    name: 'Thing three'
                },
            ]);
            this.loadcss = (path, media = null) => {
                if (path) {
                    path = '/application/assets/styles/' + path;
                    let fileref = document.createElement("link");
                    fileref.setAttribute("rel", "stylesheet");
                    fileref.setAttribute("type", "text/css");
                    fileref.setAttribute("href", path);
                    if (media) {
                        fileref.setAttribute('media', media);
                    }
                    if (typeof fileref === "undefined") {
                        console.error('Failed to load stylesheet ' + path);
                    }
                    document.getElementsByTagName("head")[0].appendChild(fileref);
                    console.log('Loaded stylesheet: ' + path);
                }
            };
            this.onViewSelected = () => {
                let count = 1;
                alert(' Items Selected');
            };
            this.save = () => {
                Peanut.ui.helper.hideModal('confirm-save-modal');
                alert('you saved');
            };
            this.onShowError = () => {
                this.application.showError("This is an error message.");
            };
            this.onService = () => {
                let me = this;
                let request = { "tester": 'Terry SoRelle' };
                me.application.hideServiceMessages();
                me.application.showWaiter('Testing service...');
                me.services.executeService('PeanutTest::HelloWorld', request, function (serviceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let response = serviceResponse.Value;
                        me.addTranslations(response.translations);
                        me.languageA(me.translate('hello', 'Hello'));
                        me.languageB(me.translate('world'));
                    }
                }).fail(function () {
                    let trace = me.services.getErrorInformation();
                }).always(() => {
                    me.application.hideWaiter();
                });
            };
            this.onShowForm = () => {
                console.log('Show form component');
                let me = this;
                this.application.attachComponent('tests/test-form', (returnFuncton) => {
                    console.log('attachComponent - returnFunction');
                    this.application.loadComponents('@app/tests/test-form', () => {
                        console.log('instatiate testForm component');
                        if (!Peanut.testFormComponent) {
                            console.log('Test form component not loaded.');
                            return;
                        }
                        me.testForm = new Peanut.testFormComponent();
                        me.testForm.setMessage('Watch this space.');
                        me.messagePanel('form');
                        returnFuncton(me.testForm);
                    });
                });
            };
            this.onSendMessage = () => {
                this.testForm.setMessage(this.messageText());
            };
            this.onShowMessageComponent = () => {
                this.attachComponent('tests/test-message');
                this.messageButtonVisible(false);
            };
            this.currentPage = ko.observable(1);
            this.maxPages = ko.observable(10);
            this.changePage = (move) => {
                let current = this.currentPage() + move;
                this.currentPage(current);
            };
        }
        init(successFunction) {
            let me = this;
            me.contextValue(me.getVmContext());
            me.application.loadStyleSheets('test.css media=print');
            me.addTranslation('test', 'Un prueba de traducadora');
            me.addTranslation('thing-plural', 'thingies');
            me.addTranslation('save-modal-message', 'Do you want to save changes now?');
            me.application.registerComponents('tests/intro-message,@pnut/modal-confirm,@pnut/pager,@pnut/multi-select', () => {
                me.application.loadComponents('tests/message-constructor', () => {
                    me.application.loadResources([
                        '@lib:lodash',
                        '@lib:local/TestLib.js',
                        '@pnut/searchListObservable',
                        '@pnut/ViewModelHelpers'
                    ], () => {
                        Testing.Test.sayHello();
                        let cvm = new Peanut.messageConstructorComponent('Smoke Test Buttons:');
                        me.application.registerComponent('tests/message-constructor', cvm, () => {
                            me.bindDefaultSection();
                            successFunction();
                        });
                    });
                });
            });
        }
        onGetItem() {
            let me = this;
            me.application.showWaiter('Please wait...');
            me.services.getFromService('TestGetService', 3, function (serviceResponse) {
                if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                    me.itemName(serviceResponse.Value.name);
                    me.itemId(serviceResponse.Value.id);
                }
                else {
                    alert("Service failed");
                }
            }).always(function () {
                me.application.hideWaiter();
            });
        }
        onPostItem() {
            let me = this;
            let request = {
                testMessageText: me.itemName()
            };
            me.application.showWaiter('Please wait...');
            me.services.executeService('TestService', request)
                .always(function () {
                me.application.hideWaiter();
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
            Peanut.WaitMessage.show(message, 'banner-waiter');
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
                    me.application.hideWaiter();
                }
                count += 1;
            }, 100);
        }
        onShowWaiter() {
            let me = this;
            me.application.showWaiter();
            let t = window.setInterval(function () {
                clearInterval(t);
                me.application.hideWaiter();
            }, 1000);
        }
        onShowProgressWaiter() {
            let count = 0;
            let message = 'Doing something long running. ';
            Peanut.WaitMessage.showProgressWaiter(message);
            let t = window.setInterval(function () {
                if (count > 100) {
                    clearInterval(t);
                    Peanut.WaitMessage.hide();
                }
                else {
                    if (count == 25) {
                        Peanut.WaitMessage.setMessage(message + ' Still woring...');
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
        onShowModalForm() {
            Peanut.ui.helper.showModal('test-modal');
        }
        onSaveChanges() {
            Peanut.ui.helper.hideModal('test-modal');
            Peanut.ui.helper.showModal('confirm-save-modal');
        }
    }
    Peanut.TestPageViewModel = TestPageViewModel;
})(Peanut || (Peanut = {}));
//# sourceMappingURL=TestPageViewModel.js.map