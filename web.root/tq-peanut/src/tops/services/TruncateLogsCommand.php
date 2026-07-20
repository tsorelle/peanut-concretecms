<?php

namespace Tops\services;

use Peanut\PeanutTasks\TaskLogRepository;
use Tops\db\model\repository\EmailFailuresRepository;
use Tops\db\model\repository\ProcessLogRepository;
use Tops\services\TServiceCommand;

class TruncateLogsCommand extends TServiceCommand
{
    protected function run()
    {
        if (!$this->getUser()->isAdmin()) {
            $this->addErrorMessage('Administrator permissions are required to run this service.');
            return;
        }
        $request = $this->getRequest();
        if (!empty($request)) {
            $timeframe = $request->timeframe ?? '-1 months';;
        }
        $repository = new ProcessLogRepository();
        $count = $repository->truncateEntries($timeframe);
        if ($count >= 0) {
            $this->addInfoMessage("Deleted $count entries from process log. Timeframe: $timeframe");
        }
        else {
            $this->addErrorMessage('No process log entries were deleted.');
        }

        $repository = new TaskLogRepository();
        $count = $repository->truncateEntries($timeframe);
        if ($count >= 0) {
            $this->addInfoMessage("Deleted $count entries from task log. Timeframe: $timeframe");
        }
        else {
            $this->addErrorMessage('No task log entries were deleted.');
        }

        $repository = new EmailFailuresRepository();
        $timeframe = "-3 months";
        $count = $repository->truncateEntries($timeframe);
        if ($count >= 0) {
            $this->addInfoMessage("Deleted $count entries from email failures log. Timeframe: $timeframe");
        }
        else {
            $this->addErrorMessage('No task log entries were deleted.');
        }

    }
}