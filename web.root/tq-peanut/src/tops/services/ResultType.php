<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 1/22/2015
 * Time: 7:40 AM
 */

namespace Tops\services;

/**
 * Class ResultType
 * @package Tops\services
 *
 * Constants for Peanut service response (TServiceResponse->Result)
 */
class ResultType {
    /**
     *  Successful execution of command
     */
    const Success = 0;
    /**
     *  Asynchronous command execution in progress
     */
    const Pending = 1;
    /**
     *  Service response contains warnings but no errors
     */
    const Warnings = 2;
    /**
     *  Service response contains errors and possibly warnings as well
     */
    const Errors = 3;
    /**
     *  Service failed execution due to unexpected problem or exception
     */
    const ServiceFailure = 4;
    /**
     *  Service was not found or was disabled
     */
    const ServiceNotAvailable = 5;
}