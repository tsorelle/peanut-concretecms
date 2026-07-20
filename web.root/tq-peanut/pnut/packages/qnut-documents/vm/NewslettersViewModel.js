var QnutDocuments;
(function (QnutDocuments) {
    class NewslettersViewModel extends Peanut.ViewModelBase {
        constructor() {
            super(...arguments);
            this.canSend = ko.observable(false);
            this.tab = ko.observable('list');
            this.documents = [];
            this.itemsPerPage = 12;
            this.newsletterList = ko.observableArray([]);
            this.resultCount = ko.observable(0);
            this.pageRange = ko.observable('');
            this.recordCount = ko.observable(0);
            this.currentPage = ko.observable(1);
            this.maxPages = ko.observable(2);
            this.newDocumentHref = ko.observable('#');
            this.docViewLinkTitle = ko.observable('View document');
            this.docDownloadLinkTitle = ko.observable('Download document');
            this.addendaLinkTitle = ko.observable('View addenda documents');
            this.docEditLinkTitle = ko.observable('Edit document information');
            this.fileSelectError = ko.observable(false);
            this.bodyError = ko.observable(false);
            this.showMessage = ko.observable(false);
            this.publish = ko.observable(true);
            this.documentId = ko.observable(0);
            this.queueLink = ko.observable('');
            this.sentCount = ko.observable(0);
            this.sendTest = ko.observable(false);
            this.fileSelected = ko.observable(false);
            this.whatIsThis = ko.observable("What's this?");
            this.overwriteSubscription = null;
            this.queued = ko.observable(false);
            this.issueYear = 2019;
            this.issueMonth = 1;
            this.nextIssue = 0;
            this.issueText = ko.observable('');
            this.oldIssue = ko.observable(false);
            this.overwrite = ko.observable(false);
            this.getNewsletters = (pageNumber, doneFunction) => {
                let me = this;
                me.application.hideServiceMessages();
                me.showLoadWaiter();
                let response = null;
                me.services.executeService('peanut.qnut-documents::GetNewsletterList', { page: pageNumber, itemsPerPage: me.itemsPerPage }, function (serviceResponse) {
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
            this.initEditor = (selector) => {
                let host = Peanut.Helper.getHostUrl() + '/';
                tinymce.init({
                    selector: selector,
                    toolbar: "undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link | image | code",
                    plugins: "image imagetools link lists code paste",
                    convert_urls: false,
                    remove_script_host: false,
                    document_base_url: host,
                    branding: false,
                    paste_word_valid_elements: "b,strong,i,em,h1,h2,h3,p,a,ul,li"
                });
            };
            this.setMessageContent = (text) => {
                tinymce.get('messagehtml').setContent(text);
            };
            this.getMessageContent = () => {
                tinymce.triggerSave();
                return this.getInputElementValue('#messagehtml');
            };
            this.onCancelSend = () => {
                this.showMessage(false);
            };
            this.updateIssueObservables = () => {
                this.issueText(this.months[this.issueMonth - 1] + ' ' + this.issueYear);
                let currentIssue = (this.issueYear * 12) + this.issueMonth;
                this.oldIssue(currentIssue < this.nextIssue);
                this.overwrite(false);
            };
            this.onNextIssue = () => {
                this.clearFile();
                this.issueMonth++;
                if (this.issueMonth > 12) {
                    this.issueYear++;
                    this.issueMonth = 1;
                }
                this.updateIssueObservables();
            };
            this.onPrevIssue = () => {
                this.clearFile();
                this.issueMonth--;
                if (this.issueMonth < 1) {
                    this.issueYear--;
                    this.issueMonth = 12;
                }
                this.updateIssueObservables();
            };
            this.showListTab = () => {
                this.tab('list');
                this.jumpEnd();
            };
            this.showSendTab = () => {
                this.showMessage(false);
                this.bodyError(false);
                this.tab('send');
            };
            this.downloadDocument = (doc) => {
                window.location.href = doc.uri + '/download';
            };
            this.changePage = (move) => {
                let current = this.currentPage() + move;
                if (current < 1) {
                    current = 1;
                }
                this.currentPage(current);
                this.getNewsletters(current);
            };
            this.jumpStart = () => {
                let current = 1;
                this.currentPage(current);
                this.getNewsletters(current);
            };
            this.jumpEnd = () => {
                let current = this.maxPages();
                this.currentPage(current);
                this.getNewsletters(current);
            };
            this.resendNewsletter = () => {
                this.publish(true);
                this.postNewsletter();
            };
            this.postNewsletter = () => {
                let me = this;
                let newFile = true;
                if (me.oldIssue()) {
                    newFile = me.overwrite();
                }
                let request = {
                    issueDate: {
                        month: me.issueMonth,
                        year: me.issueYear,
                        day: 1
                    },
                    newfile: newFile,
                    publish: me.publish()
                };
                let files = Peanut.Helper.getSelectedFiles('#newsletterFile');
                me.application.hideServiceMessages();
                me.application.showWaiter('Posting newsletter...');
                if (files && files.length) {
                    me.services.postForm('peanut.qnut-documents::PostNewsletter', request, files, null, me.handlePostNewsletterResponse).fail(function () {
                        let trace = me.services.getErrorInformation();
                        me.application.hideWaiter();
                    });
                }
                else {
                    me.services.executeService('peanut.qnut-documents::PostNewsletter', request, me.handlePostNewsletterResponse).fail(function () {
                        let trace = me.services.getErrorInformation();
                        me.application.hideWaiter();
                    });
                }
            };
            this.handlePostNewsletterResponse = (serviceResponse) => {
                let me = this;
                if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                    me.application.hideWaiter();
                    let response = serviceResponse.Value;
                    me.setMessageContent(response.messageText);
                    me.documentId(response.documentId);
                    me.bodyError(false);
                    me.showMessage(true);
                }
            };
            this.sendNewsletter = () => {
                let me = this;
                let text = me.getMessageContent();
                if (!text) {
                    me.bodyError(true);
                    return;
                }
                let request = {
                    issueDate: me.issueText(),
                    messageText: text,
                    sendTest: me.sendTest() ? 1 : 0,
                    documentId: me.documentId()
                };
                me.bodyError(false);
                me.application.hideServiceMessages();
                me.application.showWaiter('Queing newsletter list...');
                me.services.executeService('peanut.qnut-documents::SendNewsletter', request, function (serviceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        me.application.hideWaiter();
                        let response = serviceResponse.Value;
                        me.sentCount(response.sentCount);
                        if (!me.sendTest()) {
                            me.tab('send-result');
                        }
                    }
                }).fail(function () {
                    let trace = me.services.getErrorInformation();
                    me.application.hideWaiter();
                });
            };
            this.onOverwriteCheck = (checked) => {
                if (this.oldIssue() && !checked) {
                    this.clearFile();
                }
            };
            this.clearFile = () => {
                jQuery('#newsletterFile').val('');
                this.fileSelected(false);
            };
            this.onFileSelect = (arg) => {
                let fn = this.getInputElementValue('newsletterFile');
                if (fn) {
                    let extension = fn.substring((fn.lastIndexOf('.') + 1)).toLowerCase();
                    if (extension !== 'pdf') {
                        alert('PDF Files only. Try again.');
                        this.setElementValue('newsletterFile', '');
                        this.fileSelected(false);
                        return;
                    }
                }
                this.fileSelected(!!fn);
            };
        }
        init(successFunction) {
            let me = this;
            Peanut.logger.write('Newsletters Init');
            me.application.loadResources([
                '@lib:tinymce',
                '@pnut/ViewModelHelpers.js'
            ], () => {
                me.initEditor('#messagehtml');
                const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
                const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl));
                me.application.registerComponents(['@pnut/pager', '@pnut/clean-html'], () => {
                    me.getNewsletters(0, (response) => {
                        if (response !== null) {
                            me.addTranslations(response.translations);
                            me.canSend(response.canSend);
                            me.docViewLinkTitle(me.translate('document-icon-label-view'));
                            me.docDownloadLinkTitle(me.translate('document-icon-label-download'));
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
    }
    QnutDocuments.NewslettersViewModel = NewslettersViewModel;
})(QnutDocuments || (QnutDocuments = {}));
//# sourceMappingURL=NewslettersViewModel.js.map