/// <reference path="../../../../pnut/core/ViewModelBase.ts" />
/// <reference path='../../../../typings/knockout/knockout.d.ts' />
/// <reference path='../../../../pnut/core/Peanut.d.ts' />
/// <reference path='../../qnut-documents/js/document.d.ts' />
/// <reference path='../../../../pnut/js/ViewModelHelpers.ts' />

namespace QnutDocuments {

    import INameValuePair = Peanut.INameValuePair;

    interface INewsletterSearchResult {
        id: any,
        title: string,
        addenda: any;
        publicationDate: string,
        uri: string,
        editUrl: string,
        addendaUri: string
    }

    interface IGetNewslettersResponse {
        documents : INewsletterSearchResult[];
    }

    interface IDateStructure {
        month: any;
        year: any;
        day: any;
    }
    
    interface INewslettersInitResponse extends  IGetNewslettersResponse{
        translations : any[];
        recordCount: any;
        canSend: any;
        nextIssue: IDateStructure;
        months: string[];
        queueLink: string;
    }

    interface IPostNewsletterRequest {
        issueDate: IDateStructure;
        newfile: any;
        publish: boolean;
    }

    interface IPostNewsletterResponse {
        messageText: string;
        documentId: any;
    }

    interface ISendNewsletterRequest {
        messageText: string;
        issueDate: string;
        sendTest: any;
        documentId: any;
    }

    interface ISendNewsletterResponse {
        sentCount: any;
    }

    export class NewslettersViewModel extends Peanut.ViewModelBase {
        // observables
        canSend = ko.observable(false);
        tab = ko.observable('list');

        documents : IDocumentSearchResult[] = [];
        itemsPerPage = 12;
        newsletterList = ko.observableArray<INewsletterSearchResult>([]);
        resultCount = ko.observable(0);


        pageRange = ko.observable('');
        recordCount = ko.observable(0);
        currentPage = ko.observable(1);
        maxPages = ko.observable(2);
        newDocumentHref = ko.observable('#');
        docViewLinkTitle = ko.observable('View document');
        docDownloadLinkTitle = ko.observable('Download document');
        addendaLinkTitle = ko.observable('View addenda documents');
        docEditLinkTitle = ko.observable('Edit document information');
        fileSelectError = ko.observable(false);
        bodyError = ko.observable(false);
        showMessage=ko.observable(false);
        publish = ko.observable(true);
        documentId = ko.observable(0);
        queueLink = ko.observable('');
        sentCount = ko.observable(0);
        sendTest = ko.observable(false);
        fileSelected = ko.observable(false);
        whatIsThis = ko.observable("What's this?"); // todo: translate

        overwriteSubscription : KnockoutSubscription = null;
        queued = ko.observable(false);
        issueYear = 2019;
        issueMonth = 1;
        nextIssue = 0;
        issueText = ko.observable('');
        oldIssue = ko.observable(false);
        overwrite = ko.observable(false);
        months : string[]; 


        init(successFunction?: () => void) {
            let me = this;
            Peanut.logger.write('Newsletters Init');
            me.application.loadResources([
                '@lib:tinymce',
                '@pnut/ViewModelHelpers.js'
            ], () => {
                me.initEditor('#messagehtml');
                // jQuery('[data-bs-toggle="popover"]').popover();
                const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]')
                const popoverList = [...popoverTriggerList].map(
                    popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl))

/*
                const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
                var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
                    return new bootstrap.Popover(popoverTriggerEl)
                })
*/

                me.application.registerComponents(['@pnut/pager','@pnut/clean-html'], () => {
                me.getNewsletters(0,(response: INewslettersInitResponse) => {
                    if (response !== null) {
                        me.addTranslations(response.translations);
                        me.canSend(response.canSend);
                        // me.searchResultsFormat = me.translate('document-search-found');
                        me.docViewLinkTitle(me.translate('document-icon-label-view'));
                        me.docDownloadLinkTitle(me.translate('document-icon-label-download'));
                        // me.newDocumentHref(response.newDocumentHref);
                        let max = Math.ceil(response.recordCount / me.itemsPerPage);
                        let pageCount = max % this.itemsPerPage;
                        me.maxPages(Math.ceil(response.recordCount / me.itemsPerPage));
                        this.currentPage(max);
                        this.months = response.months;
                        this.issueYear = response.nextIssue.year;
                        this.issueMonth = response.nextIssue.month;
                        this.nextIssue = (response.nextIssue.year * 12) + response.nextIssue.month;
                        this.queueLink(response.queueLink);
                        this.updateIssueObservables();
                        this.overwriteSubscription = this.overwrite.subscribe(this.onOverwriteCheck);
                    }
                    me.bindDefaultSection();
                    
                });
            });
            });

            if (successFunction) {
                successFunction();
            }
        }

        getNewsletters = (pageNumber: any, doneFunction?: (serviceResponse: IGetNewslettersResponse) => void) =>  {
            let me = this;
            me.application.hideServiceMessages();
            me.showLoadWaiter();
            let response : IGetNewslettersResponse = null;
            me.services.executeService('peanut.qnut-documents::GetNewsletterList',{page: pageNumber,itemsPerPage: me.itemsPerPage},
                function(serviceResponse: Peanut.IServiceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        response = serviceResponse.Value;
                        me.newsletterList(response.documents);
                    }
                    else {
                    }
                })
                .fail(function () {
                    let trace = me.services.getErrorInformation();
                })
                .always(function () {
                    me.application.hideWaiter();
                    if (doneFunction) {
                        doneFunction(response);
                    }
                });
        };

        initEditor = (selector: string) => {
            let host = Peanut.Helper.getHostUrl() + '/';

            tinymce.init({
                selector: selector,
                toolbar: "undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link | image | code",
                plugins: "image imagetools link lists code paste",
                // default_link_target: "_blank",
                // relative_urls : false,
                // absolute_urls : false,
                convert_urls: false,
                remove_script_host : false,
                document_base_url : host,
                branding: false,
                paste_word_valid_elements: "b,strong,i,em,h1,h2,h3,p,a,ul,li"
            });

        };

        setMessageContent = (text: string) => {
            tinymce.get('messagehtml').setContent(text);
        };

        getMessageContent = () => {
            tinymce.triggerSave();
            return this.getInputElementValue('#messagehtml')
            // return jQuery('#messagehtml').val();
        };

        onCancelSend = () => {
            this.showMessage(false);
        };

        updateIssueObservables = () => {
            this.issueText(this.months[this.issueMonth - 1] + ' ' +this.issueYear);
            let currentIssue = (this.issueYear * 12) + this.issueMonth;
            this.oldIssue(currentIssue < this.nextIssue);
            this.overwrite(false);
        };
        
        onNextIssue = () => {
            this.clearFile();
            this.issueMonth++;
            if (this.issueMonth > 12) {
                this.issueYear++;
                this.issueMonth = 1;
            }
            this.updateIssueObservables();
        };

        onPrevIssue = () => {
            this.clearFile();
            this.issueMonth--;
            if (this.issueMonth < 1) {
                this.issueYear--;
                this.issueMonth = 12;
            }
            this.updateIssueObservables();
        };

        showListTab = () => {
            this.tab('list');
            this.jumpEnd();
        };

        showSendTab = () => {
            this.showMessage(false);
            this.bodyError(false);
            this.tab('send');
        };

        downloadDocument = (doc : IDocumentSearchResult) => {
            window.location.href=doc.uri + '/download';
        };

        changePage = (move: number) => {
            let current = this.currentPage() + move;
            if (current < 1) {
                current = 1;
            }
            this.currentPage(current);
            this.getNewsletters(current);
        };

        jumpStart = () => {
            let current = 1;
            this.currentPage(current);
            this.getNewsletters(current);
        };

        jumpEnd = () => {
            let current = this.maxPages();
            this.currentPage(current);
            this.getNewsletters(current);
        };

        resendNewsletter = () => {
            this.publish(true);
            this.postNewsletter();
        };

        postNewsletter = () => {
            let me = this;
            let newFile = true;
            if (me.oldIssue()) {
                newFile = me.overwrite();
            }
            let request : IPostNewsletterRequest = {
                issueDate: {
                    month: me.issueMonth,
          			year: me.issueYear,
           			day: 1
                },
                newfile : newFile,
                publish: me.publish()
            };

            let files = Peanut.Helper.getSelectedFiles('#newsletterFile');

            me.application.hideServiceMessages();
            me.application.showWaiter('Posting newsletter...');

            if (files && files.length) {
                me.services.postForm( 'peanut.qnut-documents::PostNewsletter',request,files,null,
                    me.handlePostNewsletterResponse
                ).fail(function () {
                    let trace = me.services.getErrorInformation();
                    me.application.hideWaiter();
                });
            }
            else {
                me.services.executeService('peanut.qnut-documents::PostNewsletter',request,
                    me.handlePostNewsletterResponse
                ).fail(function () {
                    let trace = me.services.getErrorInformation();
                    me.application.hideWaiter();
                });

            }
        };

        handlePostNewsletterResponse = (serviceResponse: Peanut.IServiceResponse) => {
            let me = this;
            if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                me.application.hideWaiter();
                let response = <IPostNewsletterResponse>serviceResponse.Value;
                me.setMessageContent(response.messageText);
                me.documentId(response.documentId);
                me.bodyError(false);
                me.showMessage(true);
            }

        };

        sendNewsletter = () => {
            let me = this;

            let text = me.getMessageContent();
            if (!text) {
                me.bodyError(true);
                return;
            }

            let request : ISendNewsletterRequest = {
                issueDate: me.issueText(),
                messageText: text,
                sendTest: me.sendTest() ? 1 : 0,
                documentId: me.documentId()
            };

            me.bodyError(false);
            me.application.hideServiceMessages();
            me.application.showWaiter('Queing newsletter list...');

            me.services.executeService('peanut.qnut-documents::SendNewsletter',request,
                function(serviceResponse: Peanut.IServiceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        me.application.hideWaiter();
                        let response = <ISendNewsletterResponse>serviceResponse.Value;
                        me.sentCount(response.sentCount);
                        if (!me.sendTest()) {
                            me.tab('send-result');
                        }
                    }
                }
            ).fail(function () {
                let trace = me.services.getErrorInformation();
                me.application.hideWaiter();
            });
        };

        onOverwriteCheck = (checked: any) => {
            if (this.oldIssue() && !checked) {
                this.clearFile();
            }
        };

        clearFile = () => {
            jQuery('#newsletterFile').val('');
            this.fileSelected(false);
        };

        onFileSelect = (arg: any) => {
            // let fn = jQuery('#newsletterFile').val();
            let fn = this.getInputElementValue('newsletterFile');
            if (fn) {
                let extension = fn.substring( (fn.lastIndexOf('.') +1) ).toLowerCase();
                if (extension !== 'pdf') {
                    alert('PDF Files only. Try again.');
                    // jQuery('#newsletterFile').val('');
                    this.setElementValue('newsletterFile','')
                    this.fileSelected(false);
                    return;
                }
            }
            this.fileSelected(!!fn);
        };
    }
}
