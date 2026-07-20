<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 1/19/2019
 * Time: 1:58 PM
 */

namespace Tops\concrete5;


use Concrete\Core\User\User;
use Concrete\Core\User\UserInfo;
use Concrete\Core\User\UserInfoRepository;

use Tops\sys\IUserAccountManager;
use Tops\sys\TAddUserAccountResponse;
use Tops\sys\TPermissionsManager;
use Tops\sys\TUser;

class Concrete5AccountManager implements IUserAccountManager
{

    private static $userInfoRepository;
    /**
     * @return UserInfoRepository
     * @throws \Exception
     */
    private function getUserInfoRepository() {
        if (!isset(self::$userInfoRepository)) {
            self::$userInfoRepository = \Core::make(UserInfoRepository::class);
            if (empty(self::$userInfoRepository)) {
                throw new \Exception('Cannot create user info repository');
            }
        }
        return self::$userInfoRepository;
    }

    private function formatRoles(array $roles)
    {
        $result = [];
        if (!empty($roles)) {
            $permissionsManager = TPermissionsManager::getPermissionManager();
            foreach ($roles as $role) {
                $result[] = $permissionsManager->formatRoleHandle($role);
            }
        }
        return $result;
    }


    /**
     * @param $userName
     * @return UserInfo
     * @throws \Exception
     */
    public function getUserInfoByUserName($userName)
    {
        $repo = $this->getUserInfoRepository();
        return $repo->getByName($userName);
    }

    /**
     * @param $email
     * @return UserInfo
     * @throws \Exception
     */
    public function getUserInfoByEmail($email)
    {
        $repo = $this->getUserInfoRepository();
        return $repo->getByEmail($email);
    }


    /**
     * @param $username
     * @return null|number
     * @throws \Exception
     */
    public function getCmsUserId($username)
    {
        if (empty($username)) {
            return null;
        }
        $ui = $this->getUserInfoByUserName($username);
        return empty($ui) ? null : $ui->getUserID();
    }

    /**
     * @param $username
     * @return null|number
     * @throws \Exception
     */
    public function getCmsUserIdByEmail($email)
    {
        if (empty($email)) {
            return null;
        }
        $ui = $this->getUserInfoByEmail($email);
        return empty($ui) ? null : $ui->getUserID();
    }

    /**
     * @return TAddUserAccountResponse
     */
    /**
     * @return TAddUserAccountResponse
     */
    public function addAccount($username,$password,$email=null,$roles=[],$profile=[])
    {

        $roles = $this->formatRoles($roles);

        $response = new TAddUserAccountResponse();
        $response->accountId = null;
        $response->errorCode = false;
        $response->invalidRoles = [];
        $response->invalidProperties = [];
        if (empty($password) || empty($username) || empty($email)) {
            $response->errorCode = IUserAccountManager::addAccountParameterError;
            return $response;
        }

        /**
         * @var TConcrete5User
         */
        $user = TUser::getByUserName($username);
        if ($user !== false) {
            $response->errorCode = IUserAccountManager::duplicateUsernameError;
            return $response;
        }

        $user = TUser::getByEmail($email);
        if ($user !== false) {
            $response->errorCode = IUserAccountManager::duplicateEmailError;
            return $response;
        }


        /**
         * @var %c5user \Concrete\Core\User\UserInfo
         */
        $c5user = \Core::make('user/registration')->create(['uName' => $username, 'uEmail' => $email, 'uPassword' => $password]);
           // UserInfo::add(['uName' => $username, 'uEmail' => $email, 'uPassword' => $password]);
        if (empty($c5user)) {
            $response->errorCode = IUserAccountManager::addAccountError;
            return $response;
        }
        $user = TUser::getByUserName($username);
        if (!$user !== false) {
            $response->errorCode = IUserAccountManager::addAccountError;
            return $response;
        }

        if (!empty($roles)) {
            /**
             * @var User
             */
            $u = $c5user->getUserObject();
            foreach ($roles as $roleName) {
                $group = \Concrete\Core\User\Group\Group::getByName($roleName);
                if (empty($group)) {
                    $response->invalidRoles[] = $roleName;
                    continue;
                }
                if (!$c5user->isInGroup($group)) {
                    $u->enterGroup($group);
                }
            }
            $user = TUser::getByUserName($username); // refresh
        }
        foreach ($profile as $key => $value) {
            // todo: find out why this was skipped in previous versions.
/*            if ($key == 'full-name') {
                continue;
            }*/
            try {
                $user->setProfileValue($key, $value);
                $posted = $user->getProfileValue($key);
                if ($value !== $posted) {
                    $response->invalidProperties[] = $key;
                }
            } catch (\Exception $ex) {
                $response->invalidProperties[] = $key;
            };
        }
        $response->accountId = $user->getId();
        return $response;
    }

    public function getPasswordResetUrl()
    {
        return '/login/concrete/forgot_password';
    }

    public function getLoginUrl()
    {
        return '/login';
    }

    public function registerSiteUser($username, $password, $fullname, $email, $roles = [], $profile = [])
    {
        // fullname not used in C5
        return $this->addAccount($username, $password, $email, $roles, $profile);
    }
}