var Peanut;
(function (Peanut) {
    class DomQuery {
        static GetInputElementValue(id) {
            if (id.charAt(0) === '#') {
                id = id.substring(1);
            }
            let element = document.getElementById(id);
            return element ? element.value : null;
        }
        static HideElement(id) {
            if (id.charAt(0) === '#') {
                id = id.substring(1);
            }
            let element = document.getElementById(id);
            if (element) {
                element.style.display = 'none';
            }
        }
        static ShowElement(id, style = 'block') {
            if (id.charAt(0) === '#') {
                id = id.substring(1);
            }
            let element = document.getElementById(id);
            if (element) {
                element.style.display = style;
            }
        }
        static SetElementValue(elementId, value) {
            if (elementId.charAt(0) === '#') {
                elementId = elementId.substring(1);
            }
            let element = document.getElementById(elementId);
            if (element) {
                element.value = value;
            }
            else {
                console.error("Element " + elementId + ' not found.');
            }
        }
        static SetFocus(id, formId = '') {
            if (id.charAt(0) === '#') {
                id = id.substring(1);
            }
            if (formId) {
                if (formId.charAt(0) !== '#') {
                    formId = '#' + formId;
                }
                document.location.hash = formId;
            }
            document.getElementById(id).focus();
        }
    }
    Peanut.DomQuery = DomQuery;
    class Environment {
        static getDeviceSize() {
            let width = window.screen.width;
            if (width >= 1200) {
                return 4;
            }
            if (width >= 992) {
                return 3;
            }
            if (width >= 768) {
                return 2;
            }
            return 1;
        }
    }
    Peanut.Environment = Environment;
    class ViewModelBase {
        constructor() {
            this.translations = [];
            this.bootstrapVersion = ko.observable(5);
            this.fontSet = ko.observable('');
            this.deviceSize = ko.observable(4);
            this.getApplication = () => {
                return this.application;
            };
            this.getServiceBroker = () => {
                return this.services;
            };
            this.start = (application, successFunction) => {
                let me = this;
                me.deviceSize(Environment.getDeviceSize());
                me.language = me.getUserLanguage();
                if (me.language === 'en-us') {
                    let englishTranslations = {
                        'wait-loading': 'Loading',
                        'wait-please': 'please wait',
                        'wait-action-loading': 'Loading',
                        'wait-action-update': 'Updating',
                        'wait-action-add': 'Adding',
                        'wait-action-delete': 'Deleting'
                    };
                    me.addTranslations(englishTranslations);
                }
                else {
                    me.addTranslations(Cookies.GetKvArray('peanutTranslations'));
                }
                me.application = application;
                me.services = Peanut.ServiceBroker.getInstance(application);
                Peanut.PeanutLoader.loadUiHelper(() => {
                    if (Peanut.ui.helper.getFramework() === 'Bootstrap') {
                        me.bootstrapVersion(Peanut.ui.helper.getVersion());
                        me.fontSet(Peanut.ui.helper.getFontSet());
                    }
                    me.fontSet(Peanut.ui.helper.getFontSet());
                    me.application.registerComponents('@pnut/translate', () => {
                        me.init(() => {
                            Peanut.logger.write('Loaded view model: ' + me.vmName);
                            successFunction(me);
                        });
                    });
                });
            };
            this.vmName = null;
            this.vmContext = null;
            this.vmContextId = null;
            this.language = 'en-us';
            this.setVmName = (name, context = null) => {
                this.vmName = name;
                this.vmContextId = context;
                let sharedContext = this.getPageVarialble('peanut-vm-context');
                this.vmContext = (sharedContext) ? context + '&' + sharedContext : context;
            };
            this.getVmContextId = () => {
                return this.vmContextId;
            };
            this.getVmContext = () => {
                return this.vmContext;
            };
            this.getVmName = () => {
                return this.vmName;
            };
            this.getSectionName = () => {
                return this.getVmName().toLowerCase() + '-view-container';
            };
            this.showDefaultSection = () => {
                let me = this;
                let sectionName = me.getSectionName();
                me.showElement("#" + sectionName);
                me.hideLoadMessage();
            };
            this.hideLoadMessage = () => {
                let loadMessage = this.getVmName().toLowerCase() + '-load-message';
                this.hideElement(loadMessage);
            };
            this.bindDefaultSection = () => {
                let me = this;
                let sectionName = me.getSectionName();
                me.hideLoadMessage();
                this.application.bindSection(sectionName, this);
            };
            this.attach = (componentName, finalFunction) => {
                this.attachComponent(componentName, null, finalFunction);
            };
            this.attachComponent = (componentName, section, finalFunction) => {
                this.application.registerComponentPrototype(componentName, () => {
                    if (!section) {
                        section = componentName.split('/').pop() + '-container';
                    }
                    this.application.bindSection(section, this);
                    if (finalFunction) {
                        finalFunction();
                    }
                });
            };
            this.setPageHeading = (text, textCase = 'none') => {
                if (text) {
                    text = this.translate(text);
                    text = this.changeCase(text, textCase);
                    let elements = document.getElementsByTagName("h1");
                    if (elements.length) {
                        let h1 = elements[0];
                        h1.textContent = text;
                        h1.style.display = 'block';
                    }
                    if (this.pageTitle === null) {
                        this.setPageTitle(text);
                    }
                }
            };
            this.pageTitle = null;
            this.setPageTitle = (text, textCase = 'none') => {
                text = this.translate(text);
                text = this.changeCase(text, textCase);
                this.pageTitle = text;
                document.title = text;
            };
            this.showWaitMessage = (message = 'wait-action-loading', waiter = 'spin-waiter') => {
                this.showWaiter(message, waiter);
            };
            this.showWaiter = (message = 'wait-action-loading', waiterType = 'spin-waiter') => {
                let me = this;
                message = me.translate(message) + '...';
                me.application.hideServiceMessages();
                Peanut.WaitMessage.show(message, waiterType);
            };
            this.showLoadWaiter = (message = 'wait-action-loading') => {
                let me = this;
                message = me.translate('wait-action-loading') + ', ' + me.translate('wait-please') + '...';
                Peanut.WaitMessage.show(message);
            };
            this.getActionMessage = (action, entity) => {
                return this.translate('wait-action-' + action) + ' ' + this.translate(entity) + ', ' + this.translate('wait-please') + '...';
            };
            this.showActionWaiter = (action, entity, waiter = 'spin-waiter') => {
                let message = this.getActionMessage(action, entity);
                Peanut.WaitMessage.show(message, waiter);
            };
            this.showActionWaiterBanner = (action, entity) => {
                this.showActionWaiter(action, entity, 'spin-waiter');
            };
            this.getRequestVar = (key, defaultValue = null) => {
                return HttpRequestVars.Get(key, defaultValue);
            };
            this.translate = (code, defaultText = null) => {
                let me = this;
                if (code in me.translations) {
                    return me.translations[code];
                }
                return defaultText === null ? code : defaultText;
            };
            this.addTranslation = (code, text) => {
                let me = this;
                me.translations[code] = text;
            };
            this.addTranslations = (translations) => {
                let me = this;
                if (translations) {
                    for (let code in translations) {
                        me.translations[code] = translations[code];
                    }
                }
            };
            this.setLanguage = (code) => {
                let me = this;
                me.language = code;
            };
            this.getLanguage = () => {
                let me = this;
                return me.language;
            };
            this.getTodayString = (language = null) => {
                if (!language) {
                    language = this.getLanguage();
                }
                let format = language.split('-').pop();
                let today = new Date();
                let dd = today.getDate();
                let mm = today.getMonth() + 1;
                let yyyy = today.getFullYear();
                if (dd < 10) {
                    dd = '0' + dd;
                }
                if (mm < 10) {
                    mm = '0' + mm;
                }
                return format === 'us' ? mm + '/' + dd + '/' + yyyy : yyyy + '-' + mm + '-' + dd;
            };
            this.isoToShortDate = (dateString, language = null) => {
                if (!dateString) {
                    return '';
                }
                if (!language) {
                    language = this.getLanguage();
                }
                dateString = dateString.split(' ').shift().trim();
                let format = language.split('-').pop();
                if (!dateString) {
                    return '';
                }
                if (format !== 'us') {
                    Peanut.logger.write('Warning: Simple date formatting for ' + format + 'is not supported. Using ISO.');
                    return dateString;
                }
                let parts = dateString.split('-');
                if (parts.length !== 3) {
                    console.error('Invalid ISO date string: ' + dateString);
                    return 'error';
                }
                return parts[1] + '/' + parts[2] + '/' + parts[0];
            };
            this.self = () => {
                return this;
            };
            this.getServices = () => {
                return this.services;
            };
            this.hideServiceMessages = () => {
                this.application.hideServiceMessages();
            };
            this.getUserDetails = (finalFunction) => {
                let me = this;
                if (me.userDetails) {
                    finalFunction(me.userDetails);
                    return;
                }
                me.services.executeService('Peanut::GetUserDetails', null, function (serviceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        me.application.hideWaiter();
                        me.userDetails = serviceResponse.Value;
                        finalFunction(me.userDetails);
                    }
                }).fail(function () {
                    let trace = me.services.getErrorInformation();
                    me.application.hideWaiter();
                });
            };
        }
        getInputElementValue(id) {
            return Peanut.DomQuery.GetInputElementValue(id);
        }
        hideElement(id) {
            Peanut.DomQuery.HideElement(id);
        }
        showElement(id, style = 'block') {
            Peanut.DomQuery.ShowElement(id, style);
        }
        getLocalReferrer() {
            let ref = document.referrer;
            if (ref) {
                let url = new URL(document.referrer);
                let host = window.location.hostname;
                let refhost = url.hostname;
                if (host === refhost) {
                    return url.pathname;
                }
            }
            return null;
        }
        fetchSessionItem(name) {
            let item = sessionStorage.getItem(name);
            if (item) {
                sessionStorage.removeItem(name);
            }
            return item;
        }
        getPageVarialble(id, defaultValue = null) {
            if (id.charAt(0) === '#') {
                id = id.substring(1);
            }
            let input = document.getElementById(id);
            return input ? input.value : defaultValue;
        }
        showModal(modal) {
            return Peanut.ui.helper.showModal(modal);
        }
        hideModal(modal) {
            Peanut.ui.helper.hideModal(modal);
        }
        changeCase(text, textCase) {
            switch (textCase) {
                case 'ucfirst':
                    let textLength = text.length;
                    text = text.substr(0, 1).toLocaleUpperCase() +
                        (textLength > 1 ? text.substr(1, textLength) : '');
                    break;
                case 'upper':
                    text = text.toLocaleUpperCase();
                    break;
                case 'lower':
                    text = text.toLocaleLowerCase();
                    break;
            }
            return text;
        }
        setElementValue(elementId, value) {
            Peanut.DomQuery.SetElementValue(elementId, value);
        }
        hideWaiter() {
            Peanut.WaitMessage.hide();
        }
        getUserLanguage() {
            let userLang = navigator.language || navigator.userLanguage;
            if (userLang) {
                return userLang.toLowerCase();
            }
            return 'en-us';
        }
        setFocus(id, formId = '') {
            Peanut.DomQuery.SetFocus(id, formId);
        }
        shortDateToIso(dateString) {
            if (!dateString) {
                return '';
            }
            let parts = dateString.split('/');
            if (parts.length !== 3) {
                return dateString;
            }
            let m = parts[0];
            let d = parts[1];
            let y = parts[2];
            return y + '-' +
                (m.length < 2 ? '0' + m.toString() : m) + '-' +
                (d.length < 2 ? '0' + d.toString() : d);
        }
        getDefaultLoadMessage() {
            let me = this;
            return me.translate('wait-loading', '...');
        }
        handleEvent(eventName, data) {
        }
    }
    Peanut.ViewModelBase = ViewModelBase;
    class Cookies {
        static cleanCookieString(encodedString) {
            let output = encodedString;
            let binVal, thisString;
            let myregexp = /(%[^%]{2})/;
            let match = [];
            while ((match = myregexp.exec(output)) != null
                && match.length > 1
                && match[1] != '') {
                binVal = parseInt(match[1].substr(1), 16);
                thisString = String.fromCharCode(binVal);
                output = output.replace(match[1], thisString);
            }
            return output;
        }
        static kvObjectsToArray(kvArray) {
            let result = [];
            for (let i = 0; i < kvArray.length; i++) {
                let obj = kvArray[i];
                let value = obj.Value.split('+').join(' ');
                result[obj.Key] = value.replace('[plus]', '+');
            }
            return result;
        }
        static kvCookieToArray(cookieString) {
            let a = Cookies.cleanCookieString(cookieString);
            let j = JSON.parse(a);
            return Cookies.kvObjectsToArray(j);
        }
        static Get(cookieName, index = 1) {
            let cookie = document.cookie;
            if (cookie) {
                let match = cookie.match(new RegExp(cookieName + '=([^;]+)'));
                if (match && match.length > index) {
                    return match[index];
                }
            }
            return '';
        }
        static GetKvArray(cookieName, index = 1) {
            let cookieString = Cookies.Get(cookieName, index);
            if (cookieString) {
                return Cookies.kvCookieToArray(cookieString);
            }
            return [];
        }
    }
    Peanut.Cookies = Cookies;
    class HttpRequestVars {
        constructor() {
            this.requestvars = [];
            let me = this;
            let queryString = window.location.search;
            let params = queryString.slice(queryString.indexOf('?') + 1).split('&');
            for (let i = 0; i < params.length; i++) {
                let parts = params[i].split('=');
                let key = parts[0];
                me.requestvars.push(key);
                me.requestvars[key] = parts[1];
            }
        }
        getValue(key) {
            let me = this;
            let value = me.requestvars[key];
            if (value) {
                return value;
            }
            return null;
        }
        static Get(key, defaultValue = null) {
            if (!HttpRequestVars.instance) {
                HttpRequestVars.instance = new HttpRequestVars();
            }
            let result = HttpRequestVars.instance.getValue(key);
            return (result === null) ? defaultValue : result;
        }
    }
    Peanut.HttpRequestVars = HttpRequestVars;
})(Peanut || (Peanut = {}));
//# sourceMappingURL=ViewModelBase.js.map