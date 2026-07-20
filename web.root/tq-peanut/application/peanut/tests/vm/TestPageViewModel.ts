/**
 * Created by Terry on 5/7/2017.
 */

// required for all view models:
/// <reference path='../../../../pnut/core/ViewModelBase.ts' />
/// <reference path='../../../../typings/knockout/index.d.ts' />
/// <reference path='../../../../pnut/js/htmlEditContainer.ts' />

// Module
namespace Peanut {
    // TestPage view model
    export class TestPageViewModel  extends Peanut.ViewModelBase {
        messageText = ko.observable('');

        itemName = ko.observable('');
        itemId = ko.observable(1);
        contextValue = ko.observable();
        messagePanel = ko.observable('button');
        messageFormVisible = ko.observable(false);
        messageButtonVisible = ko.observable(true);

        languageA = ko.observable('');
        languageB = ko.observable('');

        itemList = ko.observableArray<ILookupItem>([
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
        ]);
        selectedItems = ko.observableArray<ILookupItem>([
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
        ]);


        _ : any; // lodash no conflict reference.

        private testForm : testFormComponent;

        // call this funtions at end of page
        init(successFunction?: () => void) {

            let me = this;
           //  let jsver = me.checkJsVersion();
            // alert('Javascript version: ' + jsver);
            me.contextValue(me.getVmContext());
            me.application.loadStyleSheets('test.css media=print');// ,'print');
            // me.showLoadWaiter();
            // setup messaging and other application initializations
            me.addTranslation('test', 'Un prueba de traducadora');
            me.addTranslation('thing-plural', 'thingies');
            me.addTranslation('save-modal-message', 'Do you want to save changes now?');

            // for components inside the default secton (<div id='testpage-view-container>)
            // Call load component to load and register. Before calling showDefaultSection()
            // final block must bind any view models (main or component) and call the success function.

            // me.application.registerComponentPrototype('@pnut/modal-confirm', () => {
            me.application.registerComponents('tests/intro-message,@pnut/modal-confirm,@pnut/pager,@pnut/multi-select', () => {
                me.application.loadComponents('tests/message-constructor', () => {
                    me.application.loadResources([
                        // '@lib:fontawesome',
                        '@lib:lodash',
                        // 'https://cdnjs.cloudflare.com/ajax/libs/lodash.js/4.17.4/lodash.min.js',
                        '@lib:local/TestLib.js',
                        // '/application/assets/js/libraries/TestLib.js',
                        '@pnut/searchListObservable',
                        '@pnut/ViewModelHelpers'
                    ], () => {
                        Testing.Test.sayHello();
                        let cvm = new messageConstructorComponent('Smoke Test Buttons:');
                        me.application.registerComponent('tests/message-constructor', cvm, () => {
                            me.bindDefaultSection();
                            successFunction();
                        });
                    });
                });
            });
            //});
            // });

        }

        loadcss = (path,media=null) => {
            if (path) {
                path = '/application/assets/styles/' + path;
                let fileref = document.createElement("link");
                fileref.setAttribute("rel", "stylesheet");
                fileref.setAttribute("type", "text/css");
                fileref.setAttribute("href", path);
                if (media) {
                    fileref.setAttribute('media', media)
                }
                if (typeof fileref === "undefined") {
                    console.error('Failed to load stylesheet ' + path);
                }
                document.getElementsByTagName("head")[0].appendChild(fileref);
                console.log('Loaded stylesheet: ' + path);
            }
        };

        onGetItem() {
            let me = this;
            me.application.showWaiter('Please wait...');
            me.services.getFromService('TestGetService', 3, function (serviceResponse: Peanut.IServiceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        me.itemName(serviceResponse.Value.name);
                        me.itemId(serviceResponse.Value.id);
                    }
                    else {
                        alert("Service failed");
                    }
                }
            ).always(function () {
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

        // person: KnockoutObservable<any> = ko.observable();
        // Declarations
        // Examples:
        //  templateList: KnockoutObservableArray = ko.observableArray([]);
        //  currentPage: KnockoutObservableString = ko.observable("");


        // Methods
        // test() { alert("hello"); }

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
            Peanut.WaitMessage.show(message,'banner-waiter');
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

        onViewSelected = () => {
            let count = 1;
            // alert(count + ' Items Selected');
            alert(' Items Selected');
        };

        onShowProgressWaiter() {
            let count = 0;
            let message = 'Doing something long running. '
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
            // jQuery("#test-modal").modal('show');
            Peanut.ui.helper.showModal('test-modal');
        }

        onSaveChanges() {
            Peanut.ui.helper.hideModal('test-modal');
            Peanut.ui.helper.showModal('confirm-save-modal');
            //jQuery("#test-modal").modal('hide');
            // jQuery("#confirm-save-modal").modal('show');
        }

        save = () => {
            Peanut.ui.helper.hideModal('confirm-save-modal');
            // jQuery("#confirm-save-modal").modal('hide');
            alert('you saved');
        };
        onShowError = () => {
            this.application.showError("This is an error message.");
        };
        onService = () => {
            let me = this;
            let request = {"tester" : 'Terry SoRelle'};
            me.application.hideServiceMessages();
            // me.application.showWaiter('Testing service...','spin-waiter');
            me.application.showWaiter('Testing service...');
            // me.services.executeService('admin.HelloWorld', request,
            me.services.executeService('PeanutTest::HelloWorld', request,
                function (serviceResponse: Peanut.IServiceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let response = serviceResponse.Value;
                        // alert(response.message);
                        me.addTranslations(response.translations);
                        me.languageA(me.translate('hello','Hello'));
                        me.languageB(me.translate('world'));
                    }
                }
            ).fail(function () {
                let trace = me.services.getErrorInformation();
            }).always(() => {
                me.application.hideWaiter();
            });

        };

        /**
         * Demonstrates load component on demand and use of a vm factory function.
         * The factory function my be defined seperately or in-line as is doe here.
         */
        onShowForm = () => {
            console.log('Show form component');
            let me  = this;
            this.application.attachComponent(
                // component name
                'tests/test-form',

                // vm factory function
                (returnFuncton: (vm: any) => void) => {
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
                        // return instance via the final function.
                        returnFuncton(me.testForm);
                    });
                }

                // finalFunction parameter not needed here

            );
        };

        onSendMessage = () => {
            this.testForm.setMessage(this.messageText());
        };

        onShowMessageComponent = () => {
            this.attachComponent('tests/test-message');
            this.messageButtonVisible(false);
        };

        currentPage = ko.observable(1);
        maxPages = ko.observable(10);
        changePage = (move: number) => {
            let current = this.currentPage() + move;
            this.currentPage(current);
        }
    }


}
