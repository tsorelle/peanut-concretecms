<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 12/26/2017
 * Time: 9:36 AM
 */

namespace Peanut\PeanutTasks\services;


use Peanut\PeanutTasks\TaskQueueRepository;
use Tops\services\TProcessManager;
use Tops\services\TServiceCommand;
use Tops\sys\TLanguage;
use Tops\sys\TPermissionsManager;

/**
 * Class GetTaskTablesCommand
 * @package Peanut\PeanutTasks\services
 *
 * Service contract:
 *    Request: string - current offset of log view
 *    Response:
 * 	    interface IGetTaskTablesResponse {
 * 	           schedule: ITaskQueueItem[]
 * 	    			{
 * 	    				id: any;
 * 	    				frequency: string;
 * 	    				taskname: string;
 * 	    				namespace: string;
 * 	    				startdate: string;
 * 	    				enddate: string;
 * 	    				inputs: string;
 * 	    				comments: string;
 * 	    			}
 * 	           translations: string[];
 * 	       }
 */
class GetTaskScheduleCommand extends TServiceCommand
{
    public function __construct()
    {
        $this->addAuthorization(TPermissionsManager::appAdminRoleName);
    }

    protected function run()
    {
        $result = new \stdClass();
        $result->schedule = (new TaskQueueRepository())->getTaskQueueList();
        $result->translations = TLanguage::getTranslations([
            'label-active',
            'label-add',
            'label-cancel',
            'label-edit',
            'label-save',
            'nav-more',
            'nav-previous',
            'tasks-form-header-edit',
            'tasks-form-header-new',
            'tasks-get-log',
            'tasks-label-comments',
            'tasks-label-enddate',
            'tasks-label-frequency',
            'tasks-label-frequency-unit',
            'tasks-label-inputs',
            'tasks-label-log',
            'tasks-label-logtime',
            'tasks-label-message',
            'tasks-label-message-type',
            'tasks-label-namespace',
            'tasks-label-schedule',
            'tasks-label-startdate',
            'tasks-label-taskname'

            ]);

            $this->setReturnValue($result);
    }
}