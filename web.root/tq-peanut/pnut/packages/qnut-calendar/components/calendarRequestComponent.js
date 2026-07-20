var QnutCalendar;
(function (QnutCalendar) {
    class calendarRequestComponent {
        constructor(params) {
            this.ready = ko.observable(false);
            this.requestor = ko.observable('');
            this.title = ko.observable('');
            this.email = ko.observable('');
            this.date = ko.observable('');
            this.time = ko.observable('');
            this.location = ko.observable('meeting house');
            this.repeat = ko.observable('');
            this.room = ko.observable('');
            this.eventType = ko.observable('');
            this.committee = ko.observable('');
            this.comments = ko.observable('');
            this.selectedEventType = ko.observable('Public');
            this.eventTypes = ['Public', 'Private', 'Outside group'];
            this.requestorError = ko.observable(false);
            this.emailError = ko.observable(false);
            this.eventError = ko.observable(false);
            this.initialize = (user) => {
                let me = this;
                me.requestor(user.fullname);
                me.email(user.email);
                me.ready(true);
            };
            this.clearErrors = () => {
                this.requestorError(false);
                this.emailError(false);
                this.eventError(false);
            };
            this.clear = () => {
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
            this.clearAll = () => {
                this.requestor('');
                this.email('');
                this.clear();
            };
            this.getRequest = () => {
                let valid = true;
                this.clearErrors();
                let request = {
                    requestor: this.requestor().trim(),
                    email: this.email().trim(),
                    title: this.title().trim(),
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
            this.send = () => {
                let me = this;
                let request = this.getRequest();
                if (request === false) {
                    return;
                }
                me.services.executeService('peanut.qnut-calendar::SendCalendarRequest', request, (serviceResponse) => {
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
            this.cancel = () => {
                this.clear();
                if (this.onClose) {
                    this.onClose();
                }
            };
            let me = this;
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
            let ownerVm = (params.owner());
            me.application = ownerVm.getApplication();
            me.services = ownerVm.getServices();
            ownerVm.getUserDetails(this.initialize);
        }
    }
    QnutCalendar.calendarRequestComponent = calendarRequestComponent;
})(QnutCalendar || (QnutCalendar = {}));
//# sourceMappingURL=calendarRequestComponent.js.map