<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace MyServer;

/**
 * Description of CookieReader
 *
 * @author igor
 */
class CookieReader {
    
    private $cookies = array();
    
    public function __construct($header) {
        $raw_cookies = $this->getRawCookies($header);
        $this->parseCookies($raw_cookies);
    }
    
    public function __get($name) {
        return @$this->cookies[$name] ?: NULL;
    }
    
    private function getRawCookies($header) {
        $strings = array();
        if(preg_match_all('~(^|\r|\n)Cookie:\s*([^$]+?)($|\r|\n)~', $header, $match)) {
            $strings = $match[2];
        }
        return $strings;
    }
    
    private function parseCookies($raw_data) {
        foreach($raw_data as $line) {
            $parts = preg_split('~;\s*~', $line);
            foreach($parts as $raw_cookie) {
                $arr = explode('=', $raw_cookie);
                if(count($arr) == 2) {
                    array_walk($arr, function(&$value, $key){$value=urldecode($value);});
                    $this->cookies[$arr[0]] = $arr[1];
                }
            }
        }
    }
    
}
