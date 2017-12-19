<?php

namespace Qing\Api\Controllers;

use Kuga\Service\ApiService3;
use Kuga\Service\ApiV3\Request;

class ControllerBase extends \Qing\Lib\ControllerAbstract{
    protected $_formToken = 'riseup';
	public function initialize(){
		$this->view->setVar('G_BASEURL', QING_BASEURL);
		$this->view->setVar('G_IMGHOST', $this->getImgHost());

        $locale = isset($_SERVER["HTTP_ACCEPT_LANGUAGE"])?\Locale::acceptFromHttp($_SERVER["HTTP_ACCEPT_LANGUAGE"]):'zh';
        //TODO:要做自动化判断
        if($locale=='zh'){
            $locale='zh_CN';
        }
        $this->translator->setLocale(LC_MESSAGES, $locale,$this->config->system->charset);
        $this->view->setVar('t', $this->translator);
        $this->view->setVar('G_LOCALE', $locale);
	}
	protected function getImgHost(){
	    return QING_BASEURL;
	}

    protected function _generateFormSecret($time){
        return md5(md5($this->_formToken).$time);
    }
    /**
     * 取得每页页数
     * @return number
     */
    protected function getPageLimitFromCookie(){
        $value = 10;
        if ($this->cookies->has('Page_limit')) {
            $limit = $this->cookies->get('Page_limit');
            $value = $limit->getValue();
        }
        return $value;
    }
    /**
     *
     * @return \Qing\Lib\Translator\Adapter
     */
    protected function _getTranslator(){
        return $this->getDI()->getShared('translator');
    }

    protected function _getBackendModuleUri(){
        return rtrim(QING_BASEURL.QING_ADMIN_VIRTUAL_DIR,'\/').'/';
    }
    /**
     * 请求API V2的接口
     * @param string $method 接口方法名称
     * @param array $params
     * @return array
     */
    protected function apiRequest($method,$params,$autoCached=false){
        $result = false;
        if($autoCached){
            $cacheId = 'data:apiCache:'.md5(serialize($params).':'.$method);
            $cacheEngine = $this->getDI()->getShared('cache');
            $result = $cacheEngine->get($cacheId);
        }
        if(!$result){
            $apiKeys = $this->getDI()->get('config')->apiKeys;
            $apiKeys = $apiKeys->toArray();
            list($appKey,$keyValue) = each($apiKeys);
            $data['appkey'] = $appKey;
            $data['method'] = $method;
            if(!is_array($params)){
                $params = array();
            }
            foreach($params as $k=>$v){
                $data[$k]   = $v;
            }
            $data['sign']   = Request::createSign($keyValue['secret'], $data);
            $request        = new Request($data);
            $request->setOrigRequest($this->request);
            //\Kuga\Service\LogFactory::getLogger('test.txt')->log(Logger::ALERT,'begin----'.$data['method']);
            $result = ApiService3::response($request, $this->getDI());
            //\Kuga\Service\LogFactory::getLogger('test.txt')->log(Logger::ALERT,print_r($result,true));
            if($autoCached){
                $cacheEngine->set($cacheId,$result,GlobalVar::DATA_CACHE_LIFETIME);
            }
        }
        return $result;
    }
}