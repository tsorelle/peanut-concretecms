<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 2/11/2015
 * Time: 7:01 AM
 */

namespace Tops\sys;


use Tops\cache\ITopsCache;
use Tops\cache\TSessionCache;

class TUser {

    /*
     * moved to TPermisionsManager
     *
    const AdminRole = 'administrator';
    const AuthenticatedRole = 'authenticated';
    const GuestRole = 'guest';
    const PermissionsClassKey = 'tops.permissions';
    const appAdminRoleName = 'Peanut Administrator';
    const appAdminPermissionName = 'Administer peanut features';
    const mailAdminRoleName = 'Mail Administrator';
    const mailAdminPermissionName = 'Administer mailboxes';
    const directoryAdminRoleName = 'Directory Administrator';
    const directoryAdminPermissionName = 'Administer directory';
    const viewDirectoryPermissionName = 'View directory';
    const updateDirectoryPermissionName = 'Update directory';
     */


    const DefaultUserName = 'guest';
    const anonymousDisplayName = 'Guest';
    const UserFactoryClassKey = 'tops.userfactory';
    const profileKeyFullName  ='full-name';
    const profileKeyShortName ='short-name';
    const profileKeyDisplayName ='display-name';
    const profileKeyEmail     ='email';
    const profileKeyTimezone     ='timezone';
    const profileKeyUserName   ='username';
    const profileKeyLanguage  ='language';

    /**
     * @var IUser
     */
    private static $currentUser;

    /**
     * @return IUser
     */
    public static function getCurrent() {
        if (!isset(self::$currentUser)) {
            self::$currentUser = self::Create();
            self::$currentUser->loadCurrentUser();
        }
        return self::$currentUser;
    }

    public static function resetCurrentUser() {
        self::$currentUser = self::Create();
        self::$currentUser->loadCurrentUser();
    }

    /**T
     * @param IUser $user
     */
    public static function setCurrentUser(IUser $user) {
        self::$currentUser = $user;
    }

    public static function setCurrent($userName)
    {
        if (!(isset(self::$currentUser) && self::$currentUser->getUserName() == $userName)) {
            self::$currentUser = self::Create();
            self::$currentUser->loadByUserName($userName);
        }
        return self::$currentUser;
    }

    public static function SignOut() {
        $user = self::getCurrent();
        if ($user) {
            $user->signOut();
        }
    }

    public static function SignIn($username, $password=null) {
        $user = self::Create();
        $success = $user->signIn($username,$password);

        if ($success) {
           TUser::resetCurrentUser();
       }
        return $success;
    }

    /**
     * @return TAddUserAccountResponse
     */
    public static function  addAccount($username,$password,$fullname,$email,$roles=[],$profile=[],$requireAdmin=true) {
        if ($requireAdmin && !self::getCurrent()->isAdmin()) {
            $response = new TAddUserAccountResponse();
            $response->errorCode = IUserAccountManager::notAuthorizedError;
            return $response;
        }
        $username = trim($username ?? '');
        $password = trim($password ?? '');
        $email = $email === null ? null : trim(@$email);
        return self::getUserFactory()->
            createAccountManager()->registerSiteUser($username,$password,$fullname,$email,$roles,$profile);
    }

    public static function getByUserName($userName)
    {

        $result = self::Create();
        if ($result->loadByUserName($userName)) {
            return $result;
        }
        return false;
    }

    public static function getById($uid) {
        $result = self::Create();
        if ($result->loadById($uid)) {
            return $result;
        }
        return false;
    }

    public static function getByEmail($email) {
        $result = self::Create();
        if ($result->loadByEmail($email)) {
            return $result;
        }
        return false;
    }

    private static $userFactory;
    public static function setUserFactory(IUserFactory $factory)
    {
        // set factory for testing routines
        self::$userFactory = $factory;
    }

    /**
     * @return IUserFactory
     */
    private static function getUserFactory() {
        if (!isset(self::$userFactory)) {
            if (TObjectContainer::HasDefinition('tops.userfactory')) {
                self::$userFactory = TObjectContainer::Get('tops.userfactory');
            }
            else {
                self::$userFactory = new TNullUserFactory();
            }
        }
        return self::$userFactory;
    }

    /**
     * @return IUser
     */
    public static function Create() {

        $factory = self::getUserFactory();
        return $factory->createUser();

    }

    /**
     * @var ITopsCache
     */
    private static $profileCache;

    public static function clearProfiles() {
        self::getProfileCache()->Flush('users');
    }

    /**
     * @return ITopsCache
     */
    public static function getProfileCache()
    {
        if (!isset(self::$profileCache)) {
            self::$profileCache = new TSessionCache();
        }
        return self::$profileCache;
    }

    /**
     * @param $key
     * @return string
     */
    public static function getProfileFieldKey($key,$format=TStrings::keyFormat) {
        $configKey = TStrings::convertNameFormat($key,TStrings::dashedFormat);
        $default = TStrings::convertNameFormat($key,$format);
        return TConfiguration::getValue($configKey,'user-attributes',$default);
    }

}