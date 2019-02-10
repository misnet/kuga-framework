<?php
use \Phalcon\Cli\Task;
class CommandTask extends Task{
    /**
     * 任务最多执行1小时
     * @var int
     */
    private $maxExecuteTime = 3600;
    /**
     * 记录限制条数不超
     * @var int
     */
    private $limit = 65535;
    private $cacheData;

    private $isLogRuntime = true;
    private $_runEndMem =0;
    private $_runEndTime =0;
    private $_runStartTime = 0;
    private $_runStartMem =0;



    private $swooleHost = '127.0.0.1';
    private $swoolePort = 9502;
    private $serv;
    private $curl;
    /**
     * 媒体文件清理
     */
    public function mediaRecycleAction(){

    }
    private function _log($msg) {
        list($usec, $sec) = explode(" ", microtime());
        $u =   (float)$usec;
        echo date('H:i:s').'['.$u.']---';
        if (is_string($msg)) {
            $msg = $msg . "\n";
        } else {
            $msg = print_r($msg, true) . "\n";
        }
        echo $msg;
        $d = null;
        $msg = null;
        //unset($msg);
    }
    /**
     * 记录运行花费时间
     */
    private function _logRunTime($msg, $resetStart = true) {
        if ($this->isLogRuntime) {
            $this->_runEndMem  = memory_get_usage();
            $this->_runEndTime = microtime(true);
            $t = $this->_runEndTime - $this->_runStartTime;
            $m = $this->_runEndMem - $this->_runStartMem;
            $this->_log($msg . '--' . round($t, 4) . '秒,内存增加:' . $m . '，内存：' . ($this->_runEndMem / 1024));
            if ($resetStart) {
                $this->_runStartTime = microtime(true);
                $this->_runStartMem = memory_get_usage();
            }
        }
    }
    public function designsAction(){
        $client = new \Swoole\Client(SWOOLE_SOCK_TCP,SWOOLE_SOCK_ASYNC);

        $client->on('Connect',function($cli){
            echo "Start time:".date('H:i:s')."\n";
            $catas = [
                ['id'=>63,'name'=>'Sports-Games'],
                ['id'=>62,'name'=>'shpe-symbols'],
                ['id'=>61,'name'=>'seasons-holdaye'],
                ['id'=>60,'name'=>'school'],
                ['id'=>59,'name'=>'religion'],
                ['id'=>58,'name'=>'others'],
                ['id'=>57,'name'=>'nature'],
                ['id'=>56,'name'=>'music'],
                ['id'=>54,'name'=>'mascot'],
                ['id'=>53,'name'=>'food-drink'],
                ['id'=>52,'name'=>'event'],
                ['id'=>51,'name'=>'Country'],
                ['id'=>50,'name'=>'comic'],
                ['id'=>49,'name'=>'charity'],
                ['id'=>48,'name'=>'badge'],
                ['id'=>47,'name'=>'baby-family'],
                ['id'=>46,'name'=>'animal'],
                ['id'=>45,'name'=>'american'],
                ['id'=>64,'name'=>'Transportation'],
            ];
            foreach($catas as $cat){
                $cli->send(json_encode($cat));
            }
            echo "End time:".date('H:i:s')."\n";
        });
        $client->on('Error',function($cli){
            print_r(func_get_args());
        });
        $client->on('Close',function($cli){
            echo 'Close Client.';
        });

        $fp = $client->connect($this->swooleHost,$this->swoolePort,1);
        if(!$fp){
            print_r($fp);
        }
    }
    public function serverAction(){
        $this->serv = new \Swoole\Server($this->swooleHost, $this->swoolePort);
        $this->serv->set(array(
            'worker_num' => 8,
            'daemonize' => false,
            'max_request' => 25000,
            'dispatch_mode' => 1,
            'debug_mode' => 0,
            'backlog'=>40960000,
            'task_worker_num' => 80,
            'log_file'=>'/tmp/swoole.log',
            'pid_file' => '/tmp/swoole.pid'

        ));
        $this->serv->on('connect',function($serv, $fd){
            echo "Client: connect\n";
        });
        $this->serv->on('receive',function($serv, $fd,$fromId, $data){
            $serv->task($data);
            //$serv->close($fd);
        });
        $this->serv->on('close',function($serv, $fd){
            echo "Client: close\n";
        });
        $this->serv->on('task',function($serv,$taskId,$fromId,$data){
            echo "TaskID:".$taskId.' from worker '.$fromId."\n";
            echo $data."\n";
            $result ='No Action';
            $cat  = json_decode($data,true);
            if(!empty($cat)){

                $this->fetchDesign($cat['id']);
                return 'Catagory '.$cat['id'].'   '.$cat['name'].' finished';
            }else{
                return '客户端传参错误';
            }
        });
        $this->serv->on('finish',function($serv,$taskId,$data){
            echo 'TaskID:'.$taskId." finished\n";
            print_r($data);
            echo "\n";
        });
        $this->serv->start();
    }


    public function h5Action(){
        set_time_limit(0);
        $dir = QING_TMP_PATH.DS.'svg';
        if(!file_exists($dir)){
            mkdir($dir,0777);
        }
        $catas = [
            ['id'=>63,'name'=>'Sports-Games'],
            ['id'=>62,'name'=>'shpe-symbols'],
            ['id'=>61,'name'=>'seasons-holdaye'],
            ['id'=>60,'name'=>'school'],
            ['id'=>59,'name'=>'religion'],
            ['id'=>58,'name'=>'others'],
            ['id'=>57,'name'=>'nature'],
            ['id'=>56,'name'=>'music'],
            ['id'=>54,'name'=>'mascot'],
            ['id'=>53,'name'=>'food-drink'],
            ['id'=>52,'name'=>'event'],
            ['id'=>51,'name'=>'Country'],
            ['id'=>50,'name'=>'comic'],
            ['id'=>49,'name'=>'charity'],
            ['id'=>48,'name'=>'badge'],
            ['id'=>47,'name'=>'baby-family'],
            ['id'=>46,'name'=>'animal'],
            ['id'=>45,'name'=>'american'],
            ['id'=>64,'name'=>'Transportation'],
        ];
        $isFetchFile = false;
        foreach($catas as $cat){
            //$this->createDir($cat['id']);
            $this->fetchDesign($cat['id']);
        }
    }
    private function postCurl($url, $option,$header=array(),$resultJsonDecode=true)
    {

        if(!$this->curl) {
            $this->curl = curl_init();
        }
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($this->curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36');
        if (! empty($option)) {
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $option);
        }
        curl_setopt($this->curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, 1);

        $result = curl_exec($this->curl);
        if($resultJsonDecode){
            $result = json_decode($result,true);
        }
        //if($this->curl) curl_close($this->curl);
        //$this->curl = null;
        return $result;
    }
    private function fetchDesign($parentId,$lastLoaded=0){
        $dir = QING_TMP_PATH.DS.'svg';
        $url = 'https://inkxe.com/demo-store/xetool/api/index.php?reqmethod=fetchDesignsBySearch';
        $data['searchval'] = '';
        //$data['subCategoryValue'] = $catalog['id'];
        $data['subCategoryValue'] = 0;
        $data['lastLoaded'] = $lastLoaded;
        $data['loadCount'] = 200;
        $data['print_method'] = 1;
        $data['categoryValue'] = $parentId;
        $data['default_count'] = 1;
        $designList = $this->postCurl($url,$data);
        echo 'fetching '.$url."-----".$lastLoaded."\n";
        if($designList && !empty($designList)){
            //$designList = json_decode($result,true);
            //https://inkxe.com/demo-store/xetool/assets/inkxe_com/images/designs/Sports-Games/yoga/yoga9.svg
            foreach($designList as $design){

                $filename      = $dir.DS.$design['file_name'];
                $designUrl     = str_replace(' ','%20',$design['url']);
                $designContent = file_get_contents($designUrl);
                if(!file_exists(dirname($filename))){
                    mkdir(dirname($filename),0777,true);
                }
                file_put_contents($filename,$designContent);
                echo $filename."\n";
            }
            $this->fetchDesign($parentId,$lastLoaded+200);
        }
    }


}
