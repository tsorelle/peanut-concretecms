<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 1/15/2018
 * Time: 12:30 PM
 */

namespace Tops\services;


use Tops\sys\TConfiguration;
use Tops\sys\TObjectContainer;

abstract class TAbstractServiceFactory
{
    /**
     * @var ServiceRequestInputHandler
     */
    private static $inputHandler;
    private function getInputHandler()
    {
        if (!isset(self::$inputHandler)) {
            $inputHandler = TObjectContainer::Get('services.inputhandler');
            self::$inputHandler = $inputHandler === false ?  new DefaultInputHandler() : $inputHandler;
        }
        return self::$inputHandler;
    }

    /**
     * @return string|TServiceResponse
     * @throws \Exception
     */
    public function executeService($checkSecurityToken=true)
    {
        $inputHandler =  self::getInputHandler();
        $serviceId = $inputHandler->getServiceId();
        $securityToken = $checkSecurityToken ? $inputHandler->getSecurityToken() : false;
        $parts = explode('::', $serviceId);
        if (sizeof($parts) == 1) {
            $namespace = TConfiguration::getValue('applicationNamespace', 'services');
            if (empty($namespace)) {
                throw new \Exception('For default service, "applicationNamespace=" is required in settings.ini');
            }
            $namespace .= "\\" . TConfiguration::getValue('servicesNamespace', 'services', 'services');
        } else {
            $namespace = $inputHandler->getServiceNamespace($parts[0]);
            $serviceId = $parts[1];
        }

        // get subdirectories  e.g. where serviceId is 'subdirectory.serviceId'
        $serviceId = str_replace('.', "\\", $serviceId);
        $className = $namespace . "\\" . $serviceId . 'Command';
        if (!class_exists($className)) {
            throw new \Exception("Cannot instatiate service '$className'.");
        }

        /**
         * @var $cmd TServiceCommand
         */
        $cmd = new $className();
        $input = $inputHandler->getValues(['serviceCode','sid']);
        if (isset($input->request)) {
            $input = json_decode($input->request);
        }
        $response = $cmd->execute($input, $securityToken);
        return $response;
    }

    protected function getSecurityToken(ServiceRequestInputHandler $request) {

        $tokensEnabled = TConfiguration::getValue('xxstokens','security',true);
        if ($request != null && $tokensEnabled) {
            $securityToken = $request->get('topsSecurityToken');
            if (!$securityToken) {
                return 'invalid';
            }
        }
        return '';
    }




}