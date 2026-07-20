/**
 * Created by Terry on 2026-05-01.
 *
 * @see documentation
 * Tag examples:
 * With required parameters only:
 *   <content-controller params="owner:self,contentObserver:saveContent,onFetchSuccess: onNewContent"</content-controller>
 *
 *  With all parameters including overrides:
 *      <content-controller params="owner:self,contentObserver:saveContent,onFetchSuccess: onNewContent,
 *      fetchId:'fetch-content-modal',saveId:'save-content-modal',translator:self"</content-controller>
 *
 */
/// <reference path='../../../../typings/knockout/index.d.ts' />
/// <reference path='../../../../pnut/core/KnockoutHelper.ts' />
///<reference path="../../../../pnut/core/Services.ts"/>
///<reference path="../../../../pnut/core/Peanut.d.ts"/>

namespace PeanutContent {
    import IPeanutClient = Peanut.IPeanutClient;
    import ServiceBroker = Peanut.ServiceBroker;
    import ViewModelBase = Peanut.ViewModelBase;
    import IContentEditor = Peanut.IContentEditor;

/*
    interface IContentItem {
        id : any;
        title: string;
        description: string;
        shared: boolean;
    }
*/
    interface ITitlesListItem {
        id : any;
        title: string;
        author: string;
        shared: string;
        dateCreated : string;
    }

    interface IVersionsListItem {
        id : any;
        dateCreated: string;
    }

    interface IContentItemResponse {
        id : any;
        title: string;
        description: string;
        shared: boolean;
        versions: IVersionsListItem[];
    }
    interface IGetContentResponse  extends IContentItemResponse {
        content: string;
    }

    interface IGetTitlesResponse {
        titles: ITitlesListItem[];
        shared: ITitlesListItem[];
    }

    export class contentControllerComponent implements Peanut.IEditController {  // IServiceClient not needed?
        private application: IPeanutClient;
        private services: ServiceBroker;
        private owner : () => ViewModelBase;
        public saveModalId : KnockoutObservable<string>;
        public fetchModalId : KnockoutObservable<string>;
        private editor : Peanut.IContentEditor;
        public confirmAction : string = '';
        public restoreModlalId : string = '';
        // public confirmClickHandler = ko.observable(function() {});
        public confirmMessage = ko.observable('Are you sure?');

        private contentForm = {
            contentId : ko.observable(0),
            title : ko.observable(''),
            description : ko.observable(''),
            shared : ko.observable(false),
            context : ko.observable(''),
            titleError : ko.observable(false),
        }


        private contentListForm = {
            titles : ko.observableArray<ITitlesListItem>([]),
            shared : ko.observableArray<ITitlesListItem>([]),
            versions : ko.observableArray<IVersionsListItem>([]),
            tab: ko.observable('none'),
        }


        private savedContentForm = {
            contentId: 0,
            title: '',
            description: '',
            shared: false,
            versions: []
        }

        constructor(params : any) {
            console.log('contentControllerComponent initializing.');
            let me = this;
            if (!params) {
                throw('Params not defined in modalConfirmComponent');
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
                this.editor = <IContentEditor>params.editor;
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

            let saveId  = params.saveModalId || "save-content-modal";
            me.saveModalId = ko.observable(saveId);

            me.owner = params.owner;
            let ownerVm = params.owner();
            me.application = ownerVm.getApplication();
            me.services = ownerVm.getServices();
        }

         clearDocument = () => {
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
        }

        cancelSave = () => {
            let me = this
            me.contentForm.contentId(me.savedContentForm.contentId);
            me.contentForm.title(me.savedContentForm.title);
            me.contentForm.description(me.savedContentForm.description);
            me.contentForm.shared(me.savedContentForm.shared );
            me.contentListForm.versions(me.savedContentForm.versions);
            me.owner().hideModal(me.saveModalId());
        }

        doSave = () => {
            let me = this;

            let request = {
                contentId : me.contentForm.contentId(),
                title : me.contentForm.title().trim(),
                description : me.contentForm.description().trim(),
                shared : me.contentForm.shared(),
                context : me.contentForm.context(),
                content : me.editor.getContent()
            }
            if (request.title.length === 0) {
                me.contentForm.titleError(true);
                return;
            }
            me.contentForm.titleError(false);
            me.owner().hideModal(me.saveModalId());
            me.application.showWaiter('Saving content.');
            me.services.executeService('peanut.content::CreateTitle',request,
                function(serviceResponse: Peanut.IServiceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let response = <IContentItemResponse>serviceResponse.Value;
                        me.handleContentItemResponse(response);
                    }
                    else {
                        // alert('content not saved');
                    }
                }
            ).fail(function () {
                let trace = me.services.getErrorInformation();
                if (trace) {
                    console.error("Service call failed. Debug for error details. ");
                }
            }).always(() => {
                me.application.hideWaiter();
            });
        }

        newVersion = () => {
            let me = this;
            let request = {
                contentId : me.contentForm.contentId(),
                content : me.editor.getContent(),
            }
            me.application.showWaiter('Saving content.');
            me.services.executeService('peanut.content::SaveContent',request,
                function(serviceResponse: Peanut.IServiceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let result = <IVersionsListItem>serviceResponse.Value;
                        me.contentListForm.versions.unshift(result);
                        me.editor.setDirty(false);
                    }
                    else {
                        // alert('content not saved');
                    }
                }
            ).fail(function () {
                let trace = me.services.getErrorInformation();
                if (trace) {
                    console.error("Service call failed. Debug for error details. ");
                }
            }).always(() => {
                me.application.hideWaiter();
            });
        }
        finalVersion = () => {
            let me = this;
            let request = {
                contentId : me.contentForm.contentId(),
                content : me.editor.getContent(),
                final: true
            }
            me.application.showWaiter('Saving final version.');
            me.services.executeService('peanut.content::SaveContent',request,
                function(serviceResponse: Peanut.IServiceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let result = <IVersionsListItem>serviceResponse.Value;
                        me.contentListForm.versions.unshift(result);
                    }
                    else {
                        // alert('content not saved');
                    }
                }
            ).fail(function () {
                let trace = me.services.getErrorInformation();
                if (trace) {
                    console.error("Service call failed. Debug for error details. ");
                }
            }).always(() => {
                me.application.hideWaiter();
            });
        }


/*
        doFetch = () => {
            let me = this;
            // todo: service calls to fetch content

            me.owner().hideModal(me.fetchModalId());

            // test
            let content = 'Fetch content from server test.';
            me.editor.setContent(content);
        }
*/

        onTitleSelected = (title   :any) => {
            let me = this;
            me.contentForm.title(title);
        }

        newDocument(): void {
            let me = this;
            me.clearDocument();
            me.editor.setDirty(false)
        }

        openDocument(): void {
            let me = this;
            let request = {context: me.contentForm.context()};
           me.application.showWaiter('Loading content.');
            me.services.executeService('peanut.content::GetTitles',request,
                function(serviceResponse: Peanut.IServiceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let result = <IGetTitlesResponse>serviceResponse.Value;

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
                }
            ).fail(function () {
                let trace = me.services.getErrorInformation();
                if (trace) {
                    console.error("Service call failed. Debug for error details. ");
                }
            }).always(() => {
                me.application.hideWaiter();
            });

        }

        saveDocumentAs(): void {
            let me = this;
            me.clearDocument()
            // me.contentForm.contentId(0);
            me.owner().showModal(me.saveModalId())
        }

        saveDocument(): void {
            let me =  this;
            if (me.editor.isEmpty()) {
                me.editor.setDirty(false);
                return;
            }
            let contentId = me.contentForm.contentId();
            if (contentId == 0) {
                me.owner().showModal(me.saveModalId())
            }
            else {
                me.newVersion();
            }
        }

        handleContentItemResponse (response : IContentItemResponse) {
            let me = this;

            me.clearDocument()
            me.contentForm.contentId(response.id);
            me.contentForm.title(response.title);
            me.contentForm.description(response.description);
            me.contentForm.shared(response.shared);
            me.contentListForm.versions(response.versions || []);
        }
        loadContent = (item: ITitlesListItem) => {
            let me = this;
            me.application.showWaiter('Loading content.');
            me.services.executeService('peanut.content::GetContentItem',item.id,
                function(serviceResponse: Peanut.IServiceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let result = <IGetContentResponse>serviceResponse.Value;
                        me.handleContentItemResponse(result);
                        me.editor.setContent(result.content);
                        me.owner().hideModal(me.fetchModalId());
                    }
                    else {

                    }
                }
            ).fail(function () {
                let trace = me.services.getErrorInformation();
                if (trace) {
                    console.error("Service call failed. Debug for error details. ");
                }
            }).always(() => {
                me.application.hideWaiter();
            });
        }
        loadVersion = (item: IVersionsListItem) => {
            let me = this;
            me.application.showWaiter('Loading version.');
            me.services.executeService('peanut.content::GetContent',{versionId: item.id},
                function(serviceResponse: Peanut.IServiceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let result = <string>serviceResponse.Value;
                        me.editor.setContent(result);
                        me.owner().hideModal(me.fetchModalId());
                    }
                    else {

                    }
                }
            ).fail(function () {
                let trace = me.services.getErrorInformation();
                if (trace) {
                    console.error("Service call failed. Debug for error details. ");
                }
            }).always(() => {
                me.application.hideWaiter();
            });
        }

        showAuthorTab = () => {
            this.contentListForm.tab('authors');
        }
        showSharedTab = () => {
            this.contentListForm.tab('shared');
        }
        showVersionsTab = () => {
            this.contentListForm.tab('versions');
        }
        showAuthorsTab = () => {
            this.contentListForm.tab('authors');
        }
        hideContentTab = () => {
            this.contentListForm.tab('none');
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

        showConfirmModal = (action: string, message: string, currentModal: string = null) => {
            let me = this;
            me.confirmAction = action;
            me.restoreModlalId = currentModal;
            me.confirmMessage(message);
            if (currentModal) {
                me.owner().hideModal(currentModal);
            }
            me.owner().showModal('confirm-modal');
        }

        cancelConfirmModal = () => {
            let me = this;
            me.owner().hideModal('confirm-modal');
            if (me.restoreModlalId) {
                me.owner().showModal(me.restoreModlalId);
            }
            me.restoreModlalId = null;
        }

        confirmClearVersions = () => {
            let me = this;
            me.showConfirmModal('clear-versions','Are you sure you want to clear all previous versions?',me.fetchModalId());
        }

        clearVersions = () => {
            let me = this;
            me.owner().hideModal('confirm-modal');
            me.owner().showWaiter('Clering past versions.')
            me.services.executeService('peanut.content::ClearVersions',{contentId: me.contentForm.contentId()},
                function(serviceResponse: Peanut.IServiceResponse) {
                if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                    let versions = <IVersionsListItem[]>serviceResponse.Value;
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
        }

        doDeleteContent = (id) => {
            let me = this;
            me.owner().showWaiter('Deleting content...')
            me.services.executeService('peanut.content::RemoveContent',{
                        contentId: id, context:
                        me.contentForm.context()},
                function(serviceResponse: Peanut.IServiceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let response = <IGetTitlesResponse>serviceResponse.Value;
                        if (id == me.contentForm.contentId()) {
                            me.clearDocument();
                            me.editor.setContent('')
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
        }
        deleteContent = (item: ITitlesListItem) => {
            let me = this;
            me.showConfirmModal('delete-title:'+item.id,
                'Are you sure you want to delete content for '+item.title+ '?',
                me.fetchModalId());
        }


    }
}