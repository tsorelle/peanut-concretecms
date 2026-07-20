<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 1/17/2019
 * Time: 5:46 PM
 */

namespace Tops\sys;


class TAddUserAccountResponse
{
    public $accountId = null;
    public $errorCode = false;
    public $invalidRoles = [];
    public $invalidProperties = [];
}