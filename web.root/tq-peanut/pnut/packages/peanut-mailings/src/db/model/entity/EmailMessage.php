<?php 
/** 
 * Created by /tools/create-model.php 
 * Time:  2017-11-15 13:11:28
 */ 

namespace Peanut\PeanutMailings\db\model\entity;

class EmailMessage
{
    public $id;
    public $listId;
    public $sender;
    public $replyAddress;
    public $subject;
    public $messageText;
    public $contentType;
    public $template;
    public $tags;
    public $recipientCount;
    public $postedDate;
    public $postedBy;
    public $active;

    public static function Create($dto,$username)
    {
        $message = new EmailMessage();
        $today = new \DateTime();
        $message->postedDate = $today->format('Y-m-d H:i:s');
        $message->postedBy = $username;
        $message->listId		= empty($dto->listId) ? 0 : $dto->listId;
        $message->sender       = empty($dto->sender) ? '' : $dto->sender;
        $message->replyAddress = empty($dto->replyAddress) ? '' : $dto->replyAddress;
        $message->subject      = $dto->subject;
        $message->messageText  = $dto->messageText;
        $message->contentType  = $dto->contentType;
        $message->template     = empty($dto->template) ? '' : $dto->template;
        $message->tags         = empty($dto->tags) ? null : $dto->tags;
        $message->recipientCount = 0;
        $message->active = 1;
        return $message;
    }
}