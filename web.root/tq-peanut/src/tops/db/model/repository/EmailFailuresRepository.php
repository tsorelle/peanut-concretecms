<?php 
/** 
 * Created by /tools/create-model.php 
 * Time:  2025-05-26 13:24:22
 */
namespace Tops\db\model\repository;


use \PDO;
use PDOStatement;
use Tops\db\TDatabase;
use \Tops\db\TEntityRepository;
use Tops\db\model\entity\EmailFailure;

class EmailFailuresRepository extends \Tops\db\TEntityRepository
{
    protected function getTableName() {
        return 'qnut_email_failures';
    }

    public function getEntriesForEmail($email)
    {
        return $this->getEntityCollection('recipient=?',[$email]);

    }

    public function getErrorReports($request)
    {
        $response = new \stdClass();
        $filters = [];
        $params = [];
        $maxRows = $request->maxRows ?? 15;
        if (isset($request->startDate)) {
            $filters[] =  'reportedDate <= ?';
            $params[] = $request->startDate;
        }
        if (isset($request->errorlevel)) {
            $filters[] =  'errorlevel = ?';
            $params[] = $request->errorlevel;
        }
        if (isset($request->category)) {
            $filters[] =  'category = ?';
            $params[] = $request->category;
        }

        $select =
            'SELECT`id`,`reportedDate`,`recipient`, '.
            "  IF (`errorlevel` = 1, 'Permanent','Temporary') AS severity, ".
            '  `smtpCode`,`statusMessage`,`messageId`,`event`,`eventId`, '.
            '  `category`,`attemptNumber`,`sender`,`subject`,`from`,`to` ';

        $from =
            'FROM qnut_email_failures %s ORDER BY reportedDate DESC LIMIT 0, %d';

        $filterCount = count($filters);
        $where = $filterCount ? ' WHERE '.implode(' AND ', $filters) : '';
        $format = $select.$from;
        $sql = sprintf($select.$from, $where, $maxRows);
        $stmt = $this->executeStatement($sql, $params);
        $response->entries = $stmt->fetchAll(PDO::FETCH_OBJ);

        $count = count($response->entries);
        if ($count < $maxRows) {
            $response->nextDate = null;
        }
        else {
            $lastEntry = $response->entries[$count - 1];
            $lastDate = $lastEntry->reportedDate;

            if (isset($request->startDate)) {
                $params[0] = $lastDate;
            }
            else {
                array_unshift($params, $lastDate);
                if ($filterCount) {
                    array_unshift($filters, 'reportedDate < ?');
                }
                else {
                    $filters[] = 'reportedDate < ?';
                }
            }

            $select = 'SELECT reportedDate ';
            $where = ' WHERE '.implode(' AND ', $filters);
            $sql = sprintf($select.$from, $where, 1);
            $stmt = $this->executeStatement($sql, $params);
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            if (!empty($result)) {
                $response->nextDate = $result->reportedDate;
            }
        }
        return $response;
    }

    public function getEntriesByCategory(string $category)
    {
        return $this->getEntityCollection('category=?',[$category]);
    }

    protected function getDatabaseId() {
        return null;
    }

    protected function getClassName() {
         return 'Tops\db\model\entity\EmailFailure';
    }

    protected function getFieldDefinitionList()
    {
        return array(
        'id'=>PDO::PARAM_INT,
        'reportedDate'=>PDO::PARAM_STR,
        'recipient'=>PDO::PARAM_STR,
        'errorlevel'=>PDO::PARAM_INT,
        'smtpCode'=>PDO::PARAM_STR,
        'statusMessage'=>PDO::PARAM_STR,
        'messageId'=>PDO::PARAM_STR,
        'event'=>PDO::PARAM_STR,
        'eventId'=>PDO::PARAM_STR,
        'category'=>PDO::PARAM_STR,
        'attemptNumber'=>PDO::PARAM_INT,
        'sender'=>PDO::PARAM_STR,
        'subject'=>PDO::PARAM_STR,
        'from'=>PDO::PARAM_STR,
        'to'=>PDO::PARAM_STR);
    }

    public function eventPreviouslyLogged($eventId)
    {
        $sql = 'SELECT COUNT(*) FROM '.$this->getTableName().
            ' WHERE eventId = ?';
        $count = $this->getValue($sql,[$eventId]);
        return $count > 0;
    }

    public function insertEntry(EmailFailure $emailFailure) {
        // cannot use the ususal EntityRepository->insert, due to field names that match reserved words.
        // try to fix that later, meanwhile this does the job.
        $sql = 'INSERT INTO '.$this->getTableName().
            '(  `reportedDate`, `recipient`,`errorlevel`,`smtpCode`, `statusMessage`, '.
            '  `messageId`,`event`,`eventId`,`category`,`attemptNumber`,`sender`, '.
            '  `subject`,`from`,`to` )'.
            ' VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $stmt = $this->executeStatement($sql,
            [$emailFailure->reportedDate,
                $emailFailure->recipient,
                $emailFailure->errorlevel,
                $emailFailure->smtpCode,
                $emailFailure->statusMessage,
                $emailFailure->messageId,
                $emailFailure->event,
                $emailFailure->eventId,
                $emailFailure->category,
                $emailFailure->attemptNumber,
                $emailFailure->sender,
                $emailFailure->subject,
                $emailFailure->from,
                $emailFailure->to]
        );
        return $stmt->rowCount();
    }

    public function truncateEntries($timeFrame = '-3 months')
    {
        $date = new \DateTime();
        $date->modify($timeFrame);
        $dateStr =  $date->format('Y-m-d H:i:s');
        $sql = "delete from ".$this->getTableName().' WHERE errorlevel = 2 AND reportedDate < ?' ;
        $stmt = $this->executeStatement($sql, [$dateStr]);
        $lastErrorCode = $stmt->errorCode();
        if ($lastErrorCode == PDO::ERR_NONE) {
            return $stmt->rowCount();
        }
        return -1;
    }
}