<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 10/30/2017
 * Time: 6:22 AM
 */

namespace Peanut\PeanutTasks;


use Tops\db\EntityRepositoryFactory;
use Tops\services\MessageType;
use Tops\services\TServiceCommand;
use Tops\services\TServiceMessage;
use Tops\services\TServiceResponse;
use Tops\sys\TConfiguration;
use Tops\sys\TDates;
use Tops\sys\TSession;
use Tops\sys\TStrings;
use Tops\sys\TSystemUser;
use Tops\sys\TUser;
use Tops\sys\TWebSite;

class TaskManager
{
    /**
     * @var TaskLogRepository
     */
    private $logRepository;
    /**
     * @var TaskQueueRepository
     */
    private $queueRepository;

    public function __construct($repositoryNamespace = 'Tops\\db\\model\\repository')
    {
        $this->logRepository = new TaskLogRepository();
        $this->queueRepository = new TaskQueueRepository();
    }

    private function initSession() {
        global $_SESSION;
        global $_COOKIE;
        if (!isset($_SESSION)) {
            $_SESSION = array();
        }
        if (!isset($_COOKIE)) {
            $_COOKIE = array();
        }
        TSession::Initialize();
        return $_SESSION['tops']['security-token'];
    }

    /**
     * @param TaskQueueEntry $item
     * @throws \Exception
     */
    private function checkReady(TaskQueueEntry $item) {
        if ($item->intervalType == 1) {
            // run on demand
            return true;
        }
        $last = $this->logRepository->getLastEntry($item->taskname);
        $lastRun = (empty($last) || $last->type != TaskLogEntryType::EndSession) ? false : $last->time;
        $readyResponse = $item->readyToRun($lastRun);
        if ($readyResponse->error) {
            $this->addLogEntry("readyToRun function returned error: $readyResponse->error",$item->taskname);
            return false;
        }
        return $readyResponse->ready;
    }

    /**
     * @param int $id
     * @throws \Exception
     */
    public function executeTasks($id=0) {
        $user = TUser::getCurrent();
        if (!$user->isAdmin()) {
            $this->Exit($user->getUserName()." lacks administrator permission requiered to run tasks.");
        }
        $securityToken = $this->initSession();
        if ($id) {
            $item = $this->queueRepository->get($id);
            if (empty($item)) {
                $this->addLogEntry("Task $id not found.",'session',MessageType::Error);
                return;
            }
            if ($this->checkReady($item)) {
                $runlist = [$item];
            }
        }
        else {
            $queue = $this->queueRepository->getCurrent();
            $runlist = array();
            foreach ($queue as $item) {
                if ($item->intervalType == 1) {
                    // ignore run on demand items in batch process
                    continue;
                }
                if ($this->checkReady($item)) {
                    $runlist[] = $item;
                }
            }
        }

        if (empty($runlist)) {
            $this->addLogEntry('No tasks to run','session',TaskLogEntryType::Info);
        }
        else {
            $this->addLogEntry('Start session','session',TaskLogEntryType::Info);
            foreach ($runlist as $item) {
                $this->runTask($item,$securityToken);
            }
            $this->addLogEntry('End session. '.sizeof($runlist).' tasks processed','session',TaskLogEntryType::Info);
        }
    }

    /**
     * Standalone task runner for testing.
     *
     * @param $taskName
     * @return string|TServiceResponse
     * @throws \Exception
     */
    public function doTestRun($taskName,$input=null)
    {
        $queueRepository = new TaskQueueRepository();
        $entry = $queueRepository->getEntity($taskName,true,'taskname');
        if (empty($entry)) {
            return 'No task found for task '.$taskName.'.';
        }
        // $this->addLogEntry('Running task',$entry->taskname,TaskLogEntryType::StartSession);
        $cmd = self::getServiceClass($entry->taskname,$entry->namespace);
        if ($cmd === false) {
            return "No service command for '$entry->taskname'";
        }
        $input = $this->decodeInput($input ?? $entry->inputs);

        $response = $cmd->execute($input,false);
        if ($response === null) {
            return 'No service response';
        }
        if (empty($entry->active)) {
            $cmd->addWarningMessage("$taskName is inactive on the schedule");
        }
        return $response;
    }

    private $notifyLevel;
    private $notifyEmail;
    private function notify($message,$subject,$level=3) {
        if (!isset($this->notifyLevel)) {
            $this->notifyEmail = TConfiguration::getValue('notifyemail','site');
            if ($this->notifyEmail) {
                $this->notifyLevel = TConfiguration::getValue('notify','site',0);
            }
            else {
                $this->notifyLevel = 0;
            }
        }
        if ($this->notifyLevel >= $level) {
            mail($this->notifyEmail,$subject,$message);
        }
    }

    private function addLogEntry($message,$taskname,$entryType=0) {
        $entry = TaskLogEntry::Create($message,$taskname,$entryType);
        $this->notify($message,"Task Log message for $taskname");
        $this->logRepository->insert($entry,'tasks');
    }

    private function Exit($message) {
        $this->notify($message,'Task script failed',1);
        exit ($message);
    }

    /**
     * @param $taskname
     * @return bool|TServiceCommand
     * @throws \Exception
     */
    public static function getServiceClass($taskname,$namespace=null)
    {
        $serviceId = TStrings::toCamelCase($taskname);
        if (empty($namespace)) {
            $namespace = TConfiguration::getValue('applicationNamespace', 'services');
            if (empty($namespace)) {
                throw new \Exception('For default service, "applicationNamespace=" is required in settings.ini');
            }
            $namespace .= "\\" . TConfiguration::getValue('servicesNamespace', 'services', 'services');
        } else {
            $parts = explode('::', $namespace);
            if (sizeof($parts) === 1) {
                $namespace = "$namespace\\services";
            } else {
                $subdir = array_pop($parts);
                $namespace = $parts[0] . "\\services\\$subdir";
            }
            // $namespace = TStrings::formatNamespace($namespace)."\\services";
        }

        // get subdirectories  e.g. where serviceId is 'subdirectory.serviceId'
        $serviceId = str_replace('.', "\\", $serviceId);
        $className = $namespace . "\\" . $serviceId . 'Command';
        if (!class_exists($className)) {
            return false;
        }
        /**
         * @var $cmd TServiceCommand
         */
        $cmd = new $className();
        return $cmd;

    }

    private function decodeInput($input)
    {
        if (is_string($input) && strpos($input, '=') !== false) {
            $args = explode(';', $input);
            $request = new \stdClass();
            foreach ($args as $arg) {
                list($key, $value) = explode('=', $arg);
                if ($value === null) {
                    $value = 0;
                }
                $key = trim($key);
                $request->$key = trim($value);
            }
            return $request;
        }
        return trim($input);
    }

    /**
     * @param $taskname
     * @param $input
     * @param $securityToken
     */
    private function runTask(TaskQueueEntry $entry,$securityToken)
    {
        try {
            $this->addLogEntry('Running task',$entry->taskname,TaskLogEntryType::StartSession);
            $cmd = self::getServiceClass($entry->taskname,$entry->namespace);
            if ($cmd === false) {
                $this->addLogEntry("No service command for '$entry->taskname'",$entry->taskname,TaskLogEntryType::Failure);
                return;
            }
            $input = $this->decodeInput($entry->inputs);
            $response = $cmd->execute($input,$securityToken);
            if ($response === null) {
                $this->addLogEntry('No service response',$entry->taskname,TaskLogEntryType::Failure);
                return;
            }
            foreach ($response->Messages as $item) {
                $this->addLogEntry($item->Text,$entry->taskname,$item->MessageType);
            }
        } catch (\Exception $ex) {
            $this->addLogEntry('Exception: '.$ex->getMessage(),$entry->taskname,TaskLogEntryType::Failure);
            return;
        }
        $this->addLogEntry('Completed task',$entry->taskname,TaskLogEntryType::EndSession);
    }

    public static function Run($taskId)
    {
        (new TaskManager())->executeTasks($taskId);
    }

    public function runJobs($taskId = null)
    {
        try {
            ini_set('max_execution_time', 1800);
            $environment = TWebSite::GetEnvironmentName();
/*            if ($environment !== 'local') {
                $secure = TConfiguration::getBoolean('secured', 'site', true);
                if ($secure) {
                    // If remote address not in $_SERVER, we are running a test in dev environment.
                    // Otherwise running script on server.  Ensure no remote access.
                    if (isset($_SERVER['REMOTE_ADDR']) && ($_SERVER['SERVER_ADDR'] !== @$_SERVER['REMOTE_ADDR'])) {
                        $error = 'Remote script login attempted from remote IP: ' . $_SERVER['REMOTE_ADDR'];
                        $this->Exit($error);
                    }
                }
            }*/

            TUser::setCurrentUser(new TSystemUser());
            $tz = TConfiguration::getValue('timezone', 'site');
            if ($tz) {
                date_default_timezone_set($tz);
            }
            $this->executeTasks($taskId);
        } catch (\Exception $ex) {
            $content = $ex->getMessage() . "\n\n" . $ex->getTraceAsString();
            $this->notify($content, 'Task process exception', 1);
            exit ($ex->getMessage());
        }
    }
}