/// <reference path="../../typings/custom/head.load.d.ts" />
/// <reference path='Peanut.d.ts' />
namespace Peanut {
    const peanutVersionNumber = '0.3';
    const configPath =  '/peanut/settings';

    export class Config {
        static loaded: boolean = false;
        static values: IPeanutConfig = <IPeanutConfig>{};

    }

    export class logger {
        static getLoggingLevel(mode: any) {
            switch (mode) {
                case 'verbose' :
                    return 1;
                case 'info' :
                    return 2;
                case 'warnings' :
                    return 3;
                case 'errors' :
                    return 4;
                case 'fatal' :
                    return 5;
                default :
                    let n = Number(mode);
                    if (n>0 && n < 6) {
                        return n;
                    }
                    console.error('Invalid logging mode: ' + mode);
                    return 1;
            }
        }

        static write(message: string, mode: any = 5) {
            if ( mode===true || Peanut.Config.values.loggingMode === 1 ||  Peanut.Config.values.loggingMode >= Peanut.logger.getLoggingLevel(mode)) {
                console.log(message);
            }
        }
    }


    export class ui {
        static helper: IUiHelper;
    }


    export class PeanutLoader {
        private static loaded = [];
        private static application : IPeanutClient;

        private static getDependencies() {
            return [];
        }
        public static startApplication(name: string, final: (result: any) => void = null) {
            if (PeanutLoader.application) {
                PeanutLoader.application.startVM(name, final);
            }
            else {
                PeanutLoader.getConfig((config: IPeanutConfig) => {
                    // todo: test this.  Replace head.load with PeanutLoader.load
                    // to assign version numbers
                    // head.load(config.dependencies,() => {
                    PeanutLoader.load(config.preload, () => {
                        PeanutLoader.load(config.dependencies, () => {
                        if (!PeanutLoader.application) {
                            PeanutLoader.application = new window['Peanut']['Application'];
                            PeanutLoader.application.initialize(() => {
                                PeanutLoader.application.startVM(name, final);
                            });
                        }
                        else {
                            PeanutLoader.application.startVM(name, final);
                        }
                    });
                });
                });

            }
        }

        public static renderServiceMessageTags() {
            // Create the markup you want to add
            let markup = '<div id="peanut-messages"  class="container-fluid"  style="position:fixed;top:0;z-index: 10000">' +
            '<div class="row"><div class="col-12"><div id="service-messages-container"><service-messages></service-messages></div>' +
                '</div></div></div>';

            // Create a new div element and set its innerHTML to the markup
            let newElement = document.createElement('div');
            newElement.innerHTML = markup;

            // Add the new element to the top of the body
            document.body.insertBefore(newElement, document.body.firstChild);
        }

        public static loadViewModel(name: string, final: (result: any) => void = null) {
            if (PeanutLoader.application) {
                PeanutLoader.application.startVM(name, final);
            }
            else {
                console.error('Application was not initialized');
            }
        }


        public static loadUiHelper(final: () => void) {
            if (Peanut.ui.helper) {
                final();
                return;
            }
            let uiExtension = Peanut.Config.values.uiExtension;
            let uiClass = uiExtension + 'UiHelper';
            PeanutLoader.loadExtensionClass(uiExtension, uiClass, (helperInstance : any) => {
                Peanut.ui.helper = <IUiHelper>helperInstance;
                final();
            });
        }

        public static load (scripts: any, final: () => void)  {
            if (!scripts) {
                final();
                return;
            }
            if (!(scripts instanceof Array)) {
                scripts = (<string>scripts).split(',');
            }
            switch (scripts.length) {
                case 0 :
                    final();
                    return;
                case 1 :
                    PeanutLoader.getConfig(() => {
                        PeanutLoader.loadScript(scripts[0], final);
                    });
                    return;
                default:
                    PeanutLoader.getConfig(() => {
                        PeanutLoader.loadScripts(scripts, final);
                    });
                    return;
            }
        };

        public static checkConfig() {
            if (!Peanut.Config.loaded) {
                throw "Config was not loaded. Call PeanutLoader.getConfig in startup.";
            }
        }

        public static getConfig(final: (config?: IPeanutConfig) => void) {
            if (Peanut.Config.loaded) {
                final(Peanut.Config.values);
            }
            else {

                fetch( //'http://jsonplaceholder.typicode.com/todos'
                    configPath
                )
                    .then(res => res.json())
                    .then((data) =>  {
                        Peanut.Config.values.loggingMode =  Peanut.logger.getLoggingLevel(data.loggingMode);
                        Peanut.logger.write("retrieved config");
                        Peanut.Config.loaded = true;
                        Peanut.Config.values.applicationVersionNumber = peanutVersionNumber +'.' + data.applicationVersionNumber;
                        Peanut.Config.values.commonRootPath = data.commonRootPath;
                        Peanut.Config.values.peanutRootPath = data.peanutRootPath;
                        Peanut.Config.values.packagePath = data.packagePath;
                        Peanut.Config.values.mvvmPath = data.mvvmPath;
                        Peanut.Config.values.corePath = data.corePath;
                        Peanut.Config.values.serviceUrl = data.serviceUrl;
                        Peanut.Config.values.dependencies = data.dependencies;
                        // Peanut.Config.values.preload = data.preload;
                        Peanut.Config.values.vmNamespace = data.vmNamespace;
                        Peanut.Config.values.uiExtension = data.uiExtension;
                        Peanut.Config.values.libraries = data.libraries;
                        Peanut.Config.values.applicationPath = data.applicationPath;
                        Peanut.Config.values.libraryPath = data.libraryPath;
                        Peanut.Config.values.stylesPath = data.stylesPath;
                        Peanut.Config.values.cssOverrides = data.cssOverrides;
                        Peanut.logger.write('Namespace ' + Peanut.Config.values.vmNamespace);
                        final(Peanut.Config.values);

                });
                /*  OLD jquery version. removing jquery dependencies.
                jQuery.getJSON(configPath, function (data: IPeanutConfig) {
                 */
            }
        }

        public static loadScript(script: string, final: () => void) {
            if (!Peanut.Config.loaded) {
                throw "Peanut Config was not loaded.";
            }
            let filetype = script.split('.').pop().toLowerCase();
            if (PeanutLoader.loaded.indexOf(script) == -1) {
                let scriptPath = script + '?v=' + Peanut.Config.values.applicationVersionNumber;
                head.load(scriptPath,() => {
                    Peanut.logger.write("Loaded " + scriptPath);
                    PeanutLoader.loaded.push(script);
                    final();

                });
            }
            else {
                Peanut.logger.write("Skipped " + script);
                final();
            }
        }

        public static loadScripts(scripts: string[], final: () => void) {
            if (!Peanut.Config.loaded) {
                throw "Peanut Config was not loaded.";
            }
            let len = scripts.length;
            let items = [];

            for(let i=0; i< len; i++ ) {
                let script = scripts[i];
                if (PeanutLoader.loaded.indexOf(script) == -1) {
                    if (script.split('.').pop().toLowerCase() == 'js') {
                        PeanutLoader.loaded.push(script);
                        script +=  '?v=' + Peanut.Config.values.applicationVersionNumber;
                        Peanut.logger.write("Loaded " + script);
                    }
                    // todo: test this. Using unshift reversed order of scripts. Causing failure
                    // in initial dependency load,
                    // items[i] = script;
                    // items.unshift(script);
                    items.push(script);
                }
            }
            head.load(items,final);
        };

        public static loadExtensionClass(extension, className, final: (extInstance : any) => void) {
            let scriptName = Config.values.peanutRootPath + 'extensions/' + extension + '/classes/' + className + '.js';
            PeanutLoader.loadScript(scriptName, () => {
                let p = window['Peanut'];
                // let i = p['BootstrapUIHelper'];
                let i = p[className];
                let inst = window['Peanut'][className];
                let extInstance = new window['Peanut'][className];
                final(extInstance);
            } )
        }

        public  static loadHtml(htmlSource: string, successFunction : (htmlSource: string) => void) {
            PeanutLoader.checkConfig();

            fetch(htmlSource + "?v="+ Peanut.Config.values.applicationVersionNumber).then((response) => {
                if (response.ok) {
                    return response.text();
                }
                else {
                    console.error('Template not found at '+htmlSource)
                    return '';
                }
            }).then((template) => {
                if (successFunction) {
                    successFunction(template);
                }
            });

            // jQuery.get(htmlSource + "?v="+ Peanut.Config.values.applicationVersionNumber, successFunction);
        }

    }


} // end namespace