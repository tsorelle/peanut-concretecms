var Peanut;
(function (Peanut) {
    class formProtector {
        constructor(thresholdSeconds = 2, honeypotId = "security-control") {
            this.reloadKey = "lastPageLoad";
            this.startTime = null;
            this.enabled = true;
            this.reloadThresholdMs = thresholdSeconds * 1000;
            this.honeypotId = honeypotId;
        }
        setEnabled(enabled) {
            this.enabled = enabled;
        }
        isRapidReload() {
            const now = Date.now();
            const last = Number(localStorage.getItem(this.reloadKey) || 0);
            localStorage.setItem(this.reloadKey, String(now));
            if (!last) {
                return false;
            }
            return (now - last) < this.reloadThresholdMs;
        }
        start(enabled = true) {
            this.enabled = enabled;
            this.startTime = Date.now();
        }
        getDuration() {
            if (this.startTime === null) {
                return 0;
            }
            return Date.now() - this.startTime;
        }
        tookAtLeast(ms) {
            return this.getDuration() >= ms;
        }
        isHoneypotClear() {
            const el = document.getElementById(this.honeypotId);
            if (!el) {
                return true;
            }
            return el.value.trim().length === 0;
        }
        likelyHuman(minHumanMs = 7000) {
            if (!this.enabled) {
                return true;
            }
            const duration = this.getDuration();
            if (duration < minHumanMs) {
                return false;
            }
            return this.isHoneypotClear();
        }
    }
    Peanut.formProtector = formProtector;
    class editState {
    }
    editState.unchanged = 0;
    editState.created = 1;
    editState.updated = 2;
    editState.deleted = 3;
    Peanut.editState = editState;
    class Helper {
        static getRequestParam(name) {
            name = (new RegExp('[?&]' + encodeURIComponent(name) + '=([^&]*)')).exec(location.search);
            if (name) {
                return decodeURIComponent(name[1]);
            }
            return null;
        }
        static ValidateEmail(email) {
            if (!email) {
                return false;
            }
            email = email.trim();
            if (email == '') {
                return false;
            }
            return /^[\w+-]+(\.[\w+-]+)*@[a-zA-Z0-9]+(-[a-zA-Z0-9]+)*(\.[a-zA-Z0-9]+(-[a-zA-Z0-9]+)*)*\.[a-zA-Z]{2,}$/.test(email);
        }
        static ValidateCredential(value, minlength = 10, requireLower = false) {
            if (value.length < minlength) {
                return false;
            }
            if (value.replace(' ', '') !== value) {
                return false;
            }
            if (requireLower) {
                return (value.toLowerCase() === value);
            }
            return true;
        }
        ;
        static validatePositiveWholeNumber(text, maxValue = null, emptyOk = true) {
            return Helper.validateWholeNumber(text, maxValue, 0, emptyOk);
        }
        static validateWholeNumber(numberText, maxValue = null, minValue = null, emptyOk = true) {
            if (numberText == null) {
                numberText = '';
            }
            numberText = numberText + ' ';
            let result = {
                errorMessage: '',
                text: numberText.trim(),
                value: 0,
            };
            let parts = result.text.split('.');
            if (parts.length > 1) {
                let fraction = parseInt(parts[1].trim());
                if (fraction != 0) {
                    result.errorMessage = 'Must be a whole number.';
                    return result;
                }
                else {
                    result.text = parts[0].trim();
                }
            }
            if (result.text == '') {
                if (!emptyOk) {
                    result.errorMessage = 'A number is required.';
                }
                return result;
            }
            result.value = parseInt(result.text);
            if (isNaN(result.value)) {
                result.errorMessage = 'Must be a valid whole number.';
            }
            else {
                if (minValue != null && result.value < minValue) {
                    if (minValue == 0) {
                        result.errorMessage = 'Must be a positive number';
                    }
                    else {
                        result.errorMessage = 'Must be greater than ' + minValue;
                    }
                }
                if (maxValue != null && result.value > maxValue) {
                    if (result.errorMessage) {
                        result.errorMessage += ' and less than ' + maxValue;
                    }
                    else {
                        result.errorMessage = 'Must be less than ' + maxValue;
                    }
                }
            }
            return result;
        }
        static isValidDate(dateString, required = false) {
            if (dateString == null || (dateString.trim() == '')) {
                return !required;
            }
            const dateObject = new Date(dateString);
            return !isNaN(dateObject.getTime());
        }
        static checkNumeric(value) {
            return (!isNaN(parseFloat(value)) && isFinite(value));
        }
        static checkInteger(value, allowBlank) {
            if (allowBlank && value == '') {
                return true;
            }
            return (!isNaN(parseInt(value)) && isFinite(value));
        }
        static toAmPm(timeStr) {
            const match = timeStr.match(/^(\d{1,2}):(\d{2})$/);
            if (!match)
                return null;
            let hours = parseInt(match[1], 10);
            const minutes = match[2];
            const period = hours < 12 ? 'AM' : 'PM';
            if (hours === 0)
                hours = 12;
            else if (hours > 12)
                hours -= 12;
            return `${hours}:${minutes} ${period}`;
        }
        static to24Hour(timeStr) {
            const str = timeStr.trim().toUpperCase().replace(/\s+/g, '');
            const match = str.match(/^(\d{1,2})(?::(\d{2}))?(AM|PM)$/);
            if (!match)
                return null;
            let hours = parseInt(match[1], 10);
            const minutes = match[2] ? match[2] : '00';
            const period = match[3];
            if (period === 'AM') {
                if (hours === 12)
                    hours = 0;
            }
            else {
                if (hours !== 12)
                    hours += 12;
            }
            return `${String(hours).padStart(2, '0')}:${minutes}`;
        }
        static validateCurrency(value) {
            if (!value) {
                return false;
            }
            if (typeof value == 'string') {
                value = value.replace(/\s+/g, '');
                value = value.replace(',', '');
                value = value.replace('$', '');
            }
            else {
                value = value.toString();
            }
            if (!value) {
                return false;
            }
            let parts = value.split('.');
            if (parts.length > 2) {
                return false;
            }
            if (!Helper.checkNumeric(parts[0])) {
                return false;
            }
            if (parts.length == 1) {
                return parts[0] + '.00';
            }
            if (!Helper.checkNumeric(parts[0])) {
                return false;
            }
            let result = Number(parts[0] + '.' + parts[1].substring(0, 2));
            return !isNaN(result);
        }
        ;
        static getSelectedFiles(elementId) {
            let element = document.querySelector(elementId);
            if (element && element.files) {
                return element.files;
            }
            return null;
        }
        static getHostUrl() {
            let protocol = location.protocol;
            let slashes = protocol.concat("//");
            let host = slashes.concat(window.location.hostname);
            return host;
        }
        static isValidUrl(value) {
            try {
                const url = new URL(value);
                return url.protocol === 'http:' || url.protocol === 'https:';
            }
            catch {
                return false;
            }
        }
        static parseTimeString(ts) {
            if (!ts) {
                return '';
            }
            ts = ts.toUpperCase().trim();
            if (ts === '') {
                return '';
            }
            if (ts.indexOf('NOON') >= 0) {
                return '12:00';
            }
            if (ts.indexOf('MIDNIGHT') >= 0) {
                return '00:00';
            }
            let formatType = 0;
            let p = ts.indexOf('P');
            if (p === (ts.length - 1) || ts.substring(p) === 'PM') {
                formatType = 2;
            }
            else {
                p = ts.indexOf('A');
                if (p === (ts.length - 1) || ts.substring(p) === 'AM') {
                    formatType = 1;
                }
            }
            if (formatType > 0) {
                ts = ts.substring(0, p).trim();
                if (ts === '') {
                    return null;
                }
            }
            let parts = ts.split(':');
            let min = 0;
            let hr = Number(parts[0].trim());
            if (isNaN(hr) || (formatType > 0 && (hr < 1 || hr > 12))) {
                return null;
            }
            if (formatType === 1 && hr === 12) {
                hr = 0;
            }
            else if (formatType === 2 && hr !== 12) {
                hr += 12;
            }
            if (parts.length > 1) {
                let s = parts[1].trim();
                min = Number(s);
                if (isNaN(min) || min > 59 || min < 0 || s.length < 2) {
                    return null;
                }
            }
            if (min < 0 || hr < 0 || hr > 23 || min > 59) {
                return null;
            }
            if (hr < 10) {
                hr = '0' + hr;
            }
            if (min < 10) {
                min = '0' + min;
            }
            return hr + ':' + min;
        }
        static toDateObject(ds) {
            if (ds === null) {
                return null;
            }
            ds = ds.trim();
            if (ds === '') {
                return null;
            }
            let d = new Date(ds);
            if (d.toString() === 'Invalid Date') {
                return false;
            }
            return d;
        }
        static toISODate(d) {
            return d.toISOString().substring(0, 10);
        }
        static todayToISODate() {
            return this.toISODate(new Date());
        }
        static parseISODate(ds) {
            let d = Helper.toDateObject(ds);
            if (!d) {
                return false;
            }
            return d.toISOString().substring(0, 10);
        }
        static parseMySqlDate(ds, ts = null) {
            ds = Helper.parseISODate(ds);
            if (ds === false) {
                return 'Invalid date';
            }
            ts = Helper.parseTimeString(ts);
            if (ts === null) {
                return 'Invalid time';
            }
            if (!ts) {
                return ds;
            }
            return (ds.length > 0) ? ds + 'T' + ts : ts;
        }
        static validateDateRange(ds1, ds2) {
            let result = [null, null];
            let d1 = Helper.toDateObject(ds1);
            if (d1) {
                result[0] = Helper.toISODate(d1);
                let d2 = Helper.toDateObject(ds2);
                if (d2) {
                    if (d2 >= d1) {
                        result[1] = Helper.toISODate(d2);
                    }
                }
                return result;
            }
        }
        static getSelectedLookupItems(items, selected) {
            return items.filter((item) => {
                return selected.indexOf(item.id) !== -1;
            });
        }
        static getLookupValues(items) {
            let result = [];
            for (let i = 0; i < items.length; i++) {
                result.push(items[i].id);
            }
            return result;
        }
        static capitalize(s) {
            if (typeof s !== 'string')
                return '';
            return s.charAt(0).toUpperCase() + s.slice(1);
        }
        static makeFullName(first, last, middle = null) {
            let result = first ? first.trim() : '';
            if (middle) {
                result = result + ' ' + middle.trim();
            }
            if (last) {
                result = result + ' ' + last.trim();
            }
            return result;
        }
        static formatFormalDate(dateString = 'today', includeTime = true) {
            let date = (dateString === 'today') ?
                new Date() :
                new Date(dateString);
            let year = date.getFullYear();
            let day = date.getDate();
            let month = Peanut.Helper.MonthNames[date.getMonth()];
            let result = month + ' ' + day + ', ' + year;
            if (includeTime) {
                let hour = date.getHours();
                let min = date.getMinutes();
                let ampm = 'AM';
                if (hour === 0) {
                    hour = 12;
                }
                else if (hour >= 12) {
                    ampm = 'PM';
                    if (hour > 12) {
                        hour -= 12;
                    }
                }
                result += ' ' + hour + ':' + min + ' ' + ampm;
            }
            return result;
        }
        static ExcludeValues(listA, listB, property) {
            let array1 = [...listA];
            let array2 = [...listB];
            return array1.filter(a => !array2.some(b => b[property] === a[property]));
        }
        static SortByAlpha(list, property) {
            let clone = [...list];
            return clone.sort((v1, v2) => {
                let a = v1[property];
                let b = v2[property];
                a = a ? a.toLowerCase() : '';
                b = b ? b.toLowerCase() : '';
                if (a < b) {
                    return -1;
                }
                if (a > b) {
                    return 1;
                }
                return 0;
            });
        }
        static SortByInt(list, property) {
            let clone = [...list];
            return clone.sort((v1, v2) => {
                let a = v1[property];
                let b = v2[property];
                a = a ? parseInt(a) : 0;
                b = b ? parseInt(b) : 0;
                if (a < b) {
                    return -1;
                }
                if (a > b) {
                    return 1;
                }
                return 0;
            });
        }
        static FindIndex(list, testfunction) {
            if (Array.isArray(list)) {
                let len = list.length;
                for (let i = 0; i < len; i++) {
                    if (testfunction(list[i])) {
                        return i;
                    }
                }
            }
            return -1;
        }
        static SortBy(list, property) {
            if (list === null) {
                console.error('SortBy problem, list is null');
                return [];
            }
            let clone = [...list];
            return clone.sort((v1, v2) => {
                let a = v1[property];
                let b = v2[property];
                if (a < b) {
                    return -1;
                }
                if (a > b) {
                    return 1;
                }
                return 0;
            });
        }
        static endsWith(haystack, needle) {
            if (!haystack) {
                return false;
            }
            return haystack.indexOf(needle) == haystack.length - needle.length;
        }
        static ScrollToTop() {
            let pos = document.getElementById('page-top');
            pos.scrollIntoView({ behavior: "smooth" });
        }
        static ScrollTo(elementId) {
            if (elementId.charAt(0) === '#') {
                elementId = elementId.substring(1);
            }
            let pos = document.getElementById(elementId);
            pos.scrollIntoView({ behavior: "smooth" });
        }
    }
    Helper.getLookupId = (itemObservable) => {
        if (itemObservable && itemObservable()) {
            return itemObservable().id;
        }
        return null;
    };
    Helper.MonthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    Peanut.Helper = Helper;
})(Peanut || (Peanut = {}));
//# sourceMappingURL=ViewModelHelpers.js.map