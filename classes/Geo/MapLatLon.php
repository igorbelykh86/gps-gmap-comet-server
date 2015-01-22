<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Geo;

/**
 * Description of MapLatLon
 *
 * @author igor
 */
class MapLatLon {
    
    private $latitude = NULL;
    private $longitude = NULL;
    
    /**
     * MapLatLon constructor
     * 
     * @param float $latitude float value between -90 and 90
     * @param float $longitude float value between -180 and 180
     */
    public function __construct($latitude, $longitude) {
        $latitude = (float) $latitude;
        $longitude = (float) $longitude;
        if($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            throw new \UnexpectedValueException("Invalid geographical coordinates.");
        }
        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }
    
    public function __toString() {
        return $this->latitude . ',' . $this->longitude;
    }
    
    public function toArray() {
        return array('latitude' => $this->latitude, 'longitude' => $this->longitude);
    }
    
}
