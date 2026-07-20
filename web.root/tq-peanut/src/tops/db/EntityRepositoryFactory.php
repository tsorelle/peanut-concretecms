<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 8/31/2017
 * Time: 6:58 AM
 */

namespace Tops\db;


use Tops\sys\TConfiguration;
use Tops\sys\TLanguage;
use Tops\sys\TStrings;

class EntityRepositoryFactory
{
    /**
     * @param $name
     * @param string $namespace
     * @return IEntityRepository
     * @throws \Exception
     */
    public static function Get($name,$namespace=null) {
        if (empty($name)) {
            throw new \Exception('no repository name provided.');
        }
        if (empty($namespace)) {
            $namespace = TConfiguration::getValue('repositoryNamespace','services');
            if (empty($namespace)) {
                throw new \Exception('Namespace for repositories not configured.');
            }
        }

        $name = TStrings::toCamelCase($name);

        $className = $namespace.'\\'.$name.'Repository';
        return new $className();
    }
}