<?php
/** @noinspection PhpUndefinedMethodInspection */
/** @noinspection PhpUndefinedClassInspection */
require_once DIR_BASE.'/tq-peanut/bootstrap/definitions.php';
include_once (DIR_PNUT_BOOTSTRAP."/peanut-bootstrap.php");
\Peanut\Bootstrap::initialize($_SERVER['DOCUMENT_ROOT'] ? $_SERVER['DOCUMENT_ROOT'] : DIR_BASE);

/**
 * Notes:
 * For more routing examples, see application/bootstrap/app.php, and this documentation page:
 *  https://documentation.concretecms.org/9-x/developers/security/route
 *
 * This file was updated to use a router object instead of the static form `Route::register`
 * The static form still works as of version 9.5.2, and is still given in the comment examples in the distribution file:
 * However, it is not mentioned in the documentation for version 9.x and may be deprecated. So I replaced it with
 * calls to the router object instance using $router->register().
 *
 * The register method is not mentioned in the documentation either. So if it goes away in a future version, these calls
 * can be updated to use the lower level methods.
 *
 * For routes used for GET requests only, the router object instance uses the get method.
 *   $router->get(
 *       '/peanut/settings',
 *      'Tops\concrete5\ServiceRequestHandler::getSettings'
 *   );
 *
 *  For routes that may use POST requests only, the router object instance uses this form.
 *   $router->post(
 *       '/peanut/settings',
 *      'Tops\concrete5\ServiceRequestHandler::getSettings'
 *   );
 *
 *  For routes that may use POST or GET requests, the router object instance uses this form.
 *  $router->register(
 *      '/peanut/service/download',
 *      'Tops\concrete5\ServiceRequestHandler::getDownload'
 *      null,        // $handle
 *      [],          // $requirements
 *      [],          // $options
 *      '',          // $host
 *      [],          // $schemes
 *      ['GET', 'POST']  // $methods
 *  );
 *
 */

/**
 * @var \Concrete\Core\Application\Application $app
 */
$router = $app->make('router');

/*
 For testing purposes only
 $router->get('/peanut/handler', function() {
    return  class_exists('Tops\concrete5\ServiceRequestHandler') ?
    'Found handler' :
    'No handler';
});

$router->register(
    '/test1',
    'Tops\concrete5\ServiceRequestHandler::test1'
);

*/

$router->register(
    '/peanut/settings',
    'Tops\concrete5\ServiceRequestHandler::getSettings'
);

/** Development use only - begin **/
$router->register(
    '/peanut/test/{testname}',
    'Tops\concrete5\ServiceRequestHandler::runtest'
);

$router->register(
    '/peanut/service/execute',
    'Tops\concrete5\ServiceRequestHandler::executeService'
);

$router->register(
    '/peanut/service/execute/{sid}',
    'Tops\concrete5\ServiceRequestHandler::executeService'
);

$router->register(
    '/peanut/service/execute/{sid}/{arg}',
    'Tops\concrete5\ServiceRequestHandler::executeService'
);

// peanut optional

$router->register(
//    '/'.$peanutUrl.'/{vmname}',
    '/pages/{vmname}',
    'Tops\concrete5\ServiceRequestHandler::buildPage'
);

//todo: enable routes below as features added

$router->register(
    '/peanut/service/download',
    'Tops\concrete5\ServiceRequestHandler::getDownload'
);

// document handlers
$router->register(
    '/qnut/documents/{arg1}/{arg2}/{arg3}/{arg4}/{arg5}',
    'Tops\concrete5\ServiceRequestHandler::getDocument'
);

$router->register(
    '/qnut/documents/{arg1}/{arg2}/{arg3}/{arg4}',
    'Tops\concrete5\ServiceRequestHandler::getDocument'
);

$router->register(
    '/qnut/documents/{arg1}/{arg2}/{arg3}',
    'Tops\concrete5\ServiceRequestHandler::getDocument'
);

$router->register(
    '/qnut/documents/{arg1}/{arg2}',
    'Tops\concrete5\ServiceRequestHandler::getDocument'
);

$router->register(
    '/qnut/documents/{arg1}',
    'Tops\concrete5\ServiceRequestHandler::getDocument'
);

$router->register(
    '/peanut/mail/bounce',
    'Tops\concrete5\ServiceRequestHandler::handleMailBounce'
);

$router->register(
    '/peanut/tasks/run/{arg1}',
    'Tops\concrete5\ServiceRequestHandler::runScheduledTasks'
);

$router->register(
    '/peanut/tasks/run',
    'Tops\concrete5\ServiceRequestHandler::runScheduledTasks'
);


$router->register(
    '/peanut/tasks/run',
    'Tops\concrete5\ServiceRequestHandler::runScheduledTasks'
);

// /system/files/docs/fds/YCW89L22BoyJesusTemple.pdf
$router->register(
    '/system/files/docs/{arg1}/{arg2}/{arg3}/{arg4}',
    'Tops\concrete5\ServiceRequestHandler::redirectDocument'
);

$router->register(
    '/system/files/docs/{arg1}/{arg2}/{arg3}',
    'Tops\concrete5\ServiceRequestHandler::redirectDocument'
);

$router->register(
    '/system/files/docs/{arg1}/{arg2}',
    'Tops\concrete5\ServiceRequestHandler::redirectDocument'
);

$router->register(
    '/system/files/docs/{arg1}',
    'Tops\concrete5\ServiceRequestHandler::redirectDocument'
);

$router->register('/login/concrete/forgot_password', function() {
    return Redirect::to('/recover-password');
});

/*
// email handlers
$router->register(
    '/email/unsubscribe',
    'Tops\concrete5\ServiceRequestHandler::handleUnsubscribe'
);*/

/*** Event handlers */

Events::addListener('on_user_update', function($event) {
    // $user = $event->getUserInfoObject();
    // \Log::info(sprintf('Added user %s', $user->getUserName()));
    \Tops\sys\TCmsEvents::Handle('user','update',$event);
});

Events::addListener('on_user_delete', function($event) {
    // \Log::info(sprintf('Added user %s', $user->getUserName()));
    \Tops\sys\TCmsEvents::Handle('user','delete',$event);
});

Events::addListener('on_user_attributes_saved', function($event) {
    // \Log::info(sprintf('Added user %s', $user->getUserName()));
    \Tops\sys\TCmsEvents::Handle('user','attribute',$event);
});
