<?php

namespace lib\db;

class MysqliResult extends DbResult{
    
    public function valid() {
//        var_dump($this->resultSet);
        $this->row=$this->resultSet->fetch_assoc();
        return $this->row!=false;
    }

}
