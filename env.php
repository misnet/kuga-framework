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

Qing\Lib\Utils::createDir(QING_ROOT_PATH.DS.'var'.DS.'cache');
Qing\Lib\Utils::createDir(QING_ROOT_PATH.DS.'var'.DS.'meta');
Qing\Lib\Utils::createDir(QING_ROOT_PATH.DS.'var'.DS.'volt');
Qing\Lib\Utils::createDir(QING_ROOT_PATH.DS.'var'.DS.'logs');
Qing\Lib\Utils::createDir(QING_ROOT_PATH.DS.'var'.DS.'tmp');


$loader = new \Phalcon\Loader();
$eventsManager = $di->getShared('eventsManager');


//自己编写的类文件，可以通过类似以下的方式加载进来

//$classPath = realpath(__DIR__.'/class/src');
//define('QING_CLASS_PATH',$classPath);
//define('QING_EXTENSION_DIR',$classPath.DS.'kuga-extensions');
//$loader->registerNamespaces(array(
//    'Kuga\\Model'=>QING_CLASS_PATH.'/Model',
//    'Kuga\\Service'=>QING_CLASS_PATH.'/Service',
//    'Kuga\\Traits'=>QING_CLASS_PATH.'/Traits',
//));
//$loader->register();

//载入配置文件
$config = include QING_ROOT_PATH.'/config/config.default.php';
if(file_exists(QING_ROOT_PATH.'/config/config.php')){
    $customConfig = include QING_ROOT_PATH.'/config/config.php';
    $config = \Qing\Lib\Utils::arrayExtend($config, $customConfig);
}
$config = new \Phalcon\Config($config);

//支持多域名，域名别名访问
if(isset($config->domainMapping)){
    Qing\Lib\Application::setDomainAlias($config->domainMapping->toArray());
}

$appDir = \Qing\Lib\Application::getAppDir();
define('QING_APPDIR',realpath(QING_ROOT_PATH.'/apps/'.$appDir));
define('QING_APPNAME',$appDir);
$di->setShared('logger',function(){
    return \Phalcon\Logger\Factory::load([
        'name'=>QING_TMP_PATH.'/logger.txt',
        'adapter'=>'file'
    ]);
});

$loader->setEventsManager($eventsManager);
$version = Phalcon\Version::get();
//TODO:发现Nginx下,Phalcon2.0.3版还是需要require_once
if($version<2.0){
    $eventsManager->attach('loader', function($event,$loader){
        if($event->getType()=='beforeCheckPath' && stripos($loader->getCheckedPath(), 'phar://')===0){
            require_once $loader->getCheckedPath();
            return true;
        }
    });
}


//config对象纳入DI
$di->setShared('config',function($item=null) use($config){
    if(is_null($item)||!isset($config->{$item})){
        return $config;
    }else{
        return $config->{$item};
    }
});
//缓存对象纳入DI
$di->setShared('cache',function($prefix='sp_') use($config){
    $option['slow']['engine'] = 'file';
    $option['slow']['option']['lifetime'] = 86400;
    $option['slow']['option']['prefix']   = $prefix;

    $option['fast']['engine'] = 'redis';
    $option['fast']['option']['lifetime'] = 3600;
    $option['fast']['option']['host']    = $config->redis->host;
    $option['fast']['option']['port']    = $config->redis->port;
    $option['fast']['option']['auth']    = $config->redis->password;
    $option['fast']['option']['index']   = $config->redis->db;
    $option['fast']['option']['prefix']  = $prefix;
    $option['fast']['option']['statsKey']  = 'LA';

//    $option['fast']['engine'] = 'Libmemcached';
//    $option['fast']['option']['lifetime'] = 3600;
//    $option['fast']['option']['servers'] = [
//        [
//            'host'=>'127.0.0.1',
//            'port'=>11211,
//            'weight'=>1
//        ]
//    ];
    $cache = new \Qing\Lib\Cache($option);
    return $cache;
});

//翻译器
$di->setShared('translator', function() use($appDir,$di,$config){
    $locale = $config->system->locale;
    if($config->system->charset){
        $locale.='.'.$config->system->charset;
    }
    $directory['common'] = QING_ROOT_PATH.'/langs/_common';
    if(isset($appDir)){
        $directory[$appDir] = QING_ROOT_PATH.'/langs';
    }
    $translator = new Qing\Lib\Translator\Gettext(array(
        'locale'        => $locale,
        'defaultDomain' => 'common',
        'category'      => LC_MESSAGES,
        'cache'         => $di->get('cache'),
        'directory'=>$directory
    ));
    return $translator;
});

//增加插件
$eventsManager->collectResponses(true);
Kuga\Core\Service\PluginManageService::init($eventsManager,$di);
Kuga\Core\Service\PluginManageService::loadPlugins();
//\Kuga\Service\Extension\Manager::init($eventsManager, $di);

//数据库配置
$di->setShared('dbRead', function() use($config,$eventsManager){
    $dbRead= new \Phalcon\Db\Adapter\Pdo\Mysql(array(
        'host'=>$config->dbread->host,
        'username'=>$config->dbread->username,
        'password'=>$config->dbread->password,
        'port'=>$config->dbread->port,
        'dbname'=>$config->dbread->dbname,
        'charset'=>$config->dbread->charset,
        'options'=>[
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET time_zone ="'.date('P').'"'
        ],
        'dialectClass' => '\Phalcon\Db\Dialect\MysqlExtended'
    ));
    $dbRead->setEventsManager($eventsManager);
    return $dbRead;
});
$di->setShared('dbWrite', function() use($config,$eventsManager){
    $dbWrite = new \Phalcon\Db\Adapter\Pdo\Mysql(array(
        'host'=>$config->dbwrite->host,
        'username'=>$config->dbwrite->username,
        'password'=>$config->dbwrite->password,
        'port'=>$config->dbwrite->port,
        'dbname'=>$config->dbwrite->dbname,
        'charset'=>$config->dbwrite->charset,
        'options'=>[
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET time_zone ="'.date('P').'"'
        ],
        'dialectClass' => '\Phalcon\Db\Dialect\MysqlExtended'
    ));
    $dbWrite->setEventsManager($eventsManager);
    return $dbWrite;
});
//非空验证取消，否则当字段设定为not null时，虽有default值，但在model中如没指定值时，系统会报错
\Phalcon\Mvc\Model::setup(array(
    'notNullValidations' => false
));

//生成查询SQL日志
//$logger = new \Phalcon\Logger\Adapter\File(QING_ROOT_PATH."/var/logs/db.log");
////Listen all the database events
//$eventsManager->attach('db', function($event, $connection) use ($logger) {
//    if ($event->getType() == 'beforeQuery') {
//        $logger->log($connection->getSQLStatement(), Phalcon\Logger::INFO);
//        $logger->log(print_r($connection->getSqlVariables(),true), Phalcon\Logger::INFO);
//    }
//});


//生成Session
$di->setShared('session', function()  use($config,$appDir){
    //API域名不需要session
    if($appDir!='api'){
        if (isset($_POST['sessid'])){
            session_id($_POST['sessid']);
        }
        $option['uniqueId'] = 'LA-SESS';
        $option['host'] = $config->redis->host;
        $option['port'] = $config->redis->port;
        $option['auth'] = $config->redis->password;
        $option['persistent'] = false;
        $option['prefix'] = 'se_';
        $option['index'] = 2;
        $session = new \Phalcon\Session\Adapter\Redis($option);
        ini_set('session.cookie_domain', \Qing\Lib\Application::getCookieDomain());
        ini_set('session.cookie_path', '/');
        ini_set('session.cookie_lifetime', 86400);
        $session->start();
        return $session;
    }
});


//实现对model的meta缓存
$di->setShared( "modelsCache", function () use($di){
    return $di->get('cache')->getCacheEngine();
});
$di['modelsMetadata'] = function() {
    $metaData = new \Phalcon\Mvc\Model\MetaData\Files(array(
        "lifetime" => 86400,
        "prefix"   => "qing",
        "metaDataDir"=>QING_ROOT_PATH.'/var/meta/'
    ));
    return $metaData;
};

//数据库事务
$di->setShared('transactions', function(){
    $tm = new \Phalcon\Mvc\Model\Transaction\Manager();
    $tm->setDbService('dbWrite');
    return $tm;
});

//队例对象
$di->setShared('queue', function() use($config,$di){
    $redisConfig = $config->redis;
    $redisAdapter = new \Qing\Lib\Queue\Adapter\Redis($redisConfig);
    $queue = new \Qing\Lib\Queue();
    $queue->setAdapter($redisAdapter);
    $queue->setDI($di);
    return $queue;
});

//NOSQL简单存储器
$di->set('simpleStorage', function() use($config){
    $redisConfig = $config->redis;
    return new \Qing\Lib\SimpleStorage($redisConfig);
});

//文件存储管理
$di->setShared('fileStorage', function() use($config,$di){
    /*
     * localfile
     * $option['baseDir'] = trim($config->localfile->baseDir, '\/');
     * $option['hostUrl'] = $config->localfile->hostUrl;
     * $option['rootDir'] = rtrim($config->localfile->rootDir);
     */

    /**
     * aliyun oss
     * $option['accessKeyId']     = $config->aliyun->accessKeyId;
       $option['accessKeySecret'] = $config->aliyun->accessKeySecret;
       $option['endpoint']        = $config->aliyun->endpoint;
       $option['bucket']          = $config->aliyun->bucket;
       $option['hostUrl']         = $config->aliyun->hostUrl;
     */
    $adapterName = $config->fileStorage->adapter;
    $option = $config->fileStorage->{$adapterName};
    return \Kuga\Core\Service\FileService::factory($adapterName,$option,$di);
});

//加密器
$di->set('crypt', function()  use($config){
    $crypt = new Phalcon\Crypt();
    $crypt->setKey(md5($config->system->copyright)); //Use your own key!
    return $crypt;
});
$di->set('cookies', function() {
    $cookies = new Phalcon\Http\Response\Cookies();
    $cookies->useEncryption(false);
    return $cookies;
});

//短信服务
$di->set('sms', function()  use($config,$di){
    $smsAdapter = \Kuga\Core\Sms\SmsFactory::getAdapter($config->sms->adapter,$config->sms->adapter,$di);
    return $smsAdapter;
});

Phalcon\Mvc\Model::setup(
    [
        'updateSnapshotOnSave' => false,
    ]
);

