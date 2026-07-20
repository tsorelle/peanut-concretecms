<?php

namespace Peanut\users\services;

use Peanut\users\AccountManager;
use Tops\sys\TUser;

class SigninCommand extends \Tops\services\TServiceCommand
{
    /**
     * Service Contract
     * Request:
     *  {
     *      username: string,
     *      password: string,
     *  }
     *
     *  Response:
     *      the response must contain a 'status' property:
     *          {
     *              status: 'failed'
     *          }
     *      values include:
     *          'ok' - successful login
     *          'failed' - user not found for username/password
     *          'error' -  request not complete due to error
     *
     *          denial of signin due to previous activity,see AccountManager::signinOk
     *          'blocked' - too many login attempts within a specified time.
     *          'banned'  - Past activity indicates a denial of service attack or brute force breakin attempt
     *
     *      Status dependent values:
     *          OK:
     *              'redirectlink' - URL to return to previous page
     *          error:
     *              'errormessage' - message to be displayed.
     */

    protected function run()
    {
        $response = new \stdClass();
        // $user = TUser::getCurrent();
        $manager = new AccountManager();
        // $manager->setAdminAccount('B@nj0boy');
        TUser::SignOut();
        $ok = $manager->signInOk();
        if ($ok === true) {

            $request = $this->getRequest();
            $username = trim($request->username ?? null);
            $password = trim($request->password ?? null);
            if (!($username && $password)) {
                $response->status = 'failed';
                $this->addErrorMessage('User name and password are required.');
            }
            else {
                $signedIn = TUser::SignIn($username,$password);
                if ($signedIn === true) {
                    $user = TUser::getCurrent();
                    $response->status = 'ok';
                    $response->userfullname = $user->getFullName();
                    $response->redirectlink = $_SESSION[AccountManager::redirectKey] ?? '/';
                }
                else if ($signedIn === false) {
                    $response->status = 'failed';
                }
                else {
                    $response->status = 'error';
                    $response->errormessage = $signedIn;
                }

            }
        }
        else if ($ok === false) {
            $response->status = 'blocked';
        }
        else {
            $response->status = 'banned';
        }
        $this->setReturnValue($response);
    }
}