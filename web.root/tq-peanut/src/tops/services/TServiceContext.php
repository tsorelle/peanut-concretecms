<?php

/**
 * Created by PhpStorm.
 * User: tsorelle
 * Date: 2/22/14
 * Time: 9:57 AM
 */

namespace Tops\services;
use Tops\sys\TL;
use Tops\sys\TLanguage;

/**
 * Class TServiceContext
 *
 * Managing container for a service response
 *
 * @package Tops\services
 */
class TServiceContext implements IMessageContainer {
    /**
     * @var TServiceResponse
     */
    private  $response;

    public function __construct() {
        $this->response = new TServiceResponse();
        $this->response->Messages = array();
        $this->response->Result = ResultType::Success;
    }
    public function AddMessage($messageType,$text,$arg1=null, $arg2=null)
    {
        $message = new TServiceMessage();
        $message->MessageType = $messageType;
        if ($arg1 === true) {
            // text is pre-translated
            // other arguments are ignored
            $message->Text = $text;
        } else if (is_array($arg1)) {
            // arg1 is arguments array for formatted message
            // arg2 is null or default text
            $message->Text = TLanguage::formatText($text, $arg1, $arg2);
        } else {
            // translation required
            // arg1 is default text, $arg2 is ignored
            $message->Text = TLanguage::text($text, $arg1);
        }

        array_push($this->response->Messages, $message);
    }

    public function AddInfoMessage($text,$arg1=null, $arg2=null) {
        $this->AddMessage(MessageType::Info,$text,$arg1, $arg2);
    }

    public function AddWarningMessage($text,$arg1=null, $arg2=null) {
        $this->AddMessage(MessageType::Warning,$text,$arg1, $arg2);
        if ($this->response->Result < ResultType::Warnings)
            $this->response->Result = ResultType::Warnings;
    }


    public function AddErrorMessage($text,$arg1=null, $arg2=null) {
        $this->AddMessage(MessageType::Error,$text,$arg1, $arg2);
        if ($this->response->Result < ResultType::Errors)
            $this->response->Result = ResultType::Errors;
    }

    public function SetError() {
        $this->response->Result = ResultType::Errors;
    }


    public function GetResult() {
        return $this->response->Result;
    }

    public function AddServiceFatalErrorMessage($text) {
        $this->AddMessage(MessageType::Error,$text);
        $this->response->Result = ResultType::ServiceFailure;
    }

    public function ServiceNotAvailable() {
        $this->AddMessage(MessageType::Error,'The service is not available.');
        $this->response->Result = ResultType::ServiceNotAvailable;
    }

    /**
     * @return TServiceResponse
     */
    public function GetResponse() {
        return $this->response;
    }

    public function SetReturnValue($value) {
        $this->response->Value = $value;
    }

}