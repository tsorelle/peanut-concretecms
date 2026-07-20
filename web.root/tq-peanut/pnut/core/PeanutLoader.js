var Peanut;
(function (Peanut) {
    const peanutVersionNumber = '0.3';
    const configPath = '/peanut/settings';
    class Config {
    }
    Config.loaded = false;
    Config.values = {};
    Peanut.Config = Config;
    class logger {
        static getLoggingLevel(mode) {
            switch (mode) {
                case 'verbose':
                    return 1;
                case 'info':
                    return 2;
                case 'warnings':
                    return 3;
                case 'errors':
                    return 4;
                case 'fatal':
                    return 5;
                default:
                    let n = Number(mode);
                    if (n > 0 && n < 6) {
                        return n;
                    }
                    console.error('Invalid logging mode: ' + mode);
                    return 1;
            }
        }
        static write(message, mode = 5) {
            if (mode === true || Peanut.Config.values.loggingMode === 1 || Peanut.Config.values.loggingMode >= Peanut.logger.getLoggingLevel(mode)) {
                console.log(message);
            }
        }
    }
    Peanut.logger = logger;
    class ui {
    }
    Peanut.ui = ui;
    class PeanutLoader {
        static getDependencies() {
            return [];
        }
        static startApplication(name, final = null) {
            if (PeanutLoader.application) {
                PeanutLoader.application.startVM(name, final);
            }
            else {
                PeanutLoader.getConfig((config) => {
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
        static renderServiceMessageTags() {
            let markup = '<div id="peanut-messages"  class="container-fluid"  style="position:fixed;top:0;z-index: 10000">' +
                '<div class="row"><div class="col-12"><div id="service-messages-container"><service-messages></service-messages></div>' +
                '</div></div></div>';
            let newElement = document.createElement('div');
            newElement.innerHTML = markup;
            document.body.insertBefore(newElement, document.body.firstChild);
        }
        static loadViewModel(name, final = null) {
            if (PeanutLoader.application) {
                PeanutLoader.application.startVM(name, final);
            }
            else {
                console.error('Application was not initialized');
            }
        }
        static loadUiHelper(final) {
            if (Peanut.ui.helper) {
                final();
                return;
            }
            let uiExtension = Peanut.Config.values.uiExtension;
            let uiClass = uiExtension + 'UiHelper';
            PeanutLoader.loadExtensionClass(uiExtension, uiClass, (helperInstance) => {
                Peanut.ui.helper = helperInstance;
                final();
            });
        }
        static load(scripts, final) {
            if (!scripts) {
                final();
                return;
            }
            if (!(scripts instanceof Array)) {
                scripts = scripts.split(',');
            }
            switch (scripts.length) {
                case 0:
                    final();
                    return;
                case 1:
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
        }
        ;
        static checkConfig() {
            if (!Peanut.Config.loaded) {
                throw "Config was not loaded. Call PeanutLoader.getConfig in startup.";
            }
        }
        static getConfig(final) {
            if (Peanut.Config.loaded) {
                final(Peanut.Config.values);
            }
            else {
                fetch(configPath)
                    .then(res => res.json())
                    .then((data) => {
                    Peanut.Config.values.loggingMode = Peanut.logger.getLoggingLevel(data.loggingMode);
                    Peanut.logger.write("retrieved config");
                    Peanut.Config.loaded = true;
                    Peanut.Config.values.applicationVersionNumber = peanutVersionNumber + '.' + data.applicationVersionNumber;
                    Peanut.Config.values.commonRootPath = data.commonRootPath;
                    Peanut.Config.values.peanutRootPath = data.peanutRootPath;
                    Peanut.Config.values.packagePath = data.packagePath;
                    Peanut.Config.values.mvvmPath = data.mvvmPath;
                    Peanut.Config.values.corePath = data.corePath;
                    Peanut.Config.values.serviceUrl = data.serviceUrl;
                    Peanut.Config.values.dependencies = data.dependencies;
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
            }
        }
        static loadScript(script, final) {
            if (!Peanut.Config.loaded) {
                throw "Peanut Config was not loaded.";
            }
            let filetype = script.split('.').pop().toLowerCase();
            if (PeanutLoader.loaded.indexOf(script) == -1) {
                let scriptPath = script + '?v=' + Peanut.Config.values.applicationVersionNumber;
                head.load(scriptPath, () => {
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
        static loadScripts(scripts, final) {
            if (!Peanut.Config.loaded) {
                throw "Peanut Config was not loaded.";
            }
            let len = scripts.length;
            let items = [];
            for (let i = 0; i < len; i++) {
                let script = scripts[i];
                if (PeanutLoader.loaded.indexOf(script) == -1) {
                    if (script.split('.').pop().toLowerCase() == 'js') {
                        PeanutLoader.loaded.push(script);
                        script += '?v=' + Peanut.Config.values.applicationVersionNumber;
                        Peanut.logger.write("Loaded " + script);
                    }
                    items.push(script);
                }
            }
            head.load(items, final);
        }
        ;
        static loadExtensionClass(extension, className, final) {
            let scriptName = Config.values.peanutRootPath + 'extensions/' + extension + '/classes/' + className + '.js';
            PeanutLoader.loadScript(scriptName, () => {
                let p = window['Peanut'];
                let i = p[className];
                let inst = window['Peanut'][className];
                let extInstance = new window['Peanut'][className];
                final(extInstance);
            });
        }
        static loadHtml(htmlSource, successFunction) {
            PeanutLoader.checkConfig();
            fetch(htmlSource + "?v=" + Peanut.Config.values.applicationVersionNumber).then((response) => {
                if (response.ok) {
                    return response.text();
                }
                else {
                    console.error('Template not found at ' + htmlSource);
                    return '';
                }
            }).then((template) => {
                if (successFunction) {
                    successFunction(template);
                }
            });
        }
    }
    PeanutLoader.loaded = [];
    Peanut.PeanutLoader = PeanutLoader;
})(Peanut || (Peanut = {}));
//# sourceMappingURL=PeanutLoader.js.map