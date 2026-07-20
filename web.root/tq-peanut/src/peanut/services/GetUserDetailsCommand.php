<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 5/30/2019
 * Time: 12:56 PM
 */

namespace Peanut\services;


use Tops\services\TServiceCommand;
use Tops\sys\TUser;

class GetUserDetailsCommand extends TServiceCommand
{
    /**
     * Service contract:
     *     export interface IUserDetails {
     *         fullname: string;
     *         username: string;
     *         email: string;
     *         accountId: any;
     *         isAuthenticated: boolean;
     *         isAdmin: boolean;
     *     }
     */

    protected function run()
    {
        $response = new \stdClass();
        $user = TUser::getCurrent();
        $response->isAuthenticated = $user->isAuthenticated();
        $response->fullname =        $user->getFullName();
        $response->username =        $user->getUserName();
        $response->email =           $user->getEmail();
        $response->accountId =       $user->getId();
        $response->isAdmin =         $user->isAdmin();
        $this->setReturnValue($response);
    }
}