<?php
$di     = new \Phalcon\DI\FactoryDefault\Cli();
include_once dirname(dirname(__FILE__)).'/env.php';
set_time_limit(0);
$loader = new \Phalcon\Loader();
$loader->registerDirs([
    __DIR__.'/tasks'
]);
$loader->register();

$console = new \Phalcon\Cli\Console();
$console->setDI($di);
$di->setShared('console',$console);
$arguments = [];
foreach ($argv as $k => $arg) {
    if ($k === 1) {
        $arguments["task"] = $arg;
    } elseif ($k === 2) {
        $arguments["action"] = $arg;
    } elseif ($k >= 3) {
        $arguments["params"][] = $arg;
    }
}
try {
    $console->handle($arguments);
} catch (\Phalcon\Exception $e) {
    echo $e->getMessage();
    exit(255);
}catch (\Throwable $throwable) {
    fwrite(STDERR, $throwable->getMessage() . PHP_EOL);
    exit(1);
}