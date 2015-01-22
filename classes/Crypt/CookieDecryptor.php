<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Crypt;

/**
 * Description of CookieDecryptor
 *
 * @author igor
 */
class CookieDecryptor {
    
    public static function decrypt($encoded_value, $key) {
        if(empty($encoded_value)) {
            return NULL;
        }
        
        $obj = self::extractObject($encoded_value);
        if(empty($obj) || !self::checkMac($obj, $key)) {
            return NULL;
        }
        
        $decrypted_str = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $obj->value, MCRYPT_MODE_CBC, $obj->iv), "\0");
        return unserialize($decrypted_str);
    }
    
    static private function hash($iv, $value, $key) {
        return hash_hmac('sha256', base64_encode($iv).base64_encode($value), $key);
    }
    
    static private function checkMac($obj, $key) {
        return self::hash($obj->iv, $obj->value, $key) == $obj->mac;
    }
    
    static private function extractObject($str) {
        $obj = json_decode(base64_decode($str));
        if(empty($obj) || empty($obj->iv) || empty($obj->value) || empty($obj->mac)) {
            return NULL;
        }
        
        foreach(array('iv', 'value') as $var) {
            $obj->{$var} = base64_decode($obj->{$var});
        }
        
        return $obj;
    }
}
