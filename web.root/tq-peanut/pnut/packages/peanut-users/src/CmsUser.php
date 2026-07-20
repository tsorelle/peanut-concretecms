<?php

namespace Peanut\users;

use Peanut\users\db\model\entity\User;
use Tops\sys\TUser;

class CmsUser extends \Tops\sys\TAbstractUser
{
    public static function LogOut() {
        $user = TUser::getCurrent();
        if ($user) {
            $user->signOut();
        }
    }

    /**
     * @var User
     */
    private $userInfo;

    /**
     * @var
     */
    private $roles;

    /**
     * @var AccountManager
     */
    private $accountManager;

    public function __construct()
    {
        $this->accountManager = UserFactory::getAccountManager();
    }

    /**
     * @param $user  User | null
     * @param $isCurrent
     * @return bool
     */
    private function setUser($user,$isCurrent = false)
    {
        $this->isCurrentUser = $isCurrent;
        if ($user == null) {
            $this->userInfo = null;
            $this->id = 0;
            unset($this->userName);
            return false;
        }
        $this->userInfo = $user;
        $this->id = $user->id;

        $this->userName = $user->username;
        unset($this->roles);
        $this->updateLanguage();
        return true;
    }

    /**
     * @inheritDoc
     */
    public function loadById($id)
    {
        return $this->setUser($this->accountManager->getUserData($id));
    }

    /**
     * @inheritDoc
     */
    public function loadByEmail($email)
    {
        $id = $this->accountManager->getCmsUserIdByEmail($email);
        return $this->setUser($this->accountManager->getUserData($id));
    }

    /**
     * @inheritDoc
     */
    public function loadByUserName($userName)
    {
        $user = $this->accountManager->getUserData($userName);
        if ($user === false) {
            $user = null;
        }
        return $this->setUser($user);
    }

    private function loadUser(User $user) {
        return $this->setUser($user);
    }

    /**
     * @inheritDoc
     */
    public function loadCurrentUser()
    {
        $userData = $this->accountManager->getCurrentSignedInUser();
        if (!$userData) {
            $userData = new User();
            $userData->username='guest';
            $userData->id = false;
        }
        return $this->setUser($userData,true);
    }

    /**
     * @inheritDoc
     */
    public function isAdmin()
    {
        if (isset($this->userInfo)) {
            if ($this->userInfo->id == 1) {
                return true;
            }
            $roles = $this->getRoles();
            if ($roles) {
                return in_array('administrator',$roles);
            }
        }
        return false;
    }



    /**
     * @inheritDoc
     */
    public function getRoles()
    {
        if (!isset($this->userInfo)) {
            return [];
        }
        if (!isset($this->roles)) {
            $this->roles = $this->accountManager->getUserRoleNames($this->userInfo->id);
        }
        return $this->roles;
    }

    public function signIn($username, $password = null)
    {
        return $this->accountManager->signIn($username,$password);
    }

    public function signOut() {
        $this->userInfo = null;
        $this->accountManager->signOut();
        return parent::signOut();
    }


    /**
     * @inheritDoc
     */
    public function isAuthenticated()
    {
        return (isset($this->userInfo) && $this->userInfo->username !== 'guest');
    }

    protected function loadProfile()
    {
        if (isset($this->userInfo) && $this->userInfo->id) {
            $this->profile = $this->accountManager->getProfileValues($this->userInfo->id);
        }
        else {
            $this->profile = [];
        }
    }

    public function isMemberOf($roleName) {
        $result = parent::isMemberOf($roleName);
        if (!$result) {
            $roles = $this->getRoles();
            if ($roles) {
                $result = in_array($roleName, $roles);
            }
        }
        return $result;
    }
}