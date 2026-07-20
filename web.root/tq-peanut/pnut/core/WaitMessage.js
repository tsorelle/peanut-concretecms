var Peanut;
(function (Peanut) {
    class WaitMessage {
        static setCancelHandler(handler) {
            WaitMessage.cancelHandler = handler;
        }
        static addTemplate(templateName, content) {
            templateName = templateName.split('/').pop();
            WaitMessage.templates[templateName] = content;
        }
        static show(message = 'Please wait ...', waiterType = 'spin-waiter', cancelHandler = null) {
            if (WaitMessage.visible) {
                if (WaitMessage.visible === 1) {
                    console.log('Warning: Wait message is still initializing. Cannot call show more than once before hide.');
                }
                else {
                    console.log('Warning: Wait message is still active');
                    WaitMessage.setMessage(message);
                }
                console.log(message);
                return;
            }
            WaitMessage.visible = 1;
            WaitMessage.cancelHandler = cancelHandler;
            WaitMessage.waiterType = (!waiterType) || waiterType === 'waiter-message' ? 'banner-waiter' : waiterType;
            WaitMessage.waitDialog = document.getElementById(WaitMessage.waiterType);
            if (WaitMessage.waitDialog) {
                if (WaitMessage.waiterType === 'progress-waiter') {
                    WaitMessage.setProgress(0);
                }
                WaitMessage.attachWaiter(message);
            }
            else {
                if (!(waiterType in WaitMessage.templates)) {
                    WaitMessage.loadWaitMessageTemplate(WaitMessage.waiterType, () => {
                        WaitMessage.attachWaiter(message);
                    });
                }
            }
        }
        static attachWaiter(message = 'Please wait...') {
            WaitMessage.waitDialog = document.createElement('div');
            WaitMessage.waitDialog.style.display = 'block';
            WaitMessage.waitDialog.innerHTML = WaitMessage.templates[WaitMessage.waiterType];
            let parentDiv = document.getElementById('service-message-block');
            if (!parentDiv) {
                console.log('ERROR: service-message-block not found.');
                return;
            }
            parentDiv.appendChild(WaitMessage.waitDialog);
            let containerId = '#' + WaitMessage.waiterType;
            let spanId = containerId + '-text';
            let container = document.querySelector(containerId);
            if (!container) {
                console.log('Wait dialog "' + containerId + ' not found.');
                return;
            }
            WaitMessage.span = container.querySelector(spanId);
            if (!WaitMessage.span) {
                console.log('Wait messages text container not found for ' + spanId);
                return;
            }
            WaitMessage.span.textContent = message;
            container.style.display = 'block';
            WaitMessage.waitDialog = container;
            if (WaitMessage.waiterType === 'progress-waiter') {
                WaitMessage.progressBar = document.getElementById('wait-progress-bar');
            }
            WaitMessage.visible = 2;
        }
        static setMessage(message) {
            let waiterReady = false;
            if (WaitMessage.visible) {
                if (WaitMessage.span) {
                    WaitMessage.span.textContent = message;
                    waiterReady = true;
                }
                else {
                    console.log('Wait message object not found');
                    return;
                }
            }
            if (!waiterReady) {
                console.log(message);
            }
        }
        static showProgressWaiter(message, cancelHandler = null) {
            WaitMessage.show(message, 'progress-waiter', cancelHandler);
        }
        static hide() {
            if (WaitMessage.visible) {
                if (WaitMessage.visible === 2) {
                    let container = document.querySelector('#' + WaitMessage.waiterType);
                    if (container) {
                        container.style.display = 'none';
                    }
                    else {
                        console.log('Wait message is not visible');
                    }
                    WaitMessage.visible = 0;
                }
            }
            else {
                console.log("WARNING: WaitMessage.hide called but no wait message is visible");
            }
        }
        static setProgress(count, showLabel = false) {
            if (WaitMessage.waiterType == 'progress-waiter') {
                let percent = count + '%';
                let bar = document.querySelector('#wait-progress-bar');
                if (bar) {
                    bar.style.width = percent;
                    if (showLabel) {
                        bar.textContent = percent;
                    }
                    return;
                }
                else {
                    console.log('Process ' + percent + ' complete.');
                }
            }
        }
    }
    WaitMessage.waitDialog = null;
    WaitMessage.waiterType = 'waiter-message';
    WaitMessage.templates = Array();
    WaitMessage.visible = 0;
    WaitMessage.cancel = () => {
        if (WaitMessage.cancelHandler) {
            WaitMessage.cancelHandler();
        }
        else {
            WaitMessage.hide();
        }
    };
    WaitMessage.loadWaitMessageTemplate = (templateName, successFunction) => {
        let ext = Peanut.Config.values.uiExtension;
        templateName = '@pnut/extensions/' + ext + '/' + templateName;
        Peanut.Application.instance.koHelper.getHtmlTemplate(templateName, function (htmlSource) {
            if (htmlSource) {
                WaitMessage.addTemplate(templateName, htmlSource);
                successFunction();
            }
            else {
                console.error('Message template not found: ' + templateName);
            }
        });
    };
    Peanut.WaitMessage = WaitMessage;
})(Peanut || (Peanut = {}));
//# sourceMappingURL=WaitMessage.js.map