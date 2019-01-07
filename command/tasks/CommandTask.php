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

}
