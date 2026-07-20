/**
 * Created by Terry on 5/24/2016.
 */


///<reference path="../../../../pnut/js/searchListObservable.ts"/>
/// <reference path='../../../../typings/knockout/knockout.d.ts' />
/// <reference path='../../../../pnut/core/Peanut.d.ts' />
/// <reference path="../../../../pnut/core/ViewModelBase.ts" />

namespace QnutCalendar {
    import ViewModelBase = Peanut.ViewModelBase;
    import ServiceBroker = Peanut.ServiceBroker;
    import IPeanutClient = Peanut.IPeanutClient;
    import IUserDetails = Peanut.IUserDetails;
    import INameValuePair = Peanut.INameValuePair;

    interface ICalendarRequestMessage {
        requestor: string;
        email: string;
        title: string;
        location: string;
        room: string;
        date: string;
        time: string;
        eventType: string;
        repeat: string;
        committee: string;
        comments: string;
    }

    export class calendarRequestComponent {
        // parameters
        private application: IPeanutClient;
        private services: ServiceBroker;
        owner : () => ViewModelBase;
        onClose : () => void;

        // observables
        ready = ko.observable(false);
        requestor = ko.observable('');
        title = ko.observable('');
        email = ko.observable('');
        date = ko.observable('');
        time = ko.observable('');
        location = ko.observable('meeting house');
        repeat = ko.observable('');
        room = ko.observable('');
        eventType= ko.observable('');
        committee= ko.observable('');
        comments= ko.observable('');
        selectedEventType = ko.observable<string>('Public');
        eventTypes = ['Public','Private','Outside group'];

        requestorError = ko.observable(false);
        emailError = ko.observable(false);
        eventError = ko.observable(false);

        constructor(params: any) {
            let me = this;

            // initialize observavles and variables from params
            if (!params) {
                console.error('calendarRequestComponent: Params not defined in translateComponent');
                return;
            }

            if (!params.owner) {
                console.error('calendarRequestComponent: Owner parameter required for modalConfirmComponent');
                return;
            }

            if (params.onClose) {
                this.onClose = params.onClose;
            }

            me.owner = params.owner;
            let ownerVm = <ViewModelBase>(params.owner());
            me.application = ownerVm.getApplication();
            me.services = ownerVm.getServices();


            ownerVm.getUserDetails(this.initialize);

        }

        initialize = (user: IUserDetails) => {
            let me = this;
            me.requestor(user.fullname);
            me.email(user.email);
            me.ready(true);
        };

        clearErrors = () => {
            this.requestorError(false);
            this.emailError(false);
            this.eventError(false);
        };

        clear = () => {
            this.clearErrors();
            this.title('');
            this.date('');
            this.time('');
            this.location('meeting house');
            this.repeat('');
            this.room('');
            this.eventType('');
            this.committee('');
            this.comments('');
        };

        clearAll = () => {
            this.requestor('');
            this.email('');
            this.clear();
        };


        getRequest = () => {
            let valid = true;
            this.clearErrors();
            let request = <ICalendarRequestMessage> {
                requestor: this.requestor().trim(),
                email: this.email().trim(),
                title : this.title().trim(),
                comments: this.comments(),
                eventType: this.selectedEventType(),
                committee: this.committee(),
                date: this.date().trim(),
                location: this.location(),
                room: this.room(),
                time: this.time(),
                repeat: this.repeat(),
            };
            if (!request.requestor) {
                this.requestorError(true);
                valid = false;
            }
            if (!(request.email && /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/.test(request.email))) {
                this.emailError(true);
                valid = false;
            }

            if (!request.date) {
                this.eventError(true);
                valid = false;
            }

            if (!request.title) {
                this.eventError(true);
                valid = false;
            }

            if ((!request.location) && (!request.room)) {
                this.eventError(true);
                valid = false;
            }

            return valid ? request : false;
        };

        send = () => {
            let me = this;
            let request = this.getRequest();
            if (request === false) {
                return;
            }

            me.services.executeService('peanut.qnut-calendar::SendCalendarRequest', request,
                (serviceResponse: Peanut.IServiceResponse) => {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                    }
                })
                .fail(() => {
                    let trace = me.services.getErrorInformation();
                })
                .always(() => {
                    this.clear();
                    if (this.onClose) {
                        this.onClose();
                    }
                });
        };

        cancel = () => {
            this.clear();
            if (this.onClose) {
                this.onClose();
            }
        };
    }

}