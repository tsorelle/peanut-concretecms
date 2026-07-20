<?php

namespace Peanut\users\services;

use Peanut\users\AccountManager;
use Tops\sys\TUser;

class ChangePasswordCommand extends \Tops\services\TServiceCommand
{

    protected function run()
    {
        $request = $this->getRequest();
        if (empty($request->accountId)) {
            $this->addErrorMessage('No account id received');
            return;
        }
        if (empty($request->new)) {
            $this->addErrorMessage('No password received');
            return;
        }
        $id = $request->accountId;
        $pwd = $request->new;
        $target = TUser::getById($id);
        $user = TUser::getCurrent()->getUserName();

        $result =  (new AccountManager())->changePassword($id,$pwd,$user);
        if ($result === true) {
            $this->addInfoMessage('Password changed for user '.$target->getFullName());
        }
        else {
            $this->addErrorMessage($result);
        }
    }
}