<?php

namespace Peanut\users\services;

use Peanut\users\AccountManager;
use Tops\sys\TUser;

class ChangeUserNameCommand extends \Tops\services\TServiceCommand
{

    protected function run()
    {
        $request = $this->getRequest();
        if (empty($request->accountId)) {
            $this->addErrorMessage('No account id received');
            return;
        }
        if (empty($request->new)) {
            $this->addErrorMessage('No username received');
            return;
        }
        $id = $request->accountId;
        $username = $request->new;
        $target = TUser::getById($id);
        $user = TUser::getCurrent()->getUserName();
        $manager = new AccountManager();
        $result =  $manager->changeUserName($id,$username,$user);
        if ($result === true) {
            $this->addInfoMessage('Username changed for user '.$target->getFullName());
            $response = new \stdClass();
            $response->users = $manager->getUserList();
            $this->setReturnValue($response);
        }
        else {
            $this->addErrorMessage($result);
        }
    }
}