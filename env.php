<?php
/**
 * 运行环境相关设置
 * @author Donny
 */
include_once 'class/vendor/autoload.php';
define('DS',DIRECTORY_SEPARATOR);
define('QING_ROOT_PATH', realpath(__DIR__));
define('QING_PUBLIC_PATH',QING_ROOT_PATH.DS.'public');
define('QING_TMP_PATH',QING_ROOT_PATH.DS.'var'.DS.'tmp');

$config = include QING_ROOT_PATH.'/config/config.default.php';
if(file_exists(QING_ROOT_PATH.'/config/config.php')){
    $customConfig = include QING_ROOT_PATH.'/config/config.php';
    $config = \Qing\Lib\Utils::arrayExtend($config, $customConfig);
}
\Kuga\Init::setTmpDir(QING_TMP_PATH);
\Kuga\Init::setup($config,$di);


//支持多域名，域名别名访问
if(isset($config->domainMapping)){
    Qing\Lib\Application::setDomainAlias($config->domainMapping->toArray());
}
$appDir = \Qing\Lib\Application::getAppDir();
define('QING_APPDIR',realpath(QING_ROOT_PATH.'/apps/'.$appDir));
define('QING_APPNAME',$appDir);

