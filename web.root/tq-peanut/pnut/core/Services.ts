/**
 * Created by Terry on 5/21/2017.
 */
/// <reference path='./Peanut.d.ts' />
/// <reference path='./PeanutLoader.ts' />
namespace Peanut {
    export const allMessagesType = -1;
    export const infoMessageType = 0;
    export const errorMessageType = 1;
    export const warningMessageType = 2;

    export const serviceResultSuccess = 0;
    export const serviceResultPending = 1;
    export const serviceResultWarnings = 2;
    export const serviceResultErrors = 3;
    export const serviceResultServiceFailure = 4;
    export const serviceResultServiceNotAvailable = 5;


    export class ServiceBroker {

        // Note: handleServiceFailure and showExceptionMessage at top to facilitate in-browser debugging
        handleServiceFailure = (debugInfo: any) => {
            let msg = 'Service Failure: ';
            if (debugInfo.message) {
              msg += debugInfo.message;
            }
            console.log(msg);
        };

        showExceptionMessage = (errorResult: any): string => {
            let errorMessage = this.parseErrorResult(errorResult);
            this.clientApp.showError(errorMessage);
            return errorMessage;
        };

        private static instance: ServiceBroker = null;
        public static create(client: IServiceClient) {
            return new ServiceBroker(client);
        }
        public static getInstance(application: IServiceClient) {
            if (ServiceBroker.instance == null) {
                ServiceBroker.instance = new ServiceBroker(application);
            }
            return ServiceBroker.instance;
        }
        constructor(public clientApp: IServiceClient) {
            let me = this;
            me.securityToken = me.readSecurityToken();
        }

        securityToken: string = '';
        errorInfo = '';
        errorMessage = '';

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

        parseErrorResult(result: any): string {
            let me = this;
            let errorDetailLevel = 4; // verbosity control to be implemented later
            let responseText = "An unexpected system error occurred.";
            try {
                // WCF returns a big whopping HTML page.  Could add code later to parse it but for now, just status info.
                if (result.status) {
                    if (result.status == '404') {
                        return responseText + " The web service was not found.";
                    }
                    else {
                        responseText = responseText + " Status: " + result.status;
                        if (result.statusText)
                            responseText = responseText + " " + result.statusText
                    }
                }
            }
            catch (ex) {
                responseText = responseText + " Error handling failed: " + ex.toString;
            }
            return responseText;

        }

        setSecurityToken = (token: string) => {
            this.securityToken = token;
        };

        getInfoMessages(messages: IServiceMessage[]): string[] {
            let result : string[] = [];
            let j = 0;
            for (let i = 0; i < messages.length; i++) {
                let message = messages[i];
                if (message.MessageType == infoMessageType)
                    result[j++] = message.Text;
            }

            return result;
        };


        getNonErrorMessages(messages: IServiceMessage[]): string[] {
            let me = this;
            let result : string[] = [];

            let j = 0;
            for (let i = 0; i < messages.length; i++) {
                let message = messages[i];
                if (message.MessageType != errorMessageType)
                    result[j++] = message.Text;
            }

            return result;
        }


        getErrorMessages(messages: IServiceMessage[]): string[] {
            let result : string[] = [];

            let j = 0;
            for (let i = 0; i < messages.length; i++) {
                let message = messages[i];
                if (message.MessageType == errorMessageType)
                    result[j++] = message.Text;
            }

            return result;
        }


        getMessagesText(messages: IServiceMessage[]): string[] {
            let result : string[] = [];
            let j = 0;
            for (let i = 0; i < messages.length; i++) {
                let message = messages[i];
                result[j++] = message.Text;
            }
            return result;
        }


        hideServiceMessages = (): void => {
            this.clientApp.hideServiceMessages();
        };

        showServiceMessages = (serviceResponse: IServiceResponse): void => {
            this.clientApp.showServiceMessages(serviceResponse.Messages);
        };

        handleServiceResponse = (serviceResponse: IServiceResponse): boolean => {
            this.showServiceMessages(serviceResponse);
            return true;
        };

        private wrapPromise(promise: Promise<any>) : IServicePromise<any> {
            let result = promise as any;
            result.done = (callback: any) => {
                promise.then(callback);
                return result;
            };
            result.fail = (callback: any) => {
                promise.catch(callback);
                return result;
            };
            result.always = (callback: any) => {
                promise.then(callback, callback);
                return result;
            };
            return result;
        }



        getSecurityToken(successFunction?: (serviceResponse: IServiceResponse) => void) : IServicePromise<any> {
            if (!Peanut.Config.loaded) {
                throw "Peanut.config must be initialized before ajax call."
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
                    } catch (e) {
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
        };

        executeRPC = (requestMethod: string, serviceName: string, parameters: any = "",
                      successFunction?: (serviceResponse: IServiceResponse) => void,
                      errorFunction?: (errorMessage: any) => void) : IServicePromise<any> => {

            if (!Peanut.Config.loaded) {
                throw "Peanut.config must be initialized before ajax call."
            }
            let url = Peanut.Config.values.serviceUrl;
            let me = this;
            me.errorMessage = '';
            me.errorInfo = '';

            // peanut controller requires parameter as a string.
            if (!parameters)
                parameters = "";
            else  {
                parameters = JSON.stringify(parameters);
            }

            let serviceRequest : any = {
                "serviceCode" : serviceName,
                "topsSecurityToken": me.securityToken,
                "request" : parameters};

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
                    let errorResult = {'message' : me.errorMessage, 'details' : me.errorInfo};
                    if (errorFunction) {
                        errorFunction(errorResult);
                    }
                    throw errorResult;
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
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
                let errorResult = {'message' : me.errorMessage, 'details' : me.errorInfo};
                if (errorFunction) {
                    errorFunction(errorResult);
                }
                throw errorResult;
            });

            return this.wrapPromise(fetchPromise);
        };

        postForm = (serviceName: string, parameters: any = "",
                    files: any,
                    progressFunction?: (progress: any) => void,
                    successFunction?: (serviceResponse: IServiceResponse) => void,
                    errorFunction?: (errorMessage: any) => void) : IServicePromise<any> => {

            // This method is typically used when one or more files must be uploaded as part of a service
            let me = this;
            me.errorMessage = '';
            me.errorInfo = '';

            // peanut controller requires parameter as a string.
            if (!parameters)
                parameters = "";
            else  {
                parameters = JSON.stringify(parameters);
            }

            let formData = new FormData();
            formData.append("serviceCode",serviceName);
            formData.append("topsSecurityToken",me.securityToken);
            formData.append("request",parameters);
            if (files && files.length) {
                //todo: YAGNI this supports only one file upload, maybe change later for multiple files
                formData.append('file',files[0]);
            }

            // todo: support progress function

            let fetchPromise = fetch(Peanut.Config.values.serviceUrl, {
                method: 'POST',
                body: formData,
                cache: 'no-cache'
            })
            .then(async (response) => {
                if (!response.ok) {
                    me.errorMessage = me.showExceptionMessage(response);
                    me.errorInfo = await response.text();
                    let errorResult = {'message' : me.errorMessage, 'details' : me.errorInfo};
                    if (errorFunction) {
                        errorFunction(errorResult);
                    }
                    throw errorResult;
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
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
                let errorResult = {'message' : me.errorMessage, 'details' : me.errorInfo};
                if (errorFunction) {
                    errorFunction(errorResult);
                }
                throw errorResult;
            });

            return this.wrapPromise(fetchPromise);
        };



        // Execute a peanut service and handle Service Response.
        executeService = (serviceName: string, parameters: any = "",
                          successFunction?: (serviceResponse: IServiceResponse) => void,
                          errorFunction?: (errorMessage: string) => void) : IServicePromise<any> => {
            return this.executeRPC("POST", serviceName, parameters, successFunction, errorFunction);
        };

        // GET is no longer supported. This method is for backward compatibility but is identical to execute service
        getFromService = (serviceName: string, parameters: any = "",
                          successFunction?: (serviceResponse: IServiceResponse) => void,
                          errorFunction?: (errorMessage: string) => void) : IServicePromise<any> => {
            return this.executeRPC("POST", serviceName, parameters, successFunction, errorFunction);
        };

        getErrorInformation = () => {
            let me = this;
            return me.errorInfo;
        }
    }

    /**
     * Use for testing. Normally IServiceResponse is returned from a service
     */
    export class fakeServiceResponse implements IServiceResponse {
        constructor(returnValue: any) {
            let me=this;
            me.Value = returnValue;
            me.Data = returnValue;
        }

        Messages: IServiceMessage[] = [];
        Result: number = 0;
        Value: any;
        Data: any;
    }

} // end namespace
