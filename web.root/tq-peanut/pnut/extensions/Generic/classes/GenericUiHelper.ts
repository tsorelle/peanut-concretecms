/**
 * Created by Terry on 7/9/2017.
 */
///<reference path="../../../../typings/bootstrap-5/index.d.ts"/>

namespace Peanut {
    /**
     *  Implementation class for Bootstrap dependencies
     */
    export class GenericUiHelper {
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

        public getResourceList = () => {
            return [];
        };

        public getFramework = () => {
            return 'Generic'
        };

        public getVersion = () => {
            return 1;
        };

        public getFontSet = () => {
            return 'peanut';
        }

    }
}