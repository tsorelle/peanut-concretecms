<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 3/9/2019
 * Time: 4:16 AM
 */

namespace Tops\mail;


interface IEmailValidator
{
    /**
     * @param $emailAddress
     * @return true | \stdClass
     *
     * stdClass is error information.  May include:
     *      errorCode - translateable string
     *      suggestion = closest suggestion
     */
    public function validate($emailAddress);

}