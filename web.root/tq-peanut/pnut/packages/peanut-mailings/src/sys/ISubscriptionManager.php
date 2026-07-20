<?php

namespace Peanut\PeanutMailings\sys;
use Peanut\PeanutMailings\db\model\entity\EmailMessage;

interface ISubscriptionManager
{
    public function getUid($subscriberId) : string;
    public function queueEmailRecipients($messageId, $listId): int;
    public function queueEmailRecipientList($messageId, array $recipients): int;
    public function unsubscribeByUid($uid,$listId);
    // might be obsolete
    // public function subscribeLists($subscriberId,array $listIds): int;
    // might be obsolete
    // public function queueMessages($messageId,$listId);
}