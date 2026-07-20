<?php 
/** 
 * Created by /tools/create-model.php 
 * Time:  2017-11-17 14:10:01
 */

namespace Tops\db\model\entity;

class VariableEntity  extends \Tops\db\NamedEntity
{
    public $value;

    public static function Create($code, $name, $value,$description=null) {
        $instance = new VariableEntity();
        $instance->setValues($name,$code,empty($description) ? $name : $description);
        $instance->value = $value;
        return $instance;
    }
}