<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace MyServer;

use Geo\GPRMCUnit;
use Crypt\CookieDecryptor;

/**
 * Description of Server
 *
 * @author igor
 */
class Server {
    private $put_listener = NULL;
    private $read_listener = NULL;
    private $read_clients = array();
    private $put_clients = array();
    private $max_read_clients = 10;
    private $max_put_clients = 1;
    private $host = '127.0.0.1';
    private $read_port = 12345;
    private $put_port = 12346;
    private $update_data = array();
    private $put_allow_origin_header = TRUE;
    
    public function __construct($host, $put_port, $read_port) {
        $this->host = $host;
        $this->put_port = $put_port;
        $this->read_port = $read_port;
    }
    
    public function setMaxPutClients($num) {
        $this->max_put_clients = $num;
    }
    
    public function setMaxReadClients($num) {
        $this->max_read_clients = $num;
    }
    
    public function start() {
        $this->createPutListener();
        $this->createReadListener();
        while(true) {
            $this->handlePutConnections();
            $this->handleReadConnections();
            $this->update_data = array();
            usleep(300);
        }
    }
    
    /**
     * create listener for clients who connect to send updates
     * 
     * @throws ServerException
     */
    private function createPutListener() {
        $this->put_listener = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if(!$this->put_listener) {
            $err_code = socket_last_error();
            $err_str = socket_strerror($err_code);
            throw new ServerException("Couldn't create put_listener: {$err_code} {$err_str}");
        }
        
        if(!socket_bind($this->put_listener, $this->host, $this->put_port)) {
            $err_code = socket_last_error($this->put_listener);
            $err_str = socket_strerror($err_code);
            throw new ServerException("Couldn't create put_listener: {$err_code} {$err_str}");
        }
        
        if(!socket_listen($this->put_listener, $this->max_put_clients)) {
            $err_code = socket_last_error($this->put_listener);
            $err_str = socket_strerror($err_code);
            throw new ServerException("Couldn't create put_listener: {$err_code} {$err_str}");
        }
    }
    
    /**
     * create listener for clients who connect to get updates
     * 
     * @throws ServerException
     */
    private function createReadListener() {
        $this->read_listener = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if(!$this->read_listener) {
            $err_code = socket_last_error();
            $err_str = socket_strerror($err_code);
            throw new ServerException("Couldn't create read_listener: {$err_code} {$err_str}");
        }
        
        if(!socket_bind($this->read_listener, $this->host, $this->read_port)) {
            $err_code = socket_last_error($this->read_listener);
            $err_str = socket_strerror($err_code);
            throw new ServerException("Couldn't create read_listener: {$err_code} {$err_str}");
        }
        
        if(!socket_listen($this->read_listener, $this->max_put_clients)) {
            $err_code = socket_last_error($this->read_listener);
            $err_str = socket_strerror($err_code);
            throw new ServerException("Couldn't create read_listener: {$err_code} {$err_str}");
        }
    }
    
    private function handlePutConnections() {
        
        $read = array($this->put_listener);
        foreach($this->put_clients as $client) {
            !empty($client) and $read[] = $client;
        }
        if(socket_select($read, $write, $except, 0) === FALSE) {
            $err_code = socket_last_error();
            $err_str = socket_strerror($err_code);
            throw new ServerException("Couldn't select put_listener socket: {$err_code} {$err_str}");
        }
        if(in_array($this->put_listener, $read)) {
            if(!empty($new_client = socket_accept($this->put_listener))) {
                if(count($this->put_clients) < $this->max_put_clients) {
                    $this->put_clients[] = $new_client;
                } else {
                    socket_write($new_client, "Try to connect later\r\n\r\n");
                    socket_close($new_client);
                }
            }
            return;
        }
        foreach($read as $sock) {
            if($sock == $this->put_listener) {
                continue;
            }
            if(($sock_key = array_search($sock, $this->put_clients)) === FALSE) {
                continue;
            }
            $update_raw_data = '';
            while($buf = socket_read($sock , 1024)) {
                $update_raw_data .= $buf;
                if(preg_match('~\r?\n\r?\n$~', $update_raw_data)) break;
            }
            $this->parseUpdateRawData($update_raw_data);
            socket_close($sock);
            unset($this->put_clients[$sock_key]);
            $this->put_clients = array_values($this->put_clients);
        }
        count($this->update_data) > 0 and $this->updateDB();
    }
    
    private function parseUpdateRawData($str) {
        if(preg_match_all('~#(\d+)#[^#]+#[^#]+#[^#]+#[^#]+#(\d+)(\$.+?)##~', $str, $match)) {
            foreach($match[3] as $key => $grpmc) {
                $unit = new GPRMCUnit($grpmc);
                $unit_id = $match[1][$key];
                if(key_exists($unit_id, $this->update_data)) {
                    $this->update_data[$unit_id]->updateGRPMCData($unit);
                } else {
                    $this->update_data[$unit_id] = new UpdateItem($unit, $unit_id);
                }
            }
        }
    }
    
    private function updateDB() {
        $pdo = \DB::inst();
        try {
            $unit_ids = implode(',', array_keys($this->update_data));
            $ids_exist = array();
            if(count($unit_ids) > 0) {
                $sql = $pdo->query("SELECT id FROM map_units WHERE id IN ($unit_ids)");
                while(($id_exist = $sql->fetchColumn()) !== FALSE) {
                    $ids_exist[] = $id_exist;
                }
            }
            foreach($this->update_data as $item) {
                try {
                    $uid = $item->getID();
                    $latlon = $item->getGPRMCUnit()->getLatLon()->toArray();
                    if(in_array($item->getID(), $ids_exist)) {
                        $sql = $pdo->prepare('UPDATE map_units SET latitude = :lat, longitude = :lon WHERE id = :uid');
                    } else {
                        $sql = $pdo->prepare('INSERT INTO map_units (id, latitude, longitude) VALUES (:uid, :lat, :lon)');
                    }
                    $sql->execute(array('uid' => $uid, 'lat' => $latlon['latitude'], 'lon' => $latlon['longitude']));
                } catch (Exception $ex) {
                    // do nothing
                }
            }
        } catch (Exception $ex) {
            // do nothing
        }
    }
    
    private function handleReadConnections() {
        $read = array($this->read_listener);
        foreach($this->read_clients as $client) {
            !empty($client) and $read[] = $client['socket'];
        }
        if(socket_select($read, $write, $except, 0) === FALSE) {
            $err_code = socket_last_error();
            $err_str = socket_strerror($err_code);
            throw new ServerException("Couldn't select read_listener socket: {$err_code} {$err_str}");
        }
        if(in_array($this->read_listener, $read)) {
            if(!empty($new_client = socket_accept($this->read_listener))) {
                $header = $this->readBrowserHeader($new_client);
                $token = CookieDecryptor::decrypt(
                        (new CookieReader($header))->comet_token,
                        SECRET_KEY);
                $is_valid_token = $this->validateToken($token);
                
                $get = new GetReader($header);
                $callback = $get->callback;
                
                socket_write($new_client, "HTTP/1.1 200 OK\r\n"
                        . "Server: CometServer\r\n"
                        . "Cache-Control: no-cache\r\n"
                        . "Connection: keep-alive\r\n"
                        . "Content-Type: application/json\r\n"
                        . "Access-Control-Allow-Origin:".ALLOWED_HOST."\r\n\r\n");
                
                if($is_valid_token && count($this->read_clients) < $this->max_read_clients) {
                    $this->read_clients[] = array('socket' => $new_client, 'callback' => $callback);
                } else if(!$is_valid_token) {
                    $this->sendResponse($new_client, json_encode(array('error' => "Session expired")), $callback);
                    socket_close($new_client);
                } else {
                    $this->sendResponse($new_client, json_encode(array('error' => "Try to connect later")), $callback);
                    socket_close($new_client);
                }
            }
            return;
        }
        if(count($this->update_data) > 0) {
            $json_data = $this->getJSONUpdates();
            foreach($this->read_clients as $key => $client) {
                $this->sendResponse($client['socket'], $json_data, $client['callback']);
                socket_close($client['socket']);
                unset($this->read_clients[$key]);
            }
            $this->read_clients = array_values($this->read_clients);
        }
    }
    
    private function getJSONUpdates() {
        $data = array();
        $pdo = \DB::inst();
        $sql = $pdo->prepare('SELECT name FROM map_units WHERE id = :uid');
        foreach($this->update_data as $unit) {
            $sql->execute(array(':uid' => $unit->getID()));
            $name = $sql->fetchColumn();
            $data[] = array(
                'id' => $unit->getID(),
                'name' => $name,
                'latlon' => $unit->getGPRMCUnit()->getLatLon()->toArray()
            );
        }
        return json_encode($data);
    }
    
    private function readBrowserHeader($socket) {
        $header = '';
        while(($buf = socket_read($socket, 1024)) !== FALSE) {
            if(strlen($buf) == 0) {
                break;
            }
            $header .= $buf;
            if(preg_match('~\r?\n\r?\n~', $header)) {
                break;
            }
        }
        return $header;
    }
    
    private function validateToken($token) {
        if(empty($token)) {
            return FALSE;
        }
        $pdo = \DB::inst();
        $sql = $pdo->prepare('SELECT id FROM users WHERE comet_token = :token '
                . 'AND comet_token_created_at > :exp_time');
        $sql->execute(array(
            'token' => $token,
            'exp_time' => (new \DateTime("-3 min"))->setTimezone(new \DateTimeZone('UTC'))->format('H:i:s')
        ));
        $result = $sql->fetchColumn();
        return ! empty($result);
        
    }
    
    private function sendResponse($socket, $response, $callback) {
        if(!empty($callback)) {
            $response = "$callback($response);";
        }
        socket_write($socket, $response);
    }
}
