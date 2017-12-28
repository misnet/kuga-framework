<?php
/**
 * Created by PhpStorm.
 * User: donny
 * Date: 2017/11/20
 * Time: 下午3:42
 */
namespace Qing\Api\Controllers;
use Kuga\Core\Api\ApiService;
use Kuga\Core\Api\Request;
class V3Controller extends ControllerBase{
    /**
     * API服务网关地址，这种书写方式有利于nginx的rewrite分发
     * http://api.lark.com/v3/gateway/console.user.login
     * @param string $method
     */
    public function gatewayAction($method=''){
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHeader('Access-Control-Allow-Methods', 'POST, GET, OPTIONS');
        $this->response->setHeader("Access-Control-Allow-Headers","Content-Type");
        $this->response->setHeader('Access-Control-Allow-Credentials', 'true');
        $requestMethod = $this->request->getMethod();
        if($requestMethod!='POST' && $requestMethod!='GET'){
            return;
        }
        $contentType = $this->request->getContentType();
        //post json格式时
        if(preg_match('/application\/json/',$contentType)){
            $requestData = $this->request->getJsonRawBody();
            $requestData = \Qing\Lib\Utils::objectToArray($requestData);
        }else{
            //post 表单格式时
            $requestData = $this->request->getPost();
        }
        if(!isset($requestData['method'])){
            $requestData['method'] = $method;
        }
        $locale = $this->request->get('locale','string','zh_CN');
        if($locale=='zh'){
            $locale='zh_CN';
        }
        $this->getDI()->getShared('translator')->setLocale(LC_MESSAGES, $locale,$this->config->system->charset);
        $requestObject = new Request($requestData);
        $requestObject->setOrigRequest($this->request);
        ApiService::setDi($this->getDI());
        ApiService::initApiJsonConfigFile(QING_ROOT_PATH.'/config/api/api.json');
        $result = ApiService::response($requestObject);
        switch($requestObject->getFormat()){
            case 'text':
                $this->response->setContent($result);
                break;
            case 'json':
            default:
                $this->setJsonResponse($result);
        }
    }
}