<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 9/14/2017
 * Time: 4:54 PM
 */

namespace Tops\sys;

/**
 * Class NullPermissionsManager
 * @package Tops\sys
 *
 * Use if no permission manager available.  No permissions are defined.  Exceptions raised for any managment functions.
 */

class NullPermissionsManager extends TPermissionsManager
{
    /**
     * @param string $roleName
     * @return bool
     */
    public function addRole($roleName, $roleDescription = null)
    {
        throw new \Exception('Role management not supported.');
    }

    /**
     * @param string $roleName
     * @return bool
     */
    public function removeRole($roleName)
    {
        throw new \Exception('Role management not supported.');
    }

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
    public function getRoles()
    {
        return [];
    }

    /**
     * @return TPermission[]
     */
    public function getPermissions()
    {
        return [];
    }

    public function addPermission($name, $description=null)
    {
        throw new \Exception('Role management not supported.');
    }

    public function removePermission($pemissionHandle)
    {
        return false;
    }

    /**
     * @return TPermission
     */
    public function getPermission($permissionHandle)
    {
       return false;
    }

    /**
     * @param string $role
     * @param string $permission
     * @return bool
     */
    public function assignPermission($roleHandle, $permissionHandle)
    {
        throw new \Exception('Role management not supported.');
    }

    /**
     * @param string $roleName
     * @param string $permissionName
     * @return bool
     */
    public function revokePermission($roleHandle, $permissionHandle)
    {
        throw new \Exception('Role management not supported.');
    }
}