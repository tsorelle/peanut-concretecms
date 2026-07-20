<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 12/2/2017
 * Time: 6:20 AM
 */

namespace Tops\services;


use Tops\sys\TLanguage;

class TMessageContainer implements IMessageContainer 
{
    /**
     * @var TServiceMessage[]
     */
    private $messages = [];
    
    private $result = ResultType::Success;
    
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

        array_push($this->messages, $message);
    }

    public function AddInfoMessage($text,$arg1=null, $arg2=null) {
        $this->AddMessage(MessageType::Info,$text,$arg1, $arg2);
    }

    public function AddWarningMessage($text,$arg1=null, $arg2=null) {
        $this->AddMessage(MessageType::Error,$text,$arg1, $arg2);
        if ($this->result < ResultType::Warnings)
            $this->result = ResultType::Warnings;
    }


    public function AddErrorMessage($text,$arg1=null, $arg2=null) {
        $this->AddMessage(MessageType::Error,$text,$arg1, $arg2);
        if ($this->result < ResultType::Errors)
            $this->result = ResultType::Errors;
    }


    public function GetResult()
    {
        $response = new \stdClass();
        $response->result = $this->result;
        $response->messages = $this->messages;
        return $response;
    }
}