<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 9/15/2017
 * Time: 6:28 PM
 */

namespace Tops\concrete5;


use Concrete\Core\User\Group\Group;
use Concrete\Core\User\Group\GroupList;
use Tops\db\TDBPermissionsManager;
use Tops\sys\TStrings;

class Concrete5PermissionsManager extends TDBPermissionsManager
{
    // const groupNameFormat = TStrings::initialCapFormat;
    const groupNameFormat = TStrings::wordCapsFormat;
    const permissionHandleFormat = TStrings::keyFormat;
    const permissionNameFormat = TStrings::initialCapFormat;

    public function getPermissionHandleFormat()
    {
        return self::permissionHandleFormat;
    }

    public function getRoleHandleFormat()
    {
        return self::groupNameFormat;
    }

    /***********  Concrete5 functions **************************/
    /**
     * @param string $roleName
     * @return bool
     */
    public function addRole($roleName,$roleDescription=null)
    {
        $roleName = TStrings::ConvertNameFormat($roleName,self::groupNameFormat);
        if (empty($roleDescription)) {
            $roleDescription = $roleName;
        }
        $group = Group::getByName($roleName);
        if (empty($group)) {
            $group = Group::add($roleName,$roleDescription);
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
        $result = $this->getActualRoles(false);
        $result[] = $this->getVirtualRoleAuthenticated();

        return $result;
    }

    // private

    public function getActualRoles($useNativeFormat = true) {
        $result = array();
        $list = new GroupList();;
        $list->includeAllGroups();
        $groups = $list->getResults();

        /**
         * @var $group Group
         */
        foreach ($groups as $group) {
            $groupName = $group->getGroupName();
            $displayName = $group->getGroupDisplayName();
            $item = $this->createRoleObject($groupName,$displayName);
            if ($useNativeFormat) {
                $item->Key = $this->formatPermissionHandle($item->Key);
            }
            $result[] = $item;
        }
        return $result;
    }




}