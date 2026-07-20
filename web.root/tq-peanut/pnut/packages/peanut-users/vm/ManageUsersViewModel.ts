/// <reference path="../../../../pnut/core/ViewModelBase.ts" />
/// <reference path='../../../../typings/knockout/index.d.ts' />
/// <reference path='../../../../pnut/core/Peanut.d.ts' />

namespace PeanutUsers {

    interface IUserManagementProfile {
        profileId: any;
        accountId: any;
        username: string;
        fullname: string;
        email: string;
        active: any;
        roles: number[]
    }

    interface IGetUserListResponse {
        users: IUserManagementProfile[];
        roles: Peanut.ILookupItem[];
    }

    class userFormObservable {
        constructor(roles: Peanut.ILookupItem[]) {
            this.rolesController = new Peanut.multiSelectObservable(roles);
        }
        username = ko.observable('');
        password = ko.observable('')
        fullname= ko.observable('');
        email = ko.observable('');
        errorMessage = ko.observable('')
        rolesController : Peanut.multiSelectObservable;
        accountId = ko.observable(0);
        profileId = ko.observable(0);
        pwdvisible = ko.observable(false);
        active = ko.observable(true);

        assign = (user: IUserManagementProfile) => {
            this.accountId(user.accountId)
            this.profileId(user.profileId);
            this.username(user.username);
            this.fullname(user.fullname);
            this.email(user.email);
            this.rolesController.setValues(user.roles);
            this.errorMessage('');
            this.pwdvisible(false);
            this.active(user.active == 1)
        }

        clear = () => {
            this.accountId(0);
            this.profileId(0);
            this.username('');
            this.fullname('');
            this.email('');
            this.password('')
            this.rolesController.setValues([])
            this.errorMessage('');
            this.accountId(0);
            this.pwdvisible(false);
            this.active(true);
        }

        getUser = () => {
            let result = {
                accountId: this.accountId(),
                profileId: this.profileId(),
                username: this.username().trim(),
                fullname: this.fullname().trim(),
                email : this.email().trim(),
                roles: this.rolesController.getValues(),
                password: this.password().trim(),
                active: this.active() ? 1 : 0
            }
            this.errorMessage('');
            if (!result.username) {
                this.errorMessage('Username is required');
                return false;
            }
            if (result.accountId == 0 && result.password.length < 5) {
                this.errorMessage('Password must be 5 or more characters long');
                return false;
            }
            if (!result.fullname) {
                this.errorMessage('Full name is required.');
                return false;
            }
            if (!Peanut.Helper.ValidateEmail(result.email)) {
                this.errorMessage('Invalid email address');
                return false;
            }
            return result;
        }
    }

    export class ManageUsersViewModel extends Peanut.ViewModelBase {
        // observables
        users = ko.observableArray<IUserManagementProfile>([]);
        roles : Peanut.ILookupItem[] = [];
        userPanel = ko.observable('blank'); // values: blank,view,edit
        showNewUserLink = ko.observable(true);

        userForm: userFormObservable;

        selectedUser: IUserManagementProfile;

        init(successFunction?: () => void) {
            let me = this;
            Peanut.logger.write('ManageUsers Init');
            me.application.registerComponents('@pnut/selected-list,@pnut/multi-select,@pnut/change-password', () => {
                me.application.loadResources([
                    '@pnut/multiSelectObservable',
                    '@pnut/ViewModelHelpers'
                ], () => {
                    me.services.executeService('peanut.users::GetUserList',null,
                        function(serviceResponse: Peanut.IServiceResponse) {
                            if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                                let response = <IGetUserListResponse>serviceResponse.Value;
                                me.userForm = new userFormObservable(response.roles);
                                me.refreshUsers(response.users);
                                if (response.users.length === 0) {
                                    me.newUser();
                                }
                                me.bindDefaultSection();
                                successFunction();
                            }
                        }
                    ).fail(function () {
                        me.services.getErrorInformation();
                        me.application.hideWaiter();
                    });

                });
            });
        }

        refreshUsers = (userlist: IUserManagementProfile[]) => {
            this.users(userlist);
            if (userlist.length) {
                this.selectUser(userlist[0])
            }
            else {
                this.selectedUser = null;
                this.userPanel('blank')
                this.showNewUserLink(true);
            }
        }

        selectUser = (selected: IUserManagementProfile) => {
            this.selectedUser = selected;
            this.userForm.assign(selected);
            this.userPanel('view');
            this.showNewUserLink(true);
        }

        cancelChanges = () => {
            if (this.selectedUser) {
                this.userForm.assign(this.selectedUser);
                this.userPanel('view');
            }
            else {
                this.userForm.clear();
                this.userPanel('blank');
            }
        }

        newUser = () => {
            this.selectedUser = null;
            this.userForm.clear();
            this.userPanel('edit');
            this.showNewUserLink(false);
        }

        saveUserChanges = () => {
            let me = this;

            let request = me.userForm.getUser();
            if (request === false) {
                return;
            }

            me.services.executeService('peanut.users::UpdateUser',request,
                function(serviceResponse: Peanut.IServiceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let response = <IGetUserListResponse>serviceResponse.Value;
                        me.refreshUsers(response.users);
                        if (response.users.length === 0) {
                            me.newUser();
                        }
                    }
                }
            ).fail(function () {
                me.services.getErrorInformation();
                me.application.hideWaiter();
            });

        }

        editUser = () => {
            this.userPanel('edit');
        }

        changePassword = () => {
            this.userForm.errorMessage('');
            this.userForm.password('');
            this.userPanel('password');
        }

        resetUserPassword = () => {
            let me = this;

            this.userForm.errorMessage('');
            let request = {
                accountId: this.selectedUser.accountId,
                new : this.userForm.password().trim()
            }
            if (request.new.length < 6) {
                this.userForm.errorMessage('Password must be at least 5 characters long.')
                return;
            }

            this.userPanel('wait');
            me.services.executeService('peanut.users::ChangePassword',request,
                function(serviceResponse: Peanut.IServiceResponse) {
                    let panel = serviceResponse.Result == Peanut.serviceResultSuccess ?
                        'view' : 'password';
                    me.userPanel(panel);
                }
            ).fail(function () {
                me.services.getErrorInformation();
                me.userPanel('blank');
            }).always(() => {
                this.userForm.password('');
            });

        }

        changeUserName = () => {
            this.userForm.errorMessage('');
            this.userPanel('username');
        }

        updateUserName = () => {
            let me = this;

            this.userForm.errorMessage('');
            let request = {
                accountId: this.selectedUser.accountId,
                new : this.userForm.username().trim()
            }
            if (request.new.length < 6) {
                this.userForm.errorMessage('Password must be at least 5 characters long.')
                return;
            }

            this.userPanel('wait');
            me.services.executeService('peanut.users::ChangeUserName',request,
                function(serviceResponse: Peanut.IServiceResponse) {
                    if (serviceResponse.Result == Peanut.serviceResultSuccess) {
                        let response = serviceResponse.Value;
                        me.refreshUsers(response.users);
                    }
                    else {
                        me.userPanel('username');
                    }
                }
            ).fail(function () {
                me.services.getErrorInformation();
                me.userPanel('blank');
            }).always(() => {
                this.userForm.password('');
            });
        }

    }
}
