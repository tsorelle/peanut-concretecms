var Peanut;
(function (Peanut) {
    class KnockoutHelper {
        constructor() {
            this.loadList = {
                css: [],
                templates: [],
                components: [],
                scripts: [],
                bindings: []
            };
            this.loadCss = (path, media = null) => {
                if (path) {
                    let fileref = document.createElement("link");
                    fileref.setAttribute("rel", "stylesheet");
                    fileref.setAttribute("type", "text/css");
                    fileref.setAttribute("href", path);
                    if (media) {
                        fileref.setAttribute('media', media);
                    }
                    if (typeof fileref === "undefined") {
                        console.error('Failed to load stylesheet ' + path);
                    }
                    document.getElementsByTagName("head")[0].appendChild(fileref);
                    Peanut.logger.write('Loaded stylesheet: ' + path);
                }
            };
            this.loadViewModel = (vmName, final) => {
                Peanut.PeanutLoader.checkConfig();
                let me = this;
                if (vmName === null) {
                    console.error('No vm name provided in loadViewModel');
                    return;
                }
                let context = null;
                let parts = vmName.split('#');
                if (parts.length > 1) {
                    context = parts.pop();
                }
                vmName = parts.shift();
                parts = vmName.split('/');
                let prefix = '@app';
                if (vmName.slice(0, 1) === '@') {
                    prefix = parts.shift();
                }
                vmName = parts.pop();
                let vmClassName = vmName + 'ViewModel';
                let vmFile = prefix + '/' + parts.join('/') + '/vm/' + vmClassName;
                let parseResult = (this.parseFileName(vmFile, Peanut.Config.values.mvvmPath));
                let vmPath = parseResult.root + parseResult.name + '.js';
                let namespaceName = parseResult.namespace;
                Peanut.PeanutLoader.loadScript(vmPath, () => {
                    Peanut.logger.write("Loading " + namespaceName + '.' + vmClassName);
                    let namespace = window[namespaceName];
                    if (!namespace) {
                        console.error('Namespace "' + namespaceName + '" was not loaded.');
                    }
                    let vmClass = namespace[vmClassName];
                    if (!vmClass) {
                        console.error('Class "' + vmClassName + '" was not loaded.');
                    }
                    let vm = new vmClass;
                    vm.setVmName(vmName, context);
                    final(vm);
                });
            };
            this.getComponentPrototype = (componentPath) => {
                if ((window[componentPath.namespace]) && (window[componentPath.namespace][componentPath.className])) {
                    return window[componentPath.namespace][componentPath.className];
                }
                return null;
            };
            this.loadComponentTemplate = (componentPath, finalFunction) => {
                let me = this;
                Peanut.PeanutLoader.getConfig((config) => {
                    let htmlPath = componentPath.root + 'templates/' + componentPath.templateFile;
                    fetch(htmlPath).then((response) => {
                        if (response.ok) {
                            return response.text();
                        }
                        else {
                            console.error('Template not found at ' + htmlPath);
                            return '';
                        }
                    }).then((template) => {
                        if (finalFunction) {
                            finalFunction(template);
                        }
                    });
                });
            };
            this.loadComponentPrototype = (componentPath, finalFunction) => {
                if (window[componentPath.namespace] && window[componentPath.namespace][componentPath.className]) {
                    finalFunction(window[componentPath.namespace][componentPath.className]);
                }
                else {
                    let me = this;
                    let src = componentPath.root + 'components/' + componentPath.className + '.js';
                    Peanut.PeanutLoader.load(src, function () {
                        let vm = window[componentPath.namespace][componentPath.className];
                        if (finalFunction) {
                            finalFunction(vm);
                        }
                    });
                }
            };
            this.loadAndRegisterComponentPrototype = (componentName, finalFunction) => {
                let me = this;
                let componentPath = this.parseComponentName(componentName);
                if (me.alreadyLoaded(componentName, 'component')) {
                    finalFunction(componentPath);
                }
                this.loadComponentTemplate(componentPath, (template) => {
                    this.loadComponentPrototype(componentPath, (vm) => {
                        me.registerKoComponent(componentName, componentPath.componentName, {
                            viewModel: vm,
                            template: template
                        });
                        if (finalFunction) {
                            finalFunction(componentPath);
                        }
                    });
                });
            };
            this.registerComponentPrototype = (componentName, finalFunction) => {
                let me = this;
                let componentPath = this.parseComponentName(componentName);
                this.loadComponentTemplate(componentPath, (template) => {
                    let vm = this.getComponentPrototype(componentPath);
                    me.registerKoComponent(componentName, componentPath.componentName, {
                        viewModel: vm,
                        template: template
                    });
                    if (finalFunction) {
                        finalFunction(componentPath);
                    }
                });
            };
            this.registerKoComponent = (componentAlias, componentName, parameters) => {
                ko.components.register(componentName, parameters);
                this.loadList.components.push(componentAlias);
            };
            this.registerComponentInstance = (componentName, vmInstance, finalFunction) => {
                let me = this;
                if (me.alreadyLoaded(componentName, 'component')) {
                    finalFunction(null, null);
                    return;
                }
                let componentPath = this.parseComponentName(componentName);
                this.loadComponentTemplate(componentPath, (template) => {
                    this.getViewModelInstance(componentPath, vmInstance, (vm) => {
                        me.registerKoComponent(componentName, componentPath.componentName, {
                            viewModel: { instance: vm },
                            template: template
                        });
                        if (finalFunction) {
                            finalFunction(componentPath, vm);
                        }
                    });
                });
            };
            this.registerAndBindComponentInstance = (componentName, vmInstance, finalFunction) => {
                if (this.alreadyLoaded(componentName, 'component')) {
                    finalFunction();
                }
                else {
                    this.registerComponentInstance(componentName, vmInstance, (componentPath, vm) => {
                        if (componentPath !== null) {
                            this.bindSection(componentPath.componentName + '-container', vm);
                        }
                        if (finalFunction) {
                            finalFunction();
                        }
                    });
                }
            };
            this.registerComponents = (componentList, finalFunction) => {
                let componentName = componentList.shift();
                let me = this;
                if (componentName && !me.alreadyLoaded(componentName, 'component')) {
                    me.loadAndRegisterComponentPrototype(componentName, () => {
                        me.registerComponents(componentList, () => {
                            finalFunction();
                        });
                    });
                }
                else {
                    finalFunction();
                }
            };
            this.loadComponentPrototypes = (componentList, finalFunction) => {
                let me = this;
                let componentName = componentList.shift();
                if (componentName && !me.alreadyLoaded(componentName, 'scripts')) {
                    let componentPath = this.parseComponentName(componentName);
                    let src = componentPath.root + 'components/' + componentPath.className + '.js';
                    Peanut.PeanutLoader.load(src, function () {
                        me.loadComponentPrototypes(componentList, finalFunction);
                    });
                }
                else {
                    finalFunction();
                }
            };
            this.bindNode = (containerName, context) => {
                if (!this.alreadyLoaded(containerName, 'binding')) {
                    let container = this.getContainerNode(containerName);
                    if (container !== null) {
                        ko.applyBindingsToNode(container, null, context);
                    }
                    this.loadList.bindings.push(containerName);
                }
            };
            this.bindSection = (containerName, context) => {
                if (!this.alreadyLoaded(containerName, 'binding')) {
                    let container = this.getContainerNode(containerName);
                    if (container === null) {
                        return;
                    }
                    Peanut.logger.write('bind section: ' + containerName);
                    ko.applyBindings(context, container);
                    this.loadList.bindings.push(containerName);
                }
                let element = document.getElementById(containerName);
                if (element) {
                    element.style.display = 'block';
                }
            };
        }
        alreadyLoaded(name, type = 'component') {
            let list = null;
            let me = this;
            let loaded = false;
            switch (type) {
                case 'css':
                    loaded = me.loadList.css.indexOf(name) > -1;
                    break;
                case 'template':
                    loaded = me.loadList.templates.indexOf(name) > -1;
                    break;
                case 'component':
                    loaded = me.loadList.components.indexOf(name) > -1;
                    break;
                case 'script':
                    loaded = me.loadList.scripts.indexOf(name) > -1;
                    break;
                case 'binding':
                    loaded = me.loadList.bindings.indexOf(name) > -1;
                    break;
                default:
                    console.log('Warning invalid resource type ' + name);
                    return false;
            }
            if (loaded) {
                Peanut.logger.write("Skipped, already loaded " + name);
            }
            return loaded;
        }
        toCamelCase(name, seperator = '-', casingType = 'pascal') {
            let names = name.split(seperator);
            let result = (casingType == 'camel') ? names.shift() : '';
            for (let part of names) {
                let initial = part.slice(0, 1);
                initial = initial.toUpperCase();
                let remainder = part.slice(1);
                result = result + initial + remainder;
            }
            return result;
        }
        parseFileName(name, defaultPath = null) {
            defaultPath = defaultPath || Peanut.Config.values.commonRootPath;
            let result = { root: '', name: '', namespace: 'Peanut' };
            let parts = name.split('/');
            let len = parts.length;
            if (len == 1) {
                result.root = defaultPath;
                result.name = name;
            }
            else {
                if (parts[0] == '') {
                    result.name = parts.pop();
                    result.root = parts.join('/') + '/';
                }
                else {
                    let pathRoot = defaultPath;
                    switch (parts[0]) {
                        case '@pnut':
                            pathRoot = Peanut.Config.values.peanutRootPath;
                            parts.shift();
                            break;
                        case '@core':
                            pathRoot = Peanut.Config.values.corePath;
                            parts.shift();
                            break;
                        case '@app':
                            result.namespace = Peanut.Config.values.vmNamespace;
                            pathRoot = Peanut.Config.values.mvvmPath;
                            parts.shift();
                            break;
                        case '@pkg':
                            parts.shift();
                            let subDir = parts.shift();
                            result.namespace = this.toCamelCase(subDir);
                            pathRoot = Peanut.Config.values.packagePath + subDir + '/';
                            break;
                        default:
                            pathRoot = defaultPath;
                            break;
                    }
                    result.name = parts.pop();
                    result.root = parts.length == 0 ? pathRoot : pathRoot + parts.join('/') + '/';
                }
            }
            return result;
        }
        nameToFileName(componentName) {
            let parts = componentName.split('-');
            let fileName = parts[0];
            if (parts.length > 1) {
                fileName += parts[1].charAt(0).toUpperCase() + parts[1].substring(1);
            }
            return fileName;
        }
        parseComponentName(componentName) {
            let me = this;
            if (!Peanut.Config.loaded) {
                throw "Peanut Config was not loaded.";
            }
            if (componentName.slice(0, 1) !== '@') {
                componentName = '@app/' + componentName;
            }
            let parsed = me.parseFileName(componentName, Peanut.Config.values.mvvmPath);
            let fileName = me.nameToFileName(parsed.name);
            return {
                root: parsed.root,
                className: fileName + 'Component',
                templateFile: fileName + '.html',
                componentName: parsed.name,
                namespace: parsed.namespace
            };
        }
        expandFileName(fileName, defaultPath = null) {
            if (!fileName) {
                return '';
            }
            if (fileName.slice(0, 1) === '/' || fileName.toLowerCase().slice(0, 4) === 'http') {
                return fileName;
            }
            let me = this;
            let fileExtension = 'js';
            let p = fileName.lastIndexOf('.');
            if (p == -1) {
                fileName = fileName + '.js';
            }
            else {
                fileExtension = fileName.slice(p + 1).toLowerCase();
            }
            let parsed = me.parseFileName(fileName, defaultPath);
            return parsed.root + fileExtension + '/' + parsed.name;
        }
        loadResources(resourceList, successFunction) {
            let me = this;
            Peanut.PeanutLoader.checkConfig();
            Peanut.PeanutLoader.getConfig((config) => {
                let params = [];
                for (let i = 0; i < resourceList.length; i++) {
                    let name = resourceList[i];
                    if (name && (!me.alreadyLoaded(name, 'script'))) {
                        let path = (name.slice(0, 5) == '@lib:') ?
                            me.getLibrary(name, config) :
                            me.expandFileName(name, config.applicationPath);
                        if (path !== false) {
                            me.loadList.scripts.push(name);
                            params.push(path);
                        }
                    }
                }
                Peanut.PeanutLoader.load(params, successFunction);
            });
        }
        loadStyleSheets(resourceList) {
            let me = this;
            Peanut.PeanutLoader.checkConfig();
            Peanut.PeanutLoader.getConfig((config) => {
                for (let i = 0; i < resourceList.length; i++) {
                    let resourceName = resourceList[i];
                    if (me.alreadyLoaded(resourceName, 'css')) {
                        continue;
                    }
                    me.loadList.css.push(resourceName);
                    let parts = resourceName.split(' media=');
                    let path = parts.shift().trim();
                    let media = parts.shift();
                    media = media ? media.trim() : null;
                    if (path.substring(0, 1) === '/' || path.substring(0, 5) === 'http:' || path.substring(0, 6) === 'https:') {
                        me.loadCss(path, media);
                        return;
                    }
                    else if (path.slice(0, 6) == '@pnut:') {
                        path = me.getPeanutCss(path, config);
                    }
                    else if (path.slice(0, 5) == '@lib:') {
                        path = me.getLibrary(path, config);
                    }
                    else if (path.slice(0, 5) == '@pkg:') {
                        let pathParts = path.substring(5).split('/');
                        let pkgName = pathParts.shift();
                        let fileName = '/css/styles.css';
                        if (parts.length > 0) {
                            fileName = parts.pop();
                            let subdir = parts.length ? '/' + parts.join('/') + '/' : '/css/';
                            fileName = subdir + fileName;
                        }
                        path = Peanut.Config.values.packagePath + pkgName + fileName;
                    }
                    else if (path.slice(0, 1) == '@') {
                        path = me.expandFileName(path, config.applicationPath);
                    }
                    else {
                        path = config.stylesPath + path;
                    }
                    if (path) {
                        me.loadCss(path, media);
                    }
                }
            });
        }
        getPeanutCss(path, config) {
            let name = path.slice(6);
            if (config.cssOverrides.indexOf(name) === -1) {
                return config.peanutRootPath + 'styles/' + name;
            }
            return config.applicationPath + 'assets/styles/pnut/' + name;
        }
        getLibrary(name, config) {
            let key = name.slice(5);
            if (key.slice(0, 6) == 'local/') {
                return config.applicationPath + '/assets/js/' + key.slice(6);
            }
            if (key in config.libraries) {
                let path = config.libraries[key];
                if (path === 'installed') {
                    return false;
                }
                if (path.slice(0, 1) == '/' || path.slice(0, 5) == 'http:' || path.slice(0, 6) == 'https:') {
                    return path;
                }
                return config.libraryPath + path;
            }
            console.log('Library "' + key + '" not in settings.ini.');
            return false;
        }
        getHtmlTemplate(name, successFunction) {
            let me = this;
            if (me.alreadyLoaded(name, 'template')) {
                successFunction(null);
            }
            else {
                Peanut.PeanutLoader.checkConfig();
                let parsed = me.parseFileName(name, Peanut.Config.values.mvvmPath);
                let parts = parsed.name.split('-');
                let fileName = parts[0] + parts[1].charAt(0).toUpperCase() + parts[1].substring(1);
                let htmlSource = parsed.root + 'templates/' + fileName + '.html';
                Peanut.PeanutLoader.loadHtml(htmlSource, successFunction);
                me.loadList.templates.push(name);
            }
        }
        getViewModelInstance(componentPath, vmObject, returnFunction) {
            if (vmObject instanceof Function) {
                vmObject(returnFunction);
            }
            else {
                returnFunction(vmObject);
            }
        }
        getContainerNode(containerName) {
            let container = document.getElementById(containerName);
            if (container == null) {
                if (containerName) {
                    console.warn("Error: Container element '" + containerName + "' for section binding not found.");
                }
                else {
                    console.warn("Error: no container name for section binding.");
                }
            }
            return container;
        }
        static GetInputValue(oValue) {
            if (oValue !== null) {
                let value = oValue();
                if (value !== null) {
                    return value.trim();
                }
            }
            return '';
        }
    }
    Peanut.KnockoutHelper = KnockoutHelper;
})(Peanut || (Peanut = {}));
//# sourceMappingURL=KnockoutHelper.js.map