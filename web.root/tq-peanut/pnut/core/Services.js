var Peanut;
(function (Peanut) {
    Peanut.allMessagesType = -1;
    Peanut.infoMessageType = 0;
    Peanut.errorMessageType = 1;
    Peanut.warningMessageType = 2;
    Peanut.serviceResultSuccess = 0;
    Peanut.serviceResultPending = 1;
    Peanut.serviceResultWarnings = 2;
    Peanut.serviceResultErrors = 3;
    Peanut.serviceResultServiceFailure = 4;
    Peanut.serviceResultServiceNotAvailable = 5;
    class ServiceBroker {
        static create(client) {
            return new ServiceBroker(client);
        }
        static getInstance(application) {
            if (ServiceBroker.instance == null) {
                ServiceBroker.instance = new ServiceBroker(application);
            }
            return ServiceBroker.instance;
        }
        constructor(clientApp) {
            this.clientApp = clientApp;
            this.handleServiceFailure = (debugInfo) => {
                let msg = 'Service Failure: ';
                if (debugInfo.message) {
                    msg += debugInfo.message;
                }
                console.log(msg);
            };
            this.showExceptionMessage = (errorResult) => {
                let errorMessage = this.parseErrorResult(errorResult);
                this.clientApp.showError(errorMessage);
                return errorMessage;
            };
            this.securityToken = '';
            this.errorInfo = '';
            this.errorMessage = '';
            this.setSecurityToken = (token) => {
                this.securityToken = token;
            };
            this.hideServiceMessages = () => {
                this.clientApp.hideServiceMessages();
            };
            this.showServiceMessages = (serviceResponse) => {
                this.clientApp.showServiceMessages(serviceResponse.Messages);
            };
            this.handleServiceResponse = (serviceResponse) => {
                this.showServiceMessages(serviceResponse);
                return true;
            };
            this.executeRPC = (requestMethod, serviceName, parameters = "", successFunction, errorFunction) => {
                if (!Peanut.Config.loaded) {
                    throw "Peanut.config must be initialized before ajax call.";
                }
                let url = Peanut.Config.values.serviceUrl;
                let me = this;
                me.errorMessage = '';
                me.errorInfo = '';
                if (!parameters)
                    parameters = "";
                else {
                    parameters = JSON.stringify(parameters);
                }
                let serviceRequest = {
                    "serviceCode": serviceName,
                    "topsSecurityToken": me.securityToken,
                    "request": parameters
                };
                const body = new URLSearchParams();
                for (let key in serviceRequest) {
                    body.append(key, serviceRequest[key]);
                }
                let fetchPromise = fetch(url, {
                    method: requestMethod,
                    body: body,
                    cache: 'no-cache'
                })
                    .then(async (response) => {
                    if (!response.ok) {
                        me.errorMessage = me.showExceptionMessage(response);
                        me.errorInfo = await response.text();
                        let errorResult = { 'message': me.errorMessage, 'details': me.errorInfo };
                        if (errorFunction) {
                            errorFunction(errorResult);
                        }
                        throw errorResult;
                    }
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        }
                        catch (e) {
                            throw { message: "Invalid JSON response from server", details: text };
                        }
                    });
                })
                    .then(serviceResponse => {
                    if (serviceResponse.debugInfo !== undefined) {
                        me.handleServiceFailure(serviceResponse.debugInfo);
                    }
                    me.showServiceMessages(serviceResponse);
                    if (successFunction) {
                        successFunction(serviceResponse);
                    }
                    return serviceResponse;
                })
                    .catch(err => {
                    if (err && err.message && err.details) {
                        throw err;
                    }
                    me.errorMessage = "Network error or unexpected response";
                    me.errorInfo = err ? err.toString() : '';
                    let errorResult = { 'message': me.errorMessage, 'details': me.errorInfo };
                    if (errorFunction) {
                        errorFunction(errorResult);
                    }
                    throw errorResult;
                });
                return this.wrapPromise(fetchPromise);
            };
            this.postForm = (serviceName, parameters = "", files, progressFunction, successFunction, errorFunction) => {
                let me = this;
                me.errorMessage = '';
                me.errorInfo = '';
                if (!parameters)
                    parameters = "";
                else {
                    parameters = JSON.stringify(parameters);
                }
                let formData = new FormData();
                formData.append("serviceCode", serviceName);
                formData.append("topsSecurityToken", me.securityToken);
                formData.append("request", parameters);
                if (files && files.length) {
                    formData.append('file', files[0]);
                }
                let fetchPromise = fetch(Peanut.Config.values.serviceUrl, {
                    method: 'POST',
                    body: formData,
                    cache: 'no-cache'
                })
                    .then(async (response) => {
                    if (!response.ok) {
                        me.errorMessage = me.showExceptionMessage(response);
                        me.errorInfo = await response.text();
                        let errorResult = { 'message': me.errorMessage, 'details': me.errorInfo };
                        if (errorFunction) {
                            errorFunction(errorResult);
                        }
                        throw errorResult;
                    }
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        }
                        catch (e) {
                            throw { message: "Invalid JSON response from server", details: text };
                        }
                    });
                })
                    .then(serviceResponse => {
                    if (serviceResponse.debugInfo !== undefined) {
                        me.handleServiceFailure(serviceResponse.debugInfo);
                    }
                    me.showServiceMessages(serviceResponse);
                    if (successFunction) {
                        successFunction(serviceResponse);
                    }
                    return serviceResponse;
                })
                    .catch(err => {
                    if (err && err.message && err.details) {
                        throw err;
                    }
                    me.errorMessage = "Network error or unexpected response";
                    me.errorInfo = err ? err.toString() : '';
                    let errorResult = { 'message': me.errorMessage, 'details': me.errorInfo };
                    if (errorFunction) {
                        errorFunction(errorResult);
                    }
                    throw errorResult;
                });
                return this.wrapPromise(fetchPromise);
            };
            this.executeService = (serviceName, parameters = "", successFunction, errorFunction) => {
                return this.executeRPC("POST", serviceName, parameters, successFunction, errorFunction);
            };
            this.getFromService = (serviceName, parameters = "", successFunction, errorFunction) => {
                return this.executeRPC("POST", serviceName, parameters, successFunction, errorFunction);
            };
            this.getErrorInformation = () => {
                let me = this;
                return me.errorInfo;
            };
            let me = this;
            me.securityToken = me.readSecurityToken();
        }
        readSecurityToken() {
            let cookie = document.cookie;
            if (cookie) {
                let match = cookie.match(new RegExp('peanutSecurity=([^;]+)'));
                if (match) {
                    return match[1];
                }
            }
            return '';
        }
        parseErrorResult(result) {
            let me = this;
            let errorDetailLevel = 4;
            let responseText = "An unexpected system error occurred.";
            try {
                if (result.status) {
                    if (result.status == '404') {
                        return responseText + " The web service was not found.";
                    }
                    else {
                        responseText = responseText + " Status: " + result.status;
                        if (result.statusText)
                            responseText = responseText + " " + result.statusText;
                    }
                }
            }
            catch (ex) {
                responseText = responseText + " Error handling failed: " + ex.toString;
            }
            return responseText;
        }
        getInfoMessages(messages) {
            let result = [];
            let j = 0;
            for (let i = 0; i < messages.length; i++) {
                let message = messages[i];
                if (message.MessageType == Peanut.infoMessageType)
                    result[j++] = message.Text;
            }
            return result;
        }
        ;
        getNonErrorMessages(messages) {
            let me = this;
            let result = [];
            let j = 0;
            for (let i = 0; i < messages.length; i++) {
                let message = messages[i];
                if (message.MessageType != Peanut.errorMessageType)
                    result[j++] = message.Text;
            }
            return result;
        }
        getErrorMessages(messages) {
            let result = [];
            let j = 0;
            for (let i = 0; i < messages.length; i++) {
                let message = messages[i];
                if (message.MessageType == Peanut.errorMessageType)
                    result[j++] = message.Text;
            }
            return result;
        }
        getMessagesText(messages) {
            let result = [];
            let j = 0;
            for (let i = 0; i < messages.length; i++) {
                let message = messages[i];
                result[j++] = message.Text;
            }
            return result;
        }
        wrapPromise(promise) {
            let result = promise;
            result.done = (callback) => {
                promise.then(callback);
                return result;
            };
            result.fail = (callback) => {
                promise.catch(callback);
                return result;
            };
            result.always = (callback) => {
                promise.then(callback, callback);
                return result;
            };
            return result;
        }
        getSecurityToken(successFunction) {
            if (!Peanut.Config.loaded) {
                throw "Peanut.config must be initialized before ajax call.";
            }
            const body = new URLSearchParams();
            body.append('serviceCode', 'getxsstoken');
            let promise = fetch(Peanut.Config.values.serviceUrl, {
                method: 'POST',
                body: body,
                cache: 'no-cache'
            })
                .then(response => {
                if (!response.ok) {
                    throw response;
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    }
                    catch (e) {
                        throw { message: "Invalid JSON response from server", details: text };
                    }
                });
            })
                .then(serviceResponse => {
                if (successFunction) {
                    successFunction(serviceResponse);
                }
                return serviceResponse;
            });
            return this.wrapPromise(promise);
        }
        ;
    }
    ServiceBroker.instance = null;
    Peanut.ServiceBroker = ServiceBroker;
    class fakeServiceResponse {
        constructor(returnValue) {
            this.Messages = [];
            this.Result = 0;
            let me = this;
            me.Value = returnValue;
            me.Data = returnValue;
        }
    }
    Peanut.fakeServiceResponse = fakeServiceResponse;
})(Peanut || (Peanut = {}));
//# sourceMappingURL=Services.js.map