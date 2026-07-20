<?php

namespace Peanut\PeanutPermissions\services;

use Tops\services\TServiceCommand;
use Tops\sys\TPermissionsManager;

class InitializeRolesCommand extends TServiceCommand
{

    private $roles;
    /**
     * @var $manager TPermissionsManager
     */
    private $manager;
    private function findRole($key)
    {
        foreach ($this->roles as $role) {
            if ($role->Key ?? null == $key) {
                return $role;
            }
        }
        return null;
    }

    private function addRole($code, $name)
    {
        $role = $this->findRole($code);
        if ($role === null) {
            $this->manager->addRole($code, $name);
        }
        return $role;
    }
    private function assignRole($roleName, $permissionName,$description)
    {
        $this->manager->addRole($roleName, $description);
        $this->manager->assignPermission($roleName, $permissionName);
    }

    protected function run()
    {
        $this->manager = TPermissionsManager::getPermissionManager();
        $this->roles = $this->manager->getRoles();

        $defaultRoleKey = null;
        $request = $this->getRequest();
        if ($request) {
            $defaultRoleKey = $request->defaultRole ?? null;
            if ($defaultRoleKey !== null) {
                $defaultRole = $this->findRole($defaultRoleKey);
                if ($defaultRole === null) {
                    $this->addErrorMessage('Default role not found. Use existing role or create new role.');
                    return;
                }
            }
        }

        $this->addRole('sysadmin', 'System Administrator');
        if ($defaultRoleKey) {
            $this->manager->assignPermission($defaultRoleKey,TPermissionsManager::appAdminPermissionName);
            $this->manager->assignPermission($defaultRoleKey,TPermissionsManager::mailAdminPermissionName);
            $this->manager->assignPermission($defaultRoleKey,TPermissionsManager::directoryAdminPermissionName);
            $this->manager->assignPermission($defaultRoleKey,TPermissionsManager::manageCommitteesPermissionsName);
        }
        else {
            $this->assignRole(TPermissionsManager::appAdminRoleName,
                TPermissionsManager::appAdminPermissionName, 'Application Manager');



            // mailing permissions
            $this->assignRole(TPermissionsManager::mailAdminRoleName,
                TPermissionsManager::mailAdminPermissionName, 'Mail Administrator');
            $this->assignRole(TPermissionsManager::mailAdminRoleName,
                TPermissionsManager::sendMailingsPermissionName, 'Mail Administrator');
            $this->assignRole(TPermissionsManager::mailSenderRoleName,
                TPermissionsManager::sendMailingsPermissionName, 'Mail Sender');

            $this->manager->assignPermission(TPermissionsManager::authenticatedRole, TPermissionsManager::viewDirectoryPermissionName);

            // directory permissions
            $this->assignRole(TPermissionsManager::directoryAdminRoleName,
                TPermissionsManager::directoryAdminPermissionName, 'Directory Administrator');
            $this->assignRole(TPermissionsManager::directoryAdminRoleName,
                TPermissionsManager::updateDirectoryPermissionName, 'Directory Administrator');
            $this->assignRole(TPermissionsManager::committeeManagerRoleName,
                TPermissionsManager::manageCommitteesPermissionsName,'Committee Administrator');

            // calendar permissions
            $this->assignRole(TPermissionsManager::calendarAdminRoleName,
                TPermissionsManager::calendarAdminPermissionName,'Calendar Administrator');

            // document permissions
            $this->assignRole(TPermissionsManager::documentAdminRoleName,
                TPermissionsManager::documentAdminPermissionName,'Document Administrator');
        }
        $this->addInfoMessage('Roles and Permissions updated.');
        $result = new \stdClass();
        $result->roles = $this->manager->getRoles();
        $result->permissions = GetPermissionsCommand::getPermissionsList($this->manager,$result->roles);
        $this->setReturnValue($result);
    }
}