/**
 * Created by Terry on 7/9/2017.
 */
///<reference path="../../../../typings/bootstrap-5/index.d.ts"/>

namespace Peanut {
    /**
     *  Implementation class for Bootstrap dependencies
     */
    export class BootstrapFiveUiHelper {
        private modals :  { [key: string]: any } = {};
        /**
         * Deprecated.  Use WaitMessage.show()
         *
         * @param message
         * @param id
         * @param container
         * @param modal
         */
        public showMessage = (message: string, id: string,  container : any, modal=true ) => {
            WaitMessage.show(message,id)
        };

        /**
         * Deprecated, use WaitMessage.hide()
         * @param container
         */
        public hideMessage = (container : any) => {
            WaitMessage.hide();
        };

        private getModal : any  = (modal : any) => {
            if (typeof modal === 'string' || modal instanceof String) {
                if (modal.charAt(0) === '#') {
                    modal = modal.substring(1);
                }
                let obj = this.modals[<string>modal];
                if (!obj) {
                    let ele = document.getElementById(<string>modal);
                    if (ele === null)  {
                        console.log('Error: cannot find element ' + (<string>modal));
                    }
                    else {
                        obj = new bootstrap.Modal(ele,
                            {
                                backdrop: 'static',
                                keyboard: false
                            }
                            );
                        this.modals[<string>modal] = obj;
                    }
                }
                return obj;
                // return new bootstrap.Modal(document.getElementById(<string>modal));
            }
            return modal;
        }
        public showModal = (modal : any) => {
            modal = this.getModal(modal);
            if (!modal) {
                return null;
            }
            modal.show();
            return modal;
        };

        public hideModal = (modal: any) => {
            modal = this.getModal(modal);
            modal.hide();
        };

        public getResourceList = () => {
            return ['@lib:fontawesome'];
        };

        public getFramework = () => {
            return 'Bootstrap'
        };

        public getVersion = () => {
            return 5;
        };

        public getFontSet = () => {
            return 'FA';
        }

    }
}