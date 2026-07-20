<?php

namespace Peanut\PeanutMailings\db\model\repository;

use Peanut\PeanutMailings\db\model\entity\EmailMessage;
use Peanut\PeanutMailings\sys\ISubscriptionManager;
use Tops\db\TPdoQueryManager;

// todo: move to qnut-directory
class QnutSubscriptionsManager extends TPdoQueryManager
    implements ISubscriptionManager
{

    protected function getDatabaseId()
    {
        return null;
    }


    public function getUid($subscriberId): string
    {
       $sql = 'SELECT uid FROM qnut_persons WHERE id=?';
       $params = [$subscriberId];
       return $this->getValue($sql, $params);
    }

    public function queueEmailRecipients($messageId, $listId): int
    {
        $count = 0;
        $sql =
            'INSERT INTO qnut_email_queue (mailMessageId,personId,toAddress,toName) ' .
            "SELECT $messageId AS mailMessageId, p.uid as personId, p.email,p.fullName " .
            'FROM qnut_email_subscriptions s JOIN qnut_persons p ON s.personId = p.id ' .
            "WHERE (p.email IS NOT NULL AND TRIM(p.email) <> '') " .
            'AND s.listId = ?';

        $stmt = $this->executeStatement($sql, [$listId]);
        $count = $stmt->rowCount();
        return $count;
    }

    /**
     * @param int $messageId
     * @param array $recipients
     * @return int
     */
    public function queueEmailRecipientList($messageId, array $recipients): int
    {
        $count = 0;
        $sql =
            'INSERT INTO qnut_email_queue (mailMessageId,personId,toAddress,toName) ' .
            'SELECT  ' . $messageId . ' AS mailMessageId, p.uid AS personId, p.email,p.fullName ' .
            'FROM qnut_persons p ' .
            "WHERE (p.email IS NOT NULL AND TRIM(p.email) <> '') " .
            'AND p.id = ?';

        foreach ($recipients as $recipientId) {
            $stmt = $this->executeStatement($sql, [$recipientId]);
            $count += $stmt->rowCount();
        }
        return $count;
    }

    public function unsubscribeByUid($uid,$listId) {

        $findQuery = 'SELECT p.fullname as personName, l.name AS listName '.
            'FROM qnut_email_subscriptions s '.
            'JOIN qnut_persons p ON s.personId = p.id '.
            'JOIN qnut_email_lists l ON l.id = s.listId '.
            'WHERE p.uid = ? AND s.listId = ?';
        $stmt = $this->executeStatement($findQuery,[$uid,$listId]);
        $result =  $stmt->fetch(\PDO::FETCH_OBJ);
        if (!$result) {
            return false;
        }

        $deleteQuery = 'DELETE s FROM qnut_email_subscriptions s '.
            'JOIN qnut_persons p ON s.personId = p.id '.
            'WHERE p.uid = ? AND s.listId = ?';

        $this->executeStatement($deleteQuery,[$uid,$listId]);
        return $result;
    }


    public function subscribeLists($subscriberId, array $listIds): int
    {
        // see personsRepository.addSubscriptions in qnut-directory
        return 0;
    }
}

     // May be obsolete
    /*
    public function queueMessages($messageId,$listId) {
        $sql = 'INSERT INTO qnut_email_message_queue (mailMessageId,personId) '.
            'SELECT $messageId AS mailMessageId, p.uid AS personId '.
            'FROM qnut_email_subscriptions s '.
            'JOIN qnut_persons p ON s.personId = p.id '.
            'WHERE s.listId = ?';
        $stmt = $this->executeStatement($sql, [$listId]);
        return $stmt->rowCount();
    }



     */


