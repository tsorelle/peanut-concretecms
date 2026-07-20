<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 6/29/2015
 * Time: 6:37 AM
 */

namespace Tops\sys;

/**
 * Class TNullUser
 * @package Tops\sys
 *
 * An immutable object used as a placeholder where a functional user object is not defined.
 *
 */
class TNullUser implements IUser {

    /**
     * @param $id
     * @return mixed
     */
    public function loadById($id)
    {
        throw new \Exception('User type not defined. Set IUserFactory in DI container.');
    }

    /**
     * @param $userName
     * @return mixed
     */
    public function loadByUserName($userName)
    {
        throw new \Exception('User type not defined. Set IUserFactory in DI container.');
    }

    /**
     * @return mixed
     */
    public function loadCurrentUser()
    {
        return $this;
    }

    /**
     * @param $roleName
     * @return bool
     */
    public function isMemberOf($roleName)
    {
        return false;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return 0;
    }

    /**
     * @return bool
     */
    public function isAuthenticated()
    {
        return false;
    }

    /**
     * @param string $value
     * @return bool
     */
    public function isAuthorized($value = '')
    {
        return false;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return '';
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return '';
    }

    /**
     * @return string
     */
    public function getUserName()
    {
        return 'system';
    }

    /**
     * @param bool $defaultToUsername
     * @return string
     */
    public function getFullName($defaultToUsername = true)
    {
        return '';
    }

    /**
     * @param bool $defaultToUsername
     * @return string
     */
    public function getShortName($defaultToUsername = true)
    {
        return 'system';
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return '';
    }

    /**
     * @return bool
     */
    public function isAdmin()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isCurrent()
    {
        return false;
    }

    public function getProfileValue($key)
    {
        return null;
    }

    public function setProfileValue($key, $value)
    {
        // ignore
    }

    public function updateProfile($key = null)
    {
        // ignore
    }

    public function getContentTypes()
    {
        // ignore
    }

    /**
     * @param $email
     * @return mixed
     */
    public function loadByEmail($email)
    {
        throw new \Exception('User type not defined. Set IUserFactory in DI container.');

    }

    /**
     * @param string $value
     * @return bool
     */
    public function checkPermission($value)
    {
        return false;
    }

    public function getUserPicture($size = 0, array $classes = [], array $attributes = [])
    {
        return '';
    }

    public function getDisplayName($defaultToUsername = true)
    {
         return 'system';
    }

    public function signIn($username, $password = null)
    {
        return true;
    }

    /**
     * @param $newPassword
     * @return bool
     */
    public function setPassword($newPassword)
    {
        // TODO: Implement setPassword() method.
        return false;
    }

    public function getAccountPageUrl()
    {
        // TODO: Implement getAccountPageUrl() method.
    }
}