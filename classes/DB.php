<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of DB
 *
 * @author igor
 */
class DB extends PDO {
    
    static private $private_call = FALSE;
    
    public function __construct($dsn, $username = NULL, $passwd = NULL, $options = array()) {
        if(!self::$private_call) {
            throw new Exception("Use DB::inst()!");
        }
        self::$private_call = FALSE;
        parent::__construct($dsn, $username, $passwd, $options);
    }
    
    static public function inst() {
        static $instance = NULL;
        self::$private_call = TRUE;
        return $instance ?: $instance = new static('mysql:/host='.DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME, DB_USER, DB_PASSWORD);
    }
    
}
