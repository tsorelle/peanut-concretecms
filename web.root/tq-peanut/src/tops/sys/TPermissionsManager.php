<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 10/12/2017
 * Time: 8:15 AM
 */

namespace Tops\sys;

abstract class TPermissionsManager
{
    const adminRole = 'administrator';
    const authenticatedRole = 'authenticated';
    const guestRole = 'guest';
    const permissionsClassKey = 'tops.permissions';
    const appAdminRoleName = 'Peanut Administrator';
    const appAdminPermissionName = 'Administer peanut features';
    const mailAdminRoleName = 'Mail Administrator';
    const mailAdminPermissionName = 'Manage mailings';
    const mailboxesPermissionName = 'Administer mailboxes';
    const directoryAdminRoleName = 'Directory Administrator';
    const directoryAdminPermissionName = 'Administer directory';
    const viewDirectoryPermissionName = 'View directory';
    const updateDirectoryPermissionName = 'Update directory';
    const managePermissionsPermissionName = 'Manage permissions';
    const mailSenderRoleName = 'Mail Sender';
    const sendMailingsPermissionName = 'Send mailings';
    const editContentPermissionsName = 'Edit content';
    const calendarAdminRoleName = 'Calendar Administrator';
    const calendarAdminPermissionName = 'Manage calendar';
    const committeeManagerRoleName = 'Committee Manager';
    const manageCommitteesPermissionsName = 'Manage committees';
    const documentAdminRoleName = 'Document Manager';
    const documentAdminPermissionName = 'Manage document library';

    const keyFormat = TStrings::dashedFormat;
    /**
     * @var TPermissionsManager
     */
    private static $permissionManger;

    public static function toKeyArray(array $arr,$format=self::keyFormat) {
        $count = sizeof($arr);
        for ($i=0;$i<$count;$i++) {
            $formatted = TStrings::ConvertNameFormat($arr[$i],$format);
            $arr[$i] = $formatted;
        }
        return $arr;
    }

    /**
     * @return TPermissionsManager
     */
    public static  function getPermissionManager()
    {
        if (!isset(self::$permissionManger)) {
            if (TObjectContainer::HasDefinition(TPermissionsManager::permissionsClassKey)) {
                self::$permissionManger = TObjectContainer::Get(TPermissionsManager::permissionsClassKey);
            }
            else {
                self::$permissionManger = new NullPermissionsManager();
            }
        }
        return self::$permissionManger;
    }

    public function getAuthenticatedRole() {
        return self::authenticatedRole;
    }

    public function getAdminRole() {
        return self::adminRole;
    }

    public function getGuestRole() {
        return self::guestRole;
    }

    public function getRoleHandleFormat() {
        // native identifier
        return TStrings::keyFormat;
    }

    public function getPermissionHandleFormat() {
        // native identifier
        return TStrings::initialCapFormat;
    }

    public function formatRoleName($name) {
        return TStrings::ConvertNameFormat($name,TStrings::wordCapsFormat);
    }

    public function formatRoleDescription($name) {
        return TStrings::ConvertNameFormat($name,TStrings::initialCapFormat);
    }

    public function formatPermissionName($name) {
        return TStrings::ConvertNameFormat($name,TStrings::wordCapsFormat);
    }

    public function formatPermissionDescription($name) {
        return TStrings::ConvertNameFormat($name,TStrings::initialCapFormat);
    }

    public function formatRole($name,$format) {
        if ($name == self::authenticatedRole) {
            $name = $this->getAuthenticatedRole();
        }
        else if ($name == self::guestRole) {
            $name = $this->getGuestRole();
        }
        return TStrings::ConvertNameFormat($name,$format);
    }

    public function formatKey($roleKey) {
        return $this->formatRole($roleKey,self::keyFormat);
    }

    public function formatRoleHandle($roleHandle) {
        return $this->formatRole($roleHandle,$this->getRoleHandleFormat());
    }

    public function formatPermissionHandle($name) {
        return TStrings::ConvertNameFormat($name,$this->getPermissionHandleFormat());
    }

    private $virtualRoles;
    protected function getVirtualRoles() {
        if (!isset($this->virtualRoles)) {
            $this->virtualRoles = [];
            $this->virtualRoles[self::authenticatedRole] =
                $this->createRoleObject(
                    TPermissionsManager::authenticatedRole,
                    'Authenticated user',
                    'Current logged in user'
                );

            $this->virtualRoles[self::guestRole] =
                $this->createRoleObject(
                    TPermissionsManager::guestRole,
                    'Guest',
                    'Anonymous user'
                );
        }
        return $this->virtualRoles;
    }

    protected function getVirtualRoleAuthenticated() {
        $this->getVirtualRoles();
        return $this->virtualRoles[self::authenticatedRole];
    }

    protected function getVirtualRoleGuest() {
        $this->getVirtualRoles();
        return $this->virtualRoles[self::guestRole];
    }

    protected function createRoleObject($key,$name=null,$description=null)
    {
        if ($name === null) {
            $name = $key;
        }
        if ($description == null) {
            $description = $key;
        }
        $role = new \stdClass();
        $role ->Key = $this->formatKey($key);
        $role ->Name = $this->formatRoleName($name);
        $role ->Description = $this->formatRoleDescription($description);
        return $role;
    }

    /**
     * @param string $roleName
     * @return bool
     */
    public abstract function addRole($roleName, $roleDescription = null);

    /**
     * @param string $roleName
     * @return bool
     */
    public abstract function removeRole($roleName);

    /**
     * @return [];
     *
     * return array of stdClass
     *  interface ILookupItem {
     *     Key: any;
     *     Text: string;
     *     Description: string;
     *   }
     */
    public abstract function getRoles();

    /**
     * @return TPermission[]
     */
    public abstract function getPermissions();

    public abstract function addPermission($name, $description=null);

    public abstract function removePermission($pemissionHandle);

    /**
     * @return TPermission
     */
    public abstract function getPermission($permissionHandle);

    /**
     * @param string $role
     * @param string $permission
     * @return bool
     */
    public abstract function assignPermission($roleHandle, $permissionHandle);

    /**
     * @param string $roleName
     * @param string $permissionName
     * @return bool
     */
    public abstract function revokePermission($roleHandle, $permissionHandle);



}