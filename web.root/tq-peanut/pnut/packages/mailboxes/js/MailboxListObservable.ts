namespace Mailboxes {
    import ViewModelBase = Peanut.ViewModelBase;
    import IPeanutClient = Peanut.IPeanutClient;
    import ServiceBroker = Peanut.ServiceBroker;
    import mailBox = Peanut.mailBox;

    export class MailboxListObservable {
        list = ko.observableArray<IMailBox>([]);
        private owner : ViewModelBase;
        private application: IPeanutClient;
        private services: ServiceBroker;
        private callbacks = [];
        private subscriptions = [];

        constructor(client: ViewModelBase) {
            let me = this;
            me.application = client.getApplication();
            me.services = client.getServices();
            me.owner = client;
        }

        subscribe(callback: (mailboxes: IMailBox[]) => void) {
            let me = this;
            me.callbacks.push(callback);
            let subscription = me.list.subscribe(callback);
            me.subscriptions.push(subscription);
        }

        suspendSubscriptions() {
            let me = this;
            for(let i=0;i<me.subscriptions.length; i++) {
                me.subscriptions[i].dispose();
            }
            me.subscriptions = [];
        }

        restoreSubscriptions() {
            let me = this;
            for(let i=0;i<me.callbacks.length; i++) {
                let subscription = me.list.subscribe(me.callbacks[i]);
                me.subscriptions.push(subscription);
            }
        }

        downloadMailboxList = (all = true, translations = false,doneFunction?: () => void) => {
            let me = this;
            let request = {
                filter: all ? 'all' : false,
                translations: translations,
                context : me.owner.getVmContext()
            };

            me.application.hideServiceMessages();
            let translated = (me.owner.translate('mailbox-entity-plural') !== 'mailbox-entity-plural');
            if  (translated) {
                me.owner.showActionWaiter( 'load','mailbox-entity-plural');
            }

            me.services.executeService('peanut.Mailboxes::GetMailboxList',request,
                function(serviceResponse: Peanut.IServiceResponse) {
                    if (translated) {
                        me.application.hideWaiter();
                    }
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let response = <IGetMailboxesResponse>serviceResponse.Value;
                        me.owner.addTranslations(response.translations);
                        me.setMailboxes(response.list)
                    }
                }
            ).fail(function () {
                let trace = me.services.getErrorInformation();
            }).always(function() {
                if (translated) {
                    me.application.hideWaiter();
                }
                if (doneFunction) {
                    doneFunction();
                }
            });
        };

        getUpdatedMailboxList =  (doneFunction?: () => void) => {
            this.downloadMailboxList(true,false);
        };

        refreshList = (doneFunction?: () => void) => {
            let me = this;
            let list = me.list();
            me.suspendSubscriptions();
            me.list([]);
            me.restoreSubscriptions();
            me.list(list); // reassign to trigger subscriptions.
            doneFunction();
        };

        getMailboxList = (doneFunction?: () => void) => {
            let me = this;
            if (me.list().length == 0) {
                me.downloadMailboxList(true,false,doneFunction);
            }
            else {
                me.refreshList(doneFunction);
            }
        };

        getMailboxListWithTranslations = (doneFunction?: () => void) => {
            this.downloadMailboxList(true,false,doneFunction);

        };

        setMailboxes = (mailboxes: IMailBox[]) =>  {
            let me = this;
            let list = Peanut.Helper.SortByAlpha(mailboxes,'displaytext');
            me.list(list);
        };

    }

}