var Peanut;
(function (Peanut) {
    class mailBox {
    }
    Peanut.mailBox = mailBox;
    class Application {
        constructor() {
            this.startVM = (vmName, final) => {
                Peanut.PeanutLoader.getConfig((IPeanutConfig) => {
                    this.koHelper.loadViewModel(vmName, (viewModel) => {
                        viewModel.start(this, () => {
                            if (final) {
                                final(viewModel);
                            }
                        });
                    });
                });
            };
            this.loadResources = (resourceList, successFunction) => {
                let names = resourceList;
                if (!(resourceList instanceof Array)) {
                    resourceList = resourceList.split(',');
                }
                else {
                    names = resourceList.join(',');
                }
                let listLength = resourceList.length;
                this.koHelper.loadResources(resourceList, () => {
                    if (successFunction) {
                        successFunction();
                    }
                });
            };
            this.bindNode = (containerName, context) => {
                this.koHelper.bindNode(containerName, context);
            };
            this.bindSection = (containerName, context) => {
                this.koHelper.bindSection(containerName, context);
            };
            this.registerComponents = (componentList, finalFunction) => {
                let componentNames = componentList;
                if (!(componentList instanceof Array)) {
                    componentList = componentList.split(',');
                }
                else {
                    componentNames = componentList.join(',');
                }
                let listLength = componentList.length;
                this.koHelper.registerComponents(componentList, () => {
                    Application.LogMessage('Registered ' + listLength + ' components: ' + componentNames);
                    finalFunction();
                });
            };
            this.registerComponentPrototype = (componentName, finalFunction) => {
                this.koHelper.loadAndRegisterComponentPrototype(componentName, () => {
                    Application.LogMessage('Registered component prototype: ' + componentName);
                    if (finalFunction) {
                        finalFunction();
                    }
                });
            };
            this.loadComponents = (componentList, finalFunction) => {
                let componentNames = componentList;
                if (componentList instanceof Array) {
                    componentNames = componentList.join(', ');
                }
                else {
                    componentList = componentList.split(',');
                }
                this.koHelper.loadComponentPrototypes(componentList, () => {
                    Application.LogMessage('Registered component prototypes: ' + componentNames);
                    if (finalFunction) {
                        finalFunction();
                    }
                });
            };
            this.registerComponent = (componentName, vmInstance, finalFunction) => {
                if (vmInstance) {
                    this.koHelper.registerComponentInstance(componentName, vmInstance, () => {
                        if (vmInstance !== null) {
                            Application.LogMessage('Registered instance of component: ' + componentName);
                        }
                        finalFunction();
                    });
                }
                else {
                    this.registerComponentPrototype(componentName, finalFunction);
                }
            };
            this.attachComponent = (componentName, vm, finalFunction) => {
                if (vm) {
                    this.koHelper.registerAndBindComponentInstance(componentName, vm, () => {
                        Application.LogMessage('Attached component: ' + componentName);
                        if (finalFunction) {
                            finalFunction();
                        }
                    });
                }
                else {
                    console.error('attachComponent: No component instance. Use ViewModelBase.attachComponent for component prototypes.');
                }
            };
        }
        initialize(successFunction) {
            let me = this;
            Application.instance = me;
            Peanut.PeanutLoader.checkConfig();
            me.koHelper = new Peanut.KnockoutHelper();
            Peanut.PeanutLoader.loadUiHelper(() => {
                MessageManager.instance.fontSet(Peanut.ui.helper.getFontSet());
                let resources = Peanut.ui.helper.getResourceList();
                me.loadResources(resources, () => {
                    me.attachComponent('@pnut/service-messages', MessageManager.instance, function () {
                        if (successFunction) {
                            successFunction();
                        }
                    });
                });
            });
        }
        loadStyleSheets(resourceList) {
            let names = resourceList;
            if (!(resourceList instanceof Array)) {
                resourceList = resourceList.split(',');
            }
            this.koHelper.loadStyleSheets(resourceList);
        }
        showWaiter(message = "Please wait . . .", waiterType = 'waiter-message') {
            Peanut.WaitMessage.show(message, waiterType);
        }
        hideWaitMessage() {
            Peanut.WaitMessage.hide();
        }
        hideWaiter() {
            Peanut.WaitMessage.hide();
        }
        showBannerWaiter(message = "Please wait . . .") {
            Peanut.WaitMessage.show(message, 'banner-waiter');
        }
        showProgress(message = "Please wait . . .") {
            Peanut.WaitMessage.showProgressWaiter(message);
        }
        setProgress(count) {
            Peanut.WaitMessage.setProgress(count);
        }
        showServiceMessages(messages) {
            let me = this;
            if (me.messageTimer) {
                clearInterval(me.messageTimer);
            }
            MessageManager.instance.setServiceMessages(messages);
            let intervalValue = 10000;
            for (let i = 0; i < messages.length; i++) {
                if (messages[0].MessageType != Peanut.infoMessageType) {
                    intervalValue = 15000;
                    break;
                }
            }
            me.messageTimer = window.setInterval(function () {
                MessageManager.instance.clearMessages();
                clearInterval(me.messageTimer);
            }, intervalValue);
        }
        hideServiceMessages() {
            let me = this;
            if (me.messageTimer) {
                clearInterval(me.messageTimer);
                me.messageTimer = null;
            }
            MessageManager.instance.clearMessages();
        }
        showError(errorMessage) {
            if (errorMessage) {
                MessageManager.instance.addMessage(errorMessage, Peanut.errorMessageType);
            }
            else {
                MessageManager.instance.clearMessages(Peanut.errorMessageType);
            }
        }
        showMessage(messageText) {
            if (messageText) {
                MessageManager.instance.addMessage(messageText, Peanut.infoMessageType);
            }
            else {
                MessageManager.instance.clearMessages(Peanut.infoMessageType);
            }
        }
        showWarning(messageText) {
            if (messageText) {
                MessageManager.instance.addMessage(messageText, Peanut.warningMessageType);
            }
            else {
                MessageManager.instance.clearMessages(Peanut.warningMessageType);
            }
        }
        setErrorMessage(messageText) {
            if (messageText) {
                MessageManager.instance.setMessage(messageText, Peanut.errorMessageType);
            }
            else {
                MessageManager.instance.clearMessages(Peanut.errorMessageType);
            }
        }
        setInfoMessage(messageText) {
            if (messageText) {
                MessageManager.instance.setMessage(messageText, Peanut.infoMessageType);
            }
            else {
                MessageManager.instance.clearMessages(Peanut.infoMessageType);
            }
        }
        setWarningMessage(messageText) {
            if (messageText) {
                MessageManager.instance.setMessage(messageText, Peanut.warningMessageType);
            }
            else {
                MessageManager.instance.clearMessages(Peanut.infoMessageType);
            }
        }
        static LogMessage(message) {
            Peanut.logger.write(message);
        }
    }
    Peanut.Application = Application;
    class MessageManager {
        constructor() {
            this.errorMessages = ko.observableArray([]);
            this.infoMessages = ko.observableArray([]);
            this.warningMessages = ko.observableArray([]);
            this.fontSet = ko.observable('');
            this.addMessage = (message, messageType) => {
                switch (messageType) {
                    case Peanut.errorMessageType:
                        this.errorMessages.push({ type: MessageManager.errorClass, text: message });
                        break;
                    case Peanut.warningMessageType:
                        this.warningMessages.push({ type: MessageManager.warningClass, text: message });
                        break;
                    default:
                        this.infoMessages.push({ type: MessageManager.infoClass, text: message });
                        break;
                }
            };
            this.setMessage = (message, messageType) => {
                switch (messageType) {
                    case Peanut.errorMessageType:
                        this.errorMessages([{ type: MessageManager.errorClass, text: message }]);
                        break;
                    case Peanut.warningMessageType:
                        this.warningMessages([{ type: MessageManager.warningClass, text: message }]);
                        break;
                    default:
                        this.infoMessages([{ type: MessageManager.infoClass, text: message }]);
                        break;
                }
            };
            this.clearMessages = (messageType = Peanut.allMessagesType) => {
                if (messageType == Peanut.errorMessageType || messageType == Peanut.allMessagesType) {
                    this.errorMessages([]);
                }
                if (messageType == Peanut.warningMessageType || messageType == Peanut.allMessagesType) {
                    this.warningMessages([]);
                }
                if (messageType == Peanut.infoMessageType || messageType == Peanut.allMessagesType) {
                    this.infoMessages([]);
                }
            };
            this.clearInfoMessages = () => {
                this.infoMessages([]);
            };
            this.clearErrorMessages = () => {
                this.errorMessages([]);
            };
            this.clearWarningMessages = () => {
                this.warningMessages([]);
            };
            this.setServiceMessages = (messages) => {
                let count = messages.length;
                let errorArray = [];
                let warningArray = [];
                let infoArray = [];
                for (let i = 0; i < count; i++) {
                    let message = messages[i];
                    switch (message.MessageType) {
                        case Peanut.errorMessageType:
                            errorArray.push({ type: MessageManager.errorClass, text: message.Text });
                            break;
                        case Peanut.warningMessageType:
                            warningArray.push({ type: MessageManager.warningClass, text: message.Text });
                            break;
                        default:
                            infoArray.push({ type: MessageManager.infoClass, text: message.Text });
                            break;
                    }
                }
                this.errorMessages(errorArray);
                this.warningMessages(warningArray);
                this.infoMessages(infoArray);
            };
            this.hideWaitMessage = () => {
                Peanut.WaitMessage.hide();
            };
        }
    }
    MessageManager.instance = new MessageManager();
    MessageManager.errorClass = "service-message-error";
    MessageManager.infoClass = "service-message-information";
    MessageManager.warningClass = "service-message-warning";
    Peanut.MessageManager = MessageManager;
})(Peanut || (Peanut = {}));
//# sourceMappingURL=App.js.map