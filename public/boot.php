<?php
$di     = new \Phalcon\DI\FactoryDefault();
require_once '../env.php';

$di->set('dispatcher', function() {
    //Create an EventsManager
    $eventsManager = new \Phalcon\Events\Manager();
    //Camelize actions:mac上不用lcfirst也可以搞定，centos上必须要有lcfirst
    $eventsManager->attach("dispatch:beforeDispatchLoop", function($event, $dispatcher) {
        $dispatcher->setActionName(lcfirst(\Phalcon\Text::camelize($dispatcher->getActionName())));
    });
    $dispatcher = new \Phalcon\Mvc\Dispatcher();
    $dispatcher->setEventsManager($eventsManager);
    //$dispatcher->setDefaultNamespace('\Qing\Console\Controllers\\');
    return $dispatcher;
});

include_once QING_APPDIR.'/App.php';
$app = new App();
\Qing\Lib\Application::startup($app,$di);

$baseUrl = \Qing\Lib\Application::getBaseUrl(__DIR__);
define('QING_BASEURL',$baseUrl);
\Qing\Lib\Application::setConfig($config);

$di->set('url', function() use ($baseUrl){
    $url = new \Phalcon\Mvc\Url();
    $url->setBaseUri($baseUrl);
    return $url;
});