<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 12/27/2017
 * Time: 8:13 AM
 */

namespace Peanut\PeanutTasks\services;


use Peanut\PeanutTasks\TaskManager;
use Peanut\PeanutTasks\TaskQueueEntry;
use Peanut\PeanutTasks\TaskQueueRepository;
use Tops\services\TServiceCommand;
use Tops\sys\TPermissionsManager;

/**
 * Class UpdateScheduledTaskCommand
 * @package Peanut\PeanutTasks\services
 *
 *    Service Contract:
 *		Request:
 *			interface ITaskQueueItem {
 *				id: any;
 *				frequency: string;
 *				taskname: string;
 *				namespace: string;
 *				startdate: string;
 *				enddate: string;
 *				inputs: string;
 *				comments: string;
 *				active: any;
 *			}
 *
 *		Response:
 *			interface IUpdateTaskResponse {
 *				error: string;   (blank = no error, 'class' = Task class not found.)
 *				schedule: ITaskQueueItem[];
 *			}
 */
class UpdateScheduledTaskCommand extends TServiceCommand
{

    public function __construct()
    {
        $this->addAuthorization(TPermissionsManager::appAdminRoleName);
    }

    /**
     * @throws \Exception
     */
    protected function run()
    {
        $request = $this->getRequest();
        if (empty($request)) {
            $this->addErrorMessage('service-no-request');
            return;
        }
        $result = new \stdClass();
        if (isset($request->taskname) && isset($request->namespace)) {
            $request->namespace = str_replace('::', '\\', $request->namespace);
            if (@$request->subdir) {
                $request->namespace = sprintf('%s::%s',$request->namespace,$request->subdir);
            }

            //$className = sprintf('\%s\services\%sCommand', $request->namespace, ucfirst($request->taskname));
            if (TaskManager::getServiceClass($request->taskname,$request->namespace)) {
                $repository = new TaskQueueRepository();
                $isNew = empty($request->id);
                $task = $isNew ? new TaskQueueEntry() : $repository->get($request->id);
                if (empty($task)) {
                    $this->addErrorMessage("Task $request->id not found.");
                    return;
                }
                $errors = $task->assignFromObject($request);
                if ($errors) {
                    foreach ($errors as $error) {
                        $this->addErrorMessage($error);
                    }
                    return;
                }
                if ($isNew) {
                    $repository->insert($task, $this->getUser()->getUserName());
                } else {
                    $repository->update($task, $this->getUser()->getUserName());
                }
                $result->schedule = (new TaskQueueRepository())->getTaskQueueList();
                $this->addInfoMessage('Task ' . ($isNew ? 'inserted' : 'updated'));
            } else {
                $result->error = 'class';
            }
            $this->setReturnValue($result);
        } else {
            $this->addErrorMessage("Taskname or namespace not assigned");
            return;
        }
    }
}