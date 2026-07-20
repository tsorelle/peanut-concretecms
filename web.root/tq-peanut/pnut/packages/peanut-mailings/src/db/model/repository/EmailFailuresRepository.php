<?php

namespace Peanut\PeanutMailings\db\model\repository;

use Tops\db\TPdoQueryManager;

class EmailFailuresRepository extends TPdoQueryManager
{
    /* This table is a log - Records appended and retrieved only
        Structure:
    id                 auto_increment
    reportedDate       datetime
    recipient          varchar(128)
    errorlevel         varchar(10)
    smtpCode               varchar(16)
    statusMessage      varchar(256)
    statusDescription  varchar(256)
    messageId          varchar(128)
    **********************************************/


    protected function getDatabaseId()
    {
        return 'tops-db';
    }

    /*
     * Calling routine responsibe for providing all parameters in the $entry object
     */
    public function addEntry($entry) {
        $sql =
            'INSERT INTO qnut_email_failures ( '.
            'reportedDate, recipient, errorlevel, smtpCode,'.
            'statusMessage,statusDescription, messageId, event '.
            ') VALUES (NOW(),?,?,?,?,?,?,?)';
        $params = [
            $entry->recipient,
            $entry->errorLevel,
            $entry->code,
            $entry->statusMessage,
            $entry->description,
            $entry->messageId,
            $entry->event
        ];

        $this->executeStatement($sql,$params);
    }

    public function getEntries($startDate = null) {
        $sql = 'SELECT * from qnut_email_failures';
        $params = [];
        if ($startDate) {
            $sql .= ' WHERE reportedDate >= ?';
            $params = [$startDate];
        }

        $stmt = $this->executeStatement($sql,$params);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    public function checkForBounce($messageId) {
        /*
         * Checks email failure log for bounces based on message id and these SMTP error codes.
            '501', -- probably invalid email
            '510', '511', -- Bad email
            '513', -- incorrect email
            '550', -- Non existent address
            '553', -- Mailbox name invalid
            '554', 	-- blacklisted?  sometimes it is an account program. Not included, check the error logs directly
            '605', '607' -- MailGun, previously bounced

        See SMTP error codes listing:
        https://serversmtp.com/smtp-error/
         */

        $sql = "SELECT COUNT(*) FROM qnut_email_failures  WHERE smtpCode IN ('501','510','511','513','550','553','605','607') AND messageId = ?";
        $result = $this->getValue($sql,[$messageId]);
        return ($result > 0);
    }

}