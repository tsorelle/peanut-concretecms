var PeanutContent;
(function (PeanutContent) {
    class contentControllerComponent {
        constructor(params) {
            this.confirmAction = '';
            this.restoreModlalId = '';
            this.confirmMessage = ko.observable('Are you sure?');
            this.contentForm = {
                contentId: ko.observable(0),
                title: ko.observable(''),
                description: ko.observable(''),
                shared: ko.observable(false),
                context: ko.observable(''),
                titleError: ko.observable(false),
            };
            this.contentListForm = {
                titles: ko.observableArray([]),
                shared: ko.observableArray([]),
                versions: ko.observableArray([]),
                tab: ko.observable('none'),
            };
            this.savedContentForm = {
                contentId: 0,
                title: '',
                description: '',
                shared: false,
                versions: []
            };
            this.clearDocument = () => {
                let me = this;
                me.contentForm.contentId(0);
                me.savedContentForm.contentId = me.contentForm.contentId();
                me.savedContentForm.title = me.contentForm.title();
                me.savedContentForm.description = me.contentForm.description();
                me.savedContentForm.shared = me.contentForm.shared();
                me.savedContentForm.versions = me.contentListForm.versions();
                me.contentForm.title('');
                me.contentForm.description('');
                me.contentForm.shared(false);
                me.contentListForm.versions([]);
            };
            this.cancelSave = () => {
                let me = this;
                me.contentForm.contentId(me.savedContentForm.contentId);
                me.contentForm.title(me.savedContentForm.title);
                me.contentForm.description(me.savedContentForm.description);
                me.contentForm.shared(me.savedContentForm.shared);
                me.contentListForm.versions(me.savedContentForm.versions);
                me.owner().hideModal(me.saveModalId());
            };
            this.doSave = () => {
                let me = this;
                let request = {
                    contentId: me.contentForm.contentId(),
                    title: me.contentForm.title().trim(),
                    description: me.contentForm.description().trim(),
                    shared: me.contentForm.shared(),
                    context: me.contentForm.context(),
                    content: me.editor.getContent()
                };
                if (request.title.length === 0) {
                    me.contentForm.titleError(true);
                    return;
                }
                me.contentForm.titleError(false);
                me.owner().hideModal(me.saveModalId());
                me.application.showWaiter('Saving content.');
                me.services.executeService('peanut.content::CreateTitle', request, function (serviceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let response = serviceResponse.Value;
                        me.handleContentItemResponse(response);
                    }
                    else {
                    }
                }).fail(function () {
                    let trace = me.services.getErrorInformation();
                    if (trace) {
                        console.error("Service call failed. Debug for error details. ");
                    }
                }).always(() => {
                    me.application.hideWaiter();
                });
            };
            this.newVersion = () => {
                let me = this;
                let request = {
                    contentId: me.contentForm.contentId(),
                    content: me.editor.getContent(),
                };
                me.application.showWaiter('Saving content.');
                me.services.executeService('peanut.content::SaveContent', request, function (serviceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let result = serviceResponse.Value;
                        me.contentListForm.versions.unshift(result);
                        me.editor.setDirty(false);
                    }
                    else {
                    }
                }).fail(function () {
                    let trace = me.services.getErrorInformation();
                    if (trace) {
                        console.error("Service call failed. Debug for error details. ");
                    }
                }).always(() => {
                    me.application.hideWaiter();
                });
            };
            this.finalVersion = () => {
                let me = this;
                let request = {
                    contentId: me.contentForm.contentId(),
                    content: me.editor.getContent(),
                    final: true
                };
                me.application.showWaiter('Saving final version.');
                me.services.executeService('peanut.content::SaveContent', request, function (serviceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let result = serviceResponse.Value;
                        me.contentListForm.versions.unshift(result);
                    }
                    else {
                    }
                }).fail(function () {
                    let trace = me.services.getErrorInformation();
                    if (trace) {
                        console.error("Service call failed. Debug for error details. ");
                    }
                }).always(() => {
                    me.application.hideWaiter();
                });
            };
            this.onTitleSelected = (title) => {
                let me = this;
                me.contentForm.title(title);
            };
            this.loadContent = (item) => {
                let me = this;
                me.application.showWaiter('Loading content.');
                me.services.executeService('peanut.content::GetContentItem', item.id, function (serviceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let result = serviceResponse.Value;
                        me.handleContentItemResponse(result);
                        me.editor.setContent(result.content);
                        me.owner().hideModal(me.fetchModalId());
                    }
                    else {
                    }
                }).fail(function () {
                    let trace = me.services.getErrorInformation();
                    if (trace) {
                        console.error("Service call failed. Debug for error details. ");
                    }
                }).always(() => {
                    me.application.hideWaiter();
                });
            };
            this.loadVersion = (item) => {
                let me = this;
                me.application.showWaiter('Loading version.');
                me.services.executeService('peanut.content::GetContent', { versionId: item.id }, function (serviceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let result = serviceResponse.Value;
                        me.editor.setContent(result);
                        me.owner().hideModal(me.fetchModalId());
                    }
                    else {
                    }
                }).fail(function () {
                    let trace = me.services.getErrorInformation();
                    if (trace) {
                        console.error("Service call failed. Debug for error details. ");
                    }
                }).always(() => {
                    me.application.hideWaiter();
                });
            };
            this.showAuthorTab = () => {
                this.contentListForm.tab('authors');
            };
            this.showSharedTab = () => {
                this.contentListForm.tab('shared');
            };
            this.showVersionsTab = () => {
                this.contentListForm.tab('versions');
            };
            this.showAuthorsTab = () => {
                this.contentListForm.tab('authors');
            };
            this.hideContentTab = () => {
                this.contentListForm.tab('none');
            };
            this.showConfirmModal = (action, message, currentModal = null) => {
                let me = this;
                me.confirmAction = action;
                me.restoreModlalId = currentModal;
                me.confirmMessage(message);
                if (currentModal) {
                    me.owner().hideModal(currentModal);
                }
                me.owner().showModal('confirm-modal');
            };
            this.cancelConfirmModal = () => {
                let me = this;
                me.owner().hideModal('confirm-modal');
                if (me.restoreModlalId) {
                    me.owner().showModal(me.restoreModlalId);
                }
                me.restoreModlalId = null;
            };
            this.confirmClearVersions = () => {
                let me = this;
                me.showConfirmModal('clear-versions', 'Are you sure you want to clear all previous versions?', me.fetchModalId());
            };
            this.clearVersions = () => {
                let me = this;
                me.owner().hideModal('confirm-modal');
                me.owner().showWaiter('Clering past versions.');
                me.services.executeService('peanut.content::ClearVersions', { contentId: me.contentForm.contentId() }, function (serviceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let versions = serviceResponse.Value;
                        me.contentListForm.versions(versions);
                        me.owner().showModal(me.fetchModalId());
                    }
                    else {
                        alert('Failed to clear versions.');
                    }
                }).fail(function () {
                    let trace = me.services.getErrorInformation();
                    if (trace) {
                        console.error("Service call failed. Debug for error details. ");
                    }
                }).always(() => {
                    me.application.hideWaiter();
                });
            };
            this.doDeleteContent = (id) => {
                let me = this;
                me.owner().showWaiter('Deleting content...');
                me.services.executeService('peanut.content::RemoveContent', {
                    contentId: id, context: me.contentForm.context()
                }, function (serviceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let response = serviceResponse.Value;
                        if (id == me.contentForm.contentId()) {
                            me.clearDocument();
                            me.editor.setContent('');
                            me.contentListForm.versions([]);
                        }
                        me.contentListForm.titles(response.titles || []);
                        me.contentListForm.shared(response.shared || []);
                        me.owner().showModal(me.fetchModalId());
                    }
                    else {
                        alert('Failed to remove content.');
                    }
                }).fail(function () {
                    let trace = me.services.getErrorInformation();
                    if (trace) {
                        console.error("Service call failed. Debug for error details. ");
                    }
                }).always(() => {
                    me.application.hideWaiter();
                });
            };
            this.deleteContent = (item) => {
                let me = this;
                me.showConfirmModal('delete-title:' + item.id, 'Are you sure you want to delete content for ' + item.title + '?', me.fetchModalId());
            };
            console.log('contentControllerComponent initializing.');
            let me = this;
            if (!params) {
                throw ('Params not defined in modalConfirmComponent');
            }
            if (params.context) {
                this.contentForm.context(params.context);
            }
            else {
                console.error('contentControllerComponent: context parameter required');
                return;
            }
            if (!params.owner) {
                console.error('contentControllerComponent: Owner parameter required');
                return;
            }
            if (params.editor) {
                this.editor = params.editor;
                this.editor.setEditController(this);
            }
            else {
                console.error('contentControllerComponent: editor parameter required');
                return;
            }
            if (params.translator) {
                console.log('contentControllerComponent: translations not supported yet');
            }
            let fetchId = params.fetchModalId || 'fetch-content-modal';
            me.fetchModalId = ko.observable(fetchId);
            let saveId = params.saveModalId || "save-content-modal";
            me.saveModalId = ko.observable(saveId);
            me.owner = params.owner;
            let ownerVm = params.owner();
            me.application = ownerVm.getApplication();
            me.services = ownerVm.getServices();
        }
        newDocument() {
            let me = this;
            me.clearDocument();
            me.editor.setDirty(false);
        }
        openDocument() {
            let me = this;
            let request = { context: me.contentForm.context() };
            me.application.showWaiter('Loading content.');
            me.services.executeService('peanut.content::GetTitles', request, function (serviceResponse) {
                if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                    let result = serviceResponse.Value;
                    me.contentListForm.titles(result.titles || []);
                    me.contentListForm.shared(result.shared || []);
                    if (result.titles.length > 0) {
                        me.contentListForm.tab('authors');
                    }
                    else if (result.shared.length > 0) {
                        me.contentListForm.tab('shared');
                    }
                    else {
                        me.contentListForm.tab('none');
                    }
                    me.owner().showModal(me.fetchModalId());
                }
                else {
                    me.contentListForm.tab('none');
                }
            }).fail(function () {
                let trace = me.services.getErrorInformation();
                if (trace) {
                    console.error("Service call failed. Debug for error details. ");
                }
            }).always(() => {
                me.application.hideWaiter();
            });
        }
        saveDocumentAs() {
            let me = this;
            me.clearDocument();
            me.owner().showModal(me.saveModalId());
        }
        saveDocument() {
            let me = this;
            if (me.editor.isEmpty()) {
                me.editor.setDirty(false);
                return;
            }
            let contentId = me.contentForm.contentId();
            if (contentId == 0) {
                me.owner().showModal(me.saveModalId());
            }
            else {
                me.newVersion();
            }
        }
        handleContentItemResponse(response) {
            let me = this;
            me.clearDocument();
            me.contentForm.contentId(response.id);
            me.contentForm.title(response.title);
            me.contentForm.description(response.description);
            me.contentForm.shared(response.shared);
            me.contentListForm.versions(response.versions || []);
        }
        handleConfirmAction() {
            let me = this;
            me.owner().hideModal('confirm-modal');
            const parts = me.confirmAction.split(":");
            let action = parts.shift();
            let args = parts.shift();
            switch (action) {
                case 'clear-versions':
                    me.clearVersions();
                    break;
                case 'delete-title':
                    if (args) {
                        let id = parseInt(args);
                        me.doDeleteContent(id);
                    }
                    else {
                        console.error('contentControllerComponent: delete-title action requires an id');
                    }
                    break;
                default:
                    console.error('contentControllerComponent: Unknown confirm action: ' + action);
            }
        }
    }
    PeanutContent.contentControllerComponent = contentControllerComponent;
})(PeanutContent || (PeanutContent = {}));
//# sourceMappingURL=contentControllerComponent.js.map