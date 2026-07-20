<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 12/29/2016
 * Time: 7:06 AM
 */
/**
 * File Location: (doc root)\application\src\tops\services\ServiceRequestHandler.php
 *
 * Add autoloader in \application\bootstrap\app.php
 *
 * $classLoader = new \Symfony\Component\ClassLoader\Psr4ClassLoader();
 * // your application location
 * $classLoader->addPrefix('Application\\Aftm', DIR_APPLICATION . '/' . DIRNAME_CLASSES . '/aftm');
 * // tops library location
 * $classLoader->addPrefix('Application\\Tops', DIR_APPLICATION . '/' . DIRNAME_CLASSES . '/tops');
 * $classLoader->register();
 *
 * Declare routes in \application\bootstrap\app.php
 *
 * Route::register(
 * '/tops/service/execute',
 * 'Application\Tops\services\ServiceRequestHandler::executeService'
 * );
 *
 * Route::register(
 * '/tops/service/execute/{sid}',
 * 'Application\Tops\services\ServiceRequestHandler::executeService'
 * );
 *
 * Route::register(
 * '/tops/service/execute/{sid}/{arg}',
 * 'Application\Tops\services\ServiceRequestHandler::executeService'
 * );
 *
 */
namespace Tops\concrete5;

use Concrete\Core\Controller\Controller;
use Concrete\Core\Http\Request;

use Peanut\PeanutTasks\TaskManager;
use Peanut\QnutDirectory\services\messaging\UnsubscribeService;
use Peanut\QnutDocuments\DocumentManager;
use Peanut\sys\ViewModelPageBuilder;
use Tops\mail\TContentType;
use Tops\mail\TMailgunWebhookHandler;
use Tops\mail\TPostOffice;
use Tops\services\DownloadServiceFactory;
use Tops\services\ServiceFactory;
use Tops\sys\TConfiguration;
use Tops\sys\TDates;

class ServiceRequestHandler extends Controller
{
    public function executeService()
    {
        $response = ServiceFactory::Execute();
        print json_encode($response);
    }

    public function test1()
    {
        print "test1";
        exit;
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

    public function getSettings() {
        $settingsPath = DIR_PNUT_BOOTSTRAP.'/settings.php';
        include($settingsPath);
    }

    public function buildPage()  {

        // todo: afrer testing in core project: relocate to Tops\Services and use Tops\sys\TRequest
        $pageName = Request::getInstance()->get('vmname');
        $content = ViewModelPageBuilder::Build($pageName);
        print $content;
    }

    // todo: consider adding RunScheduledTasks from core.


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

        DocumentManager::outputDocumentContent($args);

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
        $to = TConfiguration::getValue('notifyemail','site','webadmin@austinquakers.org');
        mail($to,'Exception in site tasks',$content);
        exit ($msg);
    }
    public function runScheduledTasks($taskId = 0) {
        set_exception_handler('\Tops\concrete5\ServiceRequestHandler::exceptionHandler');
        (new TaskManager())->runJobs($taskId);
        exit;
    }

}