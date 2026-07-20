<?php

namespace Peanut\users\services;

use Peanut\users\AccountManager;

class GetUserListCommand extends \Tops\services\TServiceCommand
{

    public function __construct()
    {
        $this->addAuthorizedRole('administrator');
    }

    protected function run()
    {
        $manager = new AccountManager();
        $response = new \stdClass();
        $response->roles = $manager->getRoles();
        $response->users = $manager->getUserList();

        $this->setReturnValue($response);
    }
}