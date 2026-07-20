<?php

namespace Peanut\users\services;

use Peanut\users\AccountManager;

class UpdateUserCommand extends \Tops\services\TServiceCommand
{
    private function returnUserList($manager) {
        $response = new \stdClass();
        $response->users = $manager->getUserList();
        $this->setReturnValue($response);

    }
    protected function run()
    {
        $request = $this->getRequest();
        $manager = new AccountManager();
        $accountId = $request->accountId ?? 0;
        $active = $request->active ?? 1;
        if ($accountId != 0 && ($active === 0 || $active === '0')) {
            $manager->removeAccount($accountId);
            $this->returnUserList($manager);
            return;
        }

        $fullname = $request->fullname ?? null;
        if (empty($fullname)) {
            $this->addErrorMessage('Full name is required for an account');
            return;
        }
        $email = $request->email ?? null;
        if (empty($email)) {
            $this->addErrorMessage('Email is required for an account');
            return;
        }
        $roles = $request->roles ?? null;
        if (!is_array($roles)) {
            $this->addErrorMessage('Role list not received');
            return;
        }

        if ($accountId == 0) {
            if (empty($request->password)) {
                $this->addErrorMessage('Password required for new account');
                return;
            }
            $username = $request->username ?? null;
            if (empty($username)) {
                $this->addErrorMessage('Username required for new account');
                return;
            }
            $registered = $manager->registerSiteUser($username,$request->password,$fullname,$email,$roles);
            if ($registered->errorCode !== false) {
                $this->addErrorMessage("Account creation error: $registered->errorCode");
                return;
            }
        }
        else {
            $active = $request->active ?? 1;
            if ($active === 0 || $active === '0') {
                $manager->removeAccount($accountId);
            }
            else {
                $manager->updateUser($accountId, $fullname, $email, $roles);
            }
        }

        $this->returnUserList($manager);

    }
}