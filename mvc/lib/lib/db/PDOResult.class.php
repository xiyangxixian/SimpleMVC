<?php

namespace lib\db;
use PDO;

class PDOResult extends DbResult{
    
    public function valid() {
        $this->row=$this->resultSet->fetch(PDO::FETCH_ASSOC);
        return $this->row!=false;
    }

}
