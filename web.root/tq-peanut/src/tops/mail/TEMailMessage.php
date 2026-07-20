<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 1/22/2015
 * Time: 2:28 PM
 */

namespace Tops\mail;
// use Egulias\EmailValidator\EmailValidator;
use Tops\sys\TLanguage;
use Zend\I18n\Validator\DateTime;
use Zend\Validator\Date;

/**
 * Class TEMailMessage
 * @package Tops\sys
 */
class TEMailMessage {

    /**
     * @var string
     */
    private $htmlContent = null;
    /**
     * @var string
     */
    private $textContent = null;
    /**
     * @var TEmailAddress[]
     */
    private $recipientList;
    /**
     * @var TEmailAddress[]
     */
    private $ccList;
    /**
     * @var TEmailAddress[]
     */
    private $bccList;
    /**
     * @var TEmailAddress
     */
    private $fromAddress;
    /**
     * @var TEmailAddress
     */
    private $replyTo;
    /**
     * @var string
     */
    private $subject;
    /**
     * @var TEMailAddress
     */
    private $returnAddress;
    /**
     * @var string
     * Use TContentType:: constants
     */
    private $contentType;
    /**
     * @var TEmailValidator
     */
    private $addressValidator;
    /**
     * @var array
     * key = email address, value = error code
     */
    private $validationErrors;
    /**
     * @var array
     * key = email address, value = error code
     */
    private $validationWarnings;

    /** @var  array */
    private $attachments=array();

    private $timeStamp;

    private $options=array();

    private $tags=array();

    /**
     * @var \DateTime
     */
    private $deliverytime;

    private $headers = [];

    /**
     *
     */
    public function __construct() {
        $this->recipientList = array();
        $this->ccList = array();
        $this->bccList = array();

        $this->addressValidator = new TEmailValidator();
        $this->validationErrors = array();
        $this->validationWarnings = array();
    }

    /**** Content **********************/
    /**
     * @return int|string
     */
    public function getContentType() {
        $hasHtml = !empty($this->htmlContent);
        $hasText = !empty($this->textContent);
        $hasAttachments = !empty($this->attachments);
        if ($hasAttachments || ($hasHtml && $hasText)) {
            return TContentType::MultiPart;
        }
        if ($hasHtml) {
            return TContentType::Html;
        }
        if ($hasText) {
            return TContentType::Text;
        }
        return null;
    }


    /**
     * @return TEmailSendProperties | false
     */
    public function getSendProperties() {
        // $result = new \stdClass();
        $result = new TEmailSendProperties();
        $result->contentType = $this->getContentType();
        if (empty($result->contentType)) {
            $this->validationErrors[] = TLanguage::text('email-message-content-error');
            return false;
        }

        $result->to = $this->getRecipientsAsString();
        $result->from = $this->getFromAddressAsString();
        $result->text = $this->textContent;
        $result->html = $this->htmlContent;
        $result->attachments = $this->attachments;
        $result->subject = $this->getSubject();
        if ($this->ccList) {
            $cc = [];
            foreach ($this->ccList as $address) {
                $cc[] = $address->toString();
            }
            $result->cc = implode(',', $cc);
        }
        if ($this->bccList) {
            $bcc = [];
            foreach ($this->bccList as $address) {
                $bcc[] = $address->toString();
            }
            $result->bcc = implode(',', $bcc);
        }
        if ($result->to && $result->from && $result->subject) {
            return $result;
        }
        $this->validationErrors[] = TLanguage::text('email-message-incomplete');
        return false;
    }

    /**
     * @param $string
     * @return bool
     */
    private function containsScriptTags($string) {
        $badTags = array('</script>','</object>','</applet>');
        $string = preg_replace('/\s+/', '', $string);
        foreach($badTags as $tag) {
            if (stripos($string, $tag)) {
                return true;
            };
        }
        return false;
    }

    private function getPlainText($value) {
        $value = strip_tags($value);
        $value = str_replace ("\r\n", "\n", $value);
        return stripslashes($value);
    }

    private function setHtmlContent($value,$textPart=null) {
        $suspicious = $this->containsScriptTags($value);
        if ($suspicious) {
            $textPart = $value;
            $this->htmlContent = null;
        }
        else {
            $this->htmlContent = $value;
        }
        if ($textPart) {
            $this->textContent = $this->getPlainText($textPart);
        }

        return !$suspicious;
    }

    /**
     * @param $content
     * @param string $contentType  -  'html' | 'text' | or text content part
     * @param string $textPart  -  for backward compatibility, specify text part if $contentType == TContentType::Multipart.
     *      Prefer use of $contentType parameter to pass multi-part text.
     */
    public function setMessageBody($content, $contentTypeOrTextPart='html',$textPart = null)
    {
        switch($contentTypeOrTextPart) {
            case TContentType::Html :
                $htmlPart = $content;
                break;
            case TContentType::Text :
                $htmlPart = null;
                $textPart = $content;
                break;
            case TContentType::MultiPart :
                $htmlPart = $content;
                if (!$textPart) {
                    $textPart = $content;
                }
                break;
            default:
                $htmlPart = $content;
                $textPart = $contentTypeOrTextPart;
                break;
        }
        if ($htmlPart) {
            $this->setHtmlContent($htmlPart,$textPart);
        }
        else {
            $this->htmlContent = null;
        }
        if ($textPart) {
            $this->textContent = $this->getPlainText($content);
        }
        else {
            $this->textContent = null;
        }
    }  //  setMessageBody

    /**
     * @deprecated, use setMessageBody
     * @param $content
     * @param bool | string $setTextPart
     */
    public function setHtmlMessageBody($content, $setTextPart = false)
    {
        if ($setTextPart) {
            $this->setMessageBody($content,TContentType::MultiPart);
        }
       $this->setMessageBody($content,TContentType::Html);
    }

    /**
     * @deprecated use setMessageBody
     * @param $text
     */
    public function setAlternateBodyText($text)
    {
        $this->textContent = $this->getPlainText($text);
    }  //  setAlternateBodyText

    /**
     * @deprecated use getSendProperties
     * @return string
     */
    public function getMessageBody() {
        if (!empty($this->htmlContent)) {
            return $this->htmlContent;
        }
        return $this->textContent;
    }

    /**
     * @deprecated use getSendParameters
     * @return string
     */
    public function getTextPart() {
        return $this->textContent;
    }


    /** end content routines */

    private function toEmailAddress($address,$name=null)
    {
        if (empty($address)) {
            return null;
        }
        if (is_string($address)) {
            if ($name == null) {
                $address = TEmailAddress::FromString($address);
            } else {
                $address = new TEmailAddress($address, $name);
            }
        }
        return $this->validateEmailAddress($address);
    }

    /**
     * @return array
     */
    public function getValidationErrors() {
        return $this->validationErrors;
    }

    /**
     * @return string
     */
    public function getLastValidationError() {
        return @array_pop($this->validationErrors);
    }

    /**
     * @return array
     */
    public function getValidationWarnings() {
        return $this->validationWarnings;
    }

    /**
     * @return bool
     */
    public function hasErrors() {
        return empty($this->validationErrors);
    }

    /**
     * @return bool
     */
    public function hasWarnings() {
        return empty($this->validationWarnings);
    }

    /**
     * @param array $sourceList
     * @param $emailAddress
     * @return bool
     */
    private function removeAddress(array $sourceList, $emailAddress) {
        $index = $this->findAddress($sourceList, $emailAddress);
        if ($index === false)
            return false;
        unset($sourceList[$index]);
        return true;
    }

    /**
     * @param array $list
     * @param $searchAddress
     * @return bool|int
     */
    private function findAddress(array $list, $searchAddress) {
        if (empty($list))
            return false;

        for ($i=0; $i<sizeof($list); $i++) {
            if (self::AddressEquals($list[$i],$searchAddress))
                return $i;
        }
        return false;
    }

    /**
     * @param TEmailAddress $address
     * @param string $emailAddress
     * @return bool
     */
    public static function AddressEquals(TEmailAddress $address, $emailAddress) {
        return $address->getAddress() == $emailAddress;
    }

    /**
     * @param $emailAddress
     * @return bool
     */
    private function validateAddress($emailAddress) {
        if (empty($emailAddress)) {
            return false;
        }
        $isValid = $this->addressValidator->isValid($emailAddress);
        if ($this->addressValidator->hasWarnings()) {
            $this->validationWarnings[@$emailAddress] = $this->addressValidator->getWarnings();
        }
        if ($isValid) {
            return true;
        }
        $this->validationErrors[$emailAddress] = $this->addressValidator->getError();
        return false;
    }

    /**
     * @param TEmailAddress[] $list
     * @param TEmailAddress $emailAddress
     * @return bool
     * @internal param $name
     */
    public function addAddress(Array &$list, TEmailAddress $emailAddress) {

        $found = $this->findAddress($list,$emailAddress->getAddress());
        if ($found) {
            $list[$found] = $emailAddress;
        }
        else {
            array_push($list,$emailAddress);
        }
        return $found;
    }


    public function validateEmailAddress(TEmailAddress $emailAddress) {
        $isValid = $this->validateAddress($emailAddress->getAddress());
        if ($isValid ) {
            return $emailAddress;
        }
        return null;
    }


    /**
     * @param $recipient
     * @param string $name
     * @return bool
     */
    public function addRecipient($recipient, $name=null)
    {
        if (!$recipient instanceof TEmailAddress) {
            $recipient = $this->toEmailAddress($recipient,$name);
        }
        if ($recipient === null) {
            return false;
        }
        if ($this->removeAddress($this->bccList, $recipient->getAddress()) === false) {
            $this->removeAddress($this->ccList, $recipient->getAddress());
        }
        $this->addAddress($this->recipientList, $recipient, $name,$this->ccList);
        return true;
    }

    /**
     * @param $recipient
     * @param string $name
     * @return bool
     */
    public function addCC($recipient, $name='')
    {
        $recipient = $this->toEmailAddress($recipient,$name);
        if ($recipient === null) {
            return false;
        }

        if ($this->findAddress($this->recipientList,$recipient->getAddress()) !== false) {
            return true;
        }
        if ($this->findAddress($this->bccList,$recipient->getAddress()) !== false) {
            return true;
        }
        $this->addAddress($this->ccList, $recipient, $name);
        return true;
    }

    /**
     * @param $recipient
     * @param string $name
     * @return bool
     */
    public function addBCC($recipient, $name='')
    {
        $recipient = $this->toEmailAddress($recipient,$name);
        if ($recipient == null) {
            return false;
        }
        $this->removeAddress($this->ccList,$recipient);
        $this->addAddress($this->bccList, $recipient, $name,$this->ccList);
        return true;
    }



    /**
     * @param $recipients
     * @param null $name
     * @return int
     */
    public function setRecipient($recipients, $name=null)
    {
        $this->recipientList = Array();
        if (is_string($recipients) && empty($name)) {
            $recipients = explode(';',$recipients);
        }

        if (is_array($recipients)) {
            foreach($recipients as $recipient) {
                $this->addRecipient($recipient);
            }
        }
        else {
            $this->addRecipient($recipients, $name);
        }
        return sizeof($this->recipientList);
    }  //  setRecipient

    /**
     * @param $sender
     * @param null $name
     * @return TEmailAddress
     *
     * Address used in From: field
     */
    public function setFromAddress($sender, $name=null)
    {
        $this->fromAddress = $this->toEmailAddress($sender,$name);
        return $this->fromAddress;
    }  //  setFromAddress

    /**
     * @param $address
     * @param null $name
     * @return TEmailAddress
     *
     * Used in 'Return-Path' header, used to return invalid or blocked messages.
     */

    public function setReturnAddress($address, $name=null)
    {
        $this->returnAddress = $this->toEmailAddress($address,$name);
        return $this->returnAddress;
    }  //  setReturnAddress

    /**
     * @param $address
     * @param null $name
     * @return TEmailAddress
     *
     * The 'Reply-To' header may be use to redrect the recipients reply to a different address that the From: field
     */
    public function setReplyTo($address, $name=null)
    {
        $this->replyTo = $this->toEmailAddress($address,$name);
        return $this->replyTo;
    }  //  setReturnAddress

    /**
     * @param $value
     */
    public function setSubject($value)
    {
        $this->subject = stripslashes($value);
    }  //  setSubject


    /**
     * @return string
     */
    public function getSubject() {
        return $this->subject;
    }

    /**
     * @return TEmailAddress
     */
    public function getFromAddress() {
        return $this->fromAddress;
    }

    /**
     * @return string
     */
    public function getFromAddressAsString() : string {
        return $this->fromAddress->__toString();
    }

    /**
     * @param $address
     * @param null $name
     */
    public function setSender($address, $name=null) {
        $this->fromAddress = new TEmailAddress($address, $name);
    }

    /**
     * @return array|TEmailAddress[]
     */
    public function getRecipientList() {
        return $this->recipientList;
    }


    /**
     * @param array $list
     * @return array
     */
    private function addressesToArray(array $list) {
        $result = array();
        foreach ($list as $email) {
            $this->addAddressToArray($result, $email);
        }
        return $result;
    }

    /**
     * @param array $list
     * @param TEmailAddress $email
     */
    private function addAddressToArray(array &$list, TEmailAddress $email) {
        $name = $email->getName();
        if (empty($name)) {
            array_push($list,$email->getAddress());
        }
        else {
            $list[$email->getAddress()] = $name;
        }
    }

    /**
     * @param $list
     * @return string
     */
    private function addressesToString($list) {
        return implode(';',$list);
    }

    /**
     * @return array|TEmailAddress[]
     */
    public function getRecipients() {
        return $this->recipientList;
    }

    /**
     * @return array
     */
    public function getRecipientsAsArray() {
        return $this->addressesToArray( $this->recipientList );
    }

    /**
     * @return string
     */
    public function getRecipientsAsString() {
        return $this->addressesToString($this->recipientList);
    }

    /**
     * @return array|TEmailAddress[]
     */
    public function getCCs() {
        return $this->ccList;
    }

    /**
     * @return array
     */
    public function getCCsAsArray() {
        return $this->addressesToArray($this->ccList);
    }

    /**
     * @return string
     */
    public function getCCsAsString() {
        return $this->addressesToString($this->ccList);
    }

    /**
     * @return array|TEmailAddress[]
     */
    public function getBCCs() {
        return $this->bccList;
    }

    /**
     * @return array
     */
    public function getBCCsAsArray() {
        return $this->addressesToArray($this->bccList);
    }

    /**
     * @return string
     */
    public function getBCCsAsString() {
        return $this->addressesToString($this->bccList);
    }

    /**
     * @return TEMailAddress
     */
    public function getReturnAddress() {
        if (empty($this->returnAddress))
            return $this->fromAddress;
        return $this->returnAddress;
    }

    /**
     * @return TEmailAddress
     */
    public function getReplyTo() {
        if (empty($this->replyTo))
            return $this->fromAddress;
        return $this->replyTo;
    }


    public function getAddressCount() {
        return
            sizeof($this->recipientList) +
            sizeof($this->ccList) +
            sizeof($this->bccList);
    }

    public function setTimeStamp($value) {
        $this->timeStamp = $value;
    }

    public function getTimeStamp() {
        if (empty($this->timeStamp) ) {
            $this->timeStamp = time();
        }
        return $this->timeStamp;
    }

    public function addAttachment($path) {
        $this->attachments[] = $path;
    }

    public function getAttachments() {
        return $this->attachments;
    }

    public function setOption($key,$value=true) {
        $this->options[$key] = $value;
    }

    public function getOptions() {
        return $this->options;
    }
    public function addTag($value) {
        if (!in_array($value,$this->tags)) {
            $this->tags[] = $value;
        }
    }

    /**
     * @return array
     */
    public function getTags() {
        return $this->tags;
    }


    /**
     * @param $time string -- datetime format | +[number] [days | months | hours | minutes]
     * @return bool|\DateTime
     */
    public function setDeliveryTime($time) {
        $this->deliverytime = null;
        if (empty($time)) {
            return false;
        }
        try {
            $today = new \DateTime();
            if (substr($time, 0, 1) == '+') {
                $date = clone $today;
                $time = substr($time, 1);
                $interval = date_interval_create_from_date_string($time);
                $date->add($interval);
            } else {
                $date = new \DateTime($time);
            }
            if ($date == $today) {
                return false;
            }
            $this->deliverytime = $date; //$date->format(DateTime::RFC2822);
            return $this->deliverytime;
        }
        catch(\Exception $ex) {
            return false;
        }
    }

    /**
     * @return \DateTime
     */
    public function getDeliveryTime() {
        return $this->deliverytime;
    }

    public function addHeader($name,$text) {
        $this->headers[$name] = $text;
    }

    public function getHeaders() {
        return $this->headers;
    }
} // TMailMessage