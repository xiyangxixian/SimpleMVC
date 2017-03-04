<?php

namespace lib\process;

class Process implements Runnable{
    
    private $runnable;
    private $isRunning=false;
    private $pid;
    
    public function __construct($runnable=null) {
        $this->runnable=$runnable;
    }
    
    public static function create($runnable=null){
        return new Process($runnable);
    }

    final public function start(){
        if($this->isRunning){
            return;
        }
        $this->isRunning=true;
        pcntl_signal(SIGCHLD,SIG_IGN);
        $pid=pcntl_fork();
        if($pid==-1){
            die('could not fork');
        }else if($pid){
            $this->pid=$pid;
        }else{
            $this->run();
            exit();
        }
    }
    
    public function isRun(){
        return $this->isRunning;
    }

    public function join(){
        pcntl_wait($status);
    }

    public function run(){
        if($this->runnable!=null){
            if($this->runnable instanceof Runnable){
                $this->runnable->run();
            }else if(is_callable($this->runnable)){
                $this->callRunable($this->runnable);
            }
        }
    }
    
    private function callRunable(callable $runable){
        $runable();
    }
    
}
