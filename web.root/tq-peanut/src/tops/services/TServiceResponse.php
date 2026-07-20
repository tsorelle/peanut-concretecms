<?php
/**
 * Created by PhpStorm.
 * User: tsorelle
 * Date: 2/22/14
 * Time: 9:46 AM
 */
namespace Tops\services;

/**
 * Class TServiceResponse
 * @package Tops\services
 *
 * Data transfer object containing the results of a service command execution
 */
class TServiceResponse {
    /**
     * Collection of messages posted during execution
     *
     * @var TServiceMessage[]
     */
    public $Messages;
    /**
     * Result of service command execution.
     * Use ResultType:: constants.
     *
     * @var int
     */
    public $Result;
    /**
     * The returned value of a service command execution
     *
     * @var mixed
     */
    public $Value;

    public $TraceMessages;
}

