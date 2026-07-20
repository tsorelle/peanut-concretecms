<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 10/30/2017
 * Time: 6:04 AM
 */

namespace Peanut\PeanutTasks;


class TaskLogEntryType
{
    // first three must match services\MessageType
    const Info = 0;
    const Error = 1;
    const Warning = 2;

    const StartSession = 100;
    const Failure = 200;
    const EndSession = 999;
}