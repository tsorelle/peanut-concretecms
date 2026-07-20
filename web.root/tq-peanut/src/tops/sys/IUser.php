<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 2/10/2015
 * Time: 10:31 AM
 */
namespace Tops\sys;

interface IUser
{
    /**
     * @param $id
     * @return mixed
     */
    public function loadById($id);

    /**
     * @param $userName
     * @return mixed
     */
    public function loadByUserName($userName);

    /**
     * @param $email
     * @return mixed
     */
    public function loadByEmail($email);

    /**
     * @return mixed
     */
    public function loadCurrentUser();

    /**
     * @param $roleName
     * @return bool
     */
    public function isMemberOf($roleName);

    /**
     * @return int
     */
    public function getId();

    /**
     * @return bool
     */
    public function isAuthenticated();

    /**
     * @param string $value
     * @return bool
     */
    public function isAuthorized($value = '');

    /**
     * @return string
     */
    public function getUserName();

    /**
     * @param bool $defaultToUsername
     * @return string
     */
    public function getFullName($defaultToUsername = true);

    /**
     * @param bool $defaultToUsername
     * @return string
     */
    public function getShortName($defaultToUsername = true);

    public function getDisplayName($defaultToUsername = true);

    /**
     * @return string
     */
    public function getEmail();

    /**
     * @return bool
     */
    public function isAdmin();

    /**
     * @return bool
     */
    public function isCurrent();


    public function getProfileValue($key);

    public function setProfileValue($key,$value);

    public function getUserPicture($size=0, array $classes = [], array $attributes = []);

    /**
     * @param $username
     * @param null $password
     * @return bool
     */
    public function signIn($username,$password=null);

    /**
     * @return bool
     */
    public function signOut();

    /**
     * @param $newPassword
     * @return bool
     */
    public function setPassword($newPassword);

    public function getAccountPageUrl();

}