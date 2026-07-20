<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 6/29/2015
 * Time: 6:43 AM
 */

namespace Tops\sys;


use Tops\services\IMessageContainer;

/**
 * Class TNullUserFactory
 * @package Tops\sys
 *
 * Returns an immutable placeholder IUser object, where a functional user class is not defined.
 */
class TNullUserFactory implements IUserFactory {

    /**
     * @var IUser
     */
    private static $instance;

    /**
     * @return IUser
     */
    public function createUser()
    {
        if (!isset(self::$instance)) {
            self::$instance = new TNullUser();
        }
        return self::$instance;
    }

    /**
     * @return TAddUserAccountResponse
     */
    public function addAccount($username,$password,$email=null,$roles=[],$profile=[])
    {
        return  new TAddUserAccountResponse(
            $this->createUser()
        );
    }

    public function createAccountManager()
    {
        // TODO: Implement createAccountManager() method.
    }
}