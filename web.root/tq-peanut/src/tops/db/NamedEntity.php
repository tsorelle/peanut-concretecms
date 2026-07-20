<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 10/28/2017
 * Time: 3:13 PM
 */

namespace Tops\db;


use Tops\sys\TL;
use Tops\sys\TLanguage;

class NamedEntity extends TEntity
{
    public $name = '';
    public $code = '';
    public $description = '';

    public function setName($value)
    {
        if (empty($value)) {
            throw new \Exception(TLanguage::text('validation-code-blank'));
        }
        $this->name = $value;
    }

    public function getName() {
        return $this->name;
    }

    public function getCode() {
        return $this->code;
    }

    public function getDescription() {
        return $this->description;
    }

    public function setCode($value)
    {
        if (empty($value)) {
            throw new \Exception(TLanguage::text('entity-no-code'));
        }
        $this->code = $value;
    }

    public function setDescription($value) {
        $this->description = empty($value) ? '' : $value;
    }

    public function setValues($name,$code,$description) {
        $this->setName($name);
        $this->setCode($code);
        $this->setDescription($description);
    }

}