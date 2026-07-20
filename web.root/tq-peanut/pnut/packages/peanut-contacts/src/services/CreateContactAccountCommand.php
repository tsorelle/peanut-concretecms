<?php

namespace Peanut\contacts\services;

use Peanut\contacts\db\ContactsManager;
use Peanut\users\AccountManager;

class CreateContactAccountCommand extends \Tops\services\TServiceCommand
{

    protected function run()
    {
        $request = $this->getRequest();
        if (empty($request)) {
            $this->addErrorMessage('No request received');
            return;
        }
        $username = $request->username ?? null;
        if (empty($username)) {
            $this->addErrorMessage('Username required for new account');
            return;
        }

        $roles = $request->roles ?? null;
        if (!is_array($roles)) {
            $this->addErrorMessage('Role list not received');
            return;
        }

        $password = $request->password ?? null;
        if (empty($password)) {
            $this->addErrorMessage('Password required for new account');
            return;
        }
        $contactId = $request->contactId ?? null;

        $accountManager = new AccountManager();
        $account = $accountManager->addAccount($request->username, $request->password,null,$request->roles);
        if ($account->errorCode ?? false) {
            $this->addErrorMessage($account->errorCode);
            return;
        }

        $contactManager = new ContactsManager();
        $updated = $contactManager->setContactSiteAccount($contactId,$account->accountId);
        if (!$updated) {
            $this->addErrorMessage('Account created but failed to update contact.');
            return;
        }

        $this->setReturnValue($account->accountId);
    }
}