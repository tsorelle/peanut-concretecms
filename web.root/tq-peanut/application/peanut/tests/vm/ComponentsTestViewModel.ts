// required for all view models:
/// <reference path='../../../../pnut/core/ViewModelBase.ts' />
/// <reference path='../../../../typings/knockout/index.d.ts' />
///<reference path="../../../assets/js/TestLib.ts"/>

// Module
namespace Peanut {
    // ComponentsTest view model
    export class ComponentsTestViewModel  extends Peanut.ViewModelBase {
        messageText = ko.observable('This is a simple test vm');
        contextValue = ko.observable();
        private testForm : testFormComponent;
        messagePanel = ko.observable('button');
        messageFormVisible = ko.observable(false);
        messageButtonVisible = ko.observable(true);
        password = ko.observable('');
        username = ko.observable('')
        testMonth = ko.observable<Peanut.INameValuePair>();

        // call this funtions at end of page
        init(successFunction?: () => void) {
            console.log('Init ComponentsTest');
            let me = this;
            me.contextValue(me.getVmContext());
            me.application.registerComponents(
                '@pnut/change-password,@pnut/month-lookup,' +
                'tests/intro-message,@pnut/modal-confirm,@pnut/pager,@pnut/multi-select,' +
                '@pnut/incremental-select,@pnut/selected-list,@pnut/lookup-select', () => {
                me.application.loadComponents('tests/message-constructor', () => {
                    me.application.loadResources([
                        '@lib:local/TestLib.js',
                        // '/application/assets/js/libraries/TestLib.js',
                        '@pnut/ViewModelHelpers',
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
        }

        onShowMessageComponent = () => {
            this.attachComponent('tests/test-message');
            this.messageButtonVisible(false);
        };

        onSendMessage = () => {
            this.testForm.setMessage(this.messageText());
        };

        onSigninRequest = () => {
            let message = "Password: "+this.password();
            alert(message)
        }

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

        onAddMonth = () => {
            let month = this.testMonth();
            if (!month) {
                console.log('No month selected')
                return;
            }
            let message = 'Selected month: ' + month.Name + ' (value: ' + month.Value + ')';
            alert(message)
        }
    }
}
