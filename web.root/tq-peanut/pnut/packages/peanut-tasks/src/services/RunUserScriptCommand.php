<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 12/25/2017
 * Time: 6:57 PM
 */

namespace Peanut\PeanutTasks\services;


use Tops\services\TServiceCommand;
use Tops\sys\TPath;

class RunUserScriptCommand extends TServiceCommand
{

    public function __construct()
    {
        $this->requireAuthentication();
    }


    protected function run()
    {
        $script = $this->getRequest();
        if (empty($script)) {
            $this->addErrorMessage('No script name supplied.');
            return;
        }
        $path = TPath::fromFileRoot("application/src/scripts/$script.php");
        if (!file_exists($path)) {
            $this->addErrorMessage("Script not found: $path");
        }
        include $path;
        runUserScript($this->getMessages());
    }
}