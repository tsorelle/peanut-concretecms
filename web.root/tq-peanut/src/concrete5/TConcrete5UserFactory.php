<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 3/30/2017
 * Time: 7:15 AM
 */

namespace Tops\concrete5;



use PHPUnit\Runner\Exception;
use Tops\services\IMessageContainer;
use Tops\sys\IUser;
use Tops\sys\IUserAccountManager;
use Tops\sys\IUserFactory;
use Tops\sys\TAddUserAccountResponse;
use Tops\sys\TNullUserFactory;
use Tops\sys\TObjectContainer;
use Tops\sys\TUser;
use Concrete\Core\User\User;
use Concrete\Core\User\UserInfo;


class TConcrete5UserFactory implements IUserFactory
{
    /**
     * @return /Tops/sys/IUser
     */
    public function createUser()
    {
        return new TConcrete5User();
    }


    private static $accountManager;

    /**
     * @return IUserAccountManager
     */
    public function createAccountManager()
    {
        if (!isset(self::$accountManager)) {
            self::$accountManager = new Concrete5AccountManager();
        }
        return self::$accountManager;
    }


}