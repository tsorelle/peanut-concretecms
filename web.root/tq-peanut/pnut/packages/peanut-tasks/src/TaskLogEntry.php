<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 10/30/2017
 * Time: 6:00 AM
 */
namespace Peanut\PeanutTasks;


class TaskLogEntry
{
    public $id;
    public $taskname;
    public $time;
    public $type;
    public $message;
    public $active = 1;

    public static function Create($message, $taskname = 'system', $type = 0)
    {
        $result = new TaskLogEntry();
        $result->time = (new \DateTime())->format('Y-m-d H:i:s');
        $result->type = $type;
        $result->message = $message;
        $result->taskname = $taskname;
        $result->active = 1;
        return $result;
    }

}