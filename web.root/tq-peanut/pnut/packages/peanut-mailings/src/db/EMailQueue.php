<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 12/14/2017
 * Time: 2:01 AM
 */

namespace Peanut\PeanutMailings\db;


use Peanut\PeanutMailings\db\model\entity\EmailList;
use Peanut\PeanutMailings\db\model\entity\EmailMessage;
use Peanut\PeanutMailings\db\model\entity\EmailMessageRecipient;
use Peanut\PeanutMailings\db\model\repository\EmailListsRepository;
use Peanut\PeanutMailings\db\model\repository\EmailMessagesRepository;
use Peanut\PeanutMailings\sys\MailTemplateManager;
use Soundasleep\Html2Text;
use Tops\mail\TContentType;
use Tops\mail\TEmailAddress;
use Tops\mail\TEMailMessage;
use Tops\mail\TPostOffice;
use Tops\services\MessageType;
use Tops\services\TProcessManager;
use Tops\sys\IUser;
use Tops\sys\TConfiguration;
use Tops\sys\TDates;
use Tops\sys\TTemplateManager;
use Tops\sys\TWebSite;

class EMailQueue
{

    /**
     * @var MailTemplateManager
     */
    private $templateManager;
    private $messageFooter = ['html' => '', 'txt' => ''];
    private $siteUrl;
    public function __construct()
    {
        $this->siteUrl = TWebSite::GetSiteUrl();
    }

    private function initializeTemplates() {
        $this->templateManager = new MailTemplateManager();
        $footerTemplate = $this->templateManager->getTemplateContent('footer.html');
        $this->messageFooter['html'] = empty($footerTemplate) ? '' :
            TTemplateManager::ReplaceContentTokens($footerTemplate, ['siteUrl' => $this->siteUrl]);;
        $footerTemplate = $this->templateManager->getTemplateContent('footer.txt');
        $this->messageFooter['txt'] = empty($footerTemplate) ? '' :
            TTemplateManager::ReplaceContentTokens($footerTemplate, ['siteUrl' => $this->siteUrl]);;
    }

    const processCode = 'email-queue-send';

    private static $messagesRepository;
    public static function getMessagesRepository(){
        if (!isset(self::$messagesRepository)) {
            self::$messagesRepository = new EmailMessagesRepository();
        }
        return self::$messagesRepository;
    }

    /**
     * @var TProcessManager
     */
    private $process;
    public static function QueueMessageList($messageDto, $username, array $recipients=null) {
        $message = EmailMessage::Create($messageDto, $username);
        return (self::getMessagesRepository())->queueMessageList($message,$recipients);
    }

    public static function QueueSingleMessage($messageDto, $toAddress, $toName, $username,$instance=null) {
        $message = EmailMessage::Create($messageDto, $username);
        return (self::getMessagesRepository())->queueMessage($message,$toAddress,$toName);
    }

    public static function UserToEmailRecipient(IUser $user)
    {
        $recipient = new EmailMessageRecipient();
        $recipient->id = $user->getId();
        $recipient->toName = $user->getDisplayName();
        $recipient->toAddress = $user->getEmail();
        return $recipient;
    }

    public static function SendTestMessage($messageDto, EmailMessageRecipient $recipient, $listId=1, $username = 'testuser') {
        if (!$recipient->personId) {
            $recipient->personId='testusertoken';
        }
        $queue = new EMailQueue();
        $queue->initializeTemplates();
        $message = EmailMessage::Create($messageDto,$username);
        return $queue->sendMessage($recipient,$message);
    }

    public static function Send($messageId,$instance = null) {

        if ($instance === null) {
            $instance = new EMailQueue();
        }
        return $instance->sendMessages(0,$messageId);
    }

    public static function ProcessMessageQueue($sendLimit=0) {
        $instance = new EMailQueue();
        return $instance->sendMessages($sendLimit);
    }

    public static function Pause($reason='interrupt message processsing',$interval='1 hour') {
        $process = TProcessManager::Get(self::processCode);
        if ($process !== false) {
            $paused = $process->pauseProcess($reason,$interval);
            if ($paused) {
                return TDates::reformatDateTime($paused,'g:i:s A');
            }
        }
        return false;
    }

    public static function Restart()
    {
        $process = TProcessManager::Get(self::processCode);
        if ($process !== false) {
            return $process->startProcess();
        }
        return false;
    }

    public static function isPaused($format='g:i:s A') {
        $process = TProcessManager::Get(self::processCode);
        if ($process !== false) {
            $paused = $process->isPaused();
            if ($paused !== false) {
               return TDates::reformatDateTime($paused,$format);
            }
        }
        return false;
    }

     /**
     * @var EmailMessage[];
     */
    private $messages;
    private function getMessage($id)
    {
        foreach ($this->messages as $message) {
            if ($message->id === $id)
                return $message;
        }
        return false;
    }

    private function startProcess() {
        $this->process = TProcessManager::CreateProcess(self::processCode,'Send email','Process outgoing email in queue');
    }

    private function log($message,$messageType=MessageType::Info, $event='process mail',$detail=null)
    {
        if (isset($this->process)) {
            $this->process->log($event,$message,$messageType,$detail);
        }
    }
    
    private function logError($message,$event='mail process error',$detail=null) {
        if (isset($this->process)) {
            $this->process->logError($event,$message,$detail);
        }
    }

    private function logWarning($message,$event='mail process warning',$detail=null) {
        if (isset($this->process)) {
            $this->process->logWarning($event,$message,$detail);
        }
    }

    /**
     * @var string[]
     */
    private $templates=[];
    private function getTemplateContent($template)
    {
        if (!array_key_exists($template, $this->templates)) {
            $templateContent = $this->templateManager->getTemplateContent($template);
            if (empty($templateContent)) {
                $this->logError("No template content found for '$template'");
            }
            $this->templates[$template] = $templateContent;
         }
        return $this->templates[$template];
    }

    public function mergeContent(EmailMessageRecipient $recipient, $messageText,$template='',$listId=0) {
        if (!empty($template)) {
            $template = $this->getTemplateContent($template);
            if (empty($template)) {
                return 0;
            }
            $templateType = strpos($template,'.txt') === false ? 'html' : 'txt';
            $messageText = TTemplateManager::ReplaceContentTokens($template, ['content' => $messageText,'footer' => $this->messageFooter[$templateType]]);
        }

        $messageText = TTemplateManager::ReplaceContentTokens($messageText, [
            'personId' => $recipient->personId,
            'listId' => $listId,
            'recipientName' => $recipient->toName,
            'siteUrl' => $this->siteUrl,
            'unsubscribe-url' => $this->getUnsubscribeUrl($recipient->personId,$listId)
        ]);
        return $messageText;
    }

    /**
     * @var array EmailList[]
     */
    private $lists;
    private function  getList($listId) {
        if ($listId > 0) {
            if (!isset($this->lists)) {
                $this->lists = (new EmailListsRepository())->getAll(true);
            }
            /**
             * @var $list EmailList
             */
            foreach ($this->lists as $list) {
                if ($list->id == $listId) {
                    return $list;
                }
            }
        }
        return false;
    }


    private $unsubscribeHeader;
    public function getUnsubscribeHeader($personId,$listId='') {
        if (!isset($this->unsubscribeHeader)) {
            $this->unsubscribeHeader = TConfiguration::getValue('list-unsubscribe','mail');
        }

        return $this->unsubscribeHeader ?
            str_replace('[[uid]]',$personId,
                str_replace('[[listId]]',$listId,$this->unsubscribeHeader))
        : false;
    }

    public function sendMessage(EmailMessageRecipient $recipient, EmailMessage $message,$logging = true)
    {
        $contentType = empty($message->contentType) ? 'html' : $message->contentType;
        $template = $message->template;
        if (!empty($template)) {
            $ext = pathinfo($template, PATHINFO_EXTENSION);
            if (!$ext) {
                $template .= '.'.$contentType;
            }
        }
        $to = TEmailAddress::Create($recipient->toAddress, $recipient->toName);
        if (empty($to)) {
            $this->logWarning("Invalid email address, $recipient->toAddress");
            return 0;
        }
        $messageText = $this->mergeContent($recipient, $message->messageText,
            empty($template) ? '' : $template, $message->listId);

        if ($contentType === TContentType::Html) {
            try {
                $textPart = Html2Text::convert($messageText,['ignore_errors' => true]);
            }
            catch(\Exception $ex) {
                $textPart = strip_tags($messageText);
            }
        }
        else {
            $textPart = null;
        }

        $alias = null;
        $tag = null;
        if ($message->listId) {
            $list = $this->getList($message->listId);
            if ($list !== false) {
                $alias = $list->name;
                $tag = $list->code;
            }
        }

        /**
         * @var TEMailMessage
         */
        $messageObject = TPostOffice::CreateMessage(
            $to,
            $message->sender,
            $alias,
            $message->subject,
            $messageText,
            $textPart ?? $message->contentType,
            $message->contentType,
            $message->replyAddress
        );
        if (!empty($tag)) {
            $messageObject->addTag($tag);
        }

        $listUnsubscribe = $this->getUnsubscribeHeader($recipient->personId,$message->listId);
        if ($listUnsubscribe) {
            $messageObject->addHeader('List-Unsubscribe',$listUnsubscribe);
        }

        // mail('terry.sorelle@outlook.com','Message queue test.','sent message to '.$recipient->toAddress);
        $sendResult = TPostOffice::Send($messageObject);

        // todo: implement time out for message that cant be sent.
        // todo: look at detecting invalid domain estenstion
        // todo: look at logging temporary failures or timeouts
        if ($sendResult !== true) {
            $this->log("Mail server failed to send. Recipient id: $recipient->id, Message id: $message->id");
            return -1; // try again later.
        }
        return 1;
    }

    /**
     * @param EmailMessageRecipient $recipient
     * @return int  0 = error; 1 = sent; -1 = retry
     */
    public function sendQueuedMessage(EmailMessageRecipient $recipient)
    {
        $message = $this->getMessage($recipient->mailMessageId);
        if (empty($message)) {
            $this->log("Mail message #$recipient->mailMessageId not found");
            return 0;
        }
        return $this->sendMessage($recipient,$message);
    }

    /**
     * @param int $sendLimit
     * @param int $messageId
     * @return int
     * @throws \Exception
     */
    public function sendMessages($sendLimit = 0, $messageId=0)
    {

        // mail('terry.sorelle@outlook.com','Message queue test.',"sending messages: limit=$sendLimit");
        $sendCount = 0;
        $this->initializeTemplates();
        $this->startProcess();
        try {
            $this->log("Sending ".
                ($sendLimit ? $sendLimit : 'all').
                " messages for message #$messageId");
            $repository = self::getMessagesRepository();
            if ($messageId) {
                $message = $repository->get($messageId);
                $this->messages = [$message];
            } else {
                $this->messages = $repository->getQueuedMessages();
            }
            $recipients = $repository->getMessageRecipients($sendLimit, $messageId);
            if (empty($recipients)) {
                if ($messageId > 0) {
                    $this->log("No recipients were found for message #$messageId");
                }
                else {
                    $this->log("No messages waiting.");
                }
                return 0;
            }
            foreach ($recipients as $recipient) {
                if ($this->process->isPaused()) {
                    break;
                }

                $exceptionCount = 10;
                $result = $this->sendQueuedMessage($recipient);

                switch ($result) {
                    case 1:
                        $repository->undateQueue($recipient->id);
                        $sendCount++;
                        break;
                    case 0:
                        $repository->unqueue($recipient->id);
                        break;
                    case -1:
                        if ($exceptionCount-- == 0) {
                            throw new \Exception('Mail process terminated');
                        }
                        // ignore and leave for next round.
                        break;
                }
            }
            if ($sendCount > 0) {
                $this->log("Sent $sendCount messages.");
            }
        }
        catch (\Exception $ex) {
            $this->process->handleException($ex,'Email processing failure',false);
        }
        return $sendCount;
    }

    private $unsubscribeUrl;
    private function getUnsubscribeUrl($personId,$listId)
    {
        if (!isset($this->unsubscribeUrl)) {
            $url = TConfiguration::getValue('unsubscribeUrl','mail','/unsubscribe');
            if (strpos($url,'/') === 0) {
                $url = $this->siteUrl.$url;
            }
            else {
                $lower = strtolower($url);
                if (substr($lower,1,5) !== 'http:' && substr($lower,1,6) !== 'https') {
                    $url = $this->siteUrl.'/'.$url;
                }
            }
            $this->unsubscribeUrl = $url;
        }
        return $this->unsubscribeUrl."?uid=$personId&listId=$listId";
    }

    public static function GetStatus()
    {
        $response = new \stdClass();
        $isPaused = EMailQueue::isPaused();
        if ($isPaused) {
            $response->status = 'paused';
            $response->pausedUntil = $isPaused;
        }
        else {
            $response->status = self::GetActiveStatus();
            $response->pausedUntil  = '';
        }
        return $response;
    }

    public static function GetActiveStatus() {
        $activeCount = (self::getMessagesRepository())->getActiveMessageCount();
        return $activeCount ? 'active' : 'ready';
    }

    public static function GetMessageHistory() {
        return (self::getMessagesRepository())->getMessageHistory();
    }

    public static function RemoveMessage($messageId)
    {
        (self::getMessagesRepository())->removeMessage($messageId);
    }

}