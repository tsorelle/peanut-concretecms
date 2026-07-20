<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 1/21/2015
 * Time: 5:50 AM
 */

namespace Tops\services;
/**
 * Message posted during a service command execution.
 * See TServiceResponse->$messages
 *
 * Class TServiceMessage
 * @package Tops\services
 */
class TServiceMessage {
    /**
     * Type of message. Use MessageType:: constants
     *
     * @var int
     */
    public $MessageType;
    public $Text;
}
