<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 9/15/2017
 * Time: 6:28 PM
 */

namespace Tops\concrete5;


use \Concrete\Core\User\Group\Group;
use Concrete\Core\User\Group\GroupList;
use Tops\db\model\repository\PermissionsRepository;
use Tops\sys\TPermissionsManager;
use Tops\sys\TPermission;
use Tops\sys\TStrings;
use Tops\sys\TUser;
use \Concrete\Core\Permission\Access\Entity\GroupEntity as GroupPermissionAccessEntity;
use \Concrete\Core\Permission\Access\Access as PermissionAccess;


class Concrete5PermissionsManagerExperimental extends TPermissionsManager
{
    public static $groupNameFormat = TStrings::wordCapsFormat;
    public static $permissionHandleFormat = TStrings::keyFormat;
    public static $permissionNameFormat = TStrings::initialCapFormat;

    /***********  Concrete5 functions **************************/
    /**
     * @param string $roleName
     * @return bool
     */
    public function addRole($roleName, $roleDescription = null)
    {
        $roleName = TStrings::convertNameFormat($roleName, self::$groupNameFormat);
        if (empty($roleDescription)) {
            $roleDescription = $roleName;
        }
        $group = Group::getByName($roleName);
        if (empty($group)) {
            $group = Group::add($roleName, $roleDescription);
        }
        return (!empty($group));
    }

    /**
     * @param string $roleName
     * @return bool
     */
    public function removeRole($roleName)
    {
        $group = Group::getByName($roleName);
        if (empty($group)) {
            return false;
        }
        $group->delete();
        return true;
    }

    /**
     * @return \stdClass[]
     */
    public function getRoles()
    {
        $result = array();
        $list = new GroupList();;
        $list->includeAllGroups();
        $collection = $list->getResults();
        /**
         * @var $group Group
         */
        foreach ($list->getResults() as $group) {
            $item = new \stdClass();
            $groupName = $group->getGroupName();
            $displayName = $group->getGroupDisplayName();
            $item->Key = TStrings::ConvertNameFormat($groupName, TPermissionsManager::roleKeyFormat);
            $item->Name = TStrings::ConvertNameFormat($groupName, TPermissionsManager::roleNameFormat);
            $item->Description = TStrings::ConvertNameFormat($displayName, TPermissionsManager::roleDescriptionFormat);
            $result[] = $item;
        }
        return $result;
    }


    /******** Tops functions **********************/

    /**
     * @var PermissionsRepository
     */
    private $permissionsRepository;

    private function getRepository()
    {
        if (!isset($this->permissionsRepository)) {
            $this->permissionsRepository = new PermissionsRepository();
        }
        return $this->permissionsRepository;
    }


    /**
     * @return TPermission[]
     */
    public function getPermissions()
    {
        return $this->getRepository()->getAll();
    }

    public function getPermission($permissionName)
    {
        return $this->getRepository()->getPermission($permissionName);
    }

    /**
     * @param string $roleName
     * @param string $permissionName
     * @return bool
     */
    public function assignPermission($roleName, $permissionName)
    {
        $key = TStrings::convertNameFormat($permissionName, self::$permissionHandleFormat);
        $roleName = TStrings::convertNameFormat($roleName, self::$groupNameFormat);
        return $this->assignPermissionGroup($key, $roleName);

    }

    /**
     * @param $permissionKey  string handle for permission
     * @param $roleName  string handle for group
     * @param bool $delete delete if true, add if false, default false
     * @return bool  false if action would duplicate an assignment or remove a non-existant one
     * @deprecated Not working right yet
     */
    private function assignPermissionGroup($permissionKey, $roleName, $delete = false)
    {
        $group = Group::getByName($roleName);
        if (empty($group)) {
            return false;
        }

        /**
         * @var $pkObject \Concrete\Core\Permission\Key\Key
         */
        $pkObject = \Concrete\Core\Permission\Key\Key::getByHandle($permissionKey);

        /**
         * @var $permissionAssignment \Concrete\Core\Permission\Assignment\Assignment
         */
        $permissionAssignment = $pkObject->getPermissionAssignmentObject();

        // Error!! $accessId assigned null;
        $accessId = $pkObject->getPermissionAccessID();

        /**
         * @var $paGlobal PermissionAccess
         *
         * Warning, $paGlobal must be duplicated per the statement below. Any other methods applied will affect all Task permissions.
         */
        $paGlobal = PermissionAccess::getByID($accessId, $pkObject);

        /**
         * @var $permissionAccess PermissionAccess
         */
        $permissionAccess = $paGlobal->duplicate();

        /**
         * @var $groupEntity GroupPermissionAccessEntity
         */
        $groupEntity = GroupPermissionAccessEntity::getOrCreate($group);
        if ($groupEntity === null) {
            return false;
        }

        if ($delete) {
            $permissionAccess->removeListItem($groupEntity);
        } else {
            $permissionAccess->addListItem($groupEntity);
        }

        $permissionAssignment->assignPermissionAccess($permissionAccess);
        return true;
    }

    private function assignPermissionGroups($permissionKey, array $roles = [])
    {
        /**
         * @var $pkObject \Concrete\Core\Permission\Key\Key
         */
        $pkObject = \Concrete\Core\Permission\Key\Key::getByHandle($permissionKey);
        $pt = $pkObject->getPermissionAssignmentObject();
        if (empty($roles)) {
            $pt->clearPermissionAssignment();
            return;
        }

        /**
         * @var $pa PermissionAccess
         */
        $pa = PermissionAccess::create($pkObject);
        foreach ($roles as $roleName) {

            $group = Group::getByName($roleName);
            /**
             * @var $groupEntity GroupPermissionAccessEntity
             */
            $groupEntity = GroupPermissionAccessEntity::getOrCreate($group);

            $pa->addListItem($groupEntity);
        }

        /**
         * @var $pt \Concrete\Core\Permission\Assignment\Assignment
         */
        $pt->assignPermissionAccess($pa);
    }


    public function addPermission($name, $description=null)
    {

        $permission = $this->getRepository()->getPermission($name);
        if ($permission === false) {
            $username = TUser::getCurrent()->getUserName();
            $this->getRepository()->addPermission($name, $description, $username);
            $this->createPermission($name);
        }
        return true;
    }


    /**
     * @param string $roleName
     * @param string $permissionName
     * @return bool
     */
    public function revokePermission($roleName, $permissionName)
    {
        $key = TStrings::convertNameFormat($permissionName, self::$permissionHandleFormat);
        /*
                $delete = true;
                $roleName = TStrings::convertNameFormat($roleName,self::$groupNameFormat);
                return $this->assignPermissionGroup($key,$roleName, $delete);
        */


        $this->getRepository()->revokePermission($roleName, $key);

//        $permission = $this->getRepository()->getPermission($permissionName);
//        $roles = $permission->getRoles();
//        $this->assignPermissionGroups($key,$roles);
        return true;
    }

    public function removePermission($name)
    {
        // todo: remove c5 permission
    }

    public function createPermission($permissionName)
    {

        $handle = TStrings::convertNameFormat($permissionName, self::$permissionHandleFormat);
        $name = TStrings::convertNameFormat($permissionName, self::$permissionNameFormat);
        $existing = \Concrete\Core\Permission\Key\Key::getByHandle($handle);
        if (empty($existing)) {
            \Concrete\Core\Permission\Key\Key::add('admin', $handle, $name, '', false, false);
        }
    }

    public function verifyPermissionForCurrentUser($permissionName)
    {
        $handle = TStrings::convertNameFormat($permissionName, self::$permissionHandleFormat);
        $pk = $pk = \Concrete\Core\Permission\Key\Key::getByHandle($handle);
        if ($pk !== null) {
            return $pk->validate();
        }
        return false;

    }
}