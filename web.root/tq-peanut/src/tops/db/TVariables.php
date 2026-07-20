<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 11/17/2017
 * Time: 8:13 AM
 */

namespace Tops\db;


use Tops\db\model\entity\VariableEntity;
use Tops\db\model\repository\VariablesRepository;
use Tops\sys\TObjectContainer;
use Tops\cache\ITopsCache;
use Tops\cache\TSessionCache;

class TVariables
{
    const cacheKey = 'tops.variables';
    const siteLanguageKey = 'site-language';
    const siteOrganizationKey = 'site-org';
    /**
     * @var $instance TVariables
     */
    private static $instance = null;
    private static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new TVariables();
        }
        return self::$instance;
    }

    private static $repository;
    private static function getRepository()
    {
        if (!isset(self::$repository)) {
            self::$repository = new VariablesRepository();
        }
        return self::$repository;
    }

    /**
     * @var $cache ITopsCache
     */
    private $cache;

    /**
     * @return ITopsCache || null
     */
    private function getCache() {
        if (!isset($this->cache)) {
            if (TObjectContainer::HasDefinition('tops.lookup.cache')) {
                $this->cache = TObjectContainer::Get('tops.lookup.cache');
            }
            else {
                $this->cache = new TSessionCache();
            }
        }
        return $this->cache;
    }

    public function __construct()
    {
        $this->refreshCache();
    }

    private function refreshCache() {
        $repository = self::getRepository();
        $this->getCache()->Set(self::cacheKey,$repository->getArray());
    }

    public function getValue($key) {
        $values = $this->cache->Get(self::cacheKey);
        return  @$values[$key];
    }

    /**
     * @param $key
     * @param $value
     * @param string $user
     */
    public static function setValue($key,$name,$value,$description=null,$user='system') {
        $repository = self::getRepository();
        /**
         * @var $entry VariableEntity
         */
        $entry = $repository->getEntityByCode($key);
        if ($entry) {
            $entry->value = $value;
            $repository->update($entry,$user);
        }
        else {
            $entry = VariableEntity::Create($key,$name,$value,$description);
            $repository->insert($entry,$user);
        }
        self::refresh();
    }

    public static function remove($key) {
        $repository = self::getRepository();
        $entry = $repository->getEntityByCode($key);
        if ($entry) {
            $repository->delete($entry->id);
            self::refresh();
        }
    }

    public static function refresh()
    {
        if (isset(self::$instance)) {
            self::$instance->refreshCache();
        }
        else {
            self::getInstance();
        }
    }

    public function clearCache() {
        if (isset($this->cache)) {
            $this->cache->Remove(self::cacheKey);
        }
    }

    public static function Get($key,$default=false) {
        $value = self::getInstance()->getValue($key);
        return ($value === null && $default !== false) ? $default : $value;
    }

    public static function GetObject($key) {
        $value = self::Get($key);
        if (empty($value)) {
            return null;
        }
        return json_decode($value);
    }

    /**
     * @param $key
     * @param $name
     * @param $value
     * @param null $description
     * @param string $user
     */
    public static function SetObject($key,$name,$value,$description=null,$user='system') {
        $value = json_encode($value);
        self::setValue($key,$name,$value,$description,$user);
    }


    public static function Clear() {
        if (isset(self::$instance)) {
            self::$instance->clearCache();
            self::$instance = null;
        }
    }

    public static function GetSiteLanguage($default='en') {
        return self::Get(self::siteLanguageKey,$default);
    }

    public static function GetSiteOrganization() {
        return self::Get(self::siteOrganizationKey);
    }


}