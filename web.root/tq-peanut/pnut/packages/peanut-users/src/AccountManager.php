<?php

namespace Peanut\users;

use Peanut\users\db\model\entity\Role;
use Peanut\users\db\model\entity\User;
use Peanut\users\db\model\entity\Usersession;
use Peanut\users\db\model\repository\AuthenticationsRepository;
use Peanut\users\db\model\repository\RolesRepository;
use Peanut\users\db\model\repository\UserRolesAssociation;
use Peanut\users\db\model\repository\UsersessionsRepository;
use Peanut\users\db\model\repository\UsersRepository;
use Tops\db\IProfilesRepository;
use Tops\sys\IUserAccountManager;
use Tops\sys\TAddUserAccountResponse;
use Tops\sys\TConfiguration;
use Tops\sys\TObjectContainer;
use Tops\sys\TSession;

//todo: test profile and contact related functions

class AccountManager implements IUserAccountManager
{
    const maxSignInAttempts = 10;
    const redirectKey = 'signin_redirect_address';
    /**
     * @var $usersrepository UsersRepository
     */
    private $usersrepository;
    private function getUsersRepository()
    {
        if (!isset($this->usersrepository)) {
            $this->usersrepository = new UsersRepository();
        }
        return $this->usersrepository;
    }

    /**
     * @var $rolesrepository RolesRepository
     */
    private $rolesrepository;
    private function getRolesRepository() {
        if (!isset($this->rolesrepository)) {
            $this->rolesrepository = new RolesRepository();
        }
        return $this->rolesrepository;
    }

    /**
     * @var $userrolesassociation UserRolesAssociation
     */
    private $userrolesassociation;
    private function getUserRolesAssociation() {
        if (!isset($this->userrolesassociation)) {
            $this->userrolesassociation = new UserRolesAssociation();
        }
        return $this->userrolesassociation;
    }

    /**
     * @var $sessionsrepository UsersessionsRepository
     */
    private $sessionsrepository;
    private function getSessionsRepository()
    {
        if (!isset($this->sessionsrepository)) {
            $this->sessionsrepository = new UsersessionsRepository();
        }
        return $this->sessionsrepository;
    }

    function setAdminAccount($password) {
        $password = password_hash($password, PASSWORD_DEFAULT);
        $this->getUsersRepository()->setAdminAccount($password);
    }

    /**
     * @return  IProfilesRepository
     */
    private function getProfilesRepository() {
        return TObjectContainer::Get('profiles.repository');

    }

    /**
     * @param $username
     * @param $password
     * @param $fullname
     * @param $email
     * @param $roles
     * @param $profile
     * @return TAddUserAccountResponse
     */
    public function registerSiteUser($username,$password,$fullname, $email, $roles=[], $profile = [])
    {

        $profileRepository = $this->getProfilesRepository();
        if ($profileRepository) {
            $checkResult = $profileRepository->checkAvailableProfile($email,$fullname);
            if ($checkResult === true) {
                $profile['full-name'] = $fullname;
                $profile['email'] = $email;
            }
            else {
                $result = new  TAddUserAccountResponse();
                $result->errorCode = $checkResult;
                return $result;
            }
        }
        else {
            throw new \Exception('Profile repository not registered.');
        }
        $accountResult =  $this->addAccount($username,$password,$email,$roles,$profile);
        if ($accountResult->errorCode === false && count($profile) > 0) {
            $accountResult->errorCode = $profileRepository->insertProfileValues($profile,$accountResult->accountId);
            if ($accountResult->errorCode) {
                $this->removeAccount($accountResult->accountId);
                $accountResult->accountId = null;
            }
        }
        return $accountResult;
    }

    /**
     * @param $username
     * @param $password
     * @param $email (ignored)
     * @param $roles
     * @param $profile (ignored)
     * @param $creator
     * @return bool|TAddUserAccountResponse
     *
     * Note, in this implementation, email and profile are maintained in the contact system, ignored here.
     */
    public function addAccount($username, $password, $email=null,$roles=[],$profile=[],
                               $creator='system')
    {

        $result = new TAddUserAccountResponse();

        $username = trim($username);
        $password = trim($password);
        $validation = $this->validateUserName($username);
        if ($validation !== true) {
            $result->errorCode = $validation;
            return $result;
        }
        $validation = $this->validatePassword($username);
        if ($validation !== true) {
            $result->errorCode = $validation;
            return $result;
        }

        $password = password_hash($password, PASSWORD_DEFAULT);
        $user = new User();
        $user->password = $password;
        $user->username = $username;
        $user->active = 1;
        $accountId = $this->getUsersRepository()->insert($user,$creator);
        $result->accountId = $accountId;
        $result->invalidRoles = $this->addUserRoles($accountId,$roles);

        return $result;
    }

    public function addTestAccount($username,$password) {

    }

    const minUsernameLength = 5;
    private function validateUserName($username)
    {
        if (empty($username)) {
            return 'Username is blank';
        }
        if (strlen($username) < self::minUsernameLength) {
            return sprintf('User name must be at least %d characters long.',self::minUsernameLength);
        }

        $existing = $this->getAccountIdForUsername($username);
        if ($existing !== false) {
            return "Username is in use.";
        }

        return true;
    }

    public function getAccountIdForUsername($userIdentifier)
    {
        if (is_numeric($userIdentifier)) {
            if ($this->getUsersRepository()->getCount('id = '.$userIdentifier)) {
                return $userIdentifier;
            }
            return false;
        }
        return $this->getUsersRepository()->getIdForFieldValue('username',$userIdentifier);
    }

    public function getRoleIdForName($roleIdentifier) {
        if (is_numeric($roleIdentifier)) {
            if ($this->getRolesRepository()->getCount('id = '.$roleIdentifier)) {
                return $roleIdentifier;
            }
            return false;
        }
        return $this->getRolesRepository()->getIdForFieldValue('name',$roleIdentifier);
    }

    const minPasswordLength = 5;
    private function validatePassword($password)
    {
        if (empty($password)) {
            return 'Password is blank';
        }
        if (strlen($password) < self::minPasswordLength) {
            return sprintf('Password must be at least %d characters long.',self::minUsernameLength);
        }
        return true;
    }

    /**
     * @param $usr
     * @return bool|User
     */
    public function getUserData($usr) {
        $repository = $this->getUsersRepository();
        if (is_numeric($usr)) {
            return $repository->get($usr);
        }
        else {
            return $repository->getSingleEntity('username = ?', [$usr]);
        }
    }

    public function changeUserName($usr,$newName,$changedBy='admin') {
        $validation = $this->validateUserName($newName);
        if ($validation !== true) {
            return $validation;
        }
        $user = $this->getUserData($usr);
        $user->username = $newName;
        $this->getUsersRepository()->update($user,$changedBy);
        return true;
    }

    public function changePassword($usr,$newPwd,$changedBy='admin') {
        $validation = $this->validatePassword($newPwd);
        if ($validation !== true) {
            return $validation;
        }
        $user = $this->getUserData($usr);
        $user->password = password_hash($newPwd,PASSWORD_DEFAULT);
        $this->getUsersRepository()->update($user,$changedBy);
        return true;
    }

    public function removeAccount($usr,$removeProfile = false) {
        $user = $this->getUserData($usr);
        if ($user === false) {
            return false;
        }
        $profilesRepository = $this->getProfilesRepository();
        if ($profilesRepository) {
            if ($removeProfile) {
                $profilesRepository->removeProfile($user->id);
            }
            else {
                $profilesRepository->clearAccountId($user->id);
            }
        }

        $this->getUserRolesAssociation()->removeAllRight($user->id);
        return $this->getUsersRepository()->delete($user->id);
    }

    /**
     * @return false|Usersession
     */
    public function getCurrentSignedInUser() {
        $repository = $this->getSessionsRepository();
        $sessionId = $this->getCurrentSessionId();
        if (!empty($sessionId)) {
            $session = $repository->getActiveSession($sessionId);
            if ($session) {
                return $this->getUsersRepository()->get($session->userId);
            }
        }
        return false;
    }

    public function authenticateUser($username,$pwd) {
        $username = trim($username);
        $user = $this->getUserData($username);
        if (!$user) {
            return false;
        }
        if (password_verify($pwd,$user->password)) {
            return $user;
        }
        return false;
    }

    private $authRepository;
    private function getAuthRepository() {
        if (!isset($this->authRepository)) {
            $this->authRepository = new AuthenticationsRepository();
        }
        return $this->authRepository;
    }

    /**
     * @return bool|int
     *
     * true - ok
     * false - temporary block
     * 0 - permanent block
     */
    public function signInOk() {
        $repo = $this->getAuthRepository();
        $ip = $_SERVER['REMOTE_ADDR'];
        if(!$ip) {
            // Not running on a server, probably test script
            return true;
        }
        if ($repo->isBlocked($ip,self::maxSignInAttempts)) {
            return 0;
        }
        $current = ($this->getAuthRepository())->getCurrent($ip);
        if ($current) {
            return ($current->attempts < self::maxSignInAttempts);
        }
        return true;
    }

    public function logFailure() {
        $ip = $_SERVER['REMOTE_ADDR'];
        $repo = $this->getAuthRepository();
        $repo->updateForFailure($ip,self::maxSignInAttempts);
    }

    public function logSuccess() {
        $ip = $_SERVER['REMOTE_ADDR'];
        $repo = $this->getAuthRepository();
        $repo->updateForSuccess($ip);
    }

    /**
     * @param $username
     * @param $pwd
     * @return bool|User|string
     */
    public function signIn($username,$pwd) {
        $sessionsRepository = $this->getSessionsRepository();
        $user = $this->authenticateUser($username,$pwd);
        if ($user === false) {
            $this->logFailure();
            return false;
        }
        $sessionId = $this->getCurrentSessionId();
        if (!$sessionId) {
            return 'Session not initialized';
        }
        $sessionsRepository->newSession($sessionId,$user->id);
        $this->logSuccess();
        return true;
    }

    private function getCurrentSessionId() {
        $id = session_id();
        $status = session_status();
        if ($status == PHP_SESSION_NONE) {
            session_start();
        }
        return (session_status() == PHP_SESSION_ACTIVE) ? session_id() : 0;
    }

    public function signOut() {
        $sessionId = $this->getCurrentSessionId();
        if ($sessionId) {
            $repository = $this->getSessionsRepository();
            $session = $repository->getSessionBySessionId($sessionId);
            if ($session) {
                $repository->delete($session->id);
            }
        }
    }

    public function getRoleByName($name) {
        return $this->getRolesRepository()->getSingleEntity('name = ?',[$name]);
    }

    /**
     * @return Role[]|false
     */
    public function getRoles() {
        return $this->getRolesRepository()->getAll();
    }

    public function getUserRoleNames($usr) {
        $id = $this->getAccountIdForUsername($usr);
        if (!$id) {
            return false;
        }
        return $this->getUserRolesAssociation()->getRightValues($id,'name');
    }

    public function getUserRoles($usr) {
        $id = $this->getAccountIdForUsername($usr);
        if (!$id) {
            return false;
        }
        return $this->getUserRolesAssociation()->getRightObjects($id);
    }

    public function addUserRole($usr,$roleName) {
        $userId = $this->getAccountIdForUsername($usr);
        if (!$usr) {
            return 'User not found';
        }
        return $this->addRoleForUserId($userId,$roleName);
    }

    public function addRoleForUserId($userId,$roleName) {
        $roleId = is_numeric($roleName) ? $roleName : $this->getRoleIdForName($roleName);
        if (!$roleId) {
            return 'Role not found';
        }
        // $this->getUserRolesAssociation()->addAssociationLeft($userId,$roleId);
        $this->getUserRolesAssociation()->addAssociationRight($userId,$roleId);
        return true;
    }

    public function addUserRoles($userId,array $roles) {
        $invalid = [];
        if (!empty($roles)) {
            foreach ($roles as $roleName) {
                $added = $this->addRoleForUserId($userId,$roleName);
                if ($added != true) {
                    $invalid[] = $roleName;
                }
            }
        }
        return $invalid;
    }

    public function removeUserRole($usr,$roleName) {
        $userId = $this->getAccountIdForUsername($usr);
        if (!$userId) {
            return 'User not found';
        }
        $roleId = $this->getRoleIdForName($roleName);
        if (!$roleId) {
            return 'Role not found';
        }
        // $this->getUserRolesAssociation()->removeAssociationLeft($userId,$roleId);
        $this->getUserRolesAssociation()->removeAssociationRight($userId,$roleId);
        return true;
    }

    public function setUserRoles($usr,array $roles) {
        $userId = $this->getAccountIdForUsername($usr);
        if (!$userId) {
            return 'User not found';
        }
        $association = $this->getUserRolesAssociation();
        $association->removeAllRight($userId);
        foreach ($roles as $roleId) {
            $association->addAssociationRight($userId,$roleId);
        }
        return true;
    }

    public function addRole($roleName,$description,$createdBy='admin') {
        $repository = new RolesRepository();
        $roleId = $this->getRoleIdForName($roleName);
        if ($roleId) {
            return false; // already exists
        }
        $role = new Role();
        $role->name = $roleName;
        $role->description = $description;
        $role->active = 1;
        return $repository->insert($role,$createdBy);

    }

    public function getUsersInRole($roleIdentifier) {
        $repository = $this->getRolesRepository();
        $id = $this->getRoleIdForName($roleIdentifier);
        if (!$id) {
            return [];
        }
        $asso = $this->getUserRolesAssociation();
        return $this->getUserRolesAssociation()->getLeftObjects($id);
    }

    public function removeRole($roleIdentifier) {
        $id = $this->getRoleIdForName($roleIdentifier);
        if ($id) {
            $users = $this->getUsersInRole($id);
            if (count($users) === 0) {
                $this->getRolesRepository()->delete($id);
                return true;
            }
        }
        return false;
    }

    public function getCmsUserId($username)
    {
        return $this->getAccountIdForUsername($username);
    }

    public function getCmsUserIdByEmail($email)
    {
        $profilesRepository = $this->getProfilesRepository();
        $accountId = $profilesRepository->getAccountIdByEmail($email);
        if ($accountId) {
            $user = $this->getUsersRepository()->get($accountId);
            if ($user) {
                return $user->id;
            }
        }
        return false;
    }

    public function getPasswordResetUrl()
    {
        return "/user/forgot-password";
    }

    public function getLoginUrl()
    {
        return "/signin";
    }

    public function getProfileValues($userIdentifier) {
        $user = $this->getUserData($userIdentifier);
        if (!$user) {
            return [];
        }
        $id = $user->id;
        $profilesRepository = $this->getProfilesRepository();
        if ($profilesRepository) {
            return $profilesRepository->getProfileArray($id);
        }

        return [];
    }

    public function getUserList()
    {
        $users = $this->getProfilesRepository()->getUserProfiles();
        if (empty($users)) {
            return [];
        }
        foreach ($users as $user) {
            $user->roles = $this->getUserRoleIds($user->accountId);
        }
        return $users;
    }

    public function updateUser(int $accountId, $fullname, $email, array $roles)
    {
        $emailId = $this->getCmsUserIdByEmail($email);
        if ($emailId != $accountId) {
            return 'Email address is used by another account.';
        }
        $profilesRepo = $this->getProfilesRepository();
        $profilesRepo->updateProfileValues([
            'email' => $email,
            'full-name' => $fullname],$accountId);

        $this->setUserRoles($accountId,$roles);
        return true;
    }

    private function getUserRoleIds($usr)
    {
        $id = $this->getAccountIdForUsername($usr);
        if (!$id) {
            return false;
        }
        return $this->getUserRolesAssociation()->getRightValues($id);
    }


}