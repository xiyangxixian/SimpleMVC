<?php

namespace lib\db;

abstract class DbResult extends \lib\util\ArrIterator{
    
    protected $resultSet;
    protected $row;
    protected $index=0;


    public function __construct($resultSet) {
        $this->resultSet=$resultSet;
    }

    public function current() {
        return $this->row;
    }

    public function key() {
        return $this->index;
    }

    public function next() {
        $this->index++;
    }

    public function rewind() {
        $this->index=0;
    }
    
    public function isEmpty(){
        return !$this->resultSet->nextRowset();
    }
}
