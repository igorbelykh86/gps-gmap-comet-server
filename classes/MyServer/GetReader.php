<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace MyServer;

/**
 * Description of GetReader
 *
 * @author igor
 */
class GetReader {
    private $get = NULL;
    
    public function __construct($header) {
        $get_string = $this->getRawString($header);
        $this->parseGetString($get_string);
    }
    
    public function __get($name) {
        return @$this->get[$name] ?: NULL;
    }
    
    private function parseGetString($str) {
        parse_str($str, $this->get);
    }
    
    private function getRawString($header) {
        if(preg_match('~^(GET|POST)\s*.*?\?([^\s\r\n]+)~', $header, $match)) {
            return $match[2];
        }
    }
    
}
