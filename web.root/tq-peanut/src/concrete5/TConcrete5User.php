<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 3/30/2017
 * Time: 7:17 AM
 */

namespace Tops\concrete5;


use Core;
use Concrete\Core\User\User;
use Concrete\Core\User\UserInfo;
use Concrete\Core\Attribute\Category\CategoryService;
use Concrete\Core\Attribute\Key\UserKey;
use Concrete\Core\User\UserInfoRepository;
use Tops\db\model\repository\PermissionsRepository;
use Tops\sys\TAbstractUser;
use Tops\sys\TPermission;
use Tops\sys\TPermissionsManager;
use Tops\sys\TStrings;
use Tops\sys\TUser;

/**
 * Class TConcrete5User
 * @package Tops\sys
 *
 * see https://documentation.concrete5.org/developers/users-groups/reading-data-existing-users
 */
class TConcrete5User extends TAbstractUser
{
    const userAccountPage='/dashboard/users/search/view/%d';

    /**
     * @var PermissionsRepository
     */
    private $permissionsRepository;

    /**
     * @return PermissionsRepository
     */
    private function getRepository()
    {
        if (!isset($this->permissionsRepository)) {
            $this->permissionsRepository = new PermissionsRepository();
        }
        return $this->permissionsRepository;
    }


    /**
     * @param $permissionName
     * @return bool|TPermission
     */
    public function getPermission($permissionName)
    {
        return $this->getRepository()->getPermission($permissionName);
    }



    /**
     * @var $user User
     */
    private $user;

    /**
     * @var $userInfo UserInfo
     */
    private $userInfo;

    private $memberGroups;

    private static $userController;


    private static function getUserController() {
        if (!isset(self::$userController)) {
            /**
             * @var $service CategoryService;
             */
            $service = \Core::make(CategoryService::class);
            self::$userController = $service->getByHandle('user')->getController();
        }
        return self::$userController;
    }

    public static function getAttributeList() {
        return
            [
                 TUser::profileKeyFullName  =>
                    [
                        'akHandle' => self::formatC5AttributeKey(TUser::profileKeyFullName),
                        'akName' => 'Full name',
                        'akIsSearchable' => true,
                        'akIsSearchableIndex' => true,
                    ],
                TUser::profileKeyShortName  =>
                    [
                        'akHandle' => self::formatC5AttributeKey(TUser::profileKeyFullName),
                        'akName' => 'Short name',
                        'akIsSearchable' => true,
                        'akIsSearchableIndex' => true,
                    ],
                TUser::profileKeyDisplayName  =>
                    [
                        'akHandle' => self::formatC5AttributeKey(TUser::profileKeyDisplayName),
                        'akName' => 'Display name',
                        'akIsSearchable' => true,
                        'akIsSearchableIndex' => true,
                    ]
            ];
    }

    public static function CreateAttributeKeys($pkg = false) {
        $list = self::getAttributeList();
        $controller = self::getUserController();
        $type='text';
        foreach  ($list as $key => $args ) {
            $handle = $args['akHandle'];
            if (UserKey::getByHandle($handle) === null) {
                $controller->add($type, $args, $pkg);
            }
        }
    }

    /**
     * @return UserInfoRepository
     * @throws \Exception
     */
    private function getUserInfoRepository() {
        $repo = Core::make(UserInfoRepository::class);
        if (empty($repo)) {
            throw new \Exception('Cannot create user info repository');
        }
        return $repo;
    }


    private function setUser($user,$userInfo=null)
    {
        unset($this->isCurrentUser);
        if ($user == null) {
            $this->userInfo = null;
            $this->id = 0;
            unset($this->userName);
            return false;
        }
        $this->user = $user;
        $this->id = $this->user->getUserID();
        if ($userInfo === null) {
            $this->userInfo = $this->getUserInfoRepository()->getByID($this->id);
        }
        else {
            $this->userInfo = $userInfo;
        }
        $this->userName = $this->user->getUserName();
        unset($this->memberGroups);
        $this->updateLanguage();
        return true;
    }

    /**
     * @param $id
     * @return mixed
     */
    public function loadById($id)
    {
        return $this->setUser(User::getByUserID($id));
    }

    /**
     * @param $userName
     * @return mixed
     */
    public function loadByUserName($userName)
    {
        $repo = $this->getUserInfoRepository();
        $userInfo = $repo->getByName($userName);
        return $this->loadFromUserInfo($userInfo);

    }
    /**
     * @param $email
     * @return mixed
     */
    public function loadByEmail($email)
    {
        $userInfo = $this->getUserInfoRepository()->getByEmail($email);
        return $this->loadFromUserInfo($userInfo);
    }



    /**
     * @param $userInfo UserInfo
     * @return bool
     */
    private function loadFromUserInfo($userInfo) {
        if ($userInfo == null) {
            return $this->setUser(null);
        }
        return $this->setUser(User::getByUserID($userInfo->getUserID()),$userInfo);
    }

    /**
     * @return mixed
     */
    public function loadCurrentUser()
    {
        $result = $this->setUser(new User());
        $this->isCurrentUser = true;
        $this->currentUserIsAnonymous = !$this->isAuthenticated();
        return $result;
    }

    protected function getDefaultUserName()
    {
        $result = parent::getDefaultUserName();
        if ($result == TUser::anonymousDisplayName  && (!$this->isCurrent()) && $this->currentUserIsAnonymous()) {
            // concrete 5 denies access to user attributes if current user is anonymous
            return $this->getUserName();
        }
        return $result;
    }

    private $currentUserIsAnonymous;
    private function currentUserIsAnonymous() {
        if (!isset($this->currentUserIsAnonymous)) {
            $current = new User();
            $this->currentUserIsAnonymous = !$current->checkLogin();
        }
        return $this->currentUserIsAnonymous;
    }
    
    public function isCurrent()
    {
        if (!isset($this->isCurrentUser)) {
            $current = new User();
            $this->isCurrentUser = ($this->id === $current->getUserID());
            if (!isset($this->currentUserIsAnonymous)) {
                $this->currentUserIsAnonymous = !$current->checkLogin();
            }
        }
        return $this->isCurrentUser;
    }

    /**
     * @param $roleName
     * @return bool
     */
    public function isMemberOf($roleName)
    {
        $result = parent::isMemberOf($roleName);
        if (!$result) {
            return $this->isInGroup($roleName);
        }
        return $result;
    }

    private function isInGroup($roleName) {
        $groups = $this->getRoles();
        $roleName = $this->formatRoleHandle($roleName);
        return in_array($roleName,$groups);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return ($this->user) ? $this->user->getUserID() : 0;
    }

    /**
     * @return bool
     */
    public function isAuthenticated()
    {
        return $this->user->checkLogin();
    }

    /**
     * @param string $value
     * @return bool
     *
     * Use datasbase permission manager until C5 assignPermission is fixed.
     */
    public function isAuthorized($permissionName = '')
    {
        $authorized = parent::isAuthorized($permissionName);
        if (!$authorized) {
            $authorized = $this->checkDbPermission($permissionName);
            if ((!$authorized) && $this->isCurrentUser) {
                // Concrete 5 only supports permission check for current user.
                $authorized = $this->checkC5Permission($permissionName);
            }
        }
        return $authorized;
    }

    private function checkC5Permission($permissionName)
    {
        $handle =  $this->formatPermissionHandle($permissionName);
        $pk = \Concrete\Core\Permission\Key\Key::getByHandle($handle);
        if ($pk !== null) {
            return $pk->validate();
        }
        return false;
    }

    private function checkDbPermission($permissionName) {
        $permissionName =  $this->formatKey($permissionName);
        $manager = TPermissionsManager::getPermissionManager();
        $permission = $manager->getPermission($permissionName);
        if (empty($permission)) {
            return false;
        }
        $roles = $this->getRoles();
        foreach ($roles as $role) {
            if ($permission->check($role)) {
                return true;
            }
        }
        return false;
    }


    /**
     * @return bool
     */
    public function isAdmin()
    {
        return ($this->user->isSuperUser() || $this->isInGroup('Administrators'));
    }

    protected function loadProfile()
    {
        if (!empty($this->userInfo)) {
            $this->profile[TUser::profileKeyEmail] = $this->userInfo->getUserEmail();
        }
    }

    public function getProfileValue($key)
    {
        if (!$this->userInfo) {
            return '';
        }
        switch ($key) {
            case TUser::profileKeyEmail :
                $result =  $this->userInfo->getUserEmail();
                break;
            case TUser::profileKeyUserName :
                $result = $this->userInfo->getUserName();
                break;
            case TUser::profileKeyTimezone :
                $result = $this->userInfo->getUserTimezone();
                break;
            default :
                $result = parent::getProfileValue($key);
                if ($result === false) {
                    $key = TUser::getProfileFieldKey($key);
                    $result = $this->userInfo->getAttribute($key);
                }
        }
        return empty($result) ? '' : $result;
    }

    public function setPassword($newPassword) {
        return $this->userInfo->changePassword($newPassword);
    }

    public function setProfileValue($key, $value)
    {
        /* email, username and timezone are not attributes and must be altered with user->update
        $user->update(array(
            'uName' => 'aembler'),
            'uEmail' => 'new@email.com'),
            'uTimezone' => 'America/Los_Angeles'
        ));
        see: https://documentation.concrete5.org/developers/users-groups/adding-and-updating-users
        */
        $userChanges = [];
        if (isset($this->userInfo)) {
            switch ($key) {
                case TUser::profileKeyEmail :
                    $userChanges['uEmail'] = $value;
                    break;
                case TUser::profileKeyUserName :
                    $userChanges['uName'] = $value;
                    break;
                case TUser::profileKeyTimezone :
                    $userChanges['uTimezone'] = $value;
                    break;
            }
            if (count($userChanges)) {
                $this->userInfo->update($userChanges);
            }
            else {
                $list = self::getAttributeList();
                if (!empty($list[$key])) {
                    $handle = $list[$key]['akHandle'];
                    $this->userInfo->setAttribute($handle, $value);
                }
            }
        }
    }

     /**
     * @return string[]
     */
    public function getRoles()
    {
        if (!isset($this->memberGroups)) {
            $this->memberGroups = $this->getActualRoleNames();
            if ($this->isAuthenticated()) {
                $manager = TPermissionsManager::getPermissionManager();
                $this->memberGroups[] = $manager->getAuthenticatedRole();
            }
        }
        return $this->memberGroups;
    }

    public function getActualRoleNames()
    {
        $result = [];
        $groups = $this->user->getUserGroups();
        foreach ($groups as $groupID => $groupName) {
            $group = \Concrete\Core\User\Group\Group::getByID($groupID);
            $result[] = $group->getGroupName();
        }
        return $result;
    }

    public function getUserGroupNames() {
        return $this->getRoles();
    }

    public function getC5User() {
        return $this->user;
    }

    public function getC5UserInfo() {
        return $this->userInfo;
    }

    private static function formatC5AttributeKey($key) {
        return TStrings::convertNameFormat($key,TStrings::keyFormat);
    }


    /**
     * @param $username
     * @param null $password
     * @return bool
     *
     * Beware concrete5 bug.  Cannot return current user with new User() after this is called.
     */
    public function signIn($username, $password = null)
    {
        $userInfo =  \Core::make('Concrete\Core\User\UserInfoRepository')->getByName($username);
        if (empty($userInfo)) {
            return false;
        }
        $user = $userInfo->getUserObject();
        $userId = $user->getUserID();
        $result = User::loginByUserID($userId);


        if (empty($result)) {
            return false;
        }
        $this->setUser($user,$userInfo);
        return true;
    }

    public function getAccountPageUrl()
    {
        return empty($this->user) ? '' : sprintf(self::userAccountPage, $this->user->getUserID());
    }

}