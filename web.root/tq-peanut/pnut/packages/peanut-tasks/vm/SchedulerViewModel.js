var PeanutTasks;
(function (PeanutTasks) {
    class SchedulerViewModel extends Peanut.ViewModelBase {
        constructor() {
            super(...arguments);
            this.logRequest = {
                filter: null,
                limit: 15,
                offset: 0
            };
            this.tab = ko.observable('schedule');
            this.taskEditForm = {
                id: ko.observable(0),
                taskNameError: ko.observable(''),
                namespaceError: ko.observable(''),
                frequencyError: ko.observable(''),
                active: ko.observable(true),
                taskname: ko.observable(''),
                namespace: ko.observable(''),
                inputs: ko.observable(''),
                startdate: ko.observable(''),
                enddate: ko.observable(''),
                time: ko.observable(''),
                date: ko.observable(''),
                frequency: ko.observable(''),
                frequencyCount: ko.observable(1),
                comments: ko.observable(''),
                frequencyUnit: null,
                dayOfWeek: null,
                intervalType: null,
                weekOrdinal: null,
                updating: ko.observable(false)
            };
            this.taskQueue = ko.observableArray([]);
            this.logFilters = ko.observableArray();
            this.logFilter = ko.observable();
            this.taskLog = ko.observableArray([]);
            this.prevEntries = ko.observable(false);
            this.moreEntries = ko.observable(false);
            this.setSchedule = (schedule) => {
                let list = [];
                list.push('All');
                schedule.forEach((item) => {
                    if (list.indexOf(item.taskname) === -1) {
                        list.push(item.taskname);
                    }
                });
                this.logFilters(list);
                this.logFilter('All');
                this.taskQueue(schedule);
            };
            this.applyLogFilter = (filter) => {
                this.logFilter(filter);
                this.logRequest.filter = filter == 'All' ? null : filter;
                this.refreshLogs();
            };
            this.showScheduleTab = () => {
                let me = this;
                me.tab('schedule');
            };
            this.editTask = (item) => {
                let me = this;
                me.clearTaskEditForm();
                me.taskEditForm.id(item.id);
                me.taskEditForm.active(item.active == 1);
                me.taskEditForm.namespace(item.namespace);
                me.taskEditForm.comments(item.comments);
                me.taskEditForm.inputs(item.inputs);
                me.taskEditForm.startdate(item.startdate);
                me.taskEditForm.enddate(item.enddate);
                me.taskEditForm.taskname(item.taskname);
                me.assignIntervalValues(item);
                Peanut.ui.helper.showModal('edit-task-modal');
            };
            this.setIntervalType = (value = null) => {
                this.taskEditForm.intervalType.unsubscribe();
                if (value) {
                    this.taskEditForm.intervalType.setValue(value);
                }
                else {
                    this.taskEditForm.intervalType.setDefaultValue();
                }
                this.taskEditForm.intervalType.subscribe();
            };
            this.assignIntervalValues = (item) => {
                this.taskEditForm.frequency(item.frequency);
                let intervalType = 1;
                if (!item.intervalType) {
                    item.intervalType = 1;
                }
                else {
                    intervalType = Number(item.intervalType);
                }
                this.setIntervalType(intervalType);
                if (!item.frequency) {
                    return;
                }
                let parts = item.frequency.split(' ');
                switch (intervalType) {
                    case 2:
                        this.taskEditForm.frequencyCount(parts[0] || 1);
                        if (parts[1]) {
                            this.taskEditForm.frequencyUnit.setValue(parts[1].toLowerCase());
                        }
                        break;
                    case 3:
                        let dowPart = (this.taskEditForm.weekOrdinal.hasOption(parts[0])) ? 1 : 0;
                        let timePart = dowPart + 1;
                        if (dowPart > 0) {
                            this.taskEditForm.weekOrdinal.setValue(parts[0]);
                        }
                        if (dowPart < parts.length - 1) {
                            this.taskEditForm.dayOfWeek.setValue(parts[dowPart]);
                            if (parts[timePart]) {
                                this.taskEditForm.time(parts[timePart]);
                            }
                        }
                        break;
                    case 4:
                        if (item.frequency) {
                            this.taskEditForm.time(item.frequency);
                        }
                        break;
                    case 5:
                        if (item.frequency) {
                            this.taskEditForm.date(parts[0]);
                            if (parts.length > 1) {
                                this.taskEditForm.time(parts[1]);
                            }
                        }
                        break;
                    default:
                        return {};
                }
            };
            this.refreshLogs = () => {
                let me = this;
                me.logRequest.offset = 0;
                me.getLogs();
            };
            this.getLogs = () => {
                let me = this;
                me.application.showBannerWaiter(me.translate('tasks-get-log'));
                me.services.executeService('peanut.peanut-tasks::GetTaskLog', me.logRequest, function (serviceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let log = serviceResponse.Value;
                        me.taskLog(log);
                        me.moreEntries(log.length == me.logRequest.limit);
                        me.prevEntries(me.logRequest.offset > 0);
                        me.tab('log');
                    }
                }).fail(() => {
                    let trace = me.services.getErrorInformation();
                }).always(() => {
                    me.application.hideWaiter();
                });
            };
            this.getNextLog = () => {
                let me = this;
                me.logRequest.offset += me.logRequest.limit;
                me.getLogs();
            };
            this.getPrevLog = () => {
                let me = this;
                if (me.logRequest.offset >= me.logRequest.limit) {
                    me.logRequest.offset -= me.logRequest.limit;
                }
                me.getLogs();
            };
            this.newTask = () => {
                let me = this;
                me.clearTaskEditForm();
                Peanut.ui.helper.showModal('edit-task-modal');
            };
            this.getFrequencyValue = () => {
                let result = '';
                switch (this.taskEditForm.intervalType.getValue()) {
                    case 2:
                        result = this.taskEditForm.frequencyCount() + ' ' + this.taskEditForm.frequencyUnit.getValue();
                        break;
                    case 3:
                        let ord = this.taskEditForm.weekOrdinal.getValue();
                        if (ord != 'every') {
                            result = ord;
                        }
                        result += ' ' + (this.taskEditForm.dayOfWeek.getValue());
                        if (this.taskEditForm.time()) {
                            result += ' ' + this.taskEditForm.time();
                        }
                        break;
                    case 4:
                        result = this.taskEditForm.time();
                        break;
                    case 5:
                        let date = this.taskEditForm.date().trim();
                        if (!date) {
                            this.taskEditForm.frequencyError('A date is required for one-time execution');
                            return false;
                        }
                        result = date;
                        if (this.taskEditForm.time()) {
                            result += ' ' + this.taskEditForm.time();
                        }
                        break;
                }
                return result.trim();
            };
            this.updateTask = () => {
                let me = this;
                me.clearErrors();
                let request = {
                    intervalType: me.taskEditForm.intervalType.getValue(),
                    inputs: me.taskEditForm.inputs(),
                    frequency: me.getFrequencyValue(),
                    active: me.taskEditForm.active() ? 1 : 0,
                    taskname: me.taskEditForm.taskname(),
                    startdate: me.taskEditForm.startdate(),
                    comments: me.taskEditForm.comments(),
                    namespace: me.taskEditForm.namespace(),
                    subdir: '',
                    enddate: me.taskEditForm.enddate(),
                    id: me.taskEditForm.id()
                };
                if (me.validateTask(request)) {
                    let nsparts = request.namespace.split('::');
                    request.namespace = nsparts[0].replace('\\', '::');
                    if (nsparts.length > 1) {
                        request.subdir = nsparts[1];
                    }
                    request.namespace = request.namespace.replace('\\', '::');
                    me.taskEditForm.updating(true);
                    me.services.executeService('peanut.peanut-tasks::UpdateScheduledTask', request, function (serviceResponse) {
                        if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                            let response = serviceResponse.Value;
                            if (response.error == 'class') {
                                me.taskEditForm.taskNameError('Cannot create task class');
                                me.taskEditForm.namespaceError('Namespace may be incorrect');
                            }
                            else {
                                me.taskQueue(response.schedule);
                                Peanut.ui.helper.hideModal('#edit-task-modal');
                            }
                        }
                    }).fail(() => {
                        let trace = me.services.getErrorInformation();
                    }).always(() => {
                        me.taskEditForm.updating(false);
                    });
                }
            };
            this.onIntervalTypeChange = (type) => {
                let me = this;
                me.taskEditForm.time('');
            };
        }
        init(successFunction) {
            let me = this;
            Peanut.logger.write('Scheduler Init');
            me.application.loadResources([
                '@pnut/selectListObservable',
            ], () => {
                me.taskEditForm.intervalType = new Peanut.selectListObservable(me.onIntervalTypeChange, [
                    { name: 'On demand', id: 1 },
                    { name: 'Regular interval', id: 2 },
                    { name: 'Weeky', id: 3 },
                    { name: 'Daily', id: 4 },
                    { name: 'Fixed time', id: 5 }
                ], 1);
                me.taskEditForm.frequencyUnit = new Peanut.selectListObservable(null, [
                    { name: 'Minutes', id: 'minutes' },
                    { name: 'Hours', id: 'hours' },
                    { name: 'Days', id: 'days' },
                    { name: 'Months', id: 'months' }
                ], 'minutes');
                me.taskEditForm.dayOfWeek = new Peanut.selectListObservable(null, [
                    { name: 'Sunday', id: 'Sun' },
                    { name: 'Monday', id: 'Mon' },
                    { name: 'Tuesday', id: 'Tue' },
                    { name: 'Wednesday', id: 'Wed' },
                    { name: 'Thursday', id: 'Thu' },
                    { name: 'Friday', id: 'Fri' },
                    { name: 'Saturday', id: 'Sat' }
                ], 'Sun');
                me.taskEditForm.weekOrdinal = new Peanut.selectListObservable(null, [
                    { name: 'Every', id: 'every' },
                    { name: 'First', id: '1st' },
                    { name: 'Second', id: '2nd' },
                    { name: 'Third', id: '3rd' },
                    { name: 'Fourth', id: '4th' },
                    { name: 'Fifth', id: '5th' }
                ], 'every');
                me.services.executeService('peanut.peanut-tasks::GetTaskSchedule', null, function (serviceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                            let response = serviceResponse.Value;
                            me.setSchedule(response.schedule);
                            me.addTranslations(response.translations);
                        }
                    }
                }).fail(() => {
                    let trace = me.services.getErrorInformation();
                }).always(() => {
                    me.bindDefaultSection();
                    successFunction();
                });
            });
        }
        showLogsTab() {
            let me = this;
            if (me.taskLog().length == 0) {
                me.getLogs();
            }
            else {
                me.tab('log');
            }
        }
        clearErrors() {
            let me = this;
            me.taskEditForm.namespaceError('');
            me.taskEditForm.frequencyError('');
            me.taskEditForm.taskNameError('');
        }
        clearTaskEditForm() {
            let me = this;
            me.clearErrors();
            me.taskEditForm.id(0);
            me.taskEditForm.active(true);
            me.taskEditForm.namespace('');
            me.taskEditForm.comments('');
            me.taskEditForm.enddate('');
            me.taskEditForm.frequency('');
            me.taskEditForm.inputs('');
            me.taskEditForm.startdate('');
            me.taskEditForm.enddate('');
            me.taskEditForm.taskname('');
            me.taskEditForm.time('');
            me.taskEditForm.date('');
            me.setIntervalType();
            me.taskEditForm.frequencyCount(1);
            me.taskEditForm.frequencyUnit.setDefaultValue();
            me.taskEditForm.dayOfWeek.setDefaultValue();
            me.taskEditForm.weekOrdinal.setDefaultValue();
        }
        validateTask(task) {
            let me = this;
            let valid = true;
            if (!task.namespace) {
                valid = false;
                me.taskEditForm.namespaceError('Namespace is required');
            }
            if (task.frequency === false) {
                valid = false;
            }
            if (!task.taskname) {
                valid = false;
                me.taskEditForm.taskNameError('Task name is required');
            }
            return valid;
        }
    }
    PeanutTasks.SchedulerViewModel = SchedulerViewModel;
})(PeanutTasks || (PeanutTasks = {}));
//# sourceMappingURL=SchedulerViewModel.js.map