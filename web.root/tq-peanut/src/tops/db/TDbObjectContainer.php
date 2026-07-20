<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 5/28/2018
 * Time: 6:19 AM
 */

namespace Tops\db;


use Tops\sys\IObjectContainer;

class TDbObjectContainer extends TPdoQueryManager implements IObjectContainer
{
    private $instances = array();
    private $classes = array();

    private static $dbId = null;
    public static function setDatabaseId($value) {
        self::$dbId = $value;
    }

    private $definitions = null;
    private function getDefinitions() {
        if ($this->definitions === null) {
            $this->definitions = [];
            $stmt = $this->executeStatement("SELECT `key`, `classname`, singleton  FROM tops_classes");
            $definitions = $stmt->fetchAll(\PDO::FETCH_OBJ);
            foreach ($definitions as $definition) {
                $this->definitions[$definition->key] = $definition;
            }
        }
        return $this->definitions;
    }

    protected function getDatabaseId()
    {
        return self::$dbId;
    }

    private function getClassDef($key)
    {
        $definitions = $this->getDefinitions();
        if (array_key_exists($key,$definitions)) {
            return $definitions[$key];
        }
        return null;
    }

    public function get($key)
    {
        if (isset($this->instances[$key])) {
            return $this->instances[$key];
        };

        if (isset($this->classes[$key])) {
            $className = $this->classes[$key];
            $isSingleton = false;
        }
        else {
            $definition = $this->getClassDef($key);
            if ($definition === null) {
                return false;
            }

            $className = $definition->classname;
            $this->classes[$key] = $className;
            $isSingleton = !empty($definition->singleton);
        }

        $instance = new $className();
        if ($isSingleton) {
            $this->instances[$key] = $instance;
        }
        return $instance;
    }


    public function hasDefinition($key)
    {
        return $this->getClassDef($key) !== null;
    }
}