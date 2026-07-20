/**
 * Created by Terry on 5/4/2017.
 */
namespace Peanut {
    export class WaitMessage {
        private static waitDialog: HTMLElement = null;
        private static waiterType: string = 'waiter-message';
        private static templates = Array<string>();
        private static visible = 0; // 0 = closed, 1 = initializing, 2 = ready
        public static span: HTMLSpanElement;
        public static progressBar: HTMLElement;
        public static cancelHandler: () => void;


        public static setCancelHandler(handler: () => void) {
            WaitMessage.cancelHandler = handler;
        }

        public static cancel = () => {
            if (WaitMessage.cancelHandler) {
                WaitMessage.cancelHandler();
            } else {
                WaitMessage.hide();
            }
        }

        public static addTemplate(templateName: string, content: string) {
            templateName = templateName.split('/').pop(); // strip location alias and path.
            WaitMessage.templates[templateName] = content;
        }

        public static show(message: string = 'Please wait ...', waiterType: string = 'spin-waiter', cancelHandler: () => void = null) {
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
            // find previously loaded
            WaitMessage.waitDialog = document.getElementById(WaitMessage.waiterType);
            if (WaitMessage.waitDialog) {
                if (WaitMessage.waiterType === 'progress-waiter') {
                    WaitMessage.setProgress(0);
                }
                WaitMessage.attachWaiter(message);
            } else {
                if (!(waiterType in WaitMessage.templates)) {
                    WaitMessage.loadWaitMessageTemplate(WaitMessage.waiterType, () => {
                        WaitMessage.attachWaiter(message);
                    } );
                }
            }
        }

        /**
         * Add an HTML template to wait message
         *
         * @param templateName
         * @param successFunction
         */
        private static loadWaitMessageTemplate = (templateName: string, successFunction: () => void) => {
            let ext = Peanut.Config.values.uiExtension;
            templateName = '@pnut/extensions/' + ext + '/' + templateName;
            Application.instance.koHelper.getHtmlTemplate(templateName, function (htmlSource: string) {
                if (htmlSource) {
                    WaitMessage.addTemplate(templateName, htmlSource);
                    successFunction();
                } else {
                    console.error('Message template not found: ' + templateName)
                }
            });
        };

        private static attachWaiter(message: string = 'Please wait...') {
            WaitMessage.waitDialog = document.createElement('div');
            WaitMessage.waitDialog.style.display = 'block';
            WaitMessage.waitDialog.innerHTML = WaitMessage.templates[WaitMessage.waiterType];
            let parentDiv = document.getElementById('service-message-block');
            if (!parentDiv) {
                console.log('ERROR: service-message-block not found.')
                return;
            }
            parentDiv.appendChild(WaitMessage.waitDialog);

            let containerId = '#' + WaitMessage.waiterType;
            let spanId = containerId + '-text';
            let container: HTMLElement = document.querySelector(containerId);
            if (!container) {
                console.log('Wait dialog "' + containerId + ' not found.')
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
                WaitMessage.progressBar = document.getElementById('wait-progress-bar')
            }
            WaitMessage.visible = 2;
        }

        public static setMessage(message: string) {
            let waiterReady = false;
            if (WaitMessage.visible) {
                if (WaitMessage.span) {
                    WaitMessage.span.textContent = message;
                    waiterReady = true;
                } else {
                    console.log('Wait message object not found')
                    return;
                }
            }
            if (!waiterReady) {
                console.log(message)
            }
        }

        static showProgressWaiter(message: string, cancelHandler: () => void = null) {
            WaitMessage.show(message, 'progress-waiter', cancelHandler)
        }

        public static hide() {
            if (WaitMessage.visible) {
                if (WaitMessage.visible === 2) {
                    let container: HTMLElement = document.querySelector('#' + WaitMessage.waiterType);
                    if (container) {
                        container.style.display = 'none';
                    } else {
                        console.log('Wait message is not visible')
                    }
                    WaitMessage.visible = 0;
                }
            }
            else {
                    console.log("WARNING: WaitMessage.hide called but no wait message is visible")
            }
        }


        public static setProgress(count: number, showLabel: boolean = false) {

            if (WaitMessage.waiterType == 'progress-waiter') {
                let percent = count + '%';
                let bar: HTMLSpanElement = document.querySelector('#wait-progress-bar')
                if (bar) {
                    bar.style.width = percent;
                    if (showLabel) {
                        bar.textContent = percent;
                    }
                    return;
                } else {
                    console.log('Process ' + percent + ' complete.')
                }
            }
        }

    }


} // end namespace