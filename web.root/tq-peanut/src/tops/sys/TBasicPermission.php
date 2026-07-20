<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 9/11/2017
 * Time: 4:33 AM
 */

namespace Tops\sys;


class TBasicPermission
{
    public $id;
    public $permissionName;
    public $description;
    public $active = 1;

    public function getId() { return $this->id;}
    public function getPermissionName() { return $this->permissionName;}
    public function getDescription() { return $this->description;}
    public function setId($value) {$this->id = $value;}
    public function setPermissionName($value) {$this->permissionName = $value;}
    public function setDescription($value) {$this->description = $value;}
    public static function Create($name,$description) {
        $result = new TBasicPermission();
        $result->permissionName = $name;
        $result->description = $description;
        return $result;
    }

}