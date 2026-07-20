<?php

namespace Peanut\PeanutPermissions\services;

use Tops\services\TServiceCommand;
use Tops\sys\TPermissionsManager;

class AddRoleCommand extends TServiceCommand
{

    protected function run()
    {
        $roleName = $this->getRequest();
        if (empty($roleName)) {
            $this->addErrorMessage('Role name cannot be empty');
            return;
        }
        $manager= TPermissionsManager::getPermissionManager();
        $manager->addRole($roleName);
        $this->setReturnValue($manager->getRoles());
    }
}