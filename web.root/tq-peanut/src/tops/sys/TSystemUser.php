<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 4/6/2019
 * Time: 4:21 PM
 */

namespace Tops\sys;


class TSystemUser implements IUser
{
    private $email;
    private $username;
    private $profile = [];

    public function __construct($username = 'system',$email=null)
    {
        $this->username = $username;
    }

    /**
     * @param $id
     * @return mixed
     */
    public function loadById($id)
    {
        // not applicable
        return true;
    }

    /**
     * @param $userName
     * @return mixed
     */
    public function loadByUserName($userName)
    {
        $this->username = $userName;
        return true;
    }

    /**
     * @param $email
     * @return mixed
     */
    public function loadByEmail($email)
    {
        $this->email = $email;
        return true;
    }

    /**
     * @return mixed
     */
    public function loadCurrentUser()
    {
        return true;
        // not applicable
    }

    /**
     * @param $roleName
     * @return bool
     */
    public function isMemberOf($roleName)
    {
        return true;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return 1;
    }

    /**
     * @return bool
     */
    public function isAuthenticated()
    {
        return true;
    }

    /**
     * @param string $value
     * @return bool
     */
    public function isAuthorized($value = '')
    {
        return true;
    }

    /**
     * @return string
     */
    public function getUserName()
    {
        return $this->username;
    }

    /**
     * @param bool $defaultToUsername
     * @return string
     */
    public function getFullName($defaultToUsername = true)
    {
        return 'system account';
    }

    /**
     * @param bool $defaultToUsername
     * @return string
     */
    public function getShortName($defaultToUsername = true)
    {
        return $this->username;
    }

    public function getDisplayName($defaultToUsername = true)
    {
        return 'system account';
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return bool
     */
    public function isAdmin()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isCurrent()
    {
        return true;
    }

    public function getProfileValue($key)
    {
        if ($key == 'email') {
            return $this->email;
        }
        return @$this->profile[$key];
    }

    public function setProfileValue($key, $value)
    {
        $this->profile[$key] = $value;
    }

    public function getUserPicture($size = 0, array $classes = [], array $attributes = [])
    {
        return null;
    }

    /**
     * @param $username
     * @param null $password
     * @return bool
     */
    public function signIn($username, $password = null)
    {
        // not applicable
        return true;
    }

    /**
     * @param $newPassword
     * @return bool
     */
    public function setPassword($newPassword)
    {
        // not applicable
    }

    public function getAccountPageUrl()
    {
        return '';
    }

    public function signOut()
    {
        return true;
    }
}