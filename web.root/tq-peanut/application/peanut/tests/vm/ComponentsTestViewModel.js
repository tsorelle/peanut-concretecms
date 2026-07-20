var Peanut;
(function (Peanut) {
    class ComponentsTestViewModel extends Peanut.ViewModelBase {
        constructor() {
            super(...arguments);
            this.messageText = ko.observable('This is a simple test vm');
            this.contextValue = ko.observable();
            this.messagePanel = ko.observable('button');
            this.messageFormVisible = ko.observable(false);
            this.messageButtonVisible = ko.observable(true);
            this.password = ko.observable('');
            this.username = ko.observable('');
            this.testMonth = ko.observable();
            this.onShowMessageComponent = () => {
                this.attachComponent('tests/test-message');
                this.messageButtonVisible(false);
            };
            this.onSendMessage = () => {
                this.testForm.setMessage(this.messageText());
            };
            this.onSigninRequest = () => {
                let message = "Password: " + this.password();
                alert(message);
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
            this.onAddMonth = () => {
                let month = this.testMonth();
                if (!month) {
                    console.log('No month selected');
                    return;
                }
                let message = 'Selected month: ' + month.Name + ' (value: ' + month.Value + ')';
                alert(message);
            };
        }
        init(successFunction) {
            console.log('Init ComponentsTest');
            let me = this;
            me.contextValue(me.getVmContext());
            me.application.registerComponents('@pnut/change-password,@pnut/month-lookup,' +
                'tests/intro-message,@pnut/modal-confirm,@pnut/pager,@pnut/multi-select,' +
                '@pnut/incremental-select,@pnut/selected-list,@pnut/lookup-select', () => {
                me.application.loadComponents('tests/message-constructor', () => {
                    me.application.loadResources([
                        '@lib:local/TestLib.js',
                        '@pnut/ViewModelHelpers',
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
    }
    Peanut.ComponentsTestViewModel = ComponentsTestViewModel;
})(Peanut || (Peanut = {}));
//# sourceMappingURL=ComponentsTestViewModel.js.map