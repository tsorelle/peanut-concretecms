var QnutCalendar;
(function (QnutCalendar) {
    var Environment = Peanut.Environment;
    class Momentito {
        static dateOnly(m) {
            return moment(m.format('YYYY-MM-DD[T]00:00:00'));
        }
        static clone(m, useCurrentLocale = true) {
            return useCurrentLocale ? m.clone() : moment(m.format());
        }
        static swapDates(m, ds) {
            let timeString = m.format('[T]00:00:00');
            Momentito.getDateString(ds);
            return moment(ds + timeString);
        }
        static normalize(m) {
            return moment(m.format('YYYY-MM-DD[T]HH:mm:00'));
        }
        static toIsoString(m) {
            return m.format('YYYY-MM-DD[T]HH:mm:00');
        }
        static parse(m = null, normalize = true) {
            if (!m) {
                m = moment();
            }
            let t = normalize ? moment(m.format('YYYY-MM-DD[T]HH:mm:00')) : m.clone();
            return {
                date: moment(t.format('YYYY-MM-DD[T]00:00:00')),
                time: (t.hours() * 60) + t.minutes()
            };
        }
        static getTimeValue(m, normalize = true) {
            let t = normalize ? moment(m.format('YYYY-MM-DD[T]HH:mm:00')) : m;
            let h = t.hour();
            let min = t.minute();
            return (h * 60) + min;
        }
        static getStringValue(n) {
            let s = n.toString();
            return s.length == 1 ? '0' + s : s;
        }
        static momentFromString(s) {
            let parts = s.split('/');
            if (parts.length === 3) {
                let m = parts[0].length === 1 ? '0' + parts[0].toString() : parts[0];
                let d = parts[1].length === 1 ? '0' + parts[1].toString() : parts[1];
                let y = parts[2].length === 2 ? '20' + parts[2].toString() : parts[2];
                s = y + '-' + m + '-' + d + 'T00:00:00';
            }
            else if (s.length === 10) {
                s += 'T00:00:00';
            }
            return moment(s);
        }
        static getDateString(s) {
            let m = (s === null || s.trim() === '') ? moment() : this.momentFromString(s);
            return m.format('YYYY-MM-DD');
        }
    }
    QnutCalendar.Momentito = Momentito;
    class timeHelper {
        static timeStringToValue(time) {
            if (time === null) {
                return '';
            }
            time = time.replace(/\s/g, '').toLowerCase().trim();
            if (time === '') {
                return null;
            }
            let am = time.indexOf('a');
            let p = am;
            let pm = -1;
            if (am === -1) {
                pm = time.indexOf('p');
                p = pm;
            }
            if (p > -1) {
                let a = time.substring(p);
                if (a !== 'a' && a !== 'am' && a !== 'p' && a !== 'pm') {
                    return -1;
                }
                time = time.substring(0, p).trim();
                if (time === '') {
                    return -1;
                }
            }
            let parts = time.split(':');
            if (parts.length === 0) {
                return null;
            }
            let h = parts.shift().trim();
            if (h === '' && parts.length === 0) {
                return null;
            }
            let hours = Number(h);
            if (isNaN(hours) || Math.floor(hours) !== hours || hours < 0) {
                return -1;
            }
            if (am > -1) {
                if (hours === 12) {
                    hours = 0;
                }
                else if (hours > 12) {
                    return -1;
                }
            }
            else if (pm > -1) {
                if (hours > 12) {
                    return -1;
                }
                if (hours !== 12) {
                    hours += 12;
                }
            }
            if (hours > 23) {
                return -1;
            }
            let minutes = 0;
            if (parts.length > 0) {
                let m = parts.shift().trim();
                if (m === '' && h === '') {
                    return null;
                }
                minutes = (!m) ? 0 : Number(m);
                if (isNaN(minutes) || Math.floor(minutes) !== minutes || minutes > 59 || minutes < 0) {
                    return -1;
                }
            }
            return (hours * 60) + minutes;
        }
    }
    timeHelper.timeValueToString = (value, hourFormat = 12) => {
        if (value === null) {
            return '';
        }
        if (isNaN(value) || value < 0 || value > 1439) {
            return 'invalid';
        }
        if (value === null) {
            return '';
        }
        let hour = Math.floor(value / 60);
        let hourString = hour.toString();
        let minString = (value % 60).toString();
        if (minString.length === 1) {
            minString = '0' + minString;
        }
        if (hourFormat === 12) {
            minString += hour > 11 ? ' PM' : ' AM';
            hour %= 12;
            hourString = hour === 0 ? '12' : hour.toString();
        }
        else {
            if (hourString.length === 1) {
                hourString = '0' + hourString;
            }
        }
        return hourString + ':' + minString;
    };
    QnutCalendar.timeHelper = timeHelper;
    class repeatInfoEditor {
        constructor() {
            this.test = ko.observable('');
            this.basis = ko.observable('d');
            this.patternType = ko.observable('d');
            this.interval = ko.observable(1);
            this.monthInterval = ko.observable(1);
            this.dowList = ko.observableArray([]);
            this.daysOfWeek = ko.observableArray([]);
            this.selectedDow = ko.observable();
            this.ordinals = ko.observableArray([]);
            this.selectedOrdinal = ko.observable(null);
            this.ordinalsSet = [
                ko.observable(true),
                ko.observable(false),
                ko.observable(false),
                ko.observable(false),
                ko.observable(false)
            ];
            this.selectedMonth = ko.observable();
            this.selectedOrdinalMonth = ko.observable();
            this.months = ko.observableArray([]);
            this.monthDay = ko.observable('');
            this.selectedWeekdays = ko.observable('');
            this.weekdaysMessage = ko.observable('');
            this.endDateBasis = ko.observable('none');
            this.startOn = ko.observable('');
            this.isNew = ko.observable(false);
            this.endBy = ko.observable('');
            this.endOccurances = ko.observable('');
            this.basisSubscription = null;
            this.endDateBasisSubscription = null;
            this.recurEndDateSubscripton = null;
            this.recurOccurencesSubscription = null;
            this.defaultOrdinalSet = () => {
                this.ordinalsSet[0](true);
                for (let i = 1; i < 5; i++) {
                    this.ordinalsSet[i](false);
                }
            };
            this.setOrdinalSet = (values) => {
                for (let i = 0; i < 5; i++) {
                    this.ordinalsSet[i](values.indexOf((i + 1).toString()) !== -1);
                }
            };
            this.getOrdinalsString = () => {
                let result = '';
                for (let i = 0; i < 5; i++) {
                    if (this.ordinalsSet[i]()) {
                        result += (i + 1).toString();
                    }
                }
                return result;
            };
            this.setWeekdaysMessage = () => {
                let message = '';
                let days = this.selectedWeekdays();
                for (let i = 0; i < days.length; i++) {
                    let value = Number(days.charAt(i)) - 1;
                    message = message + (message !== '' ? ', ' : '') + this.daysOfWeek()[value].Name;
                }
                this.weekdaysMessage(message);
            };
            this.onOccurencesChange = (value) => {
                this.endDateBasis(value ? "occurances" : 'none');
            };
            this.onEndByChange = (value) => {
                this.endDateBasis(value ? "date" : 'none');
            };
            this.onEndDateBasisChange = (value) => {
                switch (value) {
                    case 'none':
                        this.endBy('');
                        this.endOccurances('');
                        return;
                    case 'occurances':
                        this.endBy('');
                        if (this.endOccurances() == '') {
                            this.endOccurances('1');
                        }
                        return;
                    case 'date':
                        this.endOccurances('');
                        if (this.endBy() == '') {
                            this.endBy(this.startOn());
                        }
                        return;
                }
            };
            this.setBasis = (value) => {
                if (this.basisSubscription !== null) {
                    this.basisSubscription.dispose();
                    this.basisSubscription = null;
                }
                this.basis(value);
                this.basisSubscription = this.basis.subscribe(this.onBasisChange);
            };
            this.onBasisChange = (value) => {
                switch (value) {
                    case 'w':
                        this.patternType('wk');
                        break;
                    case 'm':
                        this.patternType('mo');
                        break;
                    default:
                        this.patternType(value += 'd');
                }
                this.interval(1);
                this.monthDay(1);
                this.setWeekdaysMessage();
                this.monthInterval(1);
                this.selectedOrdinal(this.ordinals()[0]);
                this.selectedDow(this.daysOfWeek()[0]);
                this.selectedMonth(this.months()[0]);
                this.selectedOrdinalMonth(this.months()[0]);
            };
            this.onDowClick = (item) => {
                let list = this.selectedWeekdays();
                if (list.indexOf(item.Value) > -1) {
                    this.selectedWeekdays(list.replace(item.Value, ''));
                }
                else {
                    let p = 0;
                    for (let i = list.length - 1; i >= 0; i--) {
                        let x = list.charAt(i);
                        let c = Number(list.charAt(i));
                        if (c < item.Value) {
                            p = i + 1;
                            break;
                        }
                    }
                    list = list.substring(0, p) + item.Value + list.substring(p);
                    this.selectedWeekdays(list);
                }
                this.setWeekdaysMessage();
            };
        }
        initialize(vocabulary) {
            let me = this;
            for (let i = 0; i < 7; i++) {
                let day = {
                    Value: i + 1,
                    Name: vocabulary.daysOfWeek[i],
                };
                me.daysOfWeek.push(day);
                let dow = {
                    Name: day.Name.substring(0, 1),
                    Value: i + 1,
                    Description: day.Name
                };
                me.dowList.push(dow);
            }
            me.selectedDow(me.daysOfWeek()[0]);
            for (let i = 0; i < vocabulary.ordinals.length; i++) {
                let item = {
                    Name: vocabulary.ordinals[i],
                    Value: i + 1
                };
                me.ordinals().push(item);
            }
            me.selectedOrdinal(me.ordinals()[0]);
            for (let i = 0; i < vocabulary.monthNames.length; i++) {
                let item = {
                    Name: vocabulary.monthNames[i],
                    Value: i + 1
                };
                me.months().push(item);
            }
            me.selectedMonth(me.months()[0]);
            me.selectedOrdinalMonth(me.months()[0]);
        }
        setPattern(repeatPattern, dateFormat) {
            let me = this;
            if (me.endDateBasisSubscription !== null) {
                me.endDateBasisSubscription.dispose();
                me.endDateBasisSubscription = null;
                me.recurEndDateSubscripton.dispose();
                me.recurOccurencesSubscription.dispose();
            }
            me.setBasis('d');
            me.patternType('dd');
            me.interval(1);
            me.selectedOrdinal(me.ordinals()[0]);
            me.monthDay(1);
            me.selectedWeekdays('');
            me.weekdaysMessage('');
            me.monthInterval(1);
            me.endDateBasis('none');
            me.startOn('');
            me.endBy('');
            me.endOccurances('');
            let noRepeat = !repeatPattern;
            me.isNew(noRepeat);
            if (noRepeat) {
                return;
            }
            if (repeatPattern.length < 2) {
                console.error('Invalid repeat pattern.');
                return;
            }
            let parts = repeatPattern.split(';');
            if (parts.length > 0) {
                repeatPattern = parts[0];
                if (parts.length > 1) {
                    let dates = parts[1].split(',');
                    if (dates.length < 1) {
                        console.error('No start date in repeat pattern.');
                    }
                    else {
                        let startMoment = Momentito.momentFromString(dates[0]);
                        let startText = startMoment.format(dateFormat);
                        me.startOn(startText);
                        me.startOn(Momentito.momentFromString(dates[0]).format(dateFormat));
                        if (dates.length > 1) {
                            let end = dates[1];
                            let occurances = Number(end);
                            if (isNaN(occurances)) {
                                me.endDateBasis('date');
                                me.endBy(Momentito.momentFromString(end).format(dateFormat));
                            }
                            else {
                                me.endDateBasis('occurances');
                                me.endOccurances(occurances);
                            }
                        }
                        else {
                            me.endDateBasis('none');
                            me.endBy('');
                        }
                    }
                }
                me.endDateBasisSubscription = me.endDateBasis.subscribe(me.onEndDateBasisChange);
                me.recurEndDateSubscripton = me.endBy.subscribe(me.onEndByChange);
                me.recurOccurencesSubscription = me.endOccurances.subscribe(me.onOccurencesChange);
            }
            let patternParts = repeatPattern.substring(2).split(',');
            let interval = patternParts.length == 0 ? 0 : Number(patternParts[0]);
            me.setBasis(repeatPattern.substring(0, 1));
            me.patternType(repeatPattern.substring(0, 2));
            switch (me.patternType()) {
                case 'dd':
                    break;
                case 'dw':
                    break;
                case 'wk':
                    me.selectedWeekdays(patternParts.length < 2 ? '' : patternParts[1]);
                    me.setWeekdaysMessage();
                    break;
                case 'md':
                    me.monthDay(patternParts.length < 2 ? '' : patternParts[1]);
                    break;
                case 'mo':
                    me.setOrdinalSet(patternParts[1]);
                    me.setNameValuePair(patternParts.length < 3 ? 1 : patternParts[2], me.daysOfWeek, me.selectedDow);
                    break;
                case 'yd':
                    me.setNameValuePair(patternParts.length < 2 ? 1 : patternParts[1], me.months, me.selectedMonth);
                    me.monthDay(patternParts.length < 3 ? 1 : patternParts[2]);
                    break;
                case 'yo':
                    me.setNameValuePair(patternParts.length < 2 ? 1 : patternParts[1], me.ordinals, me.selectedOrdinal);
                    me.setNameValuePair(patternParts.length < 3 ? 1 : patternParts[2], me.daysOfWeek, me.selectedDow);
                    me.setNameValuePair(patternParts.length < 4 ? 1 : patternParts[3], me.months, me.selectedOrdinalMonth);
                    break;
                default:
                    console.error('Invalid repeat pattern.');
                    break;
            }
        }
        setNameValuePair(value, items, selected) {
            let list = items();
            for (let i = 0; i < list.length; i++) {
                let item = list[i];
                if (item.Value == value) {
                    selected(item);
                    return;
                }
            }
            selected(null);
        }
        getRepeatPattern() {
            let me = this;
            let pattern = me.getPattern();
            if (pattern === null) {
                return null;
            }
            let startOn = me.startOn();
            pattern += ';' + Momentito.getDateString(startOn);
            let endBasis = me.endDateBasis();
            if (endBasis == 'none') {
                me.endBy('');
            }
            else {
                pattern += ',';
            }
            if (endBasis === 'occurances') {
                pattern += me.endOccurances();
            }
            else if (endBasis === 'date') {
                let endBy = me.endBy();
                if (endBy.trim() !== '') {
                    pattern += Momentito.getDateString(endBy);
                }
            }
            return pattern;
        }
        getPattern() {
            let me = this;
            switch (me.patternType()) {
                case 'dd':
                    return 'dd' + me.interval();
                case 'dw':
                    return 'dw';
                case 'wk':
                    return 'wk' + me.interval() + ',' + me.selectedWeekdays();
                case 'md':
                    return 'md' + me.interval() + ',' + me.monthDay();
                case 'mo':
                    let moOrdinals = me.getOrdinalsString();
                    return 'mo' + me.monthInterval() + ',' + moOrdinals + ',' + me.selectedDow().Value;
                case 'yd':
                    return 'yd' + me.interval() + ',' + me.selectedMonth().Value + ',' + me.monthDay();
                case 'yo':
                    return 'yo' + me.interval() + ',' + me.selectedOrdinal().Value + ','
                        + me.selectedDow().Value + ',' + me.selectedOrdinalMonth().Value;
                default:
                    console.error('Invalid repeat pattern.');
                    return null;
            }
        }
    }
    QnutCalendar.repeatInfoEditor = repeatInfoEditor;
    class eventTimeEditor {
        constructor() {
            this.endDate = null;
            this.dayCount = 1;
            this.allDay = ko.observable(false);
            this.startDateText = ko.observable('');
            this.startTimeText = ko.observable('');
            this.endDateText = ko.observable('');
            this.endTimeText = ko.observable('');
            this.repeat = new repeatInfoEditor();
            this.firstRowColumns = ko.observable(3);
            this.showSecondRow = ko.observable(false);
            this.conjunction = ko.observable('');
            this.isCustom = ko.observable(false);
            this.startTimeList = ko.observableArray([]);
            this.endTimeList = ko.observableArray([]);
            this.daylist = ko.observableArray([]);
            this.selectedDays = ko.observable();
            this.endTimeMode = ko.observable(1);
            this.timeError = ko.observable('');
            this.timeErrorField = ko.observable('');
            this.invalidTimeErrorMsg = '';
            this.invaildTimeOrderErrorMsg = '';
            this.conjunctionThrough = '';
            this.conjunctionUntil = '';
            this.subscriptionsActive = false;
            this.hourFormat = 12;
            this.fillEndList = () => {
                let endList = this.buildTimeList(this.startTimeValue + 30, true);
                this.endTimeList(endList);
            };
            this.buildTimeList = (start = 0, showDuration = false) => {
                let result = [];
                let meridian = '';
                let duration = '';
                let hour = 0;
                let min = 0;
                let format = this.timeFormat.toLowerCase().indexOf('a') < 0 ? 0 : 12;
                if (!showDuration) {
                    start = 0;
                }
                for (let value = start; value < 1440; value += 30) {
                    let label = timeHelper.timeValueToString(value, this.hourFormat);
                    let duration = '';
                    if (showDuration) {
                        let hrs = (value - start + 30) / 60;
                        if (hrs > 0) {
                            duration += ' (' + hrs + ' ' + (hrs > 1.0 ? this.hoursNamePlural : this.hoursName) + ')';
                        }
                    }
                    result.push({
                        Name: label + duration,
                        Value: value
                    });
                }
                return result;
            };
            this.suspendSubscriptions = () => {
                if (this.subscriptionsActive) {
                    this.endDateSubscription.dispose();
                    this.startDateSubscription.dispose();
                    this.startTimeSubscription.dispose();
                    this.endTimeSubscription.dispose();
                    this.dayListSubscription.dispose();
                    this.allDaySubscription.dispose();
                    this.subscriptionsActive = false;
                }
            };
            this.activateSubscriptions = () => {
                if (!this.subscriptionsActive) {
                    this.startDateSubscription = this.startDateText.subscribe(this.onStartDateChanged);
                    this.endTimeSubscription = this.endTimeText.subscribe(this.onEndTimeChanged);
                    this.startTimeSubscription = this.startTimeText.subscribe(this.onStartTimeChanged);
                    this.endDateSubscription = this.endDateText.subscribe(this.onEndDateChanged);
                    this.dayListSubscription = this.selectedDays.subscribe(this.onDaysChange);
                    this.allDaySubscription = this.allDay.subscribe(this.onAllDayChecked);
                    this.subscriptionsActive = true;
                }
            };
            this.onSetCustomClick = () => {
                this.suspendSubscriptions();
                this.setCustomState(true);
                this.setLayoutObservables();
                this.activateSubscriptions();
            };
            this.setCustomState = (state = true) => {
                if (this.isCustom() !== state) {
                    if (state) {
                        if (this.endDate == null || (this.endDate && this.endDate.isBefore(this.startDate))) {
                            this.endDate = this.startDate.clone();
                            this.endDateText(this.startDateText());
                        }
                    }
                    else {
                        this.endDate = null;
                    }
                    this.isCustom(state);
                }
            };
            this.timeValueToString = (value) => {
                if (value === null) {
                    return '';
                }
                let hour = Math.floor(value / 60);
                let hourString = hour.toString();
                let minString = (value % 60).toString();
                if (this.hourFormat == 12) {
                    minString += hour > 11 ? ' PM' : ' AM';
                    hour %= 12;
                    hourString = hour == 0 ? '12' : hour.toString();
                }
                else {
                    if (hourString.length == 1) {
                        hourString = '0' + hourString;
                    }
                }
                return hourString + ':' + minString;
            };
            this.setStartDate = (value) => {
                let sameDay = this.endDate !== null && this.startDate.isSame(this.endDate);
                let newDate = Momentito.momentFromString(value);
                this.startDate = newDate;
                if ((this.endDate !== null) && (sameDay || this.endDate.isBefore(newDate))) {
                    this.setEndToStart();
                }
                return true;
            };
            this.setEndToStart = () => {
                this.endDate = this.startDate.clone();
                this.endDateText(this.startDateText());
            };
            this.newEndDate = (m) => {
                this.endDate = m;
                this.endDateText(m.format(this.dateFormat));
            };
            this.setEndDate = (value) => {
                if (value) {
                    let newDate = Momentito.momentFromString(value);
                    if (newDate.isBefore(this.startDate)) {
                        this.timeError(this.invaildTimeOrderErrorMsg);
                        this.timeErrorField('enddate');
                        return false;
                    }
                    this.endDate = newDate;
                }
                else {
                    this.endDate = null;
                }
                return true;
            };
            this.setEndTime = (value) => {
                if (this.startTimeValue > value) {
                    this.timeError(this.invalidTimeErrorMsg);
                    this.timeErrorField('endtime');
                    return false;
                }
                this.endTimeText(timeHelper.timeValueToString(value, this.hourFormat));
                this.timeError('');
                this.timeErrorField('');
                this.endTimeValue = value;
                return true;
            };
            this.isSameDay = () => {
                if (this.endDate == null) {
                    return true;
                }
                return this.startDate.isSame(this.endDate);
            };
            this.setStartTime = (value) => {
                if (this.isSameDay() && this.endTimeValue < this.startTimeValue) {
                    this.endTimeValue = this.startTimeValue;
                    this.endTimeText(timeHelper.timeValueToString(this.endTimeValue, this.hourFormat));
                }
                this.timeError('');
                this.timeErrorField('');
                this.startTimeValue = value;
                this.startTimeText(timeHelper.timeValueToString(value, this.hourFormat));
                return true;
            };
            this.onStartTimeSelected = (item) => {
                if (item) {
                    this.suspendSubscriptions();
                    this.setStartTime(item.Value);
                    this.fillEndList();
                    this.setLayoutObservables();
                    this.activateSubscriptions();
                }
            };
            this.onEndTimeSelected = (item) => {
                if (item) {
                    this.suspendSubscriptions();
                    this.setCustomState(item.Value === 'custom');
                    if (item.Value !== 'custom') {
                        this.setEndTime(item.Value);
                    }
                    this.setLayoutObservables();
                    this.activateSubscriptions();
                }
            };
            this.onAllDayChecked = (checked) => {
                this.suspendSubscriptions();
                if (this.isSameDay()) {
                    this.selectedDays(this.daylist()[0]);
                    this.isCustom(false);
                }
                this.setLayoutObservables();
                this.activateSubscriptions();
            };
            this.onStartDateChanged = (value) => {
                this.suspendSubscriptions();
                this.setStartDate(value);
                this.activateSubscriptions();
            };
            this.onEndDateChanged = (value) => {
                this.suspendSubscriptions();
                this.setEndDate(value);
                this.activateSubscriptions();
            };
            this.onEndTimeChanged = (value) => {
                this.suspendSubscriptions();
                let time = timeHelper.timeStringToValue(value);
                if (time < 0) {
                    this.timeError(this.invalidTimeErrorMsg);
                    this.timeErrorField('endtime');
                }
                else {
                    this.endTimeValue = time;
                    this.endTimeText(value);
                }
                this.activateSubscriptions();
            };
            this.onStartTimeChanged = (value) => {
                this.suspendSubscriptions();
                let time = timeHelper.timeStringToValue(value);
                if (time < 0) {
                    this.timeError(this.invalidTimeErrorMsg);
                    this.timeErrorField('starttime');
                }
                else {
                    this.startTimeValue = time;
                    if (this.isSameDay() && this.endTimeValue < this.startTimeValue) {
                        this.setEndTime(this.startTimeValue);
                    }
                    this.startTimeText(value);
                    this.fillEndList();
                }
                this.activateSubscriptions();
            };
            this.getLayout = () => {
                if (this.allDay()) {
                    if (this.isSameDay() && !this.isCustom()) {
                        return 5;
                    }
                    else {
                        return (this.endDate.isSame(this.startDate) && this.isCustom()) ?
                            3 : 4;
                    }
                }
                else {
                    if (this.isSameDay()) {
                        if (!this.isCustom()) {
                            return 1;
                        }
                    }
                    return 2;
                }
            };
            this.setDefaultTimes = () => {
                let value = Momentito.parse();
                if (this.startTimeValue === null) {
                    this.startTimeValue = value.time;
                    this.startTimeText(timeHelper.timeValueToString(this.startTimeValue, this.hourFormat));
                }
                if (this.endDate == null) {
                    this.setEndToStart();
                }
                if (this.endTimeValue === null || this.endTimeValue < this.startTimeValue) {
                    this.setEndTime(this.startTimeValue);
                    this.fillEndList();
                }
            };
            this.setLayoutObservables = () => {
                let layout = this.getLayout();
                let columns = 2;
                switch (layout) {
                    case 1:
                        if (this.startTimeValue === null) {
                            this.setDefaultTimes();
                        }
                        columns = 3;
                        this.endTimeMode(1);
                        this.conjunction('');
                        break;
                    case 2:
                        this.endTimeMode(1);
                        this.conjunction(this.conjunctionUntil);
                        break;
                    case 3:
                        this.endTimeMode(this.isCustom() ? 0 : 2);
                        this.conjunction(this.conjunctionThrough);
                        break;
                    case 4:
                        this.endTimeMode(0);
                        this.conjunction(this.conjunctionThrough);
                        break;
                    case 5:
                        this.endTimeMode(2);
                        this.conjunction('');
                        break;
                    default:
                        console.log('ERROR invalid layout code ' + layout);
                        break;
                }
                if (this.endTimeMode() === 1 && this.endTimeValue === null) {
                    this.setDefaultTimes();
                }
                this.firstRowColumns(columns);
                this.showSecondRow(this.isCustom() || (!this.isSameDay()));
            };
            this.onDaysChange = (item) => {
                this.suspendSubscriptions();
                this.setCustomState(item.Value > 5);
                if (item.Value > 1) {
                    if (this.endDate === null) {
                        this.setEndToStart();
                    }
                    this.endDate.add(item.Value - 1, 'days');
                    let text = moment(this.endDate).format(this.dateFormat);
                    this.endDateText(text);
                }
                this.setLayoutObservables();
                this.activateSubscriptions();
            };
        }
        initialize(translator) {
            let me = this;
            me.dateFormat = 'YYYY-MM-DD';
            me.timeFormat = translator.translate('calendar-time-format');
            me.hourFormat = me.timeFormat.toLowerCase().indexOf('a') < 0 ? 24 : 12;
            me.hoursName = translator.translate('calander-hour');
            me.hoursNamePlural = translator.translate('calander-hour-plural');
            me.custom = translator.translate('calendar-set-custorm');
            me.invaildTimeOrderErrorMsg = translator.translate('calender-time-order-error');
            me.invalidTimeErrorMsg = translator.translate('calendar-time-error');
            me.conjunctionThrough = translator.translate('conjunction-through');
            me.conjunctionUntil = translator.translate('conjunction-until');
            if (me.daylist().length == 0) {
                let days = [];
                let daysName = translator.translate('calendar-word-day-plural');
                days.push({ Name: '1 ' + translator.translate('calendar-word-day'), Value: 1 });
                for (let i = 2; i < 6; i++) {
                    days.push({ Name: i + ' ' + daysName, Value: i });
                }
                days.push({ Name: me.custom, Value: 6 });
                me.daylist(days);
            }
        }
        setTimes(start = null, end = null, allDay = true, repeatPattern = '') {
            let me = this;
            me.suspendSubscriptions();
            me.allDay(allDay);
            me.isCustom(false);
            me.repeat.setPattern(repeatPattern, me.dateFormat);
            let startDt = Momentito.parse(start);
            me.startDate = startDt.date;
            me.startDateText(startDt.date.format(me.dateFormat));
            me.startTimeValue = allDay ? null : startDt.time;
            let startList = me.buildTimeList();
            me.startTimeList(me.buildTimeList());
            me.fillEndList();
            me.endDate = null;
            me.endTimeValue = null;
            me.timeError('');
            me.timeErrorField('');
            let sameDay = true;
            if (end) {
                let endDt = Momentito.parse(end);
                sameDay = me.startDate.isSame(endDt.date);
                if (allDay) {
                    if (sameDay && endDt.date.isAfter(startDt.date.add(5, 'days'))) {
                        me.isCustom(true);
                    }
                }
                else {
                    me.endTimeValue = endDt.time;
                }
                if (sameDay) {
                    me.endDate = null;
                }
                else {
                    me.endDate = endDt.date;
                    me.endDateText(me.endDate.format(this.dateFormat));
                }
            }
            let custom = me.isCustom();
            let test = allDay ? '' : timeHelper.timeValueToString(me.endTimeValue, me.hourFormat);
            me.endTimeText(allDay ? '' : timeHelper.timeValueToString(me.endTimeValue, me.hourFormat));
            me.startTimeText(allDay ? '' : timeHelper.timeValueToString(startDt.time, me.hourFormat));
            me.setLayoutObservables();
            me.activateSubscriptions();
        }
    }
    QnutCalendar.eventTimeEditor = eventTimeEditor;
    class calenderResceduleObservable {
        constructor(owner) {
            this.eventTitle = ko.observable('');
            this.eventStartDate = ko.observable('');
            this.eventEndDate = ko.observable('');
            this.isRepeating = ko.observable(false);
            this.repeatMode = ko.observable('');
            this.showEndDate = ko.observable(true);
            this.revertFunction = null;
            this.event = null;
            this.revert = () => {
                if (this.revertFunction) {
                    this.revertFunction();
                }
                this.revertFunction = null;
                Peanut.ui.helper.hideModal('#reschedule-event-modal');
            };
            this.owner = owner;
        }
        show(event, change, revertFunction) {
            this.event = event;
            this.revertFunction = revertFunction;
            this.eventTitle(event.title);
            let startDate = event.start.format("dddd, MMMM Do YYYY");
            if (event.allDay) {
                this.eventStartDate(startDate);
            }
            else {
                this.eventStartDate(event.start.format("dddd, MMMM Do YYYY, h:mm:ss a"));
            }
            if (change === 'drop') {
                this.eventEndDate('');
            }
            else {
                let endDate = event.end.format("dddd, MMMM Do YYYY");
                if (endDate == startDate && !event.allDay) {
                    this.eventEndDate(event.end.format("h:mm:ss a"));
                }
                else {
                    this.eventEndDate(event.end.format(event.allDay ? "dddd, MMMM Do YYYY" : "dddd, MMMM Do YYYY, h:mm:ss a"));
                }
            }
            this.isRepeating(!!event.repeatPattern);
            this.repeatMode('all');
            this.owner.displayModal('#reschedule-event-modal');
        }
        close() {
            Peanut.ui.helper.hideModal('#reschedule-event-modal');
            return this.event;
        }
    }
    QnutCalendar.calenderResceduleObservable = calenderResceduleObservable;
    class calendarSearchObservable {
        constructor() {
            this.title = ko.observable('');
            this.results = ko.observableArray([]);
        }
    }
    QnutCalendar.calendarSearchObservable = calendarSearchObservable;
    class calendarEventObservable {
        constructor(owner) {
            this.id = ko.observable(null);
            this.repeatPattern = '';
            this.times = new eventTimeEditor();
            this.title = ko.observable('');
            this.allDay = false;
            this.eventType = ko.observable('');
            this.addCaption = ko.observable('');
            this.repeatMode = ko.observable('all');
            this.repeating = ko.observable(false);
            this.location = ko.observable('');
            this.url = ko.observable('');
            this.eventTime = ko.observable('');
            this.repeatText = ko.observable('');
            this.committeesText = ko.observable('');
            this.resourcesText = ko.observable('');
            this.notesLines = ko.observableArray([]);
            this.description = ko.observable('');
            this.titleError = ko.observable('');
            this.eventTypes = ko.observableArray([]);
            this.selectedEventType = ko.observable();
            this.notificationDays = ko.observable(-1);
            this.sendNotifications = ko.observable(false);
            this.isVirtual = ko.observable(false);
            this.recurInstance = null;
            this.availableResources = ko.observableArray([]);
            this.selectedResources = ko.observableArray([]);
            this.selectedResource = ko.observable();
            this.resourceSubscription = null;
            this.availableCommittees = ko.observableArray([]);
            this.selectedCommittees = ko.observableArray([]);
            this.selectedCommittee = ko.observable();
            this.committeeSubscription = null;
            this.availableGroups = ko.observableArray([]);
            this.selectedGroups = ko.observableArray([]);
            this.selectedGroup = ko.observable();
            this.groupSubscription = null;
            this.notes = ko.observable('');
            this.createdBy = ko.observable('');
            this.createdOn = ko.observable('');
            this.changedBy = ko.observable('');
            this.changedOn = ko.observable('');
            this.vocabulary = null;
            this.getMonthName = (n) => {
                return this.vocabulary.monthNames[Number(n) - 1];
            };
            this.getOrdinalsText = (values, dow = null) => {
                let result = '';
                let len = values.length;
                let added = 0;
                let conjunction = ' and ';
                for (let i = 1; i < 5; i++) {
                    if (values.indexOf((i).toString()) !== -1) {
                        added++;
                        if (result.length > 0) {
                            result += ((added == len) ? conjunction : ', ');
                        }
                        result += this.vocabulary.ordinals[i - 1];
                    }
                }
                if (result.length > 0 && dow !== null) {
                    result += ' ' + this.vocabulary.daysOfWeek[Number(dow) - 1];
                }
                return result;
            };
            this.getRepeatText = (repeatPattern) => {
                let result = '';
                let start = null;
                let end = null;
                let parts = repeatPattern.split(';');
                if (parts.length > 0) {
                    repeatPattern = parts[0];
                    if (parts.length > 1) {
                        let dates = parts[1].split(',');
                        start = moment(dates[0]).format("MMM D, YYYY");
                        if (dates.length > 1) {
                            let occurances = Number(dates[1]);
                            end = isNaN(occurances) ? moment(dates[1]).format("MMM D, YYYY") : occurances;
                        }
                    }
                }
                let patternParts = repeatPattern.substring(2).split(',');
                let interval = patternParts.length == 0 ? 0 : Number(patternParts[0]);
                let wordEvery = this.translator.translate('calendar-word-every');
                wordEvery = wordEvery.charAt(0).toUpperCase() + wordEvery.slice(1);
                let wordOn = this.translator.translate('conjunction-on');
                let wordThe = this.translator.translate('conjunction-the');
                switch (repeatPattern.substring(0, 2)) {
                    case 'dd':
                        return interval > 1 ?
                            wordEvery + ' ' + interval + ' ' + this.translator.translate('calendar-word-day-plural') + ' ' + this.formatRepeatDates(start, end) :
                            wordEvery + ' ' + this.translator.translate('calendar-word-day') + ' ' + this.formatRepeatDates(start, end);
                    case 'dw':
                        return interval > 1 ?
                            wordEvery + ' ' + interval + ' ' + this.translator.translate('calendar-word-weekday-plural') + ' ' + this.formatRepeatDates(start, end) :
                            wordEvery + ' ' + this.translator.translate('calendar-word-weekday') + ' ' + this.formatRepeatDates(start, end);
                    case 'wk':
                        return interval > 1 ?
                            wordEvery + ' ' + interval + ' ' + this.translator.translate('calendar-word-week-plural') + wordOn + this.translateDows(patternParts[1]) + this.formatRepeatDates(start, end) :
                            wordEvery + ' ' + this.translator.translate('calendar-word-week') + wordOn + this.translateDows(patternParts[1]) + this.formatRepeatDates(start, end);
                    case 'md':
                        return interval > 1 ?
                            wordEvery + ' ' + interval + ' ' + this.translator.translate('calendar-word-month-plural') + wordOn + this.translator.translate('conjunction-the') + this.asOrdinal(patternParts[1]) + ' ' + this.formatRepeatDates(start, end) :
                            wordEvery + ' ' + this.translator.translate('calendar-word-month') + wordOn + wordThe + this.asOrdinal(patternParts[1]) + ' ' + this.formatRepeatDates(start, end);
                    case 'mo':
                        let result = '';
                        if (interval <= 1) {
                            return wordEvery + ' ' + this.translator.translate('calendar-word-month') + wordOn + wordThe +
                                this.getOrdinalsText(patternParts[1], patternParts[2])
                                + ' ' + this.formatRepeatDates(start, end);
                        }
                        return interval > 1 ?
                            wordEvery + ' ' + interval + ' ' + this.translator.translate('calendar-word-month-plural') + wordOn + wordThe +
                                this.ordinalDow(patternParts[1], patternParts[2])
                                + ' ' + this.formatRepeatDates(start, end) :
                            wordEvery + ' ' + this.translator.translate('calendar-word-month') + wordOn + wordThe +
                                this.getOrdinalsText(patternParts[1], patternParts[2])
                                + this.formatRepeatDates(start, end);
                    case 'yd':
                        return interval > 1 ?
                            wordEvery + ' ' + interval + ' ' + this.translator.translate('calendar-word-year-plural') + wordOn +
                                this.getMonthName(patternParts[1]) + ' ' + patternParts[2] :
                            wordEvery + ' ' + this.translator.translate('calendar-word-year') + wordOn +
                                this.getMonthName(patternParts[1]) + ' ' + patternParts[2];
                    case 'yo':
                        return interval > 1 ?
                            wordEvery + ' ' + interval + ' ' + this.translator.translate('calendar-word-year-plural') + wordOn + this.translator.translate('conjunction-the')
                                + this.ordinalDow(patternParts[1], patternParts[2]) + ' '
                                + this.formatRepeatDates(start, end) :
                            wordEvery + ' ' + this.translator.translate('calendar-word-month') + wordOn + wordThe +
                                +this.ordinalDow(patternParts[1], patternParts[2]) + ' '
                                + this.formatRepeatDates(start, end);
                    default:
                        return '(error: invalid pattern)';
                }
            };
            this.setDescription = (text) => {
                this.description(text);
                let editor = tinymce.get('event-description');
                if (editor) {
                    editor.setContent(text);
                }
            };
            this.clear = (committees, resources, eventTypes, groups) => {
                let me = this;
                me.suspendSubscriptions();
                me.id(0);
                me.start = moment();
                me.end = null;
                me.title('');
                me.location('');
                me.url('');
                me.eventTypeId = 0;
                me.notes('');
                me.selectedResources([]);
                me.selectedCommittees([]);
                me.selectedGroups([]);
                let types = eventTypes();
                let type = types.find((type) => {
                    return type.code === 'public';
                });
                me.selectedEventType(type);
                me.availableCommittees(committees());
                me.availableResources(resources());
                me.availableGroups(groups());
                me.createdBy('');
                me.createdOn('');
                me.changedBy('');
                me.changedOn('');
                me.repeating(false);
                me.repeatPattern = '';
                me.repeatText('');
                me.committeesText('');
                me.resourcesText('');
                me.notes('');
                me.setDescription('');
                me.sendNotifications(false);
                me.notificationDays(-1);
                me.start = moment();
                me.end = moment();
                me.end.add(1, 'hour');
                me.allDay = false;
                me.times.setTimes(me.start, me.end, me.allDay, me.repeatPattern);
                me.activateSubscriptions();
            };
            this.assignFromCalendarObject = (event) => {
                let me = this;
                me.id(event.id);
                me.start = moment(event.start);
                me.end = moment(event.end);
                let range = me.formatDateRange(me.start, me.end, event.allDay);
                me.eventTime(range);
                me.repeatPattern = event.repeatPattern;
                me.title(event.title);
                me.allDay = event.allDay;
                me.location(event.location);
                me.url(event.url);
                me.eventType(event.eventType);
                me.repeating(!!event.repeatPattern);
                me.repeatText(me.repeating() ? me.getRepeatText(event.repeatPattern) : '');
                me.committeesText('');
                me.resourcesText('');
                me.notes('');
                me.notesLines([]);
                me.changedBy('');
                me.setDescription('');
                me.isVirtual(!!event.recurInstance);
                me.recurInstance = event.recurInstance;
            };
            this.assignDetails = (event) => {
                let me = this;
                me.recurId = event.recurId;
                me.eventTypeId = event.eventTypeId;
                me.notes(event.notes ? event.notes : '');
                if (event.notes) {
                    me.notesLines(event.notes.split('\n'));
                }
                else {
                    me.notesLines([]);
                }
                me.selectedResources(event.resources);
                me.assignText(event.resources, me.resourcesText);
                me.setDescription(event.description ? event.description : '');
                me.selectedCommittees(event.committees);
                me.assignText(event.committees, me.committeesText);
                me.selectedGroups(event.groups);
                me.createdBy(event.createdBy);
                me.createdOn(event.createdOn);
                me.changedBy(event.changedBy);
                me.changedOn(event.changedOn);
                me.repeatMode('all');
            };
            this.activateSubscriptions = () => {
                let me = this;
                me.selectedResource(null);
                me.selectedCommittee(null);
                me.selectedResource(null);
                me.resourceSubscription = me.selectedResource.subscribe(me.addResource);
                me.committeeSubscription = me.selectedCommittee.subscribe(me.addCommittee);
                me.groupSubscription = me.selectedGroup.subscribe(me.addGroup);
                me.times.activateSubscriptions();
            };
            this.suspendSubscriptions = () => {
                let me = this;
                if (me.resourceSubscription !== null) {
                    me.resourceSubscription.dispose();
                    me.resourceSubscription = null;
                }
                if (me.committeeSubscription !== null) {
                    me.committeeSubscription.dispose();
                    me.committeeSubscription = null;
                }
                if (me.groupSubscription !== null) {
                    me.groupSubscription.dispose();
                    me.groupSubscription = null;
                }
                me.times.suspendSubscriptions();
            };
            this.edit = (committees, resources, eventTypes, groups) => {
                let me = this;
                me.suspendSubscriptions();
                me.filterAvailable(committees(), me.selectedCommittees(), me.availableCommittees);
                me.filterAvailable(resources(), me.selectedResources(), me.availableResources);
                me.filterAvailable(groups(), me.selectedGroups(), me.availableGroups);
                let types = eventTypes();
                let type = types.find((type) => {
                    return type.id === me.eventTypeId;
                });
                me.selectedCommittee(null);
                me.selectedResource(null);
                me.selectedEventType(type);
                me.selectedGroup(null);
                me.times.setTimes(me.start, me.end, me.allDay, me.repeatPattern);
                me.setDescription(me.description());
                me.activateSubscriptions();
            };
            this.moveSelectedItem = (item, source, target) => {
                let me = this;
                me.suspendSubscriptions();
                let src = source();
                let remaining = src.filter((sourceItem) => {
                    return sourceItem.id != item.id;
                });
                remaining = Peanut.Helper.SortBy(remaining, 'name');
                target.push(item);
                let targetItems = target();
                targetItems = Peanut.Helper.SortBy(targetItems, 'name');
                source(remaining);
                target(targetItems);
                me.activateSubscriptions();
            };
            this.addCommittee = (item) => {
                this.moveSelectedItem(item, this.availableCommittees, this.selectedCommittees);
            };
            this.removeCommittee = (item) => {
                this.moveSelectedItem(item, this.selectedCommittees, this.availableCommittees);
            };
            this.addResource = (item) => {
                this.moveSelectedItem(item, this.availableResources, this.selectedResources);
            };
            this.removeResource = (item) => {
                this.moveSelectedItem(item, this.selectedResources, this.availableResources);
            };
            this.addGroup = (item) => {
                this.moveSelectedItem(item, this.availableGroups, this.selectedGroups);
            };
            this.removeGroup = (item) => {
                this.moveSelectedItem(item, this.selectedGroups, this.availableGroups);
            };
            this.onShowRepeatInfo = () => {
                this.times.repeat.setPattern(this.repeatPattern, this.times.dateFormat);
                if (!this.repeatPattern) {
                    this.times.repeat.startOn(this.times.startDateText());
                }
                this.owner.displayModal('#repeat-info-modal');
            };
            this.onSaveRepeatInfo = () => {
                this.repeatPattern = this.times.repeat.getRepeatPattern();
                this.repeating(true);
                let text = this.getRepeatText(this.repeatPattern);
                this.repeatText(text);
                Peanut.ui.helper.hideModal('#repeat-info-modal');
            };
            this.onRemoveRepeatInfo = () => {
                this.repeatMode('remove');
                this.repeating(false);
                this.repeatPattern = '';
                this.repeatText('');
                Peanut.ui.helper.hideModal('#repeat-info-modal');
            };
            this.owner = owner;
        }
        formatDateRange(startMoment, endMoment, allDay) {
            let me = this;
            if (!startMoment) {
                return '';
            }
            let startDay = startMoment.format('ddd MMM D, YYYY');
            let startTime = allDay ? '' : startMoment.format(' h:mm a');
            if (!endMoment) {
                return startDay + startTime;
            }
            let endDay = endMoment.format('ddd MMM D, YYYY');
            let endTime = '';
            let wordTo = me.translator.translate('conjunction-to');
            if (startDay == endDay) {
                endTime = allDay ? '' : wordTo + endMoment.format(' h:mm a');
                return startDay + startTime + endTime;
            }
            endTime = allDay ? '' : endMoment.format(' h:mm a');
            return startDay + startTime + wordTo + endDay + endTime;
        }
        formatRepeatDates(start, end) {
            if (end == null) {
                return this.translator.translate('conjunction-starting') + start;
            }
            let endText = isNaN(Number(end)) ?
                this.translator.translate('conjunction-until') + ' ' + end :
                this.translator.translate('calendar-word-after') + ' ' + end + ' ' + this.translator.translate('calendar-word-occurances');
            return this.translator.translate('conjunction-from') + ' ' + start + ' ' + endText;
        }
        translateDows(pattern) {
            let count = pattern.length;
            let dows = [];
            for (let i = 0; i < count; i++) {
                let n = Number(pattern.charAt(i));
                dows.push(this.vocabulary.daysOfWeek[n - 1]);
            }
            return dows.join(', ');
        }
        ordinalDow(n, d) {
            return this.vocabulary.ordinals[Number(n) - 1] + ' ' +
                this.vocabulary.daysOfWeek[Number(d) - 1];
        }
        asOrdinal(n) {
            let i = (Number(n) >= this.vocabulary.ordinalSuffix.length) ? n.toString().slice(-1) : Number(n);
            return n + this.vocabulary.ordinalSuffix[i];
        }
        assignText(items, observable) {
            let me = this;
            let text = [];
            items.forEach((item) => {
                text.push(item.name);
            });
            observable(text.join(', '));
        }
        filterAvailable(items, selected, available) {
            let me = this;
            let result = items.filter((item) => {
                let existing = selected.find((selectItem) => {
                    return selectItem.id == item.id;
                });
                return (!existing);
            });
            available(result);
        }
        parseRepeatPattern(repeatPattern) {
            let result = {
                pattern: null,
                start: null,
                endValue: null,
            };
            let parts = repeatPattern == null ? [] : this.repeatPattern.split(';');
            if (parts.length > 0) {
                result.pattern = parts[0];
                if (parts.length > 1) {
                    let dates = parts[1].split(',');
                    if (dates.length > 0) {
                        result.start = dates[0];
                    }
                    if (dates.length > 1) {
                        result.endValue = dates[1];
                    }
                }
            }
            return result;
        }
        ;
        validate() {
            let me = this;
            let valid = true;
            let title = me.title().trim();
            if (title === '') {
                me.titleError(me.translator.translate('calendar-error-no-title'));
                valid = false;
            }
            if (!valid) {
                return false;
            }
            tinymce.triggerSave();
            let val = Peanut.DomQuery.GetInputElementValue('event-description');
            me.description(val);
            let startDate = me.times.startDate;
            let endDate = me.times.endDate;
            let repeatPattern = this.parseRepeatPattern(me.repeatPattern);
            if (repeatPattern.pattern && repeatPattern.start && me.repeatMode() !== 'instance') {
                let startDay = Momentito.momentFromString(repeatPattern.start);
                let daysDiff = moment.duration(startDay.diff(me.times.startDate)).asDays();
                if (daysDiff != 0) {
                    startDate = startDay;
                    if (endDate) {
                        endDate.add(daysDiff, 'days');
                    }
                }
            }
            let start = startDate.format('YYYY-MM-DD');
            let end = endDate == null ? start : endDate.format('YYYY-MM-DD');
            if (!me.times.allDay()) {
                start += ' ' + timeHelper.timeValueToString(me.times.startTimeValue, 24);
                end += ' ' + timeHelper.timeValueToString(me.times.endTimeValue, 24);
            }
            if (start == end) {
                end = null;
            }
            let dto = {
                id: me.id(),
                title: title,
                start: start,
                end: end,
                allDay: me.times.allDay() ? 1 : 0,
                location: me.location(),
                url: null,
                eventTypeId: me.selectedEventType().id,
                recurPattern: repeatPattern.pattern,
                recurEnd: repeatPattern.endValue,
                recurId: me.recurId,
                notes: me.notes(),
                description: me.description(),
                active: 1
            };
            return dto;
        }
    }
    QnutCalendar.calendarEventObservable = calendarEventObservable;
    class calendarPage {
        constructor(year, month, startDate, endDate) {
            this.compareStart = (m) => {
                return calendarPage.compareDate(m, this.startDate);
            };
            this.compareEnd = (m) => {
                return calendarPage.compareDate(m, this.endDate);
            };
            this.getNextMonth = () => {
                let response = {
                    year: this.year,
                    month: this.month + 1
                };
                if (response.month == 13) {
                    response.year++;
                    response.month = 1;
                }
                return response;
            };
            this.getPrevMonth = () => {
                let response = {
                    year: this.year,
                    month: this.month - 1
                };
                if (response.month == 0) {
                    response.year--;
                    response.month = 12;
                }
                return response;
            };
            this.month = month;
            this.year = year;
            this.startDate = moment(startDate);
            this.endDate = moment(endDate);
            this.startDate.startOf('day');
            this.endDate.startOf('day');
        }
        static compareDate(compareDate, calendarDate) {
            let f = compareDate.format('YYYY-MM-DD[T]HH:mm:00');
            let yourDate = moment(f);
            let t = yourDate.format();
            if (yourDate.isAfter(calendarDate)) {
                return 1;
            }
            if (yourDate.isBefore(calendarDate)) {
                return -1;
            }
            return 0;
        }
    }
    QnutCalendar.calendarPage = calendarPage;
    class CalendarViewModel extends Peanut.ViewModelBase {
        constructor() {
            super(...arguments);
            this.calendarIsPaging = ko.observable(false);
            this.tab = ko.observable('list');
            this.pageSelect = ko.observable('calendar');
            this.tabsVisible = ko.observable(true);
            this.canShowMenu = false;
            this.menuVisible = ko.observable(false);
            this.format = ko.observable('list');
            this.calendarRescheduleTitle = ko.observable('Reschedule event');
            this.requestMenuTitle = ko.observable('Submit Calendar Request');
            this.rescheduleForm = new calenderResceduleObservable(this);
            this.userPermission = ko.observable('view');
            this.canSubmitRequest = ko.observable(false);
            this.userPersonId = ko.observable(0);
            this.updateMode = ko.observable('update');
            this.eventForm = new calendarEventObservable(this);
            this.searchForm = new calendarSearchObservable();
            this.eventTypes = ko.observableArray();
            this.committees = ko.observableArray();
            this.resources = ko.observableArray();
            this.groups = ko.observableArray();
            this.filtered = ko.observable('all');
            this.filterCode = ko.observable('');
            this.filterMessage = ko.observable('');
            this.filterMenu = ko.observable('');
            this.filterMenuTitle = ko.observable('');
            this.pages = [];
            this.currentPage = -1;
            this.pagingEnabled = false;
            this.listViewTitle = ko.observable('Month, Year');
            this.loadingCalendar = ko.observable(false);
            this.fullMenu = ko.observable(true);
            this.calenderViewOptions = {
                left: 'prev,next,today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            };
            this.listViewOptions = {
                left: 'prev,next',
                center: 'title',
                right: ''
            };
            this.initEventFormVocabulary = (response) => {
                let me = this;
                me.addTranslations(response.translations);
                me.eventForm.times.initialize(me);
                me.eventForm.times.repeat.initialize(response.vocabulary);
                me.eventForm.vocabulary = response.vocabulary;
                me.eventForm.translator = me;
            };
            this.initEditor = (selector) => {
                tinymce.init({
                    selector: selector,
                    toolbar: "undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link | image",
                    plugins: "image imagetools link",
                    default_link_target: "_blank",
                    branding: false,
                    height: 75
                });
            };
            this.getEvents = (request, successFunction) => {
                let me = this;
                me.services.executeService('peanut.qnut-calendar::GetEvents', request, (serviceResponse) => {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let response = serviceResponse.Value;
                        if (response.translations) {
                            let initResponse = response;
                            me.initEventFormVocabulary(initResponse);
                        }
                        me.assignEventList(response);
                        if (successFunction) {
                            successFunction(response);
                        }
                    }
                    if (this.format() !== 'list') {
                        window.scrollTo(0, 0);
                    }
                })
                    .fail(() => {
                    let trace = me.services.getErrorInformation();
                })
                    .always(() => {
                    me.calendarIsPaging(false);
                });
            };
            this.getNewCalendar = (request, successFunction) => {
                let me = this;
                me.pagingEnabled = false;
                let month = me.getCurrentMonth();
                if (!request) {
                    request = month;
                }
                else {
                    request.year = month.year;
                    request.month = month.month;
                }
                me.getEvents(request, (response) => {
                    me.pages = [new calendarPage(request.year, request.month, response.startDate, response.endDate)];
                    me.currentPage = 0;
                    me.pagingEnabled = true;
                    if (successFunction) {
                        successFunction(response);
                    }
                });
            };
            this.testClick = () => {
                alert('test click');
            };
            this.displayModal = (id) => {
                this.hideServiceMessages();
                this.showModal(id);
            };
            this.changeTab = (tabName) => {
                let me = this;
                let hideHeader = false;
                switch (tabName) {
                    case 'edit':
                        hideHeader = true;
                        this.pageSelect(tabName);
                        break;
                    case 'view':
                        hideHeader = true;
                        this.pageSelect(tabName);
                        break;
                    default:
                        this.pageSelect('calendar');
                        this.tab(tabName);
                        break;
                }
                this.menuVisible(tabName == 'calendar' && this.canShowMenu);
                this.tabsVisible(!hideHeader);
                if (this.format() !== 'list') {
                    window.scrollTo(0, 0);
                }
            };
            this.onEventResize = (event, delta, revertFunc, jsEvent, ui, view) => {
                this.rescheduleForm.show(event, 'resize', revertFunc);
            };
            this.onEventDrop = (event, delta, revertFunc, jsEvent, ui, view) => {
                this.rescheduleForm.show(event, 'drop', revertFunc);
            };
            this.rescheduleEvent = () => {
                let event = this.rescheduleForm.close();
                let currentPage = this.pages[this.currentPage];
                let request = {
                    id: event.id,
                    start: event.start.format('YYYY-MM-DD HH:mm') + ':00',
                    end: event.end ? event.end.format('YYYY-MM-DD HH:mm') + ':00' : null,
                    year: currentPage.year,
                    month: currentPage.month,
                    filter: this.filtered(),
                    code: this.filterCode(),
                };
                this.services.executeService('peanut.qnut-calendar::RescheduleEvent', request, (serviceResponse) => {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let response = serviceResponse.Value;
                        this.refreshEvents(response);
                    }
                    if (this.format() !== 'list') {
                        window.scrollTo(0, 0);
                    }
                })
                    .fail(() => {
                    let trace = this.services.getErrorInformation();
                });
            };
            this.showButton = (id, visible = true) => {
                let element = document.querySelector(id);
                if (element) {
                    element.style.display = visible ? 'block' : 'none';
                }
            };
            this.newCalendar = (newEvents) => {
                let me = this;
                me.switchEventSource(newEvents);
                me.renderCalendar();
            };
            this.switchEventSource = (newEvents) => {
                let me = this;
                me.eventSource = {
                    id: 'qnut',
                    events: newEvents
                };
                me.calendar.removeAllEventSources();
                me.calendar.addEventSource(me.eventSource);
                me.calendar.refetchEvents();
            };
            this.clearFilter = () => {
                let me = this;
                me.fullMenu(true);
                let currentFilter = me.filtered();
                if (currentFilter !== 'type' && currentFilter != 'all') {
                    me.getNewCalendar(me.getCurrentMonth(), (response) => {
                        let events = Peanut.Helper.SortBy(response.events, 'start');
                        me.setFilter(events);
                    });
                }
                else {
                    me.setFilter(me.eventSource.events);
                }
            };
            this.setFilter = (events, filter = 'all', message = '', code = '') => {
                let me = this;
                me.filtered(filter);
                me.filterCode(code);
                me.switchEventSource(events);
                me.filterMessage(message);
            };
            this.filterEventType = (item) => {
                let me = this;
                me.hideModal("#filter-menu-modal");
                let filter = 'type';
                let currentFilter = me.filtered();
                let currentCode = me.filterCode();
                if (!(currentFilter == filter && currentCode == item.code)) {
                    if (currentFilter == filter || currentFilter == 'all') {
                        me.setTypeFilter(item);
                    }
                    else {
                        me.getNewCalendar(null, (response) => {
                            me.eventSource = {
                                id: 'qnut',
                                events: response.events
                            };
                            me.setTypeFilter(item);
                        });
                    }
                }
            };
            this.getFilteredEvents = (filter, item) => {
                let me = this;
                me.hideModal("#filter-menu-modal");
                let currentFiltered = me.filtered();
                let currentCode = me.filterCode();
                if (currentFiltered != filter || currentCode != item.code) {
                    me.getNewCalendar({
                        filter: filter,
                        code: item.code
                    }, (response) => {
                        me.eventSource = {
                            id: 'qnut',
                            events: response.events
                        };
                        me.setFilter(response.events, filter, item.description, item.code);
                    });
                }
            };
            this.filterCommittee = (item) => {
                this.getFilteredEvents('committee', item);
            };
            this.filterResource = (item) => {
                this.getFilteredEvents('resource', item);
            };
            this.filterGroups = (item) => {
                this.getFilteredEvents('group', item);
            };
            this.onViewRender = (view, element) => {
                let me = this;
                if (me.currentPage >= 0) {
                    me.pageCalendar(view.start, view.end);
                }
            };
            this.pageCalendar = (start, end) => {
                let me = this;
                if (me.pagingEnabled) {
                    let startDate = start.toISOString();
                    let endDate = end.toISOString();
                    let page = me.pages[me.currentPage];
                    let movePage = 0;
                    if (page.compareStart(moment(start)) == -1) {
                        Peanut.logger.write('Page prev');
                        movePage = -1;
                    }
                    else if (page.compareEnd(moment(end)) > 0) {
                        Peanut.logger.write('Page next');
                        movePage = 1;
                    }
                    else {
                        return;
                    }
                    me.changeMonth(movePage);
                }
                else {
                    me.pagingEnabled = true;
                }
            };
            this.changeMonth = (movePage) => {
                let newPage = this.currentPage + movePage;
                if (newPage < 0 || newPage >= this.pages.length) {
                    this.getNextPage(movePage);
                }
                else {
                    this.currentPage = newPage;
                    return true;
                }
                return false;
            };
            this.getNextPage = (movePage) => {
                let me = this;
                let page = me.pages[me.currentPage];
                let tab = me.tab();
                let request = null;
                if (movePage > 0) {
                    request = page.getNextMonth();
                    request.pageDirection = 'right';
                    Peanut.logger.write('Get next page ' + request.pageDirection + ' ' + request.year + '-' + request.month);
                }
                else {
                    request = page.getPrevMonth();
                    request.pageDirection = 'left';
                    Peanut.logger.write('Get prev page' + request.pageDirection + ' ' + request.year + '-' + request.month);
                }
                if (me.filtered() != 'all' && me.filtered() != 'type') {
                    request.filter = me.filtered();
                    request.code = me.filterCode();
                }
                me.calendarIsPaging(true);
                me.getEvents(request, (response) => {
                    let events = me.eventSource.events.concat(response.events);
                    events = Peanut.Helper.SortBy(events, 'start');
                    if (me.filtered() === 'type') {
                        let code = me.filterCode();
                        me.eventSource.events = events.filter((event) => {
                            return event.eventType == code;
                        });
                    }
                    let newPage = new calendarPage(request.year, request.month, response.startDate, response.endDate);
                    if (movePage > 0) {
                        me.currentPage = me.pages.length;
                        me.pages.push(newPage);
                    }
                    else {
                        me.currentPage = 0;
                        me.pages.unshift(newPage);
                    }
                    me.newCalendar(events);
                    me.tab(tab);
                });
            };
            this.eventInfoModal = (mode) => {
                if (mode === 'show') {
                    this.showModal('event-info-modal');
                }
                else {
                    this.hideModal('event-info-modal');
                }
            };
            this.refreshEvents = (response) => {
                this.assignEventList(response);
                let events = Peanut.Helper.SortBy(response.events, 'start');
                if (this.filtered() === 'type') {
                    let code = this.filterCode();
                    let events = this.eventSource.events.filter((event) => {
                        return event.eventType == code;
                    });
                }
                let page = this.pages[this.currentPage];
                this.pages = [page];
                this.currentPage = 0;
                this.newCalendar(events);
                this.changeTab('calendar');
            };
            this.onEventListItemClick = (item) => {
                this.onEventClick(item.event, null, null);
            };
            this.onEventClick = (calEvent, jsEvent, view) => {
                let me = this;
                calEvent.jsEvent.preventDefault();
                let eventId = calEvent.event.id;
                let event = me.eventSource.events.find((eventObject) => {
                    return eventObject.id == eventId;
                });
                me.eventForm.assignFromCalendarObject(event);
                me.hideServiceMessages();
                me.showModal('event-info-modal');
            };
            this.onNewEvent = () => {
                this.updateMode('new');
                this.eventForm.clear(this.committees, this.resources, this.eventTypes, this.groups);
                this.showEditPage();
            };
            this.onDeleteEvent = () => {
                this.updateMode('delete');
                this.eventInfoModal('hide');
                this.hideServiceMessages();
                this.deleteConfirmModal('show');
            };
            this.onEditEvent = () => {
                let me = this;
                me.updateMode('update');
                me.eventInfoModal('hide');
                if (!me.eventForm.createdBy()) {
                    me.getEventDetails(() => {
                        this.showEditPage();
                    });
                }
                else {
                    this.showEditPage();
                }
            };
            this.editRescheduledEvent = () => {
                let event = this.rescheduleForm.close();
                this.eventForm.assignFromCalendarObject(event);
                this.onEditEvent();
            };
            this.showEditPage = () => {
                this.eventForm.edit(this.committees, this.resources, this.eventTypes, this.groups);
                this.changeTab('edit');
            };
            this.onCancelEdit = () => {
                this.rescheduleForm.revert();
                this.changeTab('calendar');
            };
            this.onUpdateEvent = () => {
                if (this.eventForm.repeating()) {
                    if (this.eventForm.id() && !this.eventForm.recurId) {
                        this.eventForm.repeatMode('all');
                        this.displayModal('#repeat-mode-modal');
                    }
                    else {
                        this.eventForm.repeatMode('none');
                        this.updateEvent();
                    }
                }
                else {
                    if (this.eventForm.repeatMode() === 'remove') {
                        this.displayModal('#confirm-repeat-delete-modal');
                    }
                    else {
                        this.updateEvent();
                    }
                }
            };
            this.deleteConfirmModal = (mode = 'show') => {
                let show = (mode === 'show');
                if (show) {
                    this.eventForm.repeatMode('all');
                }
                let deleteModalId = this.eventForm.repeating() ? '#repeat-mode-modal' : '#confirm-event-delete-modal';
                if (show) {
                    this.showModal(deleteModalId);
                }
                else {
                    this.hideModal(deleteModalId);
                }
            };
            this.showDeleteConfirmation = () => {
                this.hideServiceMessages();
                this.deleteConfirmModal('show');
            };
            this.deleteEvent = () => {
                this.deleteConfirmModal('hide');
                let page = this.pages[this.currentPage];
                let request = {
                    eventId: this.eventForm.id(),
                    startDate: this.eventForm.start.format('YYYY-MM-DD'),
                    repeatUpdateMode: this.eventForm.repeating() ? this.eventForm.repeatMode() : 'none',
                    filter: this.filtered(),
                    code: this.filterCode(),
                    year: page.year,
                    month: page.month
                };
                this.services.executeService('peanut.qnut-calendar::DeleteEvent', request, (serviceResponse) => {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let response = serviceResponse.Value;
                        this.refreshEvents(response);
                    }
                    if (this.format() !== 'list') {
                        window.scrollTo(0, 0);
                    }
                })
                    .fail(() => {
                    let trace = this.services.getErrorInformation();
                });
            };
            this.onUpdateConfirmed = () => {
                if (this.updateMode() == 'update') {
                    this.updateEvent();
                }
                else {
                    this.deleteEvent();
                }
            };
            this.updateEvent = () => {
                if (this.eventForm.repeatMode() == 'remove') {
                    this.hideModal('#confirm-repeat-delete-modal');
                }
                else {
                    this.hideModal('#repeat-mode-modal');
                }
                let dto = this.eventForm.validate();
                if (this.format() !== 'list') {
                    window.scrollTo(0, 0);
                }
                if (dto) {
                    let repeatMode = this.eventForm.repeating() ? this.eventForm.repeatMode() : '';
                    let currentPage = this.pages[this.currentPage];
                    let request = {
                        event: dto,
                        year: currentPage.year,
                        month: currentPage.month,
                        filter: this.filtered(),
                        code: this.filterCode(),
                        repeatUpdateMode: repeatMode,
                        resources: this.getSelectedItemIds(this.eventForm.selectedResources),
                        committees: this.getSelectedItemIds(this.eventForm.selectedCommittees),
                        groups: this.getSelectedItemIds(this.eventForm.selectedGroups),
                        repeatInstance: this.eventForm.recurInstance
                    };
                    this.services.executeService('peanut.qnut-calendar::UpdateEvent', request, (serviceResponse) => {
                        if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                            let response = serviceResponse.Value;
                            this.refreshEvents(response);
                        }
                        if (this.format() !== 'list') {
                            window.scrollTo(0, 0);
                        }
                    })
                        .fail(() => {
                        let trace = this.services.getErrorInformation();
                    });
                }
            };
            this.showEventDetails = () => {
                let me = this;
                me.getEventDetails(() => {
                    me.eventInfoModal('hide');
                    me.changeTab('view');
                });
            };
            this.getEventDetails = (successFunction) => {
                let me = this;
                me.services.executeService('peanut.qnut-calendar::GetEventDetails', me.eventForm.id(), (serviceResponse) => {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let response = serviceResponse.Value;
                        me.eventForm.assignDetails(response);
                        if (successFunction) {
                            successFunction();
                        }
                    }
                })
                    .fail(() => {
                    let trace = me.services.getErrorInformation();
                });
            };
            this.showListView = () => {
                this.tab('list');
                this.setCalendarHeader('list');
                this.pagingEnabled = false;
                this.calendar.setOption('headerToolbar', this.listViewOptions);
                this.calendar.changeView('listMonth');
                this.pagingEnabled = true;
            };
            this.showCalendarView = () => {
                this.tab('calendar');
                this.calendar.setOption('headerToolbar', this.calenderViewOptions);
                this.setCalendarHeader('calendar');
                this.pagingEnabled = false;
                this.calendar.changeView('dayGridMonth');
                this.pagingEnabled = true;
                this.changeTab('calendar');
            };
            this.testModal = () => {
                this.showModal('#confirm-repeat-delete-modal');
            };
            this.test = () => {
                alert('test');
            };
            this.showEventsFilterMenu = (item) => {
                this.showFilterMenu('events');
            };
            this.showCalendarFilterMenu = (item) => {
                this.showFilterMenu('committees');
            };
            this.showResourcesFilterMenu = (item) => {
                this.showFilterMenu('resources');
            };
            this.showGroupsFilterMenu = (item) => {
                this.showFilterMenu('groups');
            };
            this.showFilterMenu = (item) => {
                this.filterMenu(item);
                let title = this.translate('calendar-filter-title-' + item);
                this.filterMenuTitle(title);
                this.displayModal("#filter-menu-modal");
            };
            this.showReminderForm = () => {
                let me = this;
                this.eventForm.notificationDays(0);
                this.eventForm.sendNotifications(false);
                let request = {
                    personId: me.userPersonId(),
                    eventId: me.eventForm.id()
                };
                me.services.executeService('peanut.qnut-calendar::GetEventNotification', request, (serviceResponse) => {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let enabled = serviceResponse.Value >= 0;
                        this.eventForm.notificationDays(enabled ? serviceResponse.Value : 0);
                        this.eventForm.sendNotifications(enabled);
                    }
                })
                    .fail(() => {
                    let trace = me.services.getErrorInformation();
                })
                    .always(() => {
                    me.eventInfoModal('hide');
                    me.displayModal("#event-notification-modal");
                });
            };
            this.updateNotification = () => {
                let me = this;
                me.hideModal('#event-notification-modal');
                let request = {
                    personId: this.userPersonId(),
                    eventId: this.eventForm.id(),
                    notificationDays: this.eventForm.sendNotifications() ? this.eventForm.notificationDays() : -1,
                };
                me.services.executeService('peanut.qnut-calendar::UpdateEventNotification', request, (serviceResponse) => {
                })
                    .fail(() => {
                    let trace = me.services.getErrorInformation();
                });
            };
            this.enterCalendarRequest = () => {
                this.showModal('#request-menu-modal');
            };
            this.onCalendarRequestClose = () => {
                this.hideModal('#request-menu-modal');
            };
            this.onCalendarSearch = () => {
                let searchText = this.searchForm.title().trim();
                let me = this;
                me.searchForm.results([]);
                me.services.executeService('peanut.qnut-calendar::FindEvents', searchText, (serviceResponse) => {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        me.searchForm.results(serviceResponse.Value);
                        if (me.searchForm.results().length == 0) {
                            alert("No events found");
                        }
                    }
                })
                    .fail(() => {
                    let trace = me.services.getErrorInformation();
                })
                    .always(() => {
                });
            };
            this.onCancelSearch = () => {
                this.hideModal('#find-event-modal');
                this.searchForm.title('');
            };
            this.openSearchItem = (item) => {
                this.hideModal('#find-event-modal');
            };
        }
        init(successFunction) {
            let me = this;
            me.changeTab('calendar');
            Peanut.logger.write('calendar Init');
            me.application.loadStyleSheets([
                '@pkg:qnut-calendar'
            ]);
            me.application.registerComponents(['@pnut/modal-confirm', '@pkg/qnut-calendar/calendar-request'], () => {
                me.application.loadResources([
                    '@pnut/ViewModelHelpers'
                ], () => {
                    me.application.loadResources([
                        '@lib:fullcalendar-js',
                        '@lib:tinymce'
                    ], () => {
                        let request = {
                            initialize: 1,
                            screenSize: Environment.getDeviceSize(),
                            context: me.getVmContext()
                        };
                        me.getNewCalendar(request, (response) => {
                            me.format(me.deviceSize() == 1 ? 'list' : response.format);
                            me.eventTypes(response.types);
                            me.userPermission(response.userPermission);
                            me.canSubmitRequest(response.canSubmitRequest);
                            me.userPersonId(response.userPersonId);
                            me.resources(response.resources);
                            me.committees(response.committees);
                            me.groups(response.groups);
                            me.initializeFilter(response);
                            let prefiltered = !!response.filteredBy;
                            if (response.userPermission == 'edit') {
                                me.initEditor('#event-description');
                                me.eventForm.addCaption(me.translate('label-add') + '...');
                            }
                            me.canShowMenu = response.userPermission !== 'view' && !prefiltered;
                            me.showCalendar(response.events, prefiltered);
                            me.bindDefaultSection();
                            successFunction();
                        });
                    });
                });
            });
        }
        assignEventList(response) {
            let me = this;
            let eventList = [];
            let responseEvents = response.events;
            response.events = [];
            let eventsLength = responseEvents.length;
            for (let i = 0; i < eventsLength; i++) {
                let value = responseEvents[i];
                value.allDay = value.allDay == '1';
                response.events.push(value);
            }
            responseEvents = null;
        }
        showCalendar(events, preFiltered = true) {
            let me = this;
            let size = me.deviceSize();
            events = Peanut.Helper.SortBy(events, 'start');
            me.eventSource = {
                id: 'qnut',
                events: events
            };
            if (!preFiltered) {
                me.filtered('all');
            }
            let format = me.format();
            let initialView = format === 'list' ? 'listMonth' : 'dayGridMonth';
            const calendarEl = document.getElementById('calendar');
            me.calendar = new FullCalendar.Calendar(calendarEl, {
                headerToolbar: me.calenderViewOptions,
                initialView: initialView,
                navLinks: true,
                editable: me.userPermission() == 'edit',
                dayMaxEventRows: true,
                dayMaxEvents: true,
                eventClick: me.onEventClick,
                eventResize: me.onEventResize,
                eventDrop: me.onEventDrop,
                datesSet: me.onViewRender,
                fixedWeekCount: false,
                events: me.eventSource
            });
            me.renderCalendar();
            this.menuVisible(this.canShowMenu);
        }
        setCalendarHeader(format) {
            if (this.format() !== 'list') {
                if (format === 'list') {
                    this.showButton('.fc-today-button', false);
                    this.showButton('.fc-right', false);
                }
                else {
                    this.showButton('.fc-today-button');
                    this.showButton('.fc-right');
                }
            }
        }
        renderCalendar() {
            let me = this;
            let t = window.setInterval(() => {
                me.calendar.render();
                clearInterval(t);
            }, 100);
        }
        getCurrentMonth() {
            let me = this;
            if (me.currentPage < 0) {
                return {
                    year: new Date().getFullYear(),
                    month: new Date().getMonth() + 1,
                };
            }
            else {
                let page = me.pages[me.currentPage];
                return {
                    year: page.year,
                    month: page.month
                };
            }
        }
        initializeFilter(response) {
            let me = this;
            if (response.filteredBy) {
                let filterSpec = response.filteredBy.split(':');
                if (filterSpec.length !== 2) {
                    return false;
                }
                let filter = filterSpec[0];
                let code = filterSpec[1];
                let items = [];
                switch (filter) {
                    case 'committee':
                        items = response.committees;
                        break;
                    case 'group':
                        items = response.groups;
                        break;
                    default:
                        items = response.resources;
                        break;
                }
                for (let i = 0; i < items.length; i++) {
                    let item = items[i];
                    if (item.code === code) {
                        me.filtered(filter);
                        me.filterCode(code);
                        me.filterMessage(item.name);
                        return true;
                    }
                }
                return false;
            }
            return true;
        }
        setTypeFilter(item) {
            let me = this;
            let events = me.eventSource.events.filter((event) => {
                return event.eventType == item.code;
            });
            me.setFilter(events, 'type', item.description, item.code);
        }
        getSelectedItemIds(observable) {
            let result = [];
            let list = observable();
            for (let i = 0; i < list.length; i++) {
                result.push(list[i].id);
            }
            return result;
        }
        onEventSearch() {
            this.searchForm.title('');
            this.searchForm.results([]);
            this.showModal('#find-event-modal');
        }
    }
    QnutCalendar.CalendarViewModel = CalendarViewModel;
})(QnutCalendar || (QnutCalendar = {}));
//# sourceMappingURL=CalendarViewModel.js.map