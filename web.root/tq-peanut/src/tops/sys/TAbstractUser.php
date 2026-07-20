<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 2/10/2015
 * Time: 8:22 AM
 */

namespace Tops\sys;


abstract class TAbstractUser implements IUser
{
    protected $id = 0;
    protected $userName;
    protected $isCurrentUser;

    private $languageKey = TUser::profileKeyLanguage;

    protected function formatRoleName($name) {
        $manager = TPermissionsManager::getPermissionManager();
        return $manager->formatRoleName($name);
    }

    protected function formatRoleDescription($name) {
        $manager = TPermissionsManager::getPermissionManager();
        return $manager->formatRoleDescription($name);
    }

    protected function formatPermissionName($name) {
        $manager = TPermissionsManager::getPermissionManager();
        return $manager->formatPermissionName($name);
    }

    protected function formatPermissionDescription($name) {
        $manager = TPermissionsManager::getPermissionManager();
        return $manager->formatPermissionDescription($name);
    }

    protected function formatKey($roleKey) {
        $manager = TPermissionsManager::getPermissionManager();
        return $manager->formatKey($roleKey);
    }

    protected function formatRoleHandle($roleHandle) {
        $manager = TPermissionsManager::getPermissionManager();
        return $manager->formatRoleHandle($roleHandle);
    }

    protected function getRoleHandleFormat() {
        // native identifier
        $manager = TPermissionsManager::getPermissionManager();
        return $manager->getRoleHandleFormat();
    }

    protected function getPermissionHandleFormat() {
        // native identifier
        $manager = TPermissionsManager::getPermissionManager();
        return $manager->getPermissionHandleFormat();
    }

    public function formatPermissionHandle($name) {
        $manager = TPermissionsManager::getPermissionManager();
        return TStrings::ConvertNameFormat($name,$manager->getPermissionHandleFormat());
    }

    /**
     * @var array
     */
    protected   $profile = null;

    private function getCachedProfile() {
        $cache = TUser::getProfileCache();
        $result = $cache->Get('users.'.$this->userName);
        return $result;
    }

    private function cacheProfile() {
        $cache = TUser::getProfileCache();
        $cache->Set('users.'.$this->userName,$this->profile,20);
    }

    /**
     * @param $newPassword
     * @return bool
     *
     * Override in subclass
     */
    public function setPassword($newPassword) {
        return false;
    }

    /**
     * @param $id
     * @return mixed
     */
    public abstract function loadById($id);

    /**
     * @param $email
     * @return mixed
     */
    public abstract function loadByEmail($email);

    /**
     * @param $userName
     * @return mixed
     */
    public abstract function loadByUserName($userName);

    /**
     * @return mixed
     */
    public abstract function loadCurrentUser();

    /**
     * @return bool
     */
    public abstract function isAdmin();

    /**
     * @return string[]
     */
    public abstract function getRoles();

    public abstract function signIn($username, $password = null);

    /**
     * Optional override in subclass
     * @return bool
     */
    public function signOut() {
        TUser::resetCurrentUser();
        return true;
    }

    /**
     * @param $roleName
     * @return bool
     */
    public function isMemberOf($roleName) {
        // override to implement cms specific routines

        if ($this->isAdmin()) {
            return true;
        }
        $permissionsManager = TPermissionsManager::getPermissionManager();
        if ($roleName ==  TPermissionsManager::guestRole || $roleName == $permissionsManager->getGuestRole()) {
            return true;
        }
        if ($roleName == TPermissionsManager::authenticatedRole ||  $roleName == $permissionsManager->getAuthenticatedRole()) {
            return $this->isAuthenticated();
        }
        return false;
    }

    /**
     * @param string $value
     * @return bool
     */
    public function isAuthorized($permissionName = '') {
        // override to implement cms specific routines
        if ($this->isAdmin()) {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public abstract function isAuthenticated();

    protected function loadProfileValues()
    {
        $this->profile = $this->getCachedProfile();
        if ($this->profile === null) {
            $this->profile = array();
            $this->loadProfile();
            $this->cacheProfile();
        }
    }

    protected function updateLanguage($languageKey=TUser::profileKeyLanguage) {
        $this->languageKey = $languageKey;
        $language = $this->getProfileValue($languageKey);
        if (!empty($language)) {
            TLanguage::setUserLanguages($language);
        }
    }

    protected abstract function loadProfile();

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }  //  getId

    /**
     * @return string
     */
    public function getUserName()
    {
        if (isset($this->userName)) {
            return $this->userName;
        }
        return TUser::DefaultUserName;
    }  //  getUserName

    protected function getDefaultUserName() {
        if (!$this->isAuthenticated()) {
            return TUser::anonymousDisplayName;
        }
        if ($this->userName == 'admin') {
            return "The administrator";
        }
        return false;
    }

    /**
     * @param bool $defaultToUsername
     * @return string
     */
    public function getFullName($defaultToUsername = true)
    {
        $name = $this->getDefaultUserName();
        if (empty($name)) {
            $name = $this->getProfileValue(TUser::profileKeyFullName);
            if (empty($name)) {
                $name = $this->getProfileValue(TUser::profileKeyDisplayName);
                if (empty($name)) {
                    $name = $this->getProfileValue(TUser::profileKeyShortName);
                    if (empty($name)) {
                        $name = $defaultToUsername ? $this->getUserName() : '';
                    }
                }
            }
        }
        return $name;
    }  //  getfullName

    /**
     * @param bool $defaultToUsername
     * @return string
     */
    public function getShortName($defaultToUsername = true)
    {
        $name = $this->getDefaultUserName();
        if (empty($name)) {
            $name = $this->getProfileValue(TUser::profileKeyShortName);
            if (empty($name)) {
                $name = $this->getProfileValue(TUser::profileKeyDisplayName);
                if (empty($name)) {
                    $name = $this->getProfileValue(TUser::profileKeyFullName);
                    if (empty($name)) {
                        $name = $defaultToUsername ? $this->getUserName() : '';
                    }
                }
            }
        }
        return $name;
    }  //  getShortName

    public function getDisplayName($defaultToUsername = true)
    {
        $name = $this->getDefaultUserName();
        if (empty($name)) {
            $name = $this->getProfileValue(TUser::profileKeyDisplayName);
            if (empty($name)) {
                $name = $this->getProfileValue(TUser::profileKeyFullName);
                if (empty($name)) {
                    $name = $this->getProfileValue(TUser::profileKeyShortName);
                    if (empty($name)) {
                        $name = $defaultToUsername ? $this->getUserName() : '';
                    }
                }
            }
        }
        return $name;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->getProfileValue(TUser::profileKeyEmail);
    }  //  getEmail

    public function isCurrent()
    {
        return  !empty($this->isCurrentUser);
    }

    protected function setCurrent()
    {
        $this->isCurrentUser = true;
    }

    public function getProfileValue($key) {
        $key = $this->formatProfileKey($key);
        if ($this->isAuthenticated()) {
            if (!isset($this->profile)) {
                $this->loadProfileValues();
            }

            if (array_key_exists($key, $this->profile)) {
                return $this->profile[$key];
            }
            return false;
        }
        return '';
    }

    public function setProfileValue($key,$value) {
        $key = $this->formatProfileKey($value);
        if (!isset($this->profile)) {
            $this->loadProfileValues();
        }

        $isUpdate = array_key_exists($key,$this->profile) ;
        $this->profile[$key] = $value;
        if ($isUpdate) {
            if ($key == $this->languageKey) {
                TLanguage::setUserLanguages($value);
            }
            $this->updateProfile($key);
        }
    }


    public function updateProfile($key=null) {
        // override in sub-class as needed
    }

    public function getUserPicture($size=0, array $classes = [], array $attributes = []) {
        return ''; // override in subclasses as deisired.
    }

    protected function formatProfileKey($key) {
        return TStrings::convertNameFormat($key,TStrings::dashedFormat);
    }

    public function getAccountPageUrl()
    {
        // override in subclass
        return '';
    }



}