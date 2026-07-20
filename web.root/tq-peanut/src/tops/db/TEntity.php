<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 10/28/2017
 * Time: 5:07 PM
 */

namespace Tops\db;


class TEntity extends TimeStampedEntity
{
    public $id = 0;
    public $active = true;

    public function setId($value)
    {
        $this->id = empty($value) ? 0 : $value;
    }
    public function getId() {
        return isset($this->id) ? $this->id : 0;
    }

    public function setActive($value = true) {
        $this->active = !empty($value);
    }

    public function getActive() {
        return !empty($this->active);
    }

    public function getDtoDefaults($username = 'system')
    {
        $defaults = parent::getDtoDefaults($username);
        $defaults['id'] = 0;
        $defaults['active'] = 1;
        return $defaults;
    }

}