<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 12/25/2017
 * Time: 5:41 PM
 */

namespace Peanut\PeanutTasks\services;


use Tops\services\TServiceCommand;

class RunTestTaskCommand extends TServiceCommand
{
    protected function run()
    {
        $request = $this->getRequest();
        $this->addInfoMessage('Test task ok.');
    }
}