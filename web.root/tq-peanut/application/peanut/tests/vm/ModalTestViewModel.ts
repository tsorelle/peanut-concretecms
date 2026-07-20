/**
 * Created by Terry on 5/7/2017.
 */

// required for all view models:
/// <reference path='../../../../pnut/core/ViewModelBase.ts' />
/// <reference path='../../../../typings/knockout/index.d.ts' />
/// <reference path='../../../../typings/bootstrap-5/index.d.ts' />
/// <reference path='../../../../typings/bootstrap-5/js/dist/modal.d.ts' />

namespace Peanut {

    export class ModalTestViewModel  extends Peanut.ViewModelBase {
        showing = ko.observable(false);
        testModal : any;
        confirmModel: any;

        // call this funtions at end of page
        init(successFunction?: () => void) {
            console.log('Init ModalTest');
            let me = this;
            me.application.registerComponents(['@pnut/modal-confirm'], () => {
                me.bindDefaultSection();
                successFunction();
            });
        }

        onShowClick = () => {
            if (!this.testModal) {
                this.testModal = this.showModal('test-modal');
            }
            else {
                this.showModal(this.testModal);
            }
        };

        onSaveChanges = () => {
            this.hideModal(this.testModal);
            // or more efficent if we know we are using bootstrap 5
            // this.testModal.hide();
            alert('Save changes');
        }

        confirmTest = () => {
            this.showModal('#confirm-modal-test')
        }

        onConfirmTestOk = () => {
            // less efficient than the test example but will work across differnt bootstrap versions or other
            // supported frameworkd
            this.hideModal('#confirm-modal-test');
            alert('OK Clicked');
        }

    }
}
