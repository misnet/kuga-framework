# 中止维护
本项目转到kuga-server，中止本项目维护。kuga-server更为纯粹，不夹带业务相关的逻辑，只提供基础API服务

# 说明
kuga-framework是基于以前一些项目的总结，做成的。原先项目不只是提供API服务，还有其他一些Web服务，基于目前前后端分离的趋势，
本项目做为服务端项目，暂只提供API服务，之后看完善情况再定是否要再提供Web服务。

功能点：
- 提供了一个api服务的框架
- 计划提供网关服务功能，支持api访问日志保存(Redis），根据实际情况可以扩展更多内容
- 部分提供了api服务，可根据需要自行修改，例如
```
common.smscode.send   短信验证码发送
common.osssetting     阿里STS授权及OSS配置获取
common.batchrequest   批量发送api请求

console.user.loging   后台用户登录
console.user.create   创建后台用户
console.user.update   修改指定的后台用户
console.user.list     后台用户列表
console.user.delete   删除后台用户

console.menu.create   后台的菜单创建
console.menu.update   后台的菜单修改
console.menu.delete   后台的菜单删除
console.menu.list     后台的菜单列表
```

# 目录结构说明
```
├─.
├─apps
|   ├─www
|      ├─controllers
|      └─views
|   ├─config
|      ├─aliyunoss                  // 阿里云oss的配置文件目录，请参考config.sample.json的格式，配一个config.json文件
|      ├─api                        // API接口约定文件，所有API接口必须编写相应的json文件，通过json文件来验证request请求，同时借助 https://github.com/misnet/apidocs  可以生成apidoc文件。
|      ├─sms                        // 短信配置文件目录，支持tencent和aliyun
|      ├─apikeys.config.json        // api请求的key和secret
|      ├─config.default.php         // 默认配置文件，自己可以写一个config.php，系统会先读config.default.php，再读config.php，将config.php的内容覆盖config.default.php的内容
|      └─db.sql                     // 初始化用的数据库文件
|   ├─composer.json                 // 核心类文件的引用请用composer install 来安装
|   ├─langs
|      ├─en_US                      // 英文语言包，可用poedit来读取po文件，生成翻译mo文件
|      ├─zh_CN                      // 中文语言包，可用poedit来读取po文件，生成翻译mo文件
|      └─_common                    // 对应class/vendor/Kuga 文件内容，可用poedit来扫描与生成
|   ├─public                        // documentRoot目录，nginx/apache请解析到这里
|   ├─var                           // 系统生成的临时文件都在这里

```


# 安装

运行环境要求：
- 需要PHP + MySQL 支持
- 需要安装的PHP扩展有：Phalcon、Exif、GD


1、目录设权限，下载项目相关类文件
```
chmod +777 var -R
cd class
composer install
```
请留意class/README.md里的说明

2、复制config.default.php，另存一份config.php，然后配好与config.default.php不同的内容，示例：
```
<?php
$_CONFIG['dbwrite'] = array(
		'adapter' =>'mysql',
		'port'=>3306,
		'host'=>'mysql',
		'username'=>'root',
		'password'=>'',
		'dbname'=>'kuga',
        'charset'=>'utf8mb4',
        'statsDbname'=>''
);
$_CONFIG['system']['name']    = 'Kuga Platform';
$_CONFIG['system']['software_copyright']    = 'Design by Donny &copy 2016 ';
$_CONFIG['dbread'] = $_CONFIG['dbwrite'];

$_CONFIG['redis']['host'] = 'redis';
return $_CONFIG;
```

3、配置好nginx，例：
```
server {
    listen       80;
    server_name  api.kuga.wang;
    access_log /var/log/nginx/api.kuga.access common;
    error_log /var/log/nginx/api.kuga.err;
    set $root_path '/opt/kuga/public';
    root $root_path;
    try_files $uri $uri/ @rewrite;

    location @rewrite {
        rewrite ^/(.*)$ /index.php?_url=/$1;
        #try_files $uri $uri/ /index.php;
    }
    location ~ .*\.(php|phtml)?$ {
        fastcgi_split_path_info       ^(.+\.php)(/.+)$;
        fastcgi_param PATH_INFO       $fastcgi_path_info;
        fastcgi_param PATH_TRANSLATED $document_root$fastcgi_path_info;
        fastcgi_pass   127.0.0.1:9000;
        if ($request_filename ~* (.*)\.php) {
            set $php_url $1;
        }
        if (!-e $php_url.php) {
            return 403;
        }
    }
    location ~* \.(eot|ttf|svg|woff)$ {
         add_header Access-Control-Allow-Origin *;
    }
    location ~ /\.ht {
        deny all;
    }
    location ~ /\.git {
        deny all;
    }
}
```

# 编写自己的API接口

1、如果不是通过composer安装的，可以修改一下env.php文件
```
//你的类文件目录，遵循psr4规范，例如
$classPath = realpath(__DIR__.'/class/src');
define('QING_CLASS_PATH',$classPath);

$loader->registerNamespaces(array(
    'Example\\Model'=>QING_CLASS_PATH.'/Model',
    'Example\\Service'=>QING_CLASS_PATH.'/Service'
));
$loader->register();
```

2、编写API请参照class/vendor/kuga/openapi-sdk/src/Api目录下的文件写法

3、API接口必须先在config/api目录里定义好，系统会从config/api中解析所有json文件，看前端调用的api接口是否有定义，
有就可以访问，没定义就是接口不存在。有关api接口json文件的编写规范见[https://github.com/misnet/apidocs]

# 规划
- 有关后台的管理功能，将另立项目，计划采用ant design + dva（ 基于reactjs + redux）的方式
- 完善权限管理并提供相应的API
