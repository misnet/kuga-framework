# 注意：
anchnet/aliyun-openapi-php-sdk这个插件下载后，发现autoload无法正常识别到文件，必须修改部分文件，例要使用sts，那么：

composer/autoload_psr4.php中要修改，

'Sts\\' => array($vendorDir . '/anchnet/aliyun-openapi-php-sdk/aliyun-php-sdk-sts'),这个最后要加/Sts

composer/autoload_static.php中anchnet/aliyun-openapi-php-sdk/aliyun-php-sdk-sts这个路径之后也要加上/Sts

同理，要使用sms或其他插件也要类似这样改