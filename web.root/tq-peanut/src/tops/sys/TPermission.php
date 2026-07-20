<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 9/11/2017
 * Time: 4:33 AM
 */

namespace Tops\sys;


class TPermission extends TBasicPermission
{
    private $roles = array();

    /**
     * @return string[]
     */
    public function getRoles() { return $this->roles;}

    /**
     * @param $roleName
     * @return bool
     */
    public function check($roleKey) {
        $roleKey = TStrings::convertNameFormat($roleKey,TStrings::dashedFormat);
        return in_array($roleKey,$this->roles);
    }
    public function addRole($roleKey) {
        $roleKey = TStrings::convertNameFormat($roleKey,TStrings::dashedFormat);
        if (!in_array($roleKey,$this->roles)) {
            $this->roles[] = $roleKey;
        }
    }

    public function removeRole($roleKey)
    {
        $roleKey = TStrings::convertNameFormat($roleKey,TStrings::dashedFormat);
        $this->roles = array_diff($this->roles, [$roleKey]);
    }

    public static function Create($name,$description) {
        $result = new TPermission();
        $result->permissionName = $name;
        $result->description = $description;
        return $result;
    }

}