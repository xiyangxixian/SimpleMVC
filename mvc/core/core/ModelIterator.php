<?php

namespace core;
use Iterator;

class ModelIterator extends \lib\util\ArrIterator{
    
    private $model;
    private $iterator;
    
    public function __construct($model,Iterator $iterator) {
        $this->model=$model;
        $this->iterator=$iterator;
    }
    
    public function current() {
        return $this->model->mapArrToObj($this->iterator->current());
    }

    public function key() {
        return $this->iterator->key();
    }

    public function next() {
        $this->iterator->next();
    }

    public function rewind() {
        $this->iterator->rewind();
    }

    public function valid() {
        return $this->iterator->valid();
    }

}
