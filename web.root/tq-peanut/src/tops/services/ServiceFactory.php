<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 12/29/2016
 * Time: 7:06 AM
 */
/**
 * Must declare route in \bootstrap\app.php
 *
 * Route::register(
 *      '/tops/service/execute/{arg}',
 *      'Concrete\Package\Tops\Controller\ServiceRequestHandler::executeService'
 *      );
 *
 */
namespace Tops\services;

use Tops\sys;
use Tops\services\ServiceRequestInputHandler;


class ServiceFactory extends TAbstractServiceFactory
{

    private static $instance;
    private TServiceErrorResponse $failureResponse;

    /**
     * @return string|TServiceResponse
     * @throws \Exception
     */
    public static function Execute() {
        if (!isset(self::$instance)) {
            self::$instance = new ServiceFactory();
        }
        return self::$instance->executeService();
    }


    /**
     * @return string|TServiceResponse
     */
    public function executeService($checkSecurityToken=true) {
        try {
            return parent::executeService();
        }
        catch (\Throwable $ex) {
            $debugInfo = new \stdClass();

            if (sys\TObjectContainer::HasDefinition('tops.errorLogger')) {
                try {
                    $logger = sys\TObjectContainer::Get('tops.errorLogger');
                    $logReference = $logger->log($ex);
                    $debugInfo->message = "See errorlog: $logReference";
                }
                catch (\Throwable $logEx) {
                    $debugInfo->message = 'Logging error: '.$ex->getMessage();
                }
            }
            else {
                $debugInfo->message = $ex->getMessage();
                $debugInfo->location = $ex->getFile().": Line ".$ex->getLine();
                $debugInfo->trace = $ex->getTraceAsString();
            }


            return $this->getFailureResponse($debugInfo);
        }
    }

    /**
     * @return TServiceResponse
     */
    private function getFailureResponse($debugInfo = null) {
        if (!isset($this->failureResponse)) {
            $this->failureResponse = new TServiceErrorResponse();
            $this->failureResponse->Result = ResultType::ServiceFailure;
            $message = new TServiceMessage();
            $message->MessageType = MessageType::Error;
            $message->Text = sys\TLanguage::text('service-failed');
            $this->failureResponse->Messages = array($message);
            $this->failureResponse->debugInfo = $debugInfo;
        }
        return $this->failureResponse;
    }
}