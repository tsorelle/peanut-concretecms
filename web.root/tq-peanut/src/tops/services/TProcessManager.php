<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 12/15/2017
 * Time: 12:50 PM
 */

namespace Tops\services;


use Tops\db\model\entity\Process;
use Tops\db\model\entity\ProcessLogEntry;
use Tops\db\model\repository\ProcessesRepository;
use Tops\db\model\repository\ProcessLogRepository;
use Tops\sys\TDates;
use Tops\sys\TWebSite;

class TProcessManager
{
    /**
     * @var ProcessesRepository
     */
    private static $processRepository;
    /**
     * @var ProcessLogRepository
     */
    private static $logRepository;

    private static $managers = [];
    
    private static function getProcessRepository()
    {
        if (!isset(self::$processRepository)) {
            self::$processRepository = new ProcessesRepository();
        }
        return self::$processRepository;
    }

    private static function getlogRepository()
    {
        if (!isset(self::$logRepository)) {
            self::$logRepository = new ProcessLogRepository();
        }
        return self::$logRepository;
    }

    private $code;

    public function __construct($code)
    {
        $this->code = $code;
    }
    
    public static function CreateProcess($code,$name,$description) {
        $repository = self::getProcessRepository();
        /**
         * @var $process Process
         */
        $process = $repository->getEntity($code);
        if (empty($process)) {
            $process = new Process();
            $process->code = $code;
            $process->name = $name;
            $process->description = $description;
            $process->paused = null;
            $repository->insert($process);
        }
        return self::Start($code);
    }

    public static function Start($code) {
        $processManager = new TProcessManager($code);
        $started = $processManager->startProcess();
        if ($started) {
            self::$managers[$code] = $processManager;
            return $processManager;
        }
        return false;
    }

    public static function Get($code) {
        if (array_key_exists($code,self::$managers)) {
            return self::$managers[$code];
        }
        $processManager = new TProcessManager($code);
        self::$managers[$code] = $processManager;
        return $processManager;
    }

    private function getProcess() {
        $repository = self::getProcessRepository();
        /**
         * @var $process Process
         */
        $process = $repository->getEntity($this->code);
        if (empty($process)) {
            $this->log('start','error:process not defined.');
            return false;
        }
        return $process;
    }

    public function pauseProcess($reason='process paused',$interval='1 hour') {
        $process = $this->getProcess();
        if ($process === false) {
            return false;
        }
        $expiry =  new \DateTime();
        $expiry->add(TDates::StringToInterval($interval));
        $process->paused = $expiry->format(TDates::MySqlDateTimeFormat);
        self::getProcessRepository()->update($process);
        $this->log('pause',$reason);
        return $process->paused;
    }

    public function isPaused() {
        $process = $this->getProcess();
        if ($process === false || $process->paused === null) {
            return false;
        }
        $diff = TDates::CompareWithNow($process->paused);
        return ($diff == TDates::After) ? $process->paused : false;
    }

    public function startProcess() {
        /**
         * @var $process Process
         */
        $process = $this->getProcess();
        if ($process === false) {
            return false;
        }
        if ($process->paused !== null) {
            $process->paused = null;
            self::getProcessRepository()->update($process);
        }
        $this->notificationCount = 0;
        return true;
    }

    public function log($event,$message='',$messageType=MessageType::Info,$detail=null) {

        $entry = ProcessLogEntry::Create(
            $this->code,
            $event,
            $message,
            $messageType,
            $detail
        );

        self::getlogRepository()->insert($entry);
    }

    private $notificationCount = 0;

    /**
     * @param \Exception $ex
     * @param null $message
     * @param bool $rethrow
     * @throws \Exception
     */
    public function handleException(\Exception $ex, $message=null, $rethrow = true)
    {
        $message = $message ?? $ex->getMessage();
        $processName = '(unknown)';
        $when = date('Y-m-d H:i:s');
        $timestamp = date('H:i:s:v');
        $detail = 'Error code: '.$ex->getCode().', '.$ex->getFile().' ('.$ex->getLine().")\n".
            $ex->getTraceAsString();
        try {
            $recipient =  'webadmin@'.TWebSite::GetDomain();
            $processName = $this->getProcess()->name;
            $this->log('failed',$message,MessageType::Error,$detail);
        } catch (\Exception $ex) {
            // ignore exceptions here
        }
        if (isset($recipient) && $this->notificationCount++ < 10) {
            $subject = "Process error in $processName at $timestamp";
            $body = "Error occurred at: $when\n$message\n\n$detail";
            mail($recipient,$subject,$body);
        }
        if ($rethrow) {
            throw $ex;
        }
    }

    public function logError($event,$message='',$detail=null) {
        $this->log($event,$message,MessageType::Error,$detail);
    }

    public function logWarning($event,$message='',$detail=null) {
        $this->log($event,$message,MessageType::Warning,$detail);
    }

    public function logException(\Exception $ex,$event=null) {
        if ($event==null) {
            $event = 'Exception';
        }
        $message =  sprintf('%s. $s Line:d',$ex->getMessage(),$ex->getFile(),$ex->getLine());
        $detail = $ex->getTraceAsString();
        $this->logError($event,$message,$detail);
    }
}