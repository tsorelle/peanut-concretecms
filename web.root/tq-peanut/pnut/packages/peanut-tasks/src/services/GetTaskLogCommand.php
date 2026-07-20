<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 12/26/2017
 * Time: 10:44 AM
 */

namespace Peanut\PeanutTasks\services;


use Peanut\PeanutTasks\TaskLogRepository;
use Tops\services\TServiceCommand;
use Tops\sys\TPermissionsManager;

class GetTaskLogCommand extends TServiceCommand
{
    public function __construct()
    {
        $this->addAuthorization(TPermissionsManager::appAdminRoleName);
    }

    protected function run()
    {
        $request = $this->getRequest();
        if (empty($request)) {
            $request = new \stdClass();
        }
        if (!isset($request->offset)) {
            $request->offset = 0;
        }
        if (!isset($request->limit)) {
            $request->limit = 50;
        }
        $filter = @$request->filter;
        $result = (new TaskLogRepository())->getLogEntries($filter, $request->offset,$request->limit);
        $this->setReturnValue($result);
    }
}