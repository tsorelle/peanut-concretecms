<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 12/29/2016
 * Time: 7:06 AM
 */

namespace Tops\services;

// use Concrete\Core\Http\Request;
use Peanut\PeanutTasks\TaskManager;
use Peanut\sys\ViewModelPageBuilder;
use Tops\sys\IHttpRequest;
use Tops\sys\TConfiguration;
use Tops\sys\TObjectContainer;
use Tops\sys\TPath;
use Tops\sys\TUser;

class ServiceRequestHandler
{
    public function executeService()
    {
        header('Content-Type: application/json');
        try {
            $response = ServiceFactory::Execute();
            echo json_encode($response);
        }
        catch (\Throwable $ex) {
            $response = new TServiceErrorResponse();
            $response->Result = ResultType::ServiceFailure;
            $debugInfo = new \stdClass();
            $debugInfo->message = $ex->getMessage();
            $debugInfo->location = $ex->getFile().": Line ".$ex->getLine();
            $debugInfo->trace = $ex->getTraceAsString();
            $response->debugInfo = $debugInfo;
            echo json_encode($response);
        }
    }

    public function signout() {
        // $referrer = preg_replace("/(^\/)|(\/$)/","",$_SERVER['HTTP_REFERER']);
        TUser::SignOut();
        header('Location: /'); // .$referrer);
        exit();
    }

    public function runtest($testname) {
        print "<pre>";
        print "Running $testname\n";
        if (empty($testname)) {
            exit("No test name!");
        }
        $testname = strtoupper(substr($testname,0,1)).substr($testname,1);
        $className = "\\PeanutTest\\scripts\\$testname".'Test';
        $test = new $className();
        $test->run();

        print "\n</pre>";
        print "<a href='/' target='_blank'>Home</a>";
        exit;
    }

    public function getSettings() : void{
        $bootstrapDir = (defined('DIR_PNUT_BOOTSTRAP'))  ?
            DIR_PNUT_BOOTSTRAP : // old peanut
            DIR_BASE.'/tq-peanut/bootstrap';
        include($bootstrapDir.'/settings.php');
    }

    public function buildPage(IHttpRequest $request = null) : void  {
        if (empty($request)) {
            $request = \Tops\sys\TRequest::getInstance();
        }
        $pageName = $request->get('vmname');
        $content = ViewModelPageBuilder::Build($pageName);
        print $content;
    }


// todo: review if needed. not occuring in FMA project
    public function runScheduledTasks($taskId = 0) {
        set_exception_handler('Nutshell\cms\ServiceRequestHandler::exceptionHandler');
        (new TaskManager())->runJobs($taskId);
        exit;
    }

    /**
     * @throws \Exception
     */
    public function getDownload() {
        DownloadServiceFactory::PrintOutput();
        exit;
    }

    public function getDocument($arg1,$arg2=null,$arg3=null,$arg4=null,$arg5=null) {

        $args = [$arg1];
        if ($arg2) {
            $args[] = $arg2;
        }
        if ($arg3) {
            $args[] = $arg3;
        }

        if ($arg4) {
            $args[] = $arg4;
        }

        if ($arg5) {
            $args[] = $arg5;
        }

        $documentManager = TObjectContainer::Get('qnut.documentmanager');
        if ($documentManager) {
            $documentManager->outputContent($args);
        }
        else {
            exit ("Document manager not found");
        }

        // DocumentManager::outputDocumentContent($args);

    }

    public function redirectDocument($arg1,$arg2=null,$arg3=null)
    {
        $filename = $arg2 ? $arg2 : $arg1;
        $subdir = $arg2 ? $arg1 : null;
        switch ($subdir) {
            case 'fds':
                break;
            case 'fn' :
                // FNMonyy
                $month = substr($filename,2,3);
                $year = substr($filename,5,2);
                $year = ($year > 70) ? '19'.$year : '20'.$year;

                $dt = TDates::CreateDateObject("1 $month $year");
                if ($dt === false) {
                    exit ("Cannot find newsletter file $filename");
                }
                $filename = 'newsletter-'.$dt->format('Y-m-d').'.pdf';
                $subdir = 'newsletter';
                break;
            default:
                $subdir = 'archive';
                break;
        }

        $this->getDocument($subdir,$filename);

    }

    public function handleMailBounce() {
        /**
         * @var TMailgunWebhookHandler
         */
        $handler = \Tops\sys\TObjectContainer::Get('mailgun.eventhandler');
        if ($handler) {
            $handler->handleMessage();
        }
    }

    public function handleUnsubscribe() {
        $request = explode('-',$_REQUEST['subject']);
        array_shift($request);
        $listId = array_pop($request);
        $uid = implode('-',$request);

        $service = new UnsubscribeService();
        $service->unsubscribe($uid,$listId);
    }

    public static function exceptionHandler($ex) {
        $msg = $ex->getMessage();
        $content ="$msg\n\n".$ex->getTraceAsString();
        // todo: get website url elsewhare
        $to = TConfiguration::getValue('notifyemail','site','webadmin@nutshell.org');
        mail($to,'Exception in site tasks',$content);
        exit ($msg);
    }

}