/// <reference path="../../../../pnut/core/ViewModelBase.ts" />
/// <reference path='../../../../typings/knockout/knockout.d.ts' />
/// <reference path='../../../../typings/moment/moment.d.ts' />
/// <reference path='../../../../pnut/core/Peanut.d.ts' />


// import {Calendar} from "@fullcalendar/core";

namespace QnutCalendar {
    /*
        Use cases:
            Show first page
            Paging
            Different views: week, day
            New event
            Edit recurrence
            New recurring event
            Update event
            Update recurring -all
            Update recurring -instance
            Update replacement
            Update reaplacement with new recurrance
            Add/Remove notificatoins
            Add/Remove associations
            Delete single event
            Delete recurring - instance
            Delete recurring - all
     */

    import ILookupItem = Peanut.ILookupItem;
    import Environment = Peanut.Environment;
    import init = tinymce.init;
    import ViewModelBase = Peanut.ViewModelBase;
    import IUserDetails = Peanut.IUserDetails;

    interface Moment extends moment.Moment {}  // for syntactical help

    // for calendar display
    interface ICalendarEvent {
        id : any;
        title : string;
        start : string;
        end : string;
        location: string;
        allDay : any;
        url : string;
        eventType : string;
        backgroundColor : string;
        borderColor : string;
        textColor : string;
        repeatPattern : string;
        occurance: any;
    }

    interface ICalendarSearchResult {
        id : any;
        title : string;
        startdate : string;
    }


    // Calendar object converted for edit
    interface ICalendarEventObject {
        id : any;
        title : string;
        start : Moment;
        end : Moment;
        allDay : boolean;
        location: string;
        url : string;
        eventType : string;
        repeatPattern : string;
        recurInstance: any;
    }

    interface IEventListItem {
        startTime: string;
        event: ICalendarEventObject;
        eventTimeFormatted: string;
    }

    interface IFCEventSource {
        id: any;
        events: any[];
    }

    interface IEventListPage {
        year: any;
        month: any;
        items: IEventListItem[];
    }

    interface ICalendarDto {
        id : any;
        title : string;
        start : string;
        end : string;
        allDay : number;
        location: string;
        url : string;
        eventTypeId : any;
        recurPattern : string;
        recurEnd : any;
        recurId: any;
        notes: string;
        description: string;
    }

    interface ICalendarUpdateRequest {
        event: ICalendarDto;
        year: any;
        month: any;
        repeatInstance: any;
        filter: string;
        code: string;
        repeatUpdateMode: string;  // 'all' | 'instance'
        // notificationDays: any;
        resources: any[];
        committees: any[];
        groups: any[];
    }

    interface IRescheduleEventRequest {
        id : any;
        start : string;
        end : string;
        filter: string;
        code: string;
        year: any;
        month: any;
    }

    interface ICalendarDeleteRequest {
        eventId: any;
        year: any;
        month: any;
        startDate: any;
        repeatUpdateMode: string;  // 'all' | 'instance' | 'none'
        filter: string;
        code: string;
    }


    interface ICalendarEventDetails extends ICalendarEvent {
        eventTypeId: any;
        notes: string;
        description: string;
        recurId: any;
        // notificationDays: any;
        resources: Peanut.ILookupItem[];
        committees: Peanut.ILookupItem[];
        groups: Peanut.ILookupItem[];
        // notification: any;
        recurStartOn: string;
        createdBy: string;
        createdOn: string;
        changedBy: string;
        changedOn: string;
    }

    interface IGetCalendarResponse {
        events: ICalendarEvent[];
        startDate: string;
        endDate: string;
        filteredBy: string;
        month: any;
        year: any;
    }

    interface ICalendarTranslations {
        daysOfWeek: string[];
        daysOfWeekPlural: string[];
        monthNames: string[];
        ordinalSuffix: string[];
        ordinals: string[];
    }

    interface ICalendarInitResponse extends IGetCalendarResponse {
        events: ICalendarEvent[];
        userPermission: any;
        canSubmitRequest: any;
        userPersonId: any;
        types: Peanut.ILookupItem[];
        committees: Peanut.ILookupItem[];
        resources: Peanut.ILookupItem[];
        groups: Peanut.ILookupItem[];
        vocabulary: ICalendarTranslations;
        format: string;
        translations : string[];
    }

    interface ICalendarNotification {
        personId: any,
        eventId: any,
        notificationDays: any;
    }

    interface IRepeatPattern {
        pattern: string;
        start: string;
        endValue: string;
    }

    interface IDateTime {
        date: Moment;
        time: number;
    }

    export class Momentito {
        public static dateOnly(m: Moment) {
            return moment(m.format('YYYY-MM-DD[T]00:00:00'));
        }
        public static clone(m: Moment, useCurrentLocale = true) {
            return useCurrentLocale ? m.clone() : moment(m.format());
        }
        public static swapDates(m:Moment, ds: string) {
            let timeString = m.format('[T]00:00:00');
            Momentito.getDateString(ds); // convert to iso
            return moment(ds + timeString);
        }

        public static normalize(m: Moment) {
            return moment(m.format('YYYY-MM-DD[T]HH:mm:00'));
        }
        public static toIsoString(m: Moment) {
            return m.format('YYYY-MM-DD[T]HH:mm:00');
        }

        public static parse(m: Moment = null, normalize = true) {
            if (!m) {
                m = moment(); // today
            }
            let t = normalize ? moment(m.format('YYYY-MM-DD[T]HH:mm:00')) : m.clone();
            return <IDateTime> {
                date: moment(t.format('YYYY-MM-DD[T]00:00:00')),
                time: (t.hours() * 60) + t.minutes()
            };
        }

        public static getTimeValue(m: Moment, normalize = true) {
            let t = normalize ? moment(m.format('YYYY-MM-DD[T]HH:mm:00')) : m;
            let h = t.hour();
            let min = t.minute();
            return (h*60) + min;
        }

        public static getStringValue(n: number) {
            let s = n.toString();
            return s.length == 1 ? '0' + s : s;
        }

        public static momentFromString(s: string) {
            let parts = s.split('/');
            if (parts.length === 3) {
                // convert US short date to iso format
                let m = parts[0].length === 1 ? '0' + parts[0].toString() : parts[0];
                let d = parts[1].length === 1 ? '0' + parts[1].toString() : parts[1];
                let y = parts[2].length === 2 ? '20' + parts[2].toString() : parts[2];
                s =  y + '-' + m + '-'+ d + 'T00:00:00';
            }
            else if (s.length === 10) {
                s += 'T00:00:00'; // If date only specify midnight to avoid timezone issues
            }
            return moment(s);
        }

        public static getDateString(s: string) {
            let m = (s === null || s.trim() === '') ? moment() : this.momentFromString(s);
            return m.format('YYYY-MM-DD');
        }


    }

    export class timeHelper {
        static timeStringToValue(time: string) {
            if (time === null) {
                return '';
            }
            time = time.replace(/\s/g, '').toLowerCase().trim();
            if (time === '') {
                return null;
            }
            // console.log('time: ' + time);
            let am = time.indexOf('a');
            let p = am;
            let pm = -1;
            if (am === -1) {
                pm = time.indexOf('p');
                p = pm
            }

            if (p > -1) {
                let a = time.substring(p);
                if (a !== 'a' && a !== 'am' && a !== 'p' && a !== 'pm') {
                    return -1;
                }
                time = time.substring(0, p).trim();
                if (time === '') {
                    // console.log('no time');
                    return -1;
                }
            }
            let parts = time.split(':');
            if (parts.length === 0)  {
                return null;
            }
            let h = parts.shift().trim();
            if (h === '' && parts.length === 0) {
                return null;
            }

            let hours = Number(h);
            if (isNaN(hours) || Math.floor(hours) !== hours || hours < 0) {
                // console.log('Bad hour: ' + hours + '(' + h);
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
                // console.log('too many hours: ' + hours);
                return -1;
            }
            let minutes = 0;
            if (parts.length > 0) {
                let m = parts.shift().trim();
                if (m ==='' && h ==='') {
                    return null;
                }
                minutes = (!m) ? 0 : Number(m);
                if (isNaN(minutes) || Math.floor(minutes) !== minutes || minutes > 59 || minutes < 0) {
                    return -1;
                }
            }
            // console.log('h:' + hours + ' m:' + minutes);
            return (hours * 60) + minutes;
        }

        static timeValueToString = (value: any, hourFormat = 12) => {
            if (value === null) {
                return '';
            }
            if (isNaN(value) || value < 0 || value > 1439) {
                return 'invalid'
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


    }

    export class repeatInfoEditor {

        test = ko.observable('');

        translator: Peanut.ITranslator;
        basis = ko.observable('d');
        patternType = ko.observable('d');
        interval = ko.observable(1);
        monthInterval = ko.observable(1);
        dowList = ko.observableArray<Peanut.ISelectListItem>([]);
        daysOfWeek = ko.observableArray<Peanut.INameValuePair>([]);
        selectedDow = ko.observable<Peanut.INameValuePair>();
        ordinals = ko.observableArray<Peanut.INameValuePair>([]);
        selectedOrdinal = ko.observable<Peanut.INameValuePair>(null);
        ordinalsSet = [
            ko.observable(true),
            ko.observable(false),
            ko.observable(false),
            ko.observable(false),
            ko.observable(false)
        ];
        selectedMonth = ko.observable<Peanut.INameValuePair>();
        selectedOrdinalMonth = ko.observable<Peanut.INameValuePair>();
        months = ko.observableArray<Peanut.INameValuePair>([]);
        monthDay = ko.observable<any>('');
        selectedWeekdays = ko.observable('');
        weekdaysMessage = ko.observable('');
        endDateBasis = ko.observable('none');
        startOn = ko.observable('');
        isNew = ko.observable(false);
        endBy = ko.observable('');
        endOccurances = ko.observable<any>('');
        basisSubscription : any = null;
        endDateBasisSubscription: any = null;
        recurEndDateSubscripton: any = null;
        recurOccurencesSubscription: any = null;

        public initialize(vocabulary: ICalendarTranslations) {
            let me = this;
            for(let i = 0; i<7; i++) {
                let day =  {
                    Value: i + 1,
                    Name: vocabulary.daysOfWeek[i],
                };
                me.daysOfWeek.push(day);

                let dow = {
                    Name: day.Name.substring(0,1),
                    Value: i + 1,
                    Description: day.Name
                };
                me.dowList.push(dow);
            }
            me.selectedDow(me.daysOfWeek()[0]);
            for (let i = 0; i < vocabulary.ordinals.length; i++) {
                let item = <Peanut.INameValuePair> {
                    Name: vocabulary.ordinals[i],
                    Value : i + 1
                };
                me.ordinals().push(item);
            }

            me.selectedOrdinal (me.ordinals()[0]);
            for (let i = 0; i < vocabulary.monthNames.length; i++) {
                let item = <Peanut.INameValuePair> {
                    Name: vocabulary.monthNames[i],
                    Value : i + 1
                };
                me.months().push(item);
            }
            me.selectedMonth (me.months()[0]);
            me.selectedOrdinalMonth (me.months()[0])

        }

        defaultOrdinalSet = () => {
            this.ordinalsSet[0](true);
            for(let i=1;i<5;i++) {
                this.ordinalsSet[i](false);
            }
        };

        setOrdinalSet = (values: string) => {
            for(let i=0;i<5;i++) {
                this.ordinalsSet[i](values.indexOf((i+1).toString()) !== -1);
            }
        };

        getOrdinalsString = () => {
            let result = '';
            for(let i=0;i<5;i++) {
                if (this.ordinalsSet[i]()) {
                    result += (i+1).toString();
                }
            }
            return result;
        };


        setWeekdaysMessage =  () => {
            let message = '';
            let days = this.selectedWeekdays();
            for (let i = 0; i < days.length; i++ ) {
                let value = Number(days.charAt(i)) - 1;
                message = message + (message !== '' ? ', ' : '') + this.daysOfWeek()[value].Name;
            }
            this.weekdaysMessage(message);
        };


        public setPattern(repeatPattern: string, dateFormat: string) {
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
            // let selectedOrdinal = me.selectedOrdinal();
            me.selectedOrdinal(me.ordinals()[0]);
            // me.yearlySelectedOrdinal(me.ordinals()[0]);
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
                me.recurEndDateSubscripton =  me.endBy.subscribe(me.onEndByChange);
                me.recurOccurencesSubscription = me.endOccurances.subscribe(me.onOccurencesChange)

            }

            let patternParts = repeatPattern.substring(2).split(',');
            let interval = patternParts.length == 0 ? 0 : Number(patternParts[0]);
            me.setBasis(repeatPattern.substring(0, 1));
            me.patternType(repeatPattern.substring(0, 2));
            switch (me.patternType()) {
                case 'dd' :
                    break;
                case 'dw' :
                    break;
                case 'wk' :
                    me.selectedWeekdays(patternParts.length < 2 ? '' : patternParts[1]);
                    me.setWeekdaysMessage();
                    break;
                case 'md' :
                    me.monthDay(patternParts.length < 2 ? '' : patternParts[1]);
                    break;
                case 'mo' :
                    // me.setNameValuePair(patternParts.length < 2 ? 1 : patternParts[1],me.ordinals,me.selectedOrdinal);
                    me.setOrdinalSet(patternParts[1]);
                    me.setNameValuePair(patternParts.length < 3 ? 1 : patternParts[2],me.daysOfWeek,me.selectedDow);
                    break;
                case 'yd' :
                    me.setNameValuePair(patternParts.length < 2 ? 1 : patternParts[1],me.months,me.selectedMonth);
                    me.monthDay(patternParts.length < 3 ? 1 : patternParts[2]);
                    break;
                case 'yo' :
                    me.setNameValuePair(patternParts.length < 2 ? 1 : patternParts[1],me.ordinals,me.selectedOrdinal);
                    me.setNameValuePair(patternParts.length < 3 ? 1 : patternParts[2],me.daysOfWeek,me.selectedDow);
                    me.setNameValuePair(patternParts.length < 4 ? 1 : patternParts[3],me.months,me.selectedOrdinalMonth);
                    break;
                default:
                    // log error
                    console.error('Invalid repeat pattern.');
                    break;
            }

        }

        onOccurencesChange = (value: any) => {
            this.endDateBasis( value ? "occurances" : 'none');
        };

        onEndByChange = (value: any) => {
            this.endDateBasis( value ? "date" : 'none');
        };

        onEndDateBasisChange = (value: any) => {
            switch (value) {
                case 'none':
                    this.endBy('');
                    this.endOccurances('');
                    return;
                case 'occurances' :
                    this.endBy('');
                    if (this.endOccurances() == '') {
                        this.endOccurances('1');
                    }
                    return;
                case 'date' :
                    this.endOccurances('');
                    if (this.endBy() == '') {
                        this.endBy(this.startOn());
                    }
                    return;
            }
        };

        setBasis = (value: any) =>{
            if (this.basisSubscription !== null) {
                this.basisSubscription.dispose();
                this.basisSubscription = null;
            }
            this.basis(value);
            this.basisSubscription = this.basis.subscribe(this.onBasisChange);
        };

        onBasisChange = (value: string) => {
            // reset defaults
            switch (value) {
                case 'w' :
                    this.patternType('wk');
                    break;
                case 'm' :
                    this.patternType('mo');
                    break;
                default:
                    this.patternType(value += 'd');
            }
            // this.patternType(value == 'w' ? 'wk' : value+'d');
            this.interval(1);
            this.monthDay(1);
            // this.selectedWeekdays('');
            this.setWeekdaysMessage();
            this.monthInterval(1);
            this.selectedOrdinal (this.ordinals()[0]);
            // this.yearlySelectedOrdinal (this.ordinals()[0]);
            // verify: dayOfWeek used??
            // this.dayOfWeek(this.dowList()[0]);
            this.selectedDow(this.daysOfWeek()[0]);
            this.selectedMonth(this.months()[0]);
            this.selectedOrdinalMonth(this.months()[0]);
        };

        onDowClick = (item: Peanut.ISelectListItem) => {
            let list = this.selectedWeekdays();
            if (list.indexOf(item.Value) > -1) {
                this.selectedWeekdays(list.replace(item.Value,''));
            }
            else {
                let p = 0;
                for (let i = list.length -1 ; i>= 0; i--) {
                    let x = list.charAt(i);
                    let c = Number(list.charAt(i));
                    if (c < item.Value) {
                        p = i+1;
                        break;
                    }
                }
                list = list.substring(0,p) + item.Value + list.substring(p);
                this.selectedWeekdays(list);
            }
            this.setWeekdaysMessage();
        };

        setNameValuePair(value: any, items: KnockoutObservableArray<Peanut.INameValuePair>,selected : KnockoutObservable<Peanut.INameValuePair>) {
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

        public getRepeatPattern() : string {
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

        /**
         * Derive repeat pattern from dialog box
         */
        public getPattern() : string {
            let me = this;
            switch (me.patternType()) {
                case 'dd' :
                    // Daily: Every [n] day     interval
                    return 'dd' + me.interval();
                case 'dw' :
                    return 'dw';
                case 'wk' :
                    //Weekly:
                    // Every [n] week			interval
                    // [days-of-week]			dowList
                    return 'wk' + me.interval() + ',' + me.selectedWeekdays();
                case 'md' :
                    // Monthly: Day[x] of [y]		 	monthDay interval
                    return 'md' + me.interval() + ',' + me.monthDay();
                case 'mo' :
                    // Monthly: The [x] [y] every [z]   selectedOrdinal, selectedDow, monthInterval
                    // return 'mo' + me.monthInterval() + ',' +  me.selectedOrdinal().Value + ',' +  me.selectedDow().Value;
                    let moOrdinals = me.getOrdinalsString();
                    return 'mo' + me.monthInterval() + ',' +  moOrdinals + ',' +  me.selectedDow().Value;
                case 'yd' :
                    // Yearly:
                    // Every [n] year(s)	interval
                    // On [x] [y]			selectedMonth, monthDay
                    return 'yd' +me.interval()+ ',' +me.selectedMonth().Value + ',' + me.monthDay();
                case 'yo' :
                    // Yearly:
                    // Every [n] year(s)	interval
                    // On [x] [y] of [z]	selectedOrdinal, selectedDow, selectedOrdinalMonth
                    return 'yo' + me.interval() + ',' + me.selectedOrdinal().Value + ','
                        + me.selectedDow().Value + ',' + me.selectedOrdinalMonth().Value;
                default:
                    // log error
                    console.error('Invalid repeat pattern.');
                    return null;
            }
        }
    }

    export class eventTimeEditor {
        // varibles
        startDate: Moment;
        endDate: Moment = null;
        startTimeValue : any;
        endTimeValue : any;
        dayCount = 1;

        // observables
        allDay = ko.observable(false);
        startDateText = ko.observable('');
        startTimeText = ko.observable('');
        endDateText = ko.observable('');
        endTimeText = ko.observable('');
        repeat = new repeatInfoEditor();

        firstRowColumns = ko.observable(3);
        showSecondRow = ko.observable(false);
        conjunction = ko.observable('');
        isCustom = ko.observable(false);
        startTimeList = ko.observableArray([]);
        endTimeList = ko.observableArray([]);
        daylist : KnockoutObservableArray<Peanut.INameValuePair> = ko.observableArray([]);
        selectedDays : KnockoutObservable<Peanut.ILookupItem> = ko.observable();
        endTimeMode = ko.observable(1);
        timeError = ko.observable('');
        timeErrorField = ko.observable('');
        invalidTimeErrorMsg = '';
        invaildTimeOrderErrorMsg = '';
        conjunctionThrough = '';
        conjunctionUntil = '';


        startDateSubscription : any;
        endDateSubscription : any;
        startTimeSubscription : any;
        endTimeSubscription : any;
        dayListSubscription: any;
        allDaySubscription: any;
        subscriptionsActive = false;

        hoursName : string;
        hoursNamePlural : string;
        dateFormat: string;
        timeFormat: string;
        custom: string;
        hourFormat = 12;

        initialize(translator: Peanut.ITranslator) {
            let me = this;

            // using ISO now for HTML date lookups
//            me.dateFormat = translator.translate('calendar-date-format');
            me.dateFormat ='YYYY-MM-DD';

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
                days.push({Name: '1 ' + translator.translate('calendar-word-day'), Value: 1});
                for (let i = 2; i < 6; i++) {
                    days.push({Name: i + ' ' + daysName, Value: i});
                }
                days.push({Name: me.custom, Value: 6});
                me.daylist(days);
            }
        }

        setTimes(start: Moment = null, end: Moment = null, allDay: boolean = true, repeatPattern = '') {
            let me = this;
            me.suspendSubscriptions();
            me.allDay(allDay);
            me.isCustom(false);
            me.repeat.setPattern(repeatPattern,me.dateFormat);
            let startDt = Momentito.parse(start);
            me.startDate = startDt.date;
            me.startDateText(startDt.date.format(me.dateFormat));
            me.startTimeValue = allDay ? null : startDt.time;
            let startList = me.buildTimeList();
            me.startTimeList(me.buildTimeList());
            me.fillEndList();

            me.endDate = null;
            me.endTimeValue = null;
            me.timeError(''); me.timeErrorField('');
            let sameDay = true;
            if (end) {
                let endDt = Momentito.parse(end);
                // if (allDay) {
                //     end.subtract(1,'days');
                // }
                sameDay = me.startDate.isSame(endDt.date);
                if (allDay) {
                    if (sameDay && endDt.date.isAfter(startDt.date.add(5,'days'))) {
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
            let test = allDay ?  '' : timeHelper.timeValueToString(me.endTimeValue,me.hourFormat);
            me.endTimeText(allDay ?  '' : timeHelper.timeValueToString(me.endTimeValue,me.hourFormat));
            me.startTimeText(allDay ? '' : timeHelper.timeValueToString(startDt.time,me.hourFormat));

            me.setLayoutObservables();
            me.activateSubscriptions();
        }

        fillEndList = () => {
            let endList = this.buildTimeList(this.startTimeValue + 30,true);
            /*
                        if (this.isSameDay() && (!this.isCustom())) {
                            endList.push({Name: this.custom, Value: 'custom'});
                        }
            */
            this.endTimeList(endList);
        };

        buildTimeList = (start: number=0,showDuration: boolean = false) => {
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
                let label = timeHelper.timeValueToString(value,this.hourFormat);
                let duration = '';
                if (showDuration) {
                    let hrs = (value - start + 30) / 60;
                    if (hrs > 0) {
                        duration += ' (' + hrs + ' '+ (hrs > 1.0 ? this.hoursNamePlural : this.hoursName) + ')';
                    }
                }
                result.push(
                    {
                        Name: label + duration,
                        Value: value
                    });
            }
            return result;
        };

        suspendSubscriptions = () => {
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

        activateSubscriptions = () => {
            if (!this.subscriptionsActive) {
                this.startDateSubscription  = this.startDateText.subscribe(this.onStartDateChanged);
                this.endTimeSubscription = this.endTimeText.subscribe(this.onEndTimeChanged);
                this.startTimeSubscription = this.startTimeText.subscribe(this.onStartTimeChanged);
                this.endDateSubscription = this.endDateText.subscribe(this.onEndDateChanged);
                this.dayListSubscription = this.selectedDays.subscribe(this.onDaysChange);
                this.allDaySubscription = this.allDay.subscribe(this.onAllDayChecked);
                this.subscriptionsActive = true;
            }
        };

        onSetCustomClick = () => {
            this.suspendSubscriptions();
            this.setCustomState(true);
            this.setLayoutObservables();
            this.activateSubscriptions();
        };

        setCustomState = (state : boolean = true) => {
            if (this.isCustom() !== state) {
                if (state) {
                    if (this.endDate == null || (this.endDate && this.endDate.isBefore(this.startDate))) {
                        this.endDate = this.startDate.clone();
                        this.endDateText(this.startDateText())
                        // this.endDate.add(1,'days');
                        // this.endDateText(this.endDate.format(this.dateFormat));
                    }
                }
                else  {
                    this.endDate = null;
                }
                this.isCustom(state);
            }
        };

        timeValueToString = (value: any) => {
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

        setStartDate = (value: string) => {
            // let sameDay = this.endDate === null || this.startDate.isSame(this.endDate);
            let sameDay = this.endDate !== null && this.startDate.isSame(this.endDate);
            let newDate = Momentito.momentFromString(value);
            this.startDate = newDate;
            if ((this.endDate !== null) && (sameDay || this.endDate.isBefore(newDate))) {
                this.setEndToStart();
            }
            return true;
        };

        setEndToStart = () => {
            this.endDate = this.startDate.clone();
            this.endDateText(this.startDateText());
        };

        newEndDate = (m: Moment) => {
            this.endDate = m;
            this.endDateText(m.format(this.dateFormat));
        };

        setEndDate = (value: string) => {
            if (value) {
                let newDate = Momentito.momentFromString(value);
                if (newDate.isBefore(this.startDate)) {
                    this.timeError(this.invaildTimeOrderErrorMsg);
                    this.timeErrorField('enddate');
                    return false;
                }
                // this.showSecondRow(true);
                this.endDate = newDate;
            }
            else {
                this.endDate = null;
                //  this.showSecondRow(false);
            }
            return true;
        };

        setEndTime = (value:any) => { // called from dropdown binding
            if (this.startTimeValue > value) { // && !this.endDateText()) { what was i thinking?
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

        isSameDay = () => {
            if (this.endDate == null) {
                return true;
            }
            return this.startDate.isSame(this.endDate);
        };

        setStartTime = (value:any) => { // called from dropdown binding
            if  (this.isSameDay() && this.endTimeValue < this.startTimeValue) {
                this.endTimeValue = this.startTimeValue;
                this.endTimeText(timeHelper.timeValueToString(this.endTimeValue, this.hourFormat));
            }
            this.timeError('');
            this.timeErrorField('');
            this.startTimeValue = value;
            this.startTimeText(timeHelper.timeValueToString(value, this.hourFormat));
            return true;
        };

        onStartTimeSelected = (item: any) => { // called from dropdown binding
            if (item) {
                this.suspendSubscriptions();
                this.setStartTime(item.Value);
                this.fillEndList();
                this.setLayoutObservables();
                this.activateSubscriptions();
            }
        };

        onEndTimeSelected = (item:any) => {
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

        onAllDayChecked = (checked: any) => {
            this.suspendSubscriptions();
            if ( this.isSameDay() ) {
                this.selectedDays(<any>this.daylist()[0]);
                this.isCustom(false);

            }
            this.setLayoutObservables();
            this.activateSubscriptions();
        };

        onStartDateChanged = (value: any) => {
            this.suspendSubscriptions();
            this.setStartDate(value);
            this.activateSubscriptions();
        };

        onEndDateChanged = (value: any) => {
            this.suspendSubscriptions();
            this.setEndDate(value);
            this.activateSubscriptions();
        };

        onEndTimeChanged = (value: any) => {
            this.suspendSubscriptions();
            let time = timeHelper.timeStringToValue(value);
            // @ts-ignore
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

        onStartTimeChanged = (value: any) => {
            this.suspendSubscriptions();
            let time = timeHelper.timeStringToValue(value);
            // @ts-ignore
            if ( time < 0) {
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

        getLayout = () => {
            if (this.allDay()) {
                if (this.isSameDay() && !this.isCustom()) { // single day
                    return 5; // AFC | AFD
                }
                else { // multiple days
                    return (this.endDate.isSame(this.startDate) && this.isCustom()) ?
                        3 : 4;
                    //return 4;
                    // return (this.isCustom()) ?
                    //     4 : // AEC
                    //     3; // AED
                }
            }
            else { // part of day
                if (this.isSameDay()) {
                    if (!this.isCustom()) {
                        return 1; // BFD
                    }
                }
                return 2; // BEC | BED | BFC
            }
        };

        setDefaultTimes = () => {
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

        setLayoutObservables = () => {
            let layout = this.getLayout();
            let columns = 2;
            switch(layout) {
                case 1: // BFD
                    if (this.startTimeValue === null) {
                        this.setDefaultTimes();
                    }
                    columns =  3; // this.startTimeValue === null ? 2 : 3;
                    this.endTimeMode(1);
                    this.conjunction('');
                    break;
                case 2: // // BEC | BED | BFC
                    this.endTimeMode(1);
                    this.conjunction(this.conjunctionUntil);
                    break;
                case 3: // AED
                    this.endTimeMode(this.isCustom() ? 0 : 2);
                    this.conjunction(this.conjunctionThrough);
                    break;
                case 4: // AEC
                    this.endTimeMode(0);
                    this.conjunction(this.conjunctionThrough);
                    break;
                case 5 : // 5: AFC | AFD
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

        onDaysChange = (item: any) => {
            this.suspendSubscriptions();
            this.setCustomState(item.Value > 5);
            if (item.Value> 1) {
                if (this.endDate === null) {
                    this.setEndToStart();
                }
                this.endDate.add(item.Value - 1,'days');
                let text = moment(this.endDate).format(this.dateFormat);
                this.endDateText(text);
            }
            this.setLayoutObservables();
            this.activateSubscriptions();
        };


    }

    export class calenderResceduleObservable {
        eventTitle = ko.observable('');
        eventStartDate = ko.observable('');
        eventEndDate = ko.observable('');
        isRepeating = ko.observable(false);
        repeatMode = ko.observable('');
        showEndDate = ko.observable(true);
        revertFunction : any = null;
        owner : CalendarViewModel;


        event : ICalendarEventObject = null;

        constructor(owner: CalendarViewModel) {
            this.owner = owner;
        }

        show(event: ICalendarEventObject,change: string,revertFunction) {
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
                    this.eventEndDate(event.end.format("h:mm:ss a"))
                }
                else {
                    this.eventEndDate(event.end.format(
                        event.allDay ? "dddd, MMMM Do YYYY" : "dddd, MMMM Do YYYY, h:mm:ss a")
                    );
                }
            }

            this.isRepeating(!!event.repeatPattern);
            this.repeatMode('all');
            this.owner.displayModal('#reschedule-event-modal');
        }

        close() {
            // jQuery('#reschedule-event-modal').modal('hide');
            Peanut.ui.helper.hideModal('#reschedule-event-modal');
            return this.event;
        }

        revert = () => {
            if (this.revertFunction) {
                this.revertFunction();
            }
            this.revertFunction = null;
            // jQuery('#reschedule-event-modal').modal('hide');
            Peanut.ui.helper.hideModal('#reschedule-event-modal');
        }
    }

    export class calendarSearchObservable {
        // owner: CalendarViewModel;

        title = ko.observable('');
        results = ko.observableArray<ICalendarSearchResult>([]);

        /*
                constructor(owner: CalendarViewModel) {
                    this.owner = owner;
                }
        */

    }

    export class calendarEventObservable {
        owner : CalendarViewModel;
        id = ko.observable<any>(null);
        recurId : any;

        lo: any;

        repeatPattern: string = '';
        // debug
        // testPattern = ko.observable('no repeat pattern');

        times = new eventTimeEditor();

        title = ko.observable('');
        start: Moment;
        end: Moment;
        allDay = false;

        eventType = ko.observable('');
        addCaption = ko.observable('');
        repeatMode = ko.observable('all');
        repeating = ko.observable(false);
        location = ko.observable('');
        url = ko.observable('');
        eventTime = ko.observable('');
        repeatText = ko.observable('');
        committeesText = ko.observable('');
        resourcesText = ko.observable('');
        notesLines = ko.observableArray([]);
        description = ko.observable('');
        titleError = ko.observable('');
        eventTypes = ko.observableArray([]);
        selectedEventType = ko.observable<Peanut.ILookupItem>();
        notificationDays = ko.observable(-1);
        sendNotifications = ko.observable(false);
        isVirtual = ko.observable(false);
        recurInstance = null;

        availableResources = ko.observableArray([]);
        selectedResources = ko.observableArray([]);
        selectedResource = ko.observable();
        resourceSubscription : any = null;

        availableCommittees = ko.observableArray([]);
        selectedCommittees = ko.observableArray([]);
        selectedCommittee = ko.observable();
        committeeSubscription : any = null;

        availableGroups = ko.observableArray([]);
        selectedGroups = ko.observableArray([]);
        selectedGroup = ko.observable();
        groupSubscription : any = null;

        // edit
        eventTypeId: any;
        notes = ko.observable('');
        createdBy = ko.observable('');
        createdOn = ko.observable('');
        changedBy = ko.observable('');
        changedOn = ko.observable('');

        translator: Peanut.ITranslator;
        vocabulary: ICalendarTranslations = null;

        constructor(owner: CalendarViewModel) {
            this.owner = owner;
        }

        formatDateRange(startMoment: Moment, endMoment: Moment, allDay) {
            // moment formatting: http://momentjs.com/docs/#/displaying/
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

        formatRepeatDates(start: string, end: string) {
            if (end == null) {
                return this.translator.translate('conjunction-starting') + start;
            }
            let endText = isNaN(Number(end)) ?
                this.translator.translate('conjunction-until') + ' ' + end :
                this.translator.translate('calendar-word-after') + ' ' + end + ' ' + this.translator.translate('calendar-word-occurances');
            return this.translator.translate('conjunction-from') + ' ' +  start + ' ' + endText;
        }

        translateDows(pattern: string) {
            let count = pattern.length;
            let dows = [];
            for (let i = 0; i < count; i++) {
                let n = Number(pattern.charAt(i));
                dows.push(this.vocabulary.daysOfWeek[n - 1]);
            }
            return dows.join(', ');
        }

        ordinalDow(n, d) {
            return this.vocabulary.ordinals[Number(n)-1] + ' ' +
                this.vocabulary.daysOfWeek[Number(d)-1];
        }

        asOrdinal(n: string) {
            let i = (Number(n) >= this.vocabulary.ordinalSuffix.length) ? n.toString().slice(-1) : Number(n);
            return n + this.vocabulary.ordinalSuffix[i];
        }

        getMonthName = (n: string) => {
            return this.vocabulary.monthNames[Number(n) - 1];
        };

        /*
        getRepeatStartDate(repeatPattern: string) {
            let parts = repeatPattern.split(';');
            if (parts.length > 1) {
                let dates = parts[1].split(',');
                return moment(dates[0]).format("MMM D, YYYY");
            }
            return null;
        }
        */

        getOrdinalsText = (values: string, dow: any = null) => {
            let result = '';
            let len = values.length;
            let added = 0;
            let conjunction = ' and '; // todo: translate
            for(let i=1;i<5;i++) {
                if (values.indexOf((i).toString()) !== -1) {
                    added++;
                    if (result.length > 0) {
                        result += ((added == len) ? conjunction : ', ');
                    }

                    // return this.vocabulary.ordinals[Number(n)-1] + ' ' +
                    //     this.vocabulary.daysOfWeek[Number(d)-1];

                    result += this.vocabulary.ordinals[i-1];
                }
            }
            if (result.length > 0 &&  dow !== null) {
                result += ' '+this.vocabulary.daysOfWeek[Number(dow)-1];
            }
            return result;
        };



        getRepeatText = (repeatPattern: string) => {
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

            let wordOn =       this.translator.translate('conjunction-on');
            let wordThe =      this.translator.translate('conjunction-the');

            switch (repeatPattern.substring(0, 2)) {
                case 'dd' :
                    return interval > 1 ?
                        wordEvery + ' ' + interval + ' ' +  this.translator.translate('calendar-word-day-plural') + ' ' + this.formatRepeatDates(start, end) :
                        wordEvery + ' ' + this.translator.translate('calendar-word-day') + ' ' + this.formatRepeatDates(start, end);

                case 'dw' :
                    return interval > 1 ?
                        wordEvery + ' ' + interval + ' ' + this.translator.translate('calendar-word-weekday-plural') + ' ' + this.formatRepeatDates(start, end) :
                        wordEvery + ' ' + this.translator.translate('calendar-word-weekday') + ' ' + this.formatRepeatDates(start, end);

                case 'wk' :
                    return interval > 1 ?
                        wordEvery + ' ' + interval + ' ' + this.translator.translate('calendar-word-week-plural') + wordOn + this.translateDows(patternParts[1]) + this.formatRepeatDates(start, end) :
                        wordEvery + ' ' + this.translator.translate('calendar-word-week') + wordOn + this.translateDows(patternParts[1]) + this.formatRepeatDates(start, end);

                case 'md' :
                    return interval > 1 ?
                        wordEvery + ' ' + interval + ' ' + this.translator.translate('calendar-word-month-plural') + wordOn + this.translator.translate('conjunction-the') + this.asOrdinal(patternParts[1]) + ' ' + this.formatRepeatDates(start, end) :
                        wordEvery + ' ' + this.translator.translate('calendar-word-month') + wordOn + wordThe + this.asOrdinal(patternParts[1]) + ' ' + this.formatRepeatDates(start, end);

                case 'mo' :
                    let result = '';

                    if (interval <= 1) {
                        return wordEvery + ' ' + this.translator.translate('calendar-word-month') + wordOn + wordThe +
                            // this.ordinalDow(patternParts[1], patternParts[2])
                            this.getOrdinalsText(patternParts[1], patternParts[2])
                            + ' ' + this.formatRepeatDates(start, end);
                    }

                    return interval > 1 ?
                        wordEvery + ' ' + interval + ' ' + this.translator.translate('calendar-word-month-plural') + wordOn + wordThe +
                        this.ordinalDow(patternParts[1], patternParts[2])
                        + ' '  + this.formatRepeatDates(start, end) :
                        wordEvery + ' ' + this.translator.translate('calendar-word-month') + wordOn + wordThe +
                        // this.ordinalDow(patternParts[1], patternParts[2])
                        this.getOrdinalsText(patternParts[1], patternParts[2])
                        + this.formatRepeatDates(start, end);

                case 'yd' :
                    return interval > 1 ?
                        wordEvery + ' ' + interval + ' ' + this.translator.translate('calendar-word-year-plural') + wordOn +
                        this.getMonthName(patternParts[1]) + ' ' + patternParts[2] :
                        wordEvery + ' ' + this.translator.translate('calendar-word-year') + wordOn +
                        this.getMonthName(patternParts[1]) + ' ' + patternParts[2];

                case 'yo' :
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

        setDescription = (text: string) => {
            this.description(text);
            let editor = tinymce.get('event-description');
            if (editor) {
                editor.setContent(text);
            }
        };

        clear = (committees: KnockoutObservableArray<Peanut.ILookupItem>,
                 resources: KnockoutObservableArray<Peanut.ILookupItem>,
                 eventTypes: KnockoutObservableArray<Peanut.ILookupItem>,
                 groups: KnockoutObservableArray<Peanut.ILookupItem>
        ) => {

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
            let type = types.find((type: Peanut.ILookupItem) => {
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
            // debug
            // me.testPattern('no repeat pattern');

            me.repeatText('');
            me.committeesText('');
            me.resourcesText('');
            me.notes('');
            me.setDescription('');
            me.sendNotifications(false);
            me.notificationDays(-1);

            me.start = moment();
            me.end = moment();
            me.end.add(1,'hour');
            me.allDay = false;

            me.times.setTimes(me.start,me.end,me.allDay,me.repeatPattern);
            me.activateSubscriptions();

        };


        /*
                setEventType = (code? : any) => {
                    let me = this;
                    if (!code) {
                        code = 'public';
                    }
                    let type = me.lo.find(me.eventTypes(),(item: Peanut.ILookupItem) => {
                        return item.code == code;
                    });
                    me.selectedEventType(type);
                };

        */
        assignFromCalendarObject = (event: any) => {
            let me = this;
            // let event = calevent.event;
            me.id(event.id);
            me.start = moment(event.start);
            me.end = moment(event.end);
            let range = me.formatDateRange(me.start,me.end, event.allDay);
            me.eventTime(range);

            me.repeatPattern = event.repeatPattern;

            // debug
            // me.testPattern('no repeat pattern');

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

        assignDetails = (event: ICalendarEventDetails) => {
            let me = this;
            me.recurId = event.recurId;
            me.eventTypeId = event.eventTypeId;
            me.notes(event.notes ? event.notes : '');
            // me.notificationDays(event.notification < 1 ? 1 : event.notification);
            // me.sendNotifications(event.notification > 0);
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
            // me.assignText(event.committees, me.committeesText);

            me.createdBy(event.createdBy);
            me.createdOn(event.createdOn);
            me.changedBy(event.changedBy);
            me.changedOn(event.changedOn);
            me.repeatMode('all');
        };

        assignText(items: Peanut.ILookupItem[], observable: KnockoutObservable<string>) {
            let me = this;
            let text = [];
            // me.lo.forEach(items, (item: Peanut.ILookupItem) => {
            items.forEach((item: Peanut.ILookupItem) => {
                text.push(item.name);
            });
            observable(text.join(', '));
        }

        filterAvailable(items: Peanut.ILookupItem[], selected: Peanut.ILookupItem[], available: KnockoutObservableArray<Peanut.ILookupItem>) {
            let me = this;
            // let result = me.lo.filter(items, (item: Peanut.ILookupItem) => {
            let result = items.filter((item: Peanut.ILookupItem) => {
                // let existing = me.lo.find(selected, (selectItem: Peanut.ILookupItem) => {
                let existing = selected.find( (selectItem: Peanut.ILookupItem) => {
                    return selectItem.id == item.id;
                });
                return (!existing);
            });
            available(result);
        }

        activateSubscriptions = () => {
            let me = this;
            me.selectedResource(null);
            me.selectedCommittee(null);
            me.selectedResource(null);
            me.resourceSubscription = me.selectedResource.subscribe(me.addResource);
            me.committeeSubscription = me.selectedCommittee.subscribe(me.addCommittee);
            me.groupSubscription = me.selectedGroup.subscribe(me.addGroup);
            me.times.activateSubscriptions();
        };

        suspendSubscriptions = () => {
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

        edit = (committees: KnockoutObservableArray<Peanut.ILookupItem>,
                resources: KnockoutObservableArray<Peanut.ILookupItem>,
                eventTypes: KnockoutObservableArray<Peanut.ILookupItem>,
                groups: KnockoutObservableArray<Peanut.ILookupItem>
        ) => {
            let me = this;
            me.suspendSubscriptions();
            me.filterAvailable(committees(), me.selectedCommittees(), me.availableCommittees);
            me.filterAvailable(resources(), me.selectedResources(), me.availableResources);
            me.filterAvailable(groups(), me.selectedGroups(), me.availableGroups);
            // let type = me.lo.find(eventTypes(), (type: Peanut.ILookupItem) => {
            let types = eventTypes();
            // let type = me.lo.find(eventTypes(), (type: Peanut.ILookupItem) => {
            let type = types.find((type: Peanut.ILookupItem) => {
                return type.id === me.eventTypeId;
            });

            me.selectedCommittee(null);
            me.selectedResource(null);
            me.selectedEventType(type);
            me.selectedGroup(null);

            me.times.setTimes(me.start,me.end,me.allDay,me.repeatPattern);
            me.setDescription(me.description());
            me.activateSubscriptions();
        };

        moveSelectedItem = (item: Peanut.ILookupItem,
                            source: KnockoutObservableArray<Peanut.ILookupItem>,
                            target: KnockoutObservableArray<Peanut.ILookupItem>) => {

            let me = this;

            me.suspendSubscriptions();
            let src = source();
            // let remaining = me.lo.filter(source(),
            let remaining = src.filter(
                (sourceItem: Peanut.ILookupItem) => {
                    return sourceItem.id != item.id;
                });

            remaining = Peanut.Helper.SortBy(remaining,'name');
                // me.lo.sortBy(remaining,'name');
            target.push(item);
            let targetItems = target();
            targetItems = Peanut.Helper.SortBy(targetItems,'name');
                // me.lo.sortBy(target(),'name');
            source(remaining);
            target(targetItems);
            me.activateSubscriptions();
        };
        addCommittee = (item: Peanut.ILookupItem) => {
            this.moveSelectedItem(item,this.availableCommittees,this.selectedCommittees);
        };

        removeCommittee = (item: Peanut.ILookupItem) => {
            this.moveSelectedItem(item,this.selectedCommittees,this.availableCommittees);

        };

        addResource = (item: Peanut.ILookupItem) => {
            this.moveSelectedItem(item,this.availableResources,this.selectedResources);
        };

        removeResource = (item: Peanut.ILookupItem) => {
            this.moveSelectedItem(item,this.selectedResources,this.availableResources);

        };

        addGroup = (item: Peanut.ILookupItem) => {
            this.moveSelectedItem(item,this.availableGroups,this.selectedGroups);
        };

        removeGroup = (item: Peanut.ILookupItem) => {
            this.moveSelectedItem(item,this.selectedGroups,this.availableGroups);
        };


        parseRepeatPattern(repeatPattern : string) {
            let result = {
                pattern: null,
                start: null,
                endValue: null,
            };
            // let repeatPattern = this.repeatPattern;
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
        };

        validate() : boolean | ICalendarDto {
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
            // me.description(jQuery('#event-description').val());
            // jQuery('#event-description').val()
            let val = Peanut.DomQuery.GetInputElementValue('event-description');
            me.description(val);

            let startDate =  me.times.startDate; // .format('YYYY-MM-DD');
            let endDate =  me.times.endDate;
            let repeatPattern = this.parseRepeatPattern(me.repeatPattern);

            if (repeatPattern.pattern && repeatPattern.start && me.repeatMode() !== 'instance') {
                // if recurrence start date doesn't match start, adjust
                let startDay = Momentito.momentFromString(repeatPattern.start); //  moment(repeatPattern.start);
                let daysDiff = moment.duration(startDay.diff(me.times.startDate)).asDays();
                if (daysDiff != 0) {
                    startDate = startDay;
                    if (endDate) {
                        endDate.add(daysDiff,'days');
                    }
                }
            }
            let start = startDate.format('YYYY-MM-DD');
            let end = endDate == null? start : endDate.format('YYYY-MM-DD');

            if (!me.times.allDay()) {
                start +=  ' ' + timeHelper.timeValueToString(me.times.startTimeValue,24);
                end   +=  ' ' + timeHelper.timeValueToString(me.times.endTimeValue,24);
            }

            if (start == end) {
                end = null;
            }

            let dto = <ICalendarDto> {
                id : me.id(),
                title : title,
                start : start,
                end : end,
                allDay : me.times.allDay() ? 1 : 0,
                location: me.location(),
                url : null, // not inplemented yet
                eventTypeId : me.selectedEventType().id,
                recurPattern : repeatPattern.pattern,
                // recurEnd : me.times.repeat.endDateBasis() == 'none' ? null : repeatPattern.endValue,
                recurEnd : repeatPattern.endValue,
                recurId: me.recurId,
                notes: me.notes(),
                description: me.description(),
                active: 1
            };

            return dto;
        }

        onShowRepeatInfo = () => {
            this.times.repeat.setPattern(this.repeatPattern,this.times.dateFormat);
            if (!this.repeatPattern) {
                this.times.repeat.startOn(this.times.startDateText())
            }
            this.owner.displayModal('#repeat-info-modal');
        };

        onSaveRepeatInfo = () => {
            this.repeatPattern = this.times.repeat.getRepeatPattern();
            // debug
            // this.testPattern(this.repeatPattern);

            this.repeating(true);
            let text = this.getRepeatText(this.repeatPattern);
            this.repeatText(text);
            // jQuery('#repeat-info-modal').modal('hide');
            Peanut.ui.helper.hideModal('#repeat-info-modal');
        };

        onRemoveRepeatInfo = () => {
            this.repeatMode('remove');
            this.repeating(false);
            this.repeatPattern = '';
            this.repeatText('');
            // jQuery('#repeat-info-modal').modal('hide');
            Peanut.ui.helper.hideModal('#repeat-info-modal');
        };

        /*
        onCancelRemoveRepeat = () => {
            this.repeatMode('');
        };

        removeRepeat =() => {
            jQuery('#confirm-repeat-delete-modal').modal('hide');
            this.repeating(false);
            this.repeatPattern = '';
            this.repeatMode('remove');
        };
    */
    }

    export class calendarPage {
        month: number;
        year: number;
        startDate: Moment;
        endDate: Moment;

        constructor(year: any, month: any, startDate: string, endDate: string)  {
            this.month = month;
            this.year = year;
            this.startDate = moment(startDate);// + 'T00:00:00');
            this.endDate =  moment(endDate);//  + 'T00:00:00');
            this.startDate.startOf('day');
            this.endDate.startOf('day');
            // let test = this.startDate.format() + ' to ' + this.endDate.format();
            // console.log(test);

        }

        static compareDate(compareDate: Moment, calendarDate: Moment) {
            let f = compareDate.format('YYYY-MM-DD[T]HH:mm:00');
            let yourDate = moment(f); // ignore locale;
            let t = yourDate.format();
            if (yourDate.isAfter(calendarDate)) {
                return 1;
            }
            if (yourDate.isBefore(calendarDate)) {
                return -1;
            }
            return 0;
        }

        compareStart = (m : Moment) => {
            return calendarPage.compareDate(m, this.startDate)
        };

        compareEnd = (m : Moment) => {
            return calendarPage.compareDate(m, this.endDate)
        };

        getNextMonth = () => {
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

        getPrevMonth = () => {
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
    }

    export class CalendarViewModel extends Peanut.ViewModelBase {
        // observables

        calendarIsPaging = ko.observable(false);
        tab = ko.observable('list');
        pageSelect = ko.observable('calendar')
        tabsVisible = ko.observable(true);
        canShowMenu = false;
        menuVisible = ko.observable(false);
        format = ko.observable('list');
        calendarRescheduleTitle = ko.observable('Reschedule event'); // todo:: translate
        requestMenuTitle = ko.observable('Submit Calendar Request'); // todo: translate

        rescheduleForm = new calenderResceduleObservable(this);
        eventSource : IFCEventSource;

        // lo: _.LoDashStatic; // any; // alias for lodash _(), to prevent conflicts with underscore.js
        userPermission = ko.observable('view');
        canSubmitRequest = ko.observable(false);
        userPersonId = ko.observable<any>(0);
        updateMode = ko.observable('update');
        eventForm = new calendarEventObservable(this);
        searchForm = new calendarSearchObservable();

        eventTypes = ko.observableArray<Peanut.ILookupItem>();
        committees = ko.observableArray<Peanut.ILookupItem>();
        resources = ko.observableArray<Peanut.ILookupItem>();
        groups = ko.observableArray<Peanut.ILookupItem>();
        filtered = ko.observable('all');

        filterCode = ko.observable('');
        filterMessage = ko.observable('');
        filterMenu = ko.observable('');
        filterMenuTitle = ko.observable('');
        pages: calendarPage[] = [];
        currentPage = -1;


        private calendar : any;

        private pagingEnabled = false;

        listViewTitle = ko.observable('Month, Year');
        loadingCalendar = ko.observable(false);

        fullMenu = ko.observable(true); // todo: used?

        private calenderViewOptions =  {
            left:   'prev,next,today',
            center: 'title',
            right:  'dayGridMonth,timeGridWeek,timeGridDay'
        };

        private listViewOptions = {
            left: 'prev,next',
            center: 'title',
            right: ''
        }


        init(successFunction?: () => void) {
            let me = this;
            me.changeTab('calendar');
            Peanut.logger.write('calendar Init');
            // me.eventInfoModal = jQuery('#event-info-modal');

            me.application.loadStyleSheets([
                // '@pnut:pnut-modal.css',
                '@pkg:qnut-calendar'
                // ,'@lib:fullcalendar-css'
                // ,'@lib:fullcalendar-print-css media=print'
            ]);


            me.application.registerComponents(
                ['@pnut/modal-confirm','@pkg/qnut-calendar/calendar-request'], () => {
                    me.application.loadResources([
                        '@pnut/ViewModelHelpers'
                        // ,  '@lib:moment-js'
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

                            me.getNewCalendar(request, (response: ICalendarInitResponse) => {
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

                                // me.menuVisible(response.userPermission !== 'view' && !prefiltered);
                                me.showCalendar(response.events,prefiltered);
                                me.bindDefaultSection();
                                successFunction();
                            });
                        });
                    });
                });
        }

        initEventFormVocabulary = (response : ICalendarInitResponse) => {
            let me = this;
            me.addTranslations(response.translations);
            me.eventForm.times.initialize(me);
            me.eventForm.times.repeat.initialize(response.vocabulary);
            me.eventForm.vocabulary = response.vocabulary;
            me.eventForm.translator = me;
        };

        initEditor = (selector: string) => {
            tinymce.init({
                selector: selector,
                toolbar: "undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link | image",
                plugins: "image imagetools link",
                default_link_target: "_blank",
                branding: false,
                height: 75
            });
        };

        assignEventList(response : IGetCalendarResponse) {
            let me = this;
            let eventList : IEventListItem[] = [];
            let responseEvents = response.events;
            response.events = [];
            let eventsLength = responseEvents.length;

            for(let i=0;i<eventsLength;i++) {
               let value: ICalendarEvent = responseEvents[i];
                value.allDay = value.allDay == '1';
                response.events.push(value);
            }
            responseEvents = null;
        }

        getEvents = (request: any, successFunction? : (response: IGetCalendarResponse) => void) => {
            let me = this;
            me.services.executeService('peanut.qnut-calendar::GetEvents', request,
                (serviceResponse: Peanut.IServiceResponse) => {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let response = <IGetCalendarResponse>serviceResponse.Value;
                        if ((<ICalendarInitResponse>response).translations) {
                            let initResponse = <ICalendarInitResponse>response;
                            me.initEventFormVocabulary(initResponse);
                        }
                        me.assignEventList(response);
                        if (successFunction) {
                            successFunction(response);
                        }
                    }
                    if (this.format() !== 'list') {
                        // jQuery(window).scrollTop(0);
                        window.scrollTo(0,0);
                    }
                })
                .fail(() => {
                    let trace = me.services.getErrorInformation();
                })
                .always(() => {
                    me.calendarIsPaging(false);
                });
        };


        getNewCalendar = (request: any, successFunction? : (response: IGetCalendarResponse) => void) => {
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
            me.getEvents(request, (response: IGetCalendarResponse) => {
                me.pages = [new calendarPage(request.year,request.month,response.startDate,response.endDate)];
                me.currentPage = 0;
                me.pagingEnabled = true;
                if (successFunction) {
                    successFunction(response);
                }
            })
        };

        testClick = () => {
            alert('test click')
        }
        showCalendar(events,preFiltered = true) {
            let me = this;
            let size = me.deviceSize();
            events = Peanut.Helper.SortBy(events,'start');
            // me.events = Peanut.Helper.SortBy(events,'start');
                // me.lo.sortBy(events, ['start']);
            me.eventSource = {
                id: 'qnut',
                events: events
            }
            if (!preFiltered) {
                me.filtered('all');
            }
            let format = me.format();
            //me.calendar = jQuery('#calendar');
            // @ts-ignore

            let initialView = format === 'list' ? 'listMonth' : 'dayGridMonth';
            // let initialView = 'listDay'

            const calendarEl = document.getElementById('calendar')

            // @ts-ignore
            me.calendar = new FullCalendar.Calendar(calendarEl, {
                // plugins: ['list'],
                headerToolbar: me.calenderViewOptions,
                initialView: initialView,
                navLinks: true, // can click day/week names to navigate views
                editable: me.userPermission() == 'edit',
                dayMaxEventRows: true, // or a number
                dayMaxEvents: true, // or a number
                eventClick: me.onEventClick,
                // eventClick: me.testClick();
                eventResize: me.onEventResize,
                eventDrop: me.onEventDrop,
                datesSet: me.onViewRender,
                fixedWeekCount : false,
                events: <any>me.eventSource
            })
            me.renderCalendar();
            this.menuVisible(this.canShowMenu);
        }

        displayModal = (id: string) => {
            this.hideServiceMessages();
            // jQuery(id).modal('show');
            this.showModal(id);
        };


        changeTab = (tabName: string) => {
            let me = this;
            let hideHeader = false;
            switch (tabName) {
                case 'edit' :
                    hideHeader = true;
                    this.pageSelect(tabName)
                    break;
                case 'view' :
                    hideHeader = true;
                    this.pageSelect(tabName);
                    break;
                default:
                    this.pageSelect('calendar')
                    this.tab(tabName);
                    break;
            }
            this.menuVisible(tabName=='calendar' && this.canShowMenu);
            this.tabsVisible(!hideHeader);

            // this.tab(tabName);

            if (this.format() !== 'list') {
                // jQuery(window).scrollTop(0);
                window.scrollTo(0, 0);
            }
        };


        onEventResize = ( event: ICalendarEventObject, delta, revertFunc, jsEvent, ui, view ) => {
            this.rescheduleForm.show(event,'resize',revertFunc);
        };


        onEventDrop = ( event: ICalendarEventObject, delta, revertFunc, jsEvent, ui, view ) => {
            this.rescheduleForm.show(event,'drop',revertFunc);
        };

        rescheduleEvent = () => {
            let event = this.rescheduleForm.close();
            let currentPage = this.pages[this.currentPage];
            let request = <IRescheduleEventRequest>{
                id: event.id,
                start: event.start.format('YYYY-MM-DD HH:mm') + ':00',
                end: event.end ? event.end.format('YYYY-MM-DD HH:mm') + ':00' : null,
                year: currentPage.year,
                month: currentPage.month,
                filter: this.filtered(),
                code: this.filterCode(),
            };

            this.services.executeService('peanut.qnut-calendar::RescheduleEvent', request,
                (serviceResponse: Peanut.IServiceResponse) => {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let response = <IGetCalendarResponse>serviceResponse.Value;
                        this.refreshEvents(response);
                    }
                    if (this.format() !== 'list') {
                        // jQuery(window).scrollTop(0);
                        window.scrollTo(0,0);
                    }
                })
                .fail(() => {
                    let trace = this.services.getErrorInformation();
                });
        };


        showButton = (id: string, visible: boolean = true) => {
            let element = <HTMLElement> document.querySelector(id);
            if (element) {
                element.style.display = visible? 'block' : 'none';
            }
        }

        setCalendarHeader(format: string) {
            if (this.format() !== 'list') {
                if (format === 'list') {
                    // jQuery('.fc-today-button').hide();
                    this.showButton('.fc-today-button',false)
                    // jQuery('.fc-right').hide();
                    this.showButton('.fc-right',false);
                }
                else {
                    // jQuery('.fc-today-button').show();
                    this.showButton('.fc-today-button')
                    // jQuery('.fc-right').show();
                    this.showButton('.fc-right');
                }
            }
        }

        /**
         * This is to correct an issue where calendar.render() produces an incorrectly sized or mangled calendar diaplay
         * I think this occurs when render is called from a closure, e.g. success event in a service call.
         *
         * Anyway, this seems to fix the issue.
         */
        renderCalendar() {
            let me = this;
            let t = window.setInterval(() => {
                me.calendar.render()
                clearInterval(t);
            }, 100);

        }
        newCalendar = (newEvents: ICalendarEventObject[]) => {
            let me = this;
            me.switchEventSource(newEvents);
            me.renderCalendar();
        }

        switchEventSource = (newEvents: ICalendarEventObject[]) => {
            let me = this;
            me.eventSource = {
                id: 'qnut',
                events: newEvents
            }
            me.calendar.removeAllEventSources();  // Remove existing event sources
            me.calendar.addEventSource(me.eventSource); // Add the new event source
            me.calendar.refetchEvents();          // Fetch events again to re-render properly
            /*
                        let source = me.calendar.getEventSourceById('qnut');
                        source.remove();
                        me.eventSource = {
                            id: 'qnut',
                            events: newSource
                        }
                        me.calendar.addEventSource(me.eventSource);
             */
        };

        getCurrentMonth() {
            let me = this;
            if (me.currentPage < 0) {
                return {
                    year: new Date().getFullYear(),
                    month:  new Date().getMonth() + 1,
                };
            }
            else {
                let page = me.pages[me.currentPage];
                return {
                    year: page.year,
                    month: page.month
                }
            }

        }

        clearFilter = () => {
            let me = this;
            me.fullMenu(true);
            let currentFilter= me.filtered();
            if (currentFilter !== 'type' && currentFilter != 'all') {
                me.getNewCalendar(me.getCurrentMonth(),(response: IGetCalendarResponse) => {
                    let events = Peanut.Helper.SortBy(response.events,'start');
                    me.setFilter(events);
                });
            }
            else {
                me.setFilter(me.eventSource.events);
            }
        };

        initializeFilter(response: ICalendarInitResponse) {
            let me = this;
            if (response.filteredBy) {
                let filterSpec = response.filteredBy.split(':');
                if (filterSpec.length !== 2) {
                    return false;
                }
                let filter = filterSpec[0];
                let code = filterSpec[1];
                let items = [];
                switch(filter) {
                    case 'committee' :
                        items = response.committees;
                        break;
                    case 'group' :
                        items = response.groups;
                        break;
                    default:
                        items = response.resources;
                        break;
                }
                // let items = filter == 'committee' ? response.committees : response.resources;
                for (let i = 0; i<items.length;i++) {
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

        setFilter = (events: any[], filter = 'all', message = '', code = '') => {
            let me = this;
            me.filtered(filter);
            me.filterCode(code);
            me.switchEventSource(events);
            me.filterMessage(message);
        };

        setTypeFilter(item: any) {
            let me = this;
            let events = me.eventSource.events.filter((event: ICalendarEvent) => {
                return event.eventType == item.code;
            });
            me.setFilter(events,'type',item.description,item.code);
        }

        filterEventType = (item: any) => {
            // jQuery("#filter-menu-modal").modal('hide');
            let me = this;
            me.hideModal("#filter-menu-modal");
            let filter = 'type';

            let currentFilter = me.filtered();
            let currentCode = me.filterCode();
            if (!(currentFilter == filter && currentCode == item.code)) {
                if (currentFilter == filter || currentFilter == 'all' ) {
                    me.setTypeFilter(item);
                }
                else {
                    // refetch events before filter
                    me.getNewCalendar(null,(response: IGetCalendarResponse) => {
                        me.eventSource = {
                            id: 'qnut',
                            events: response.events
                        }

                        me.setTypeFilter(item);
                    });
                }
            }
        };

        getFilteredEvents = (filter: string, item: any) => {
            let me = this;
            //jQuery("#filter-menu-modal").modal('hide');
            me.hideModal("#filter-menu-modal");
            let currentFiltered = me.filtered();
            let currentCode = me.filterCode();
            if (currentFiltered != filter || currentCode != item.code) {
                me.getNewCalendar(
                    {
                        filter: filter,
                        code: item.code
                    },(response: IGetCalendarResponse) => {
                        me.eventSource = {
                            id: 'qnut',
                            events: response.events
                        }
                        me.setFilter(response.events,filter,item.description,item.code);
                    });
            }
        };

        filterCommittee = (item: any) => {
            this.getFilteredEvents('committee',item);
        };

        filterResource = (item: any) => {
            this.getFilteredEvents('resource',item);
        };

        filterGroups = (item: any) => {
            this.getFilteredEvents('group',item);
        };

        onViewRender = (view,element) => {
            let me = this;
            if (me.currentPage >= 0) {
                me.pageCalendar(view.start,view.end);
            }
        };

        pageCalendar = (start: moment.Moment,end:moment.Moment) => {

            let me = this;
            if (me.pagingEnabled) {
/*
                let startDate = start.format('Y-M-D');
                let endDate = end.format('Y-M-D');
*/
                let startDate = start.toISOString();
                let endDate = end.toISOString();
                // console.log('PAGING: start=' + startDate + '; end='+endDate);

                let page = me.pages[me.currentPage];

                let movePage = 0;

                // if (page.compareStart(startDate) == -1) {
                if (page.compareStart(moment(start)) == -1) {
                    Peanut.logger.write('Page prev');
                    movePage = -1;
                }
                // else if (page.compareEnd(endDate) > 0) {
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

        changeMonth = (movePage) => {
            let newPage = this.currentPage + movePage;
            if (newPage < 0 || newPage >= this.pages.length) {
                this.getNextPage(movePage);
            }
            else {
                this.currentPage = newPage;
                return true; // found page in memory
            }
            return false; // page not in memory
        };

        getNextPage = (movePage: number) => {
            let me = this;
            let page =me.pages[me.currentPage];
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

                Peanut.logger.write('Get prev page'+ request.pageDirection + ' ' + request.year + '-' + request.month);
            }

            if (me.filtered() != 'all' && me.filtered() != 'type') {
                request.filter = me.filtered();
                request.code = me.filterCode();
            }
            me.calendarIsPaging(true)
            me.getEvents(request, (response: IGetCalendarResponse) => {
                let events = me.eventSource.events.concat(response.events);
                // me.events = me.lo.sortBy(events, ['start']);
                events = Peanut.Helper.SortBy(events,'start');
                if (me.filtered() === 'type') {
                    let code = me.filterCode();
                    // events = events.filter((event: ICalendarEvent) => {
                    me.eventSource.events = events.filter((event: ICalendarEvent) => {
                        // filter global source? or what?
                        return event.eventType == code;
                    });
                }

                let newPage = new calendarPage(request.year,request.month,response.startDate,response.endDate);
                if (movePage > 0) {
                    me.currentPage = me.pages.length;
                    me.pages.push(newPage);
                }
                else {
                    me.currentPage = 0;
                    me.pages.unshift(newPage);
                }
                me.newCalendar(events)
                me.tab(tab);
                // me.switchEventSource(events);
            })
        };

        eventInfoModal = (mode: string) => {
            if (mode === 'show') {
                this.showModal( 'event-info-modal');
            }
            else {
                this.hideModal( 'event-info-modal');
            }
        }

        refreshEvents = (response: IGetCalendarResponse) => {
            // let response = <IGetCalendarResponse>serviceResponse.Value;
            this.assignEventList(response);
            // this.events = this.lo.sortBy(response.events, ['start']);
            let events = Peanut.Helper.SortBy(response.events, 'start');
            if (this.filtered() === 'type') {
                let code = this.filterCode();
                 // let events = this.lo.filter(this.events, (event: ICalendarEvent) => {
                let events = this.eventSource.events.filter((event: ICalendarEvent) => {
                    return event.eventType == code;
                });
            }
            let page = this.pages[this.currentPage];
            this.pages = [page];
            this.currentPage = 0;
            // this.switchEventSource(events);
            this.newCalendar(events);
            this.changeTab('calendar');
        };

        onEventListItemClick = (item : IEventListItem) => {
            this.onEventClick(item.event,null,null);
        };

        onEventClick = (calEvent, jsEvent, view) => {
            let me = this;
            // by default the event tries to forward to a null page. (go figure)
            calEvent.jsEvent.preventDefault();

            let eventId = calEvent.event.id;

            // get the original object from the datasource. calEvent.event is missing some attributes.
            let event = me.eventSource.events.find((eventObject: ICalendarEventObject)=> {
                return eventObject.id == eventId;
            });

            me.eventForm.assignFromCalendarObject(event);
            me.hideServiceMessages();
            me.showModal('event-info-modal');
            /*
              alert('Coordinates: ' + jsEvent.pageX + ',' + jsEvent.pageY);
              alert('View: ' + view.name);
            let x = jsEvent.clientX;
            let y = jsEvent.clientY;


             */
        };

        onNewEvent = () => {
            this.updateMode('new');
            this.eventForm.clear(this.committees,this.resources,this.eventTypes,this.groups);
            this.showEditPage();
        };


        onDeleteEvent = () => {
            this.updateMode('delete');
            this.eventInfoModal('hide');
            this.hideServiceMessages();
            this.deleteConfirmModal('show');
        };

        onEditEvent = () => {
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

        editRescheduledEvent = () => {
            let event = this.rescheduleForm.close();
            this.eventForm.assignFromCalendarObject(event);
            this.onEditEvent();
        };

        showEditPage = () => {
            this.eventForm.edit(this.committees,this.resources,this.eventTypes,this.groups);
            this.changeTab('edit');
        };

        onCancelEdit = () => {
            this.rescheduleForm.revert();
            this.changeTab('calendar');
        };

        onUpdateEvent  = () => {
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


        getSelectedItemIds(observable: KnockoutObservableArray<ILookupItem>) {
            let result = [];
            let list = observable();
            for (let i = 0; i<list.length; i++) {
                result.push(list[i].id);
            }
            return result;
        }

        deleteConfirmModal = (mode = 'show') => {
            let show = (mode === 'show')
            if (show) {
                this.eventForm.repeatMode('all');
            }
            let deleteModalId = this.eventForm.repeating() ? '#repeat-mode-modal' : '#confirm-event-delete-modal';
            // @ts-ignore
            // jQuery(deleteModalId).modal(mode);
            if (show) {
                this.showModal(deleteModalId);
            }
            else {
                this.hideModal(deleteModalId);
            }
        };

        showDeleteConfirmation = () => {
            this.hideServiceMessages();
            this.deleteConfirmModal('show');
        };

        deleteEvent = () => {
            this.deleteConfirmModal('hide');
            let page = this.pages[this.currentPage];
            let request = <ICalendarDeleteRequest> {
                eventId: this.eventForm.id(),
                startDate: this.eventForm.start.format('YYYY-MM-DD'),
                repeatUpdateMode: this.eventForm.repeating() ? this.eventForm.repeatMode() : 'none',
                filter: this.filtered(),
                code: this.filterCode(),
                year: page.year,
                month: page.month
            };

            this.services.executeService('peanut.qnut-calendar::DeleteEvent', request,
                (serviceResponse: Peanut.IServiceResponse) => {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let response = <IGetCalendarResponse>serviceResponse.Value;
                        this.refreshEvents(response);
                    }
                    if (this.format() !== 'list') {
                        // jQuery(window).scrollTop(0);
                        window.scrollTo(0, 0);
                    }
                })
                .fail(() => {
                    let trace = this.services.getErrorInformation();
                })

        };

        onUpdateConfirmed = () => {
            if (this.updateMode() == 'update') {
                this.updateEvent();
            }
            else {
                this.deleteEvent();
            }
        };

        updateEvent = () => {
            if (this.eventForm.repeatMode() == 'remove') {
                this.hideModal('#confirm-repeat-delete-modal');
            }
            else {
                this.hideModal('#repeat-mode-modal');
            }

            let dto = this.eventForm.validate();
            if (this.format() !== 'list') {
               // jQuery(window).scrollTop(0);
                window.scrollTo(0, 0);
            }
            if (dto) {
                let repeatMode = this.eventForm.repeating() ? this.eventForm.repeatMode() : '';
                let currentPage = this.pages[this.currentPage];
                let request = <ICalendarUpdateRequest>{
                    event: dto,
                    year: currentPage.year,
                    month: currentPage.month,
                    filter: this.filtered(),
                    code: this.filterCode(),
                    repeatUpdateMode: repeatMode,
                    // notificationDays: this.eventForm.sendNotifications() ? this.eventForm.notificationDays() : -1,
                    resources: this.getSelectedItemIds(this.eventForm.selectedResources),
                    committees: this.getSelectedItemIds(this.eventForm.selectedCommittees),
                    groups: this.getSelectedItemIds(this.eventForm.selectedGroups),
                    repeatInstance : this.eventForm.recurInstance
                };

                this.services.executeService('peanut.qnut-calendar::UpdateEvent', request,
                    (serviceResponse: Peanut.IServiceResponse) => {
                        if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                            let response = <IGetCalendarResponse>serviceResponse.Value;
                            this.refreshEvents(response);
                        }
                        if (this.format() !== 'list') {

                           //  jQuery(window).scrollTop(0);
                            window.scrollTo(0, 0);
                        }
                    })
                    .fail(() => {
                        let trace = this.services.getErrorInformation();
                    })
            }

        };

        showEventDetails = () => {
            let me = this;
            me.getEventDetails(() => {
                me.eventInfoModal('hide');
                me.changeTab('view');
            });
        };

        getEventDetails = (successFunction? : ()=> void) => {
            let me = this;
            me.services.executeService('peanut.qnut-calendar::GetEventDetails', me.eventForm.id(),
                (serviceResponse: Peanut.IServiceResponse) => {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let response = <ICalendarEventDetails>serviceResponse.Value;
                        me.eventForm.assignDetails(response);
                        if (successFunction) {
                            successFunction();
                        }
                    }
                })
                .fail(() => {
                    let trace = me.services.getErrorInformation();
                })

        };

        showListView = () => {
            this.tab('list')
            this.setCalendarHeader('list');
            this.pagingEnabled = false;
            this.calendar.setOption('headerToolbar', this.listViewOptions);

            // @ts-ignore
            this.calendar.changeView('listMonth');
            // fullCalendar('changeView', 'listMonth');
            this.pagingEnabled = true;

        };
        showCalendarView = () => {
            this.tab('calendar');
            this.calendar.setOption('headerToolbar',this.calenderViewOptions);
            this.setCalendarHeader('calendar');
            this.pagingEnabled = false;
            // @ts-ignore
            this.calendar.changeView('dayGridMonth');
            this.pagingEnabled = true;
            this.changeTab('calendar');
        };
        testModal = () => {
            // jQuery("#confirm-save-modal").modal('show');
            this.showModal('#confirm-repeat-delete-modal');
        };

        test = () => {
            // jQuery("#confirm-save-modal").modal('hide');
            alert('test');
        };

        showEventsFilterMenu = (item: any) => {
            this.showFilterMenu('events');
        };

        showCalendarFilterMenu = (item: any) => {
            this.showFilterMenu('committees');
        };

        showResourcesFilterMenu = (item: any) => {
            this.showFilterMenu('resources');
        };

        showGroupsFilterMenu = (item: any) => {
            this.showFilterMenu('groups');
        };


        showFilterMenu = (item: string) => {
            this.filterMenu(item);
            let title = this.translate('calendar-filter-title-' + item);
            this.filterMenuTitle(title);
            this.displayModal("#filter-menu-modal");
        };

        showReminderForm = () => {
            let me = this;
            this.eventForm.notificationDays(0);
            this.eventForm.sendNotifications(false);
            let request = {
                personId: me.userPersonId(),
                eventId: me.eventForm.id()
            };
            me.services.executeService('peanut.qnut-calendar::GetEventNotification', request,
                (serviceResponse: Peanut.IServiceResponse) => {
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

        updateNotification = () => {
            let me = this;
            // jQuery('#event-notification-modal').modal('hide');
            me.hideModal('#event-notification-modal');
            let request = <ICalendarNotification>  {
                personId: this.userPersonId(),
                eventId: this.eventForm.id(),
                notificationDays: this.eventForm.sendNotifications() ? this.eventForm.notificationDays() : -1,
            };
            me.services.executeService('peanut.qnut-calendar::UpdateEventNotification', request,
                (serviceResponse: Peanut.IServiceResponse) => {
                })
                .fail(() => {
                    let trace = me.services.getErrorInformation();
                })
        };

        enterCalendarRequest = () => {
            this.showModal('#request-menu-modal');
        };

        onCalendarRequestClose = () => {
            this.hideModal('#request-menu-modal');
        }

        /** Search **/
        onCalendarSearch = () => {
            // alert('searching')
            let searchText = this.searchForm.title().trim();
            let me = this;
            me.searchForm.results([]);
            me.services.executeService('peanut.qnut-calendar::FindEvents', searchText,
                (serviceResponse: Peanut.IServiceResponse) => {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        me.searchForm.results(serviceResponse.Value);
                        if(me.searchForm.results().length == 0) {
                            alert("No events found");
                        }
                    }
                })
                .fail(() => {
                    let trace = me.services.getErrorInformation();
                })
                .always(() => {
                });
        }

        onCancelSearch = () => {
            // alert('cancelled')
            this.hideModal('#find-event-modal');
            this.searchForm.title('');
        }

        onEventSearch() {
            this.searchForm.title('');
            this.searchForm.results([]);
            this.showModal('#find-event-modal');
        }

        openSearchItem = (item : ICalendarSearchResult) => {
            /*
                        let me = this;

                        if (!item.startdate || item.startdate.length < 6) {
                            alert('Invalid date ' + item.startdate);
                            return;
                        }

                        let request = {
                            year : item.startdate.substring(0,4),
                            month : item.startdate.substring(5,7)

                        }
                        //  alert('item: ' + item.startdate)
                        me.fullMenu(true);
                        me.filtered('all');
                        // me.pagingEnabled = false;
                        // do we need this?
                        me.calendarIsPaging(true); //?

                        me.getEvents(request, (response: IGetCalendarResponse) => {
                            let events =  response.events;// me.events.concat(response.events);
                            me.events = me.lo.sortBy(events, ['start']);

                            let newPage = new calendarPage(request.year,request.month,response.startDate,response.endDate);

                            this.pages = [newPage];
                            this.currentPage = 0;
                            me.showCalendar(events);
                            me.switchEventSource(me.events);
                            me.calendar.fullCalendar('render');
                        })

            */

            // jQuery('#find-event-modal').modal('hide');
            this.hideModal('#find-event-modal');


        }

    }
}
