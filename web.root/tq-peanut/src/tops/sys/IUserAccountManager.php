<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 1/19/2019
 * Time: 1:55 PM
 */

namespace Tops\sys;


interface IUserAccountManager
{
    const duplicateUsernameError = 'account-error-duplicate-name';
    const duplicateEmailError = 'account-error-duplicate-email';
    const addAccountError = 'account-error-add-failed';
    const addAccountParameterError = 'account-error-bad-args';
    const notAuthorizedError = 'account-error-not-authorized';

    /**
     * @param $username
     * @return number | null
     */
    public function getCmsUserId($username);

    /**
     * @param $email
     * @return number | null
     */
    public function getCmsUserIdByEmail($email);

    /**
     * @return TAddUserAccountResponse
     */
    public function addAccount($username,$password,$email=null,$roles=[],$profile=[]);

    public function registerSiteUser($username,$password,$fullname, $email, $roles=[], $profile = []);

    public function getPasswordResetUrl();

    public function getLoginUrl();


}